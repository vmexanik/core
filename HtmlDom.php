<?
/**
 * @author Irina Miroshnichenko
 */

class HtmlDom extends Base {
	//-----------------------------------------------------------------------------------------------
	function __construct() {

	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Add option to the current select in the client side
	 *
	 * @param int $sSelectId - id of select element
	 * @param String $sOptionText - text of option
	 * @param String $sOptionValue - value of option
	 */
	public function AddCreateOption($sSelectId, $sOptionText, $sOptionValue){
		Base::$oResponse->addScript("general.AddOption('".$sSelectId."', '".$sOptionText."', '".$sOptionValue."');");
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Fill select element to the current select in the client side.
	 * Prepare clear the select
	 * @param int $sSelectId - id of select element
	 * @param Array $aOptions - array of options[key][value]
	 */
	public function AddCreateOptions($sSelectId, $aOptions){
		Base::$oResponse->addScript("general.ClearAllOption('".$sSelectId."');");
		if ($aOptions){
			foreach($aOptions as $sOptionText => $sOptionValue){
				HtmlDom::AddCreateOption($sSelectId, $sOptionText, $sOptionValue);
			}
		}
	}


}
?>