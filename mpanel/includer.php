<?
include 'action_array.php';

krsort($action_array, SORT_STRING);

$sMethod='Index';

$global_include = array();
$curract = Base::$aRequest['action'];
foreach ($action_array as $action_key => $action_value)
{
	$action_parts = explode('*', $action_key);
	$hasAll = true;
	$f = true;
	foreach ($action_parts as $action_part)
	{
		if (strlen(trim($action_part)) > 0)
		{
			$spos = strpos($curract,$action_part);
			if ($spos === false || (($spos > 0) && ($f == true)))
			{
				$hasAll = false;
			}
			$f = false;
		}
	}
	if ($hasAll == true)
	{
		foreach ($global_include as $gi)
		{
			include_once('spec/'.$gi);
		}

		if (Base::$aGeneralConf['LogAdmin']) {
			require_once(SERVER_PATH.'/class/core/Log.php');
			Log::AdminAdd(Base::$aRequest['xajaxargs'][0]);
		}

		if (file_exists(SERVER_PATH.'/mpanel/spec/'.$action_value) ) include_once(SERVER_PATH.'/mpanel/spec/'.$action_value);
		else include_once(SERVER_PATH.'/class/core/mpanel/spec/'.$action_value);

		$sActionBase=substr($action_value,0,stripos($action_value,'.php'));
		if (strpos(Base::$aRequest['action'],'_')) {
			$sActionMethod=substr(Base::$aRequest['action'],strlen($sActionBase) );
			$aActionMethod=explode('_',trim($sActionMethod,'_'));
			if ($aActionMethod ) {
				$sMethod='';
				foreach ($aActionMethod as $value) $sMethod.=ucfirst($value);
			}
		}

		$sClass='A'.Admin::ActionToClass($sActionBase);
		if (class_exists($sClass)) {
			$oObject=new $sClass();
			if (method_exists($oObject,$sMethod)) $oObject->$sMethod();
			else $oObject->Index();
		}
		break;
	}
}
?>