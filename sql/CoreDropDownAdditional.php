<?
function SqlCoreDropDownAdditionalCall($aData)
{
	$sWhere.=$aData['where'];

	if ($aData['id']) {
		$sWhere.=" and dda.id='".$aData['id']."'";
	}
	if ($aData['url']) {
		$sWhere.=" and dda.url in ('".$aData['url']."','".urldecode($aData['url'])."')";
	}
	if ($aData['visible']) {
		$sWhere.=" and dda.visible='".$aData['visible']."'";
	}


	$sSql="select dda.*
			from drop_down_additional as dda
			where 1=1
			".$sWhere;

	return $sSql;
}
?>