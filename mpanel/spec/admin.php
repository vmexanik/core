<?
/**
 * @author Mikhail Starovoyt
 *
 * @version 4.5.1
 * - changed:AT-138 admin login with salt. mpanel table and form updated.
 *   Alter Table needed.
 *
 * @version 4.5.0
 * Initial admin module from base auto box: AT-114
 */

class AAdmin extends Admin {
	public $sVersion;

	//-----------------------------------------------------------------------------------------------
	function __construct() {
		$this->sVersion=Language::GetConstant('module_version:aadmin','4.5.0');
		Repository::Get()->CheckUpdate('aadmin');

		$this->sTableName='admin';
		$this->sTablePrefix='a';
		$this->sAction='admin';
		$this->sWinHead=Language::getDMessage('Administrator');
		$this->sPath=Language::GetDMessage('>>Configuration >');
		$this->aCheckField=array('login');
		
		if(file_exists(SERVER_PATH."/template/mpanel/dtree_new.tpl")) {
		    //use /template/mpanel/***
		} else {
		    $this->sAddonPath='addon/';
		}
		$this->sSqlPath='CoreAdmin';
		$this->Admin();
	}
	//-----------------------------------------------------------------------------------------------
	public function Index() {
		$this->PreIndex();

		$oTable=new Table();
		$oTable->aColumn=array(
		'id'=>array('sTitle'=>'Id','sOrder'=>'a.id'),
		'login'=>array('sTitle'=>'Login','sOrder'=>'a.login'),
		'name'=>array('sTitle'=>'flname','sOrder'=>'a.name'),
		'last_login'=>array('sTitle'=>'Last login','sOrder'=>'a.last_login'),
		'now_login'=>array('sTitle'=>'Now login','sOrder'=>'a.now_login'),
		'last_referer'=>array('sTitle'=>'Last referer','sOrder'=>'a.last_referer'),
		'now_referer'=>array('sTitle'=>'Now referer','sOrder'=>'a.now_referer'),
		'type_'=>array('sTitle'=>'Type','sOrder'=>'a.type_'),
		'is_base_denied'=>array('sTitle'=>'Denied','sOrder'=>'a.is_base_denied'),
		'action'=>array(),
		);
		$this->SetDefaultTable($oTable);
		Base::$sText.=$oTable->getTable();

		$this->AfterIndex();
	}
	//-----------------------------------------------------------------------------------------------
	public function Apply() {
		if (!$this->CheckField()) {
			$this->Message('MT_ERROR', Language::getDMessage('Please fill out all fields'));
			return;
		}

		if ('4.5.1'==Language::GetConstant('module_version:aadmin','4.5.0')) {
			if (!Base::$aRequest['data']['id']) {
				$sSalt=String::GenerateSalt();
				Base::$aRequest['data']['salt']=$sSalt;
				Base::$aRequest['data']['password']=String::Md5Salt(Base::$aRequest['data']['password'],$sSalt);
			}
		}
		else {
			Base::$aRequest['data']['passwd'] = (Base::$aRequest['data']['pwd_type'] == "md5" ?
			md5(Base::$aRequest['data']['passwd']) : Base::$aRequest['data']['passwd']);
		}
		parent::Apply ();
	}
	//-----------------------------------------------------------------------------------------------
	public function BeforeAddAssign(&$aData) {
		Base::$tpl->assign('aType', BaseTemp::EnumToArray('admin','type_'));
		$bHasLanguageAccessRules = Base::GetConstant("mpanel:admin_language_denied","0");
		Base::$tpl->assign('bHasLanguageAccessRules',$bHasLanguageAccessRules);
		if($bHasLanguageAccessRules){
			Base::$tpl->assign('iAdminLangSelectWidth', Base::GetConstant("admin:admin_select_lang_width",'100px'));
			Base::$tpl->assign('iAdminLangCount', Base::GetConstant("admin:admin_select_lang_count",5));
			$aLocaleAll=Db::GetAssoc(Base::GetSql("CoreAssocLanguage",array('visible'=>'1')));
			Base::$tpl->assign('aLocaleAll', $aLocaleAll);
			$aLocaleDenied=Db::GetAll(Base::GetSql("CoreAdminLanguageDenied",array('id_admin'=>$aData['id'])));
			if($aLocaleDenied){
				$aLocaleDenied  =Language::Array2hash($aLocaleDenied,'id_language');
			}
			Base::$tpl->assign('aLocaleDenied', array_keys($aLocaleDenied));
		}
	}
	//-----------------------------------------------------------------------------------------------
	public function BeforeApply()
	{
		$bHasLanguageAccessRules = Base::GetConstant("mpanel:admin_language_denied","0");
		if($bHasLanguageAccessRules){
			$aIdLangDenied = Base::$aRequest['data']['id_language'];
			if($aIdLangDenied && count($aIdLangDenied)>0){
				Db::Execute("delete from admin_language_denied where id_admin = '".Base::$aRequest['data']['id']."'");
				$aData=array();
				$aData['id_admin'] = Base::$aRequest['data']['id'];
				foreach($aIdLangDenied as $sValue){
					if(intval($sValue)){
						$aData['id_language'] = $sValue;
						Db::AutoExecute('admin_language_denied',$aData);
					}
				}
			}
		}
	}
	//-----------------------------------------------------------------------------------------------
	public function ChangePassword()
	{
		Base::$tpl->assign('sReturn', stripslashes(Base::$aRequest['return']));
		Base::$tpl->assign('aData',array('id'=>Base::$aRequest['id']));

		$this->sAction = "admin/change_password";
		Admin::ProcessTemplateForm('>>Admin > Change password');
	}
	//-----------------------------------------------------------------------------------------------
	public function ChangePasswordApply()
	{
		if (strlen(trim(Base::$aRequest['data']['password']))<=5 ){
			Admin::Message('MT_ERROR','Password must more than 5 digits');
			return;
		}

		if (Base::$aRequest['data']['password']!=Base::$aRequest['data']['retype_password']){
			Admin::Message('MT_ERROR','Passwords are not the same');
			return;
		}

		$sSalt=String::GenerateSalt();
		$aAdminUpdate=array(
		'password'=>String::Md5Salt(Base::$aRequest['data']['password'],$sSalt),
		'salt'=>$sSalt,
		);
		Db::AutoExecute('admin',$aAdminUpdate,"UPDATE"," id='".Base::$aRequest['data']['id']."'");

		parent::AdminRedirect($this->sAction);
	}
	//-----------------------------------------------------------------------------------------------
}
?>