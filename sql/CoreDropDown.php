<?
function SqlCoreDropDownCall($aData) {

	$sWhere.=$aData['where'];

	if ($aData['id']) {
		$sWhere.=" and dd.id='{$aData['id']}'";
	}
	if ($aData['code']) {
		$sWhere.=" and dd.code='{$aData['code']}'";
	}

	if ($aData['level']) {
		$sWhere.=" and dd.level='{$aData['level']}'";
	}
	if ($aData['id_parent']) {
		$sWhere.=" and dd.id_parent='{$aData['id_parent']}'";
	}

	$sSql="select dd.*
			from drop_down as dd
			where 1=1 ".$sWhere."
			group by dd.id
			".$aData['order'];

	return $sSql;
}
?>