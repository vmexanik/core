<?
function SqlCoreAssocLanguageCall($aData) {

	$sWhere.=$aData['where'];

	if ($aData['id']) {
		$sWhere.=" and l.id='{$aData['id']}'";
	}
    if ($aData['visible']) {
		$sWhere.=" and l.visible='".$aData['visible']."' ";
	}

	$sSql="select l.id,concat(l.code,'-',l.name) as code_name
			from language l
			where 1=1 ".$sWhere."
			group by l.id";

	return $sSql;
}
?>