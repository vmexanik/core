<?
function SqlCoreAdminLanguageDeniedCall($aData) {

	$sWhere.=$aData['where'];

	if ($aData['id']) {
		$sWhere.=" and alg.id='".$aData['id']."'";
	}
    if ($aData['id_admin']) {
		$sWhere.=" and alg.id_admin='".$aData['id_admin']."'";
	}

	$sSql="select alg.*,l.*
			from admin_language_denied as alg
			inner join language as l on l.id = alg.id_language
			where 1=1 "
	        .$sWhere;

	return $sSql;
}
?>