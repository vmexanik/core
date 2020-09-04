<?
function SqlCoreTranslateMessageCall($aData) {
    $sJoin = '';
    $sField = '';
	$sWhere.=$aData['where'];

	if ($aData['id']) {
		$sWhere.=" and t.id='".$aData['id']."'";
	}
	
	if ($aData['join_locale_global']) {
	    $sJoin="
    	    left join locale_global lg on (lg.id_reference=t.id and lg.table_name='translate_message' and lg.locale='ua')
    	";
	     
	    $sField=", lg.content as content_ua";
	}

	$sSql="select t.* ".$sField."
			from translate_message as t
	        ".$sJoin."
			where 1=1 ".$sWhere."
			group by t.id";

	return $sSql;
}
?>