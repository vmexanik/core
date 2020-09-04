<?
function SqlCoreLanguageCall($aData) {

	$sWhere.=$aData['where'];

	if ($aData['id']) {
		$sWhere.=" and l.id='{$aData['id']}'";
	}
    if ($aData['visible']) {
		$sWhere.=" and l.visible='".$aData['visible']."'";
	}
    if ($aData['id_admin_denied']) {
		$sWhere.=" and l.id not in (select id_language from admin_language_denied where id_admin='"
		.$aData['id_admin_denied']."')";
	}

	$sSql="select l.*
			from language l
			where 1=1 ".$sWhere."
			group by l.id";

	return $sSql;
}
?>