<?
/**
 * @author Mikhail Strovoyt
 * @author Roman Dehtyarev
 */

require_once(SERVER_PATH.'/class/core/Base.php');
class Auth extends Base {
	private static $iRememberDays=90;
	public static $aUser;
	public static $sWhere;
	public static $bIgnoreCookie=true;

	//-----------------------------------------------------------------------------------------------
	public function Login($sLogin,$sPassword,$bIgnoreVisible=false,$bIgnoreEmailConfirmation=true,$bSalt=false,$bCheckPassword=true,$sOtherFieldAsLogin='')
	{
		if($bCheckPassword)
		$aUser=Auth::IsUser($sLogin,$sPassword,$bIgnoreVisible,$bSalt,$sOtherFieldAsLogin);
		else
		$aUser=Db::GetRow(Base::GetSql('User',array(
		'login'=>$sLogin,
		'or_email'=>1,
		'other_field_as_login'=>$sOtherFieldAsLogin,
		'visible'=>($bIgnoreVisible?0:1),
		)));

		if (!$bIgnoreEmailConfirmation && !$aUser['approved']) {
			$this->Redirect("/?action=user_login&error_login=1");
		}

		if ($aUser['id'])
		{
			Auth::UpdateLastVisit($aUser);
			Auth::RefreshSession($aUser);
			if (Base::$aRequest['remember_me']) {
				$sCookie=Auth::RefreshCookie($aUser['login'],$aUser['password'],$aUser['id_user']);
				$sQuery="update user set cookie='".$sCookie."' where login='".$sLogin."'";
				Base::$db->Execute($sQuery);
			}
			else {
				if (!Auth::$bIgnoreCookie) {
					setcookie("user_auth_signature", "",time()+60*60*24*Auth::$iRememberDays);
					$_COOKIE['user_auth_signature']="";
					$sQuery="update user set cookie='' where login='".$sLogin."'";
					Base::$db->Execute($sQuery);
				}
			}
		}

		if (!$_SESSION['user']['isUser'.Auth::$sProjectName] && !Base::$aRequest['xajax']) {
			$this->Redirect("/?action=user_login&error_login=1");
		}
		if(method_exists(Base::$oContent,'AfterLogin')) Base::$oContent->AfterLogin($aUser);
		return $aUser;
	}
	//-----------------------------------------------------------------------------------------------
	public function Logout()
	{
		setcookie("user_auth_signature", "",time()+60*60*24*$this->iRememberDays,'/');
		$_COOKIE['user_auth_signature']="";
		setcookie("user_auth_session", "",time()+60*60*24*$this->iRememberDays,'/');
		$_COOKIE['user_auth_session']="";
		$_SESSION['user']="";
		$_SESSION['user']=array();
		if ($_SESSION['user']['login']) {
			Base::$db->Execute("update user set cookie='' where login='".$_SESSION['user']['login']."'");
		}
	}
	//-----------------------------------------------------------------------------------------------
	public static function NeedAuth($sType='')
	{
		if (Auth::IsAuth()) {
			if ($sType && Auth::$aUser['type_']!=$sType) {
				if (Base::GetConstant('auth:error_type_redirect',1)) Auth::LoginErrorRedirect(false);
				else {
					Base::$sText.=Language::GetText('auth_error_type_redirect');
					Base::Process();
					die();
				}
			}
			return true;
		}
		else {
			Auth::LoginErrorRedirect();
		}
	}
	//-----------------------------------------------------------------------------------------------
	public static function LoginErrorRedirect($bSaveReturn=true)
	{
		if ($bSaveReturn) $_SESSION['auth_return']=$_SERVER['REQUEST_URI'];
		else $_SESSION['auth_return']='';
		
		// need logout before redirect, becouse user menu present on page, but user not login!
		$oAuth=new Auth();
		$oAuth->Logout();
		
		if(Base::GetConstant("auth:new_error_login_redirect",0)==1){
			$sUrl='/pages/user_login_error/';
		} else {
			$sUrl='/?action=user_login&login_error=1';
		}
		if (Base::$aRequest['xajax']) Base::$oResponse->AddRedirect($sUrl);
		else Base::Redirect($sUrl);
	}
	//-----------------------------------------------------------------------------------------------
	public static function IsAuth()
	{
		if (($_SESSION['user'] && $_SESSION['user']['isUser'.Auth::$sProjectName]) || Auth::IsValidCookie($_COOKIE['user_auth_signature'])) {
			Auth::$aUser=Auth::GetUserProfile($_SESSION['user']['id'],$_SESSION['user']['type_']);
			Auth::$sWhere=" and id_user='".Auth::$aUser['id']."'";
			return true;
		}
		return false;
	}
	//---------------------------------------------------------------------------------
	private function GetUserProfile($iId,$sType='customer')
	{
// 		$aUser=Db::GetRow("select u.*,uu.*,u2.login as login_id_type_price_user from user u
// 						inner join user_".$sType." uu on u.id=uu.id_user
// 						left join user_manager um on um.id_user = u.id				
// 						left join user u2 on u2.id = um.id_type_price_user
// 						where u.id=".$iId);
$aUser=Db::GetRow("select u.*,uu.* from user u
						inner join user_".$sType." uu on u.id=uu.id_user
						
						where u.id=".$iId);
		if ($aUser['type_']=='customer') {
			$aUser=Db::GetRow(Base::GetSql('Customer',array('id'=>$aUser['id'])));
		}
		if ($aUser['type_']=='provider') {
			$aUser=Db::GetRow("select ua.*, u.*, uu.*
				from user u
				inner join user_account ua on u.id=ua.id_user
				inner join user_provider uu on u.id=uu.id_user
				where u.id='{$aUser['id']}'
				group by u.id");
		}
		if ($aUser['type_']=='manager') {
			if (Base::GetConstant('user_role:is_available',0)) {
				$aUserRoleItemAssoc=Db::GetAssoc('Assoc/UserRoleItem',array('id_user'=>$aUser['id']));
				$aUserRoleItemArray=explode(',',$aUserRoleItemAssoc[$aUser['id']]);

				/** backward compatibility for super manager functionality */
				if (in_array(1,$aUserRoleItemArray)) $aUser['is_super_manager']=1;
				else $aUser['is_super_manager']=0;

				$aUser['user_role_array']=$aUserRoleItemArray;
			}
		}

		return $aUser;
	}
	//-----------------------------------------------------------------------------------------------
	public function IsUser($sLogin,$sPassword,$bIgnoreVisible=false,$bSalt=false,$sOtherFieldAsLogin='')
	{
		if (!trim($sLogin) || !trim($sPassword)) return false;

		if ($bSalt) {
			$aUser=Db::GetRow(Base::GetSql('User',array(
			'login'=>$sLogin,
			'or_email'=>1,
			'other_field_as_login'=>$sOtherFieldAsLogin,
			'visible'=>($bIgnoreVisible?0:1),
			)));

			if ($aUser['password']!=String::Md5Salt($sPassword,$aUser['salt'])) return false;
		}
		else {
			$sQuery="select * from user u where login='".$sLogin."' and password='".$sPassword."'
				".($bIgnoreVisible ? "":" and visible='1'");
			$aUser=Db::GetRow($sQuery);
		}

		if ($aUser) {
			return Auth::GetUserProfile($aUser['id'],$aUser['type_']);
		}
		return false;
	}
	//---------------------------------------------------------------------------------
	function IsValidCookie($sCookie)
	{
		if (Auth::$iRememberDays<=1) Auth::$iRememberDays=90;

		if (empty($sCookie)) return false;

		$sQuery="select * from user where cookie='$sCookie' and visible='1'";
		$aRow=Db::GetRow($sQuery);

		if($sCookie!==md5(Base::$sProjectName.$aRow['login'].$aRow['password'].$aRow['id']))
	    //if($sCookie!==md5(Base::$sProjectName.$aRow['login'].$aRow['id']))
			return false;
		
		if ($aRow) {
			Auth::UpdateLastVisit($aRow);
			$aUser=Auth::GetUserProfile($aRow['id'],$aRow['type_']);

			//			$oForum = new Forum();
			//			$oForum->LoginForum($aUser);
		}

		if ($aUser['id']) {
			Auth::RefreshSession($aUser);
			Auth::RefreshCookie($aUser['login'],$aUser['password'],$aUser['id']);
			return true;
		}
		return false;
	}

	//---------------------------------------------------------------------------------
	function RefreshSession($aUser)
	{
		$_SESSION['user']['isUser'.Base::$sProjectName]=true;
		$_SESSION['user']['isGuest'.Base::$sProjectName]=false;
		foreach ($aUser as $key => $value) $_SESSION['user'][$key]=$value;
		setcookie("user_auth_session", "1",time()+60*60*24*Auth::$iRememberDays,'/');
	}
	//---------------------------------------------------------------------------------
	function RefreshCookie($sLogin,$sPassword,$iIdCustomer)
	{
		if (Auth::$iRememberDays<=10) Auth::$iRememberDays=90;

		$sNewCookieValue=md5(Base::$sProjectName.$sLogin.$sPassword.$iIdCustomer);
		//$sNewCookieValue=md5(Base::$sProjectName.$sLogin.$iIdCustomer);
		setcookie("user_auth_signature", $sNewCookieValue,time()+60*60*24*Auth::$iRememberDays,'/');
		$_COOKIE['user_auth_signature'] = $sNewCookieValue;
		return $sNewCookieValue;
	}
	//---------------------------------------------------------------------------------
	function FilterLetters($input)
	{
		$output="";
		$input=trim($input);
		$length=strlen($input);
		for ($i=0;  $i<$length;$i++)
		if (preg_match ("/^[a-zA-Z�-��-�0-9,;\s-_()]/", $input[$i])) $output.=$input[$i];
		return $output;
	}
	//---------------------------------------------------------------------------------
	public static function GetIp()
	{
		$sIp = $_SERVER['HTTP_X_FORWARDED_FOR'];
		if ($sIp=="") $sIp = $_SERVER['REMOTE_ADDR'];
		return $sIp;
	}
	//---------------------------------------------------------------------------------
	/**
	 * Create automatically user for unregistered sue of registered sections of site
	 *
	 * @return Array of standard User account
	 */
	public function AutoCreateUser()
	{
		if (!$sLogin) $sLogin=Auth::GenerateLogin();
		$bCheckedLogin=false;
		//if (!Db::GetOne("select count(*) from user where login='$sLogin'")) $bCheckedLogin=true;
		if (Auth::CheckLogin($sLogin)) $bCheckedLogin=true;
		if (!$bCheckedLogin) for ($i=0;$i<=100;$i++) {
			$sLogin=Auth::GenerateLogin();
			if (Auth::CheckLogin($sLogin)) {
				$bCheckedLogin=true;
				break;
			}
		}
		if ($bCheckedLogin) {
			$oUser= new User();
			Base::$aRequest['login']=$sLogin;
			Base::$aRequest['password']=Auth::GeneratePassword();
			if (Base::$aRequest['mobile']) {
				Base::$aRequest['phone']=Base::$aRequest['operator'].Base::$aRequest['mobile'];
				Base::$aRequest['data']['phone']=Base::$aRequest['operator'].Base::$aRequest['mobile'];
			}
			$oUser->DoNewAccount(true);

			return Db::GetRow(Base::GetSql('Customer',array('login'=>$sLogin)));
		}
		return false;
	}
	//---------------------------------------------------------------------------------
	public function CheckLogin($sLogin)
	{
		return !Db::GetOne("select count(*) from user where login='".$sLogin."'");
	}
	//---------------------------------------------------------------------------------
	public function GenerateLogin()
	{
		return 'a'.Db::GetOne("select max(id)+1 from user").rand(1,9);
	}
	//---------------------------------------------------------------------------------
	public function GeneratePassword()
	{
		return 'd2'.rand(10,100).rand(1,100);
	}
	//---------------------------------------------------------------------------------
	public function UpdateLastVisit($aUser)
	{
		Base::$db->Execute("update user set last_visit_date = now(), session='".session_id()."' where id='".$aUser['id']."'");
	}
	//---------------------------------------------------------------------------------
	/**
	 * For MA-207: Method Cron::ClearAutoCreatedUser additional check for users started with cart processing
	 */
	public function UpdateCustomerReal($iUser)
	{
		Db::Execute("update user_customer set is_real='1' where id_user='".$iUser."'");
	}
	//---------------------------------------------------------------------------------
	public static function CheckPermissions($iIdAction=0)
	{
		if(Auth::$aUser['type_']=="manager" && $iIdAction)
		{
			$aManagerRoles = Db::GetAll('SELECT rm.id_role from role_manager as rm where id_manager = '.$_SESSION['user']['id_user']);
			foreach($aManagerRoles as $aRole)
			{
				$aAction = Db::GetAll('SELECT * FROM role_permissions WHERE id_action ='.$iIdAction." AND id_role =".$aRole['id_role']);
				if($aAction)
					return true;
			}
			return false;			
		}
		return false;
	}
}
?>