<?
/**
 * @author Mikhail Strovoyt
 */

require_once(SERVER_PATH.'/class/core/Base.php');
class FileCache extends Base {
	//-----------------------------------------------------------------------------------------------
	public function Cache()
	{

	}
	//-----------------------------------------------------------------------------------------------
	static function GetValue($sSection,$sKey,$bAddIndexDB=false)
	{
		if ($bAddIndexDB) {
			if(file_exists(SERVER_PATH."/cache/{$sSection}_".DB_OCAT."/{$sKey}.cache"))
				return unserialize(file_get_contents(SERVER_PATH."/cache/{$sSection}_".DB_OCAT."/{$sKey}.cache"));
			else
				return false;
		}
		else {
			if(file_exists(SERVER_PATH."/cache/{$sSection}/{$sKey}.cache")) 
				return unserialize(file_get_contents(SERVER_PATH."/cache/{$sSection}/{$sKey}.cache"));
			else 
				return false;
		}
	}
	//-----------------------------------------------------------------------------------------------
	static function SetValue($sSection,$sKey,$sValue,$bAddIndexDb=false)
	{
		if ($bAddIndexDb) {
			@mkdir(SERVER_PATH."/cache/{$sSection}_".DB_OCAT."/", 0777, true);
			file_put_contents(SERVER_PATH."/cache/{$sSection}_".DB_OCAT."/{$sKey}.cache", serialize($sValue));
		}
		else {
			@mkdir(SERVER_PATH."/cache/{$sSection}/", 0777, true);
			file_put_contents(SERVER_PATH."/cache/{$sSection}/{$sKey}.cache", serialize($sValue));
		}
	}
	//-----------------------------------------------------------------------------------------------

}
?>