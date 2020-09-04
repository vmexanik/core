<?
function SqlCoreVinRequestCall($aData) {

	$sWhere.=$aData['where'];

	if ($aData['id']) {
		$sWhere.=" and vr.id='".$aData['id']."'";
	}
	if ($aData['id_in']) {
		$sWhere.=" and vr.id in (".$aData['id_in'].")";
	}
	if ($aData['id_manager_fixed']) {
		$sWhere.=" and vr.id_manager_fixed='".$aData['id_manager_fixed']."'";
	}
	if ($aData['refuse_for']) {
		$sWhere.=" and vr.refuse_for='".$aData['refuse_for']."'";
	}

	$sSql="select cg.*,u.*,uc.*,vr.*, u.login, m.login as manager_login
			from vin_request vr
			inner join user u on vr.id_user=u.id
			inner join user_customer uc on uc.id_user=u.id
			inner join customer_group cg on uc.id_customer_group=cg.id
			inner join user m on uc.id_manager=m.id
			where 1=1
				".$sWhere;
	return $sSql;
}
?>