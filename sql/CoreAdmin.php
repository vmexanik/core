<?
function SqlCoreAdminCall($aData) {

	$sWhere.=$aData['where'];

	if ($aData['id']) {
		$sWhere.=" and a.id='".$aData['id']."'";
	}
	if ($aData['login']) {
		$sWhere.=" and a.login='".$aData['login']."'";
	}

	$sSql="select *
			from admin as a
			where 1=1 ".$sWhere."
			group by a.id";

	return $sSql;
}
?>