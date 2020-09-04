<?
function SqlCoreDirectorySiteCall($aData) {

	$sWhere.=$aData['where'];

	if ($aData['id']) {
		$sWhere.=" and ds.id='".$aData['id']."'";
	}

	$sSql="select ds.*,ds.visible as visible,dsc.name as directory_site_category_name
			from directory_site ds
     		inner join directory_site_category as dsc on ds.id_directory_site_category=dsc.id
			where 1=1 ".$sWhere."
			group by ds.id";

	return $sSql;
}
?>