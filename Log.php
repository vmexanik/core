<?
/**
 * @author Mikhail Strovoyt
 */

class Log extends Base
{
	//-----------------------------------------------------------------------------------------------
	public function __construct() {

	}

	//-----------------------------------------------------------------------------------------------
	/**
	 * log_visit methods
	 *
	 */
	public static function VisitAdd()
	{
		if (Base::$aRequest['xajaxr']) return;
		Auth::NeedAuth();//only logged users can input logs

		$sQuery="insert into log_visit (id_user,post,url,referer,ip)
			values('".Auth::$aUser['id']."',UNIX_TIMESTAMP(),'".mysql_real_escape_string($_SERVER['QUERY_STRING'])."'
				,'".mysql_real_escape_string($_SERVER['HTTP_REFERER'])."'
				,'".Auth::GetIp()."')";
		Base::$db->Execute($sQuery);
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Logs admin requests in mapnel
	 */
	public static function AdminAdd($sAction,$sTableName='',$sBeforeApply='',$sAfterApply='')
	{
		if (!$sAction) return;
		$sQuery="insert into log_admin (login,action,table_name,ip)
			values('".$_SESSION['admin']['login']."'
				,'".mysql_real_escape_string($sAction)."'
				,'".$sTableName."'
				,'".Auth::GetIp()."')";
		Base::$db->Execute($sQuery);
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * log_finance methods
	 *
	 */
	public static function FinanceAdd($aData,$sSection,$iIdUser='',$sDescription,$sCreatedBy='')
	{
		$sData=String::Serialize($aData);
		if (!$iIdUser) {
			$iIdUser=Auth::$aUser['id'];
		}
		$sQuery="insert into log_finance (id_user,created_by,post,section,data,description)
			values('$iIdUser','$sCreatedBy',UNIX_TIMESTAMP(),'$sSection','$sData','$sDescription')";
		Base::$db->Execute($sQuery);
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * log_global methods
	 *
	 */
	public static function GlobalAdd($aData,$sSection,$iIdUser='')
	{
		//Nedd to be changed !!!!!

		//		global $db, $CURRENT_USER;
		//		include_once("functions/get_ip.php");
		//		$sIP = get_ip();
		//		$aBrowser = get_browser_info();
		//
		//		if ((!$account||$account==0)&&$id_user)
		//		{
		//			$dRow = $db->getRow("SELECT `amount`, `bonus` FROM `user_account` WHERE `id`='$id_user'");
		//			$_account = $dRow['amount'];
		//			$_bonus_account = $dRow['bonus'];
		//		} else {
		//			$_account = $account;
		//			$_bonus_account = 0;
		//		}
		//
		//		$db->execSQL("INSERT INTO `user_global_log`
		//			( `id` , `id_user` , `action` , `id_betroom` , `id_protest` , `id_tournament` , `id_admin` , `account`
		// , `bonus_account` , `dt` , `amount` , `bonus_amount`
		//				, ip, id_coupon, payment_type, `browser`)
		//			VALUES (
		//			NULL , '$id_user', '$action', '$id_betroom', '$id_protest', '$id_tournament', '$id_admin', '$_account',
		// '$_bonus_account', '".date("U")."', '$amount', '$bonus_amount'
		//				, '$sIP' ,'$id_coupon' ,'$payment_type', '$aBrowser'
		//			);
		//			");

	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * log_order methods
	 *
	 */
	public static function OrderAdd($aData,$sSection,$iIdUser='')
	{
		require_once(SERVER_PATH.'/class/core/String.php');
		$sData=String::serialize($aData);
		if (!$iIdUser) {
			$iIdUser=Auth::$aUser['id'];
		}
		$sQuery="insert into log_order (id_user,post,section,data,ip)
			values('$iIdUser',UNIX_TIMESTAMP(),'$sSection','$sData','".Auth::GetIp()."')";
		Base::$db->Execute($sQuery);

	}


}
?>