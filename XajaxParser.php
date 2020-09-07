<?
/**
 * @author Mikhail Strovoyt
 */

define('RU',"1");//for windows-1251

require_once(SERVER_PATH.'/libp/xajax/xajax.inc.php');

$xajax = new xajax();

//AT-1245 begin
if(Base::GetConstant('xajax_custom_uri_on','0')==1) {
    if(strpos($_SERVER['REQUEST_URI'], "/mpanel")===false) {
        $xajax->setRequestURI(Base::GetConstant('xajax_custom_uri','http://klan_branch.lc/'));
    }
}
if($_REQUEST['original_xajax_query_uri']) {
    $xajax->setRequestURI($_REQUEST['original_xajax_query_uri']);
}
//AT-1245 end

if (RU==1) $xajax->setCharEncoding('windows-1251');
//$xajax->debugOn();
if (RU==1) $xajax->decodeUTF8InputOn();

$xajax->statusMessagesOn();
$xajax->errorHandlerOn();
//$xajax->setLogFile("../xajax/xajax_log/errors.log");

$xajax->registerFunction("process_form");
$xajax->registerFunction("process_browse_url");

$xajax->processRequests();

$sXajaxJavascript=$xajax->getJavascript('/libp/xajax');


?>