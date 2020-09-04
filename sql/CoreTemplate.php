<?
function SqlCoreTemplateCall($aData) {

	$sWhere.=$aData['where'];

	if ($aData['id']) {
		$sWhere.=" and t.id='{$aData['id']}'";
	}
	if ($aData['code']) {
		$sWhere.=" and t.code='{$aData['code']}'";
	}

	$sSql="select t.*
			from template as t
			where 1=1 ".$sWhere."
			group by t.id";

	return $sSql;
}
?>