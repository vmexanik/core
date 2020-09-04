<?
/**
 * @author Mikhail Strovoyt
 */

require_once(SERVER_PATH.'/class/core/Base.php');
class Cache extends Base {

	//-----------------------------------------------------------------------------------------------
	public function Cache()
	{

	}
	//-----------------------------------------------------------------------------------------------
	function GetValue($sSection,$sKey)
	{
		$sKey=substr($sKey,0,254);//TODO need to make hash from $sKey

		$sCache=Base::$db->getOne("select value from cache where section='$sSection'
			and key_='".mysql_real_escape_string($sKey)."' and valid_till>UNIX_TIMESTAMP()");
		if (!$sCache) {
			//Base::$db->Execute("delete from cache where section='$sSection' and key_='$sKey'");
			return false;
		}
		return $sCache;
	}
	//-----------------------------------------------------------------------------------------------
	function SetValue($sSection,$sKey,$sValue,$iValidTill=false)
	{
		$sKey=substr($sKey,0,254);
		if (!$iValidTill) $iValidTill=time()+7*86400;
		Base::$db->Execute("insert into cache(section,key_,value,valid_till)
			values('$sSection','".mysql_real_escape_string($sKey)."','$sValue','$iValidTill') on duplicate key
			update value='$sValue', valid_till='$iValidTill'");
	}
	//-----------------------------------------------------------------------------------------------

}
?>