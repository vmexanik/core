<?
/**
 * @author Mikhail Strovoyt
 */

require_once(SERVER_PATH.'/class/core/Base.php');
class Permission extends Base {

	private static $aAllowAction = array ();

	//-----------------------------------------------------------------------------------------------
	public static function Init() {
		$aAllowActionAll=Base::$db->getAll("select action from permission_action where type_='".Auth::$aUser['type_']."'");
		foreach ($aAllowActionAll as $aValue) $aAllowAction[]=$aValue['action'];

		$aDenyAction=array();
		$aDenyActionAll=Base::$db->getAll("select action from permission_deny where id_user='".Auth::$aUser['id']."'");
		if ($aDenyActionAll) foreach ($aDenyActionAll as $aValue) $aDenyAction[]=$aValue['action'];

		Permission::$aAllowAction=array_diff($aAllowAction,$aDenyAction);
	}

	//-----------------------------------------------------------------------------------------------
	public static function AppendPermission() {
		if (!in_array(Base::$aRequest['action'],Permission::$aAllowAction)) {
			Base::$db->Execute("insert into permission_action (type_,action)
				values ('".Auth::$aUser['type_']."','".Base::$aRequest['action']."')");
			Permission::$aAllowAction[]=Base::$aRequest['action'];
		}
	}

	//-----------------------------------------------------------------------------------------------
	public static function CheckPermission() {
		if (!$aAllowAction) Permission::Init();

		if (Base::$aGeneralConf['AppendPermission']) {
			Permission::AppendPermission();
		}

		if (in_array(Base::$aRequest['action'],Permission::$aAllowAction)) return true;
		else {
			Base::Redirect('./?action=permission_required');
		}
	}
	//-----------------------------------------------------------------------------------------------
}
?>