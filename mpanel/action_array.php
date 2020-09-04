<?
//###########################################################
// Action Array
//###########################################################
$action_array = array(

);
//###########################################################
$aDirectory=array(SERVER_PATH.'/mpanel/spec/',SERVER_PATH.'/class/core/mpanel/spec/');
foreach($aDirectory as $sValue) {

	if ($dh = opendir($sValue)) {
		unset($arr_include);
		while (($file = readdir($dh)) !== false) {
			if ($file != "." && $file != ".." && strpos($file,'.php')!==false) {
				if (filetype($sValue.$file)=="file") {
					$file_name_array=preg_split("/\.php/",$file);
					$file_name=$file_name_array[0];
					if (!in_array($file,$action_array)
					&& (strpos($file_name,'_standart_')===false)
					) {
						$action_array[$file_name]=$file;
					}
				}
			}
		}
		closedir($dh);
	}
}

//###########################################################
//###########################################################

?>
