<?

//#################################################################################
define ( RU, "1" ); //for windows-1251

include_once(SERVER_PATH."/libp/xajax/xajax.inc.php");
//#################################################################################
function check_auth() {
	if ($_SESSION["mpanel_auth".Base::$aGeneralConf['ProjectName']]) return true;
	return false;
}
//#################################################################################
function do_include($aTemp, $aFormValues = "", $aURL = "") {
	Base::$sText = '';

	Base::$oResponse = new xajaxResponse();
	if (RU == 1) Base::$oResponse->setCharEncoding(Base::$aGeneralConf['Charset']);

	if (!check_auth()) {
		Base::$oResponse->addRedirect ( "./?auth=bad" );
		return Base::$oResponse->getXML ();
	}

	include_once("action_array.php");

	//--globalize-------------------------
	if (is_array ( $aFormValues )) {
		foreach ( $aFormValues as $sys_key => $sys_value ) {
			$$sys_key = $sys_value;
			Base::$aRequest [$sys_key] = $sys_value;
		}
	}
	//--end globalize---------------------

	//---globalize url variables----------
	if ($aURL != "") {
		$sys_url_string_array = parse_url ( $aURL );
		parse_str ( $sys_url_string_array [query] );
		parse_str ( $sys_url_string_array [query], $aUrlVar );
		Base::FixParseStrBug($aUrlVar);

		Base::$aRequest = array_merge ( Base::$aRequest, $aUrlVar );
	}
	//----end globalize url variables-----

	$aExludeArray = array('click_from_menu', 'xajax', 'xajaxargs', 'xajaxr' );
	$aQueryArray = array();
	if (Base::$aRequest) foreach (Base::$aRequest as $sKey => $aValue) {
		if (!in_array($sKey, $aExludeArray) && !is_array($aValue)) $aQueryArray[$sKey]=stripslashes($aValue);
	}
	Base::$sServerQueryString = http_build_query($aQueryArray);

	include_once(SERVER_PATH.'/class/core/mpanel/includer.php');

	return Base::$oResponse->getXML ();
}

//#################################################################################
function process_form($aFormValues) {
	return do_include ( Base::$oResponse, $aFormValues, "" );
}
//#################################################################################
function process_browse_url($url) {
	return do_include ( Base::$oResponse, "", $url );
}
//#################################################################################

$xajax = new xajax();
if (RU == 1) $xajax->setCharEncoding(Base::$aGeneralConf['Charset']);
//$xajax->debugOn();
if (RU == 1) $xajax->decodeUTF8InputOn();

$xajax->statusMessagesOn();
$xajax->errorHandlerOn();
//$xajax->setLogFile("../xajax/xajax_log/errors.log");
$xajax->registerFunction("process_form");
$xajax->registerFunction("process_browse_url");
$xajax->processRequests();

Base::$tpl->assign('sXajaxJavascript', $xajax->getJavascript('../libp/xajax'));
//#################################################################################


?>
