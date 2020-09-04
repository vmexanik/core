<?
function SqlCoreUserManagerRolePrivilegeCall($aData) {

    $sWhere.=$aData['where'];

    if ($aData['id']) {
        $sWhere.=" and t.id='{$aData['id']}'";
    }

    $sSql="select t.*
		from user_manager_role_privilege as t
		where 1=1 ".$sWhere;
    return $sSql;
}
?>