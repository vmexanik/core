<?
function SqlCoreTrashCall($aData) {

	$sWhere.=$aData['where'];

	if ($aData['id']) {
		$sWhere.=" and t.id='{$aData['id']}'";
	}

	$sSql="select t.*
		from trash as t
		where 1=1 ".$sWhere;
	return $sSql;
}
?>