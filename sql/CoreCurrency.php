<?
function SqlCoreCurrencyCall($aData) {

	$sWhere.=$aData['where'];

	if ($aData['id']) {
		$sWhere.=" and c.id='{$aData['id']}'";
	}

	if ($aData['order']) {
		$sOrder.=" order by '{$aData['order']}'";
	}

	$sSql="select c.*
			from currency c
			where 1=1 ".$sWhere."
			group by c.id".$sOrder;

	return $sSql;
}
?>