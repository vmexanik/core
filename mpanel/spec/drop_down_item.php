<?
/**
 * @author Irina Miroshnichenko
 * @author Mikhail Starovoyt
 */

require_once(SERVER_PATH.'/class/core/mpanel/spec/drop_down.php');
class ADropDownItem extends ADropDown
{
	public $aUserRoleAssoc=array();
	public $aUserRoleDropDownAssoc=array();

	//-----------------------------------------------------------------------------------------------
	function __construct() {
		//$this->sSqlPath = 'PageManager';
		$this->sTableName='drop_down';
		$this->sTablePrefix='dd';
		$this->sAction='drop_down_item';
		$this->sWinHead=Language::getDMessage('Dropdown Manager');
		$this->sPath=Language::GetDMessage('>>Content >');
		$this->aCheckField=array('name','code');
		if(file_exists(SERVER_PATH."/template/mpanel/dtree_new.tpl")) {
		    //use /template/mpanel/***
		} else {
		    $this->sAddonPath='addon/';
		}
		$this->sSqlPath='CoreDropDown';
		$this->Admin();
	}

	//-----------------------------------------------------------------------------------------------
	public function Index() {
		if (Base::$aRequest['id_parent']) $_SESSION['mpanel']['id_parent']=Base::$aRequest['id_parent'];

		$this->PreIndex();
		$this->initLocaleGlobal ();
		$oTable=new Table();

		$oTable->aColumn['id']=array('sTitle'=>'Id','sOrder'=>'dd.id');
		$oTable->aColumn['name']=array('sTitle'=>'Name','sOrder'=>'dd.name');
		$oTable->aColumn['code']=array('sTitle'=>'Code','sOrder'=>'dd.code');
		$oTable->aColumn['num']=array('sTitle'=>'Num','sOrder'=>'dd.num');
		$oTable->aColumn['visible']=array('sTitle'=>'Visible','sOrder'=>'dd.visible');
		$oTable->aColumn['lang']=array('sTitle'=>'Lang');
		if (Base::GetConstant('user_role:is_available',0)) {
			$this->aUserRoleAssoc=Db::GetAssoc('Assoc/UserRole',array('exclude_super_user'=>1));
			$this->aUserRoleDropDownAssoc=Db::GetAssoc('Assoc/UserRoleDropDown');
			Base::$tpl->assign_by_ref('oADropDownItem',$this);
			$oTable->aColumn['user_role']=array('sTitle'=>'User role');
		}
		$oTable->aColumn['action']=array();

		$aData['where'] = "and dd.id_parent = '".$_SESSION['mpanel']['id_parent']."' and level=3 ";
		$this->SetDefaultTable($oTable, $aData);
		Base::$sText.=$oTable->getTable();
		$this->AfterIndex();
	}
	//-----------------------------------------------------------------------------------------------
	public function BeforeAddAssign(&$aData) {
		Base::$tpl->assign ( 'idParent', Base::$aRequest['id_parent']);
	}
	//-----------------------------------------------------------------------------------------------
	public function GetRoleCheckbox($iIdDropDown)
	{
		Base::$tpl->assign('iIdDropDown',$iIdDropDown);
		if ($this->aUserRoleAssoc) foreach ($this->aUserRoleAssoc as $sKey => $aValue) {
			$aRoleDropDownArray=explode(',',$this->aUserRoleDropDownAssoc[$iIdDropDown]);

			Base::$tpl->assign('bChecked', in_array($sKey,$aRoleDropDownArray));
			Base::$tpl->assign('sRoleName',$aValue);
			Base::$tpl->assign('iIdUserRole',$sKey);
			$sRoleCheckboxText.=Base::$tpl->fetch($this->sAddonPath.'mpanel/drop_down_item/role_checkbox.tpl');
		}
		return $sRoleCheckboxText;
	}
	//-----------------------------------------------------------------------------------------------
	public function RoleUpdate()
	{
		if (Base::$aRequest['checked']=='true') {
			$aUserRoleDropDownInsert=array(
			'id_drop_down'=>Base::$aRequest['id_drop_down'],
			'id_user_role'=>Base::$aRequest['id_user_role'],
			);
			Db::AutoExecute('user_role_drop_down',$aUserRoleDropDownInsert);
		}
		else {
			Db::Execute("delete from user_role_drop_down where id_drop_down='".Base::$aRequest['id_drop_down']."'
				and id_user_role='".Base::$aRequest['id_user_role']."'");
		}
	}
	//-----------------------------------------------------------------------------------------------
	public function AfterApply ($aBeforeRow,$aAfterRow) {
	    //remove cache
	    if(file_exists(SERVER_PATH."/cache/Home/account_menu_customer.cache")) unlink(SERVER_PATH."/cache/Home/account_menu_customer.cache");
	    if(file_exists(SERVER_PATH."/cache/Home/account_menu_manager.cache")) unlink(SERVER_PATH."/cache/Home/account_menu_manager.cache");
	    if(file_exists(SERVER_PATH."/cache/Home/drop_down.cache")) unlink(SERVER_PATH."/cache/Home/drop_down.cache");
	}
	//-----------------------------------------------------------------------------------------------
}
?>