<?
function SqlCoreNewsCall($aData) {

	$sWhere.=$aData['where'];

	if ($aData['id']) {
		$sWhere.=" and n.id='{$aData['id']}'";
	}

	$sSql="select n.*
			from news as n
			where 1=1 ".$sWhere."
			group by n.id ".$aData['order'];

	return $sSql;
}
?>