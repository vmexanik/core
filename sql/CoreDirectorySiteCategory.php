<?
function SqlDirectorySiteCategoryCall($aData) {

	$sWhere.=$aData['where'];

	if ($aData['id']) {
		$sWhere.=" and dsc.id='".$aData['id']."'";
	}

	$sSql="select *
			from directory_site_category as dsc
			where 1=1 ".$sWhere."
			order by dsc.num";

	return $sSql;
}
?>