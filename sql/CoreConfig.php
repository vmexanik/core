<?
function SqlCoreConfigCall($aData) {

	$sWhere.=$aData['where'];

	if ($aData['id']) {
		$sWhere.=" and c.id='".$aData['id']."'";
	}

	$sSql="select c.*
			from config as c
			where 1=1 ".$sWhere."
			group by c.id";

	return $sSql;
}
?>