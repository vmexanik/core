<?
function SqlCoreCommentCall($aData)
{
	$sWhere.=$aData['where'];

	if ($aData['id']) {
		$sWhere.=" and c.id='".$aData['id']."'";
	}
	if ($aData['section']) {
		$sWhere.=" and c.section='".$aData['section']."'";
	}
	if ($aData['ref_id']) {
		$sWhere.=" and c.ref_id='".$aData['ref_id']."'";
	}

	$sSql="select c.*
			from comment as c
			where 1=1
			".$sWhere."
			group by c.id
			".$aData['order'];

	return $sSql;
}
?>