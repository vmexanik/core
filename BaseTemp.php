<?
/**
 * @author Mikhail Strovoyt
 */
/**
 * Class for placing general methods when there is no strict class for it
 *
 */
class BaseTemp {

	//-----------------------------------------------------------------------------------------------
	/**
	 * Method convert from DB enum/set in array
	 *
	 * @param String $sTable - from table
	 * @param String $sField - wich field must be convert
	 * @return array
	 */
	public static function EnumToArray($sTable, $sField){
		$aFieldType =  Base::$db->GetRow("SHOW COLUMNS FROM ".$sTable." LIKE '%".$sField."%'");
		return explode("','",preg_replace("/(enum|set)\('(.+?)'\)/","\\2", $aFieldType['Type']));
	}
	//-----------------------------------------------------------------------------------------------
}
?>