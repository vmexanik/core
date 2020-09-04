<?
/**
 * @author Mikhail Strovoyt
 */


class String {
    protected static $code39 = array(
        '0' => 'bwbwwwbbbwbbbwbw','1' => 'bbbwbwwwbwbwbbbw',
        '2' => 'bwbbbwwwbwbwbbbw','3' => 'bbbwbbbwwwbwbwbw',
        '4' => 'bwbwwwbbbwbwbbbw','5' => 'bbbwbwwwbbbwbwbw',
        '6' => 'bwbbbwwwbbbwbwbw','7' => 'bwbwwwbwbbbwbbbw',
        '8' => 'bbbwbwwwbwbbbwbw','9' => 'bwbbbwwwbwbbbwbw',
        'A' => 'bbbwbwbwwwbwbbbw','B' => 'bwbbbwbwwwbwbbbw',
        'C' => 'bbbwbbbwbwwwbwbw','D' => 'bwbwbbbwwwbwbbbw',
        'E' => 'bbbwbwbbbwwwbwbw','F' => 'bwbbbwbbbwwwbwbw',
        'G' => 'bwbwbwwwbbbwbbbw','H' => 'bbbwbwbwwwbbbwbw',
        'I' => 'bwbbbwbwwwbbbwbw','J' => 'bwbwbbbwwwbbbwbw',
        'K' => 'bbbwbwbwbwwwbbbw','L' => 'bwbbbwbwbwwwbbbw',
        'M' => 'bbbwbbbwbwbwwwbw','N' => 'bwbwbbbwbwwwbbbw',
        'O' => 'bbbwbwbbbwbwwwbw','P' => 'bwbbbwbbbwbwwwbw',
        'Q' => 'bwbwbwbbbwwwbbbw','R' => 'bbbwbwbwbbbwwwbw',
        'S' => 'bwbbbwbwbbbwwwbw','T' => 'bwbwbbbwbbbwwwbw',
        'U' => 'bbbwwwbwbwbwbbbw','V' => 'bwwwbbbwbwbwbbbw',
        'W' => 'bbbwwwbbbwbwbwbw','X' => 'bwwwbwbbbwbwbbbw',
        'Y' => 'bbbwwwbwbbbwbwbw','Z' => 'bwwwbbbwbbbwbwbw',
        '-' => 'bwwwbwbwbbbwbbbw','.' => 'bbbwwwbwbwbbbwbw',
        ' ' => 'bwwwbbbwbwbbbwbw','*' => 'bwwwbwbbbwbbbwbw',
        '$' => 'bwwwbwwwbwwwbwbw','/' => 'bwwwbwwwbwbwwwbw',
        '+' => 'bwwwbwbwwwbwwwbw','%' => 'bwbwwwbwwwbwwwbw');
	//-----------------------------------------------------------------------------------------------
	public static function Serialize($aValue)
	{
		if (is_array($aValue)) {
			foreach ($aValue as $key =>$value) $aRow[$key]=base64_encode(mysql_real_escape_string($value));
			$aData=serialize($aRow);
		}
		return $aData;
	}
	//-----------------------------------------------------------------------------------------------
	public static function Unserialize($sValue)
	{
		$aRow=unserialize($sValue);
		if (is_array($aRow)) foreach ($aRow as $key =>$value) $aData[$key]=base64_decode($value);
		return $aData;
	}
	//-----------------------------------------------------------------------------------------------
	public static function FirstNwords($sString, $iWord)
	{
		$aWord=explode(" ", strip_tags($sString),$iWord+1);
		$aWord[$iWord]='';
		return implode(' ',$aWord);
	}
	//-----------------------------------------------------------------------------------------------
	public static function CheckEmail($sEmail)
	{
		return preg_match('/^[0-9a-z^\-\.\_]+@([0-9a-z\-\_]+\.)+[a-z]{2,}$/i', $sEmail);
	}
	//-----------------------------------------------------------------------------------------------
	/** Filter all the user data put into database and outputed on the pages
	 * @param $aData - input request
	 * @param $aFieldArray - array of string which need to be added to filtereoutptut array
	 * @return unknown
	 */
	public static function FilterRequestData($aData,$aFieldArray=array())
	{
		if ($aData) foreach ($aData as $sKey => $aValue)  {
			if(is_array($aValue)){
				//for arrays in multiple selects and radios...
				foreach ($aValue as $sKey2 => $sValue2)
				$aData[$Key][$sKey2]=htmlspecialchars(strip_tags($sValue2));
			}else
			$aData[$sKey]=htmlspecialchars(strip_tags($aValue));
		}
		if ($aFieldArray) {
			foreach ($aFieldArray as $value) {
				if (isset($aData[$value])) $aReturnData[$value]=$aData[$value];
			}
		}
		else $aReturnData=$aData;

		return $aReturnData;
	}
	//-----------------------------------------------------------------------------------------------
	/** Parse columned string like "mstar, alexey, otherlogin"
	 * @param $aData - input request
	 * @return array like ('value1','value2',...,'valuen')
	 */
	public static function QuoteCommaString($sString)
	{
		$aPart=preg_split("/[\s,;]+/", $sString);
		if ($aPart) {
			foreach ($aPart as $aValue) {
				if ($aValue) $aReturn[]="'".mysql_real_escape_string($aValue)."'";
			}
			if ($aReturn) return implode(',',$aReturn);
		}
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Check whether the string $sNeedle is the same domain with $sHaystack
	 *
	 * @return bollean
	 */
	public static function CheckDomain($sHaystack, $sNeedle){
		return in_array(strtolower($sNeedle),array(strtolower($sHaystack),strtolower('www.'.$sHaystack)));
	}
	//-----------------------------------------------------------------------------------------------
	public static function UtfEncode($sString){
		return iconv('windows-1251','utf-8',$sString);
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Parse smarty template from 'template' table by 'code' field and Localize it.
	 *
	 * @param String $sKey - code field from tamplete table
	 * @param arary $aTemplateData
	 * @return parsed with Smarty text from Tamplate
	 */
	public function GetSmartyTemplate($sKey,$aTemplateData=array(), $bOldStyleTemplate=true)
	{
		$aRow=String::GetTemplateRow($sKey);

		if ($aTemplateData) foreach ($aTemplateData as $sKey => $aValue) {
			Base::$tpl->assign($sKey, $aValue);
		}

		if ($bOldStyleTemplate) {
			//Backward compatibility for old style templates
			$aRow['content']=str_replace('[','{',$aRow['content']);
			$aRow['content']=str_replace(']','}',$aRow['content']);
		}
		
		// parse text constants
		$sPattern="!const::\((.*?)\)!si";
		preg_match_all($sPattern,$aRow['content'],$aMatches);
		if (count($aMatches[1]) > 0) {
			$aMatches[1] = array_unique($aMatches[1]);
			foreach ($aMatches[1] as $sConstant)
				$aRow['content'] = str_replace('const::('.$sConstant.')', Language::getConstant($sConstant), $aRow['content']);
		}
		
		// before assign removing the are all of HTML entities like &lt; &gt; etc.
		if (Base::GetConstant('string:entity_decode_template',1)){
			Base::$tpl->assign('sSmartyTemplate', html_entity_decode($aRow['content'],ENT_COMPAT,'UTF-8'));
		}
		else Base::$tpl->assign('sSmartyTemplate', $aRow['content']);

		$aRow['parsed_text'] = Base::$tpl->fetch('addon/smarty_template.tpl');

		return $aRow;
	}
	//-----------------------------------------------------------------------------------------------
	public function GetTemplateRow($sKey)
	{
		$sQuery="select * from template where code='$sKey'";
		$aRow=Base::$db->getRow($sQuery);
		if (!$aRow['id']) {
			Base::$db->Execute("insert into template (code,name,content) values('".$sKey."','".$sKey."','".$sKey."') ");
			$aRow=Base::$language->getLocalizedRow(array(
			'table'=>'template',
			'where'=>" and code='".$sKey."'",
			));
		}
		$aData=array(
		'table'=>'template',
		'where'=>" and code='$sKey'",
		);
		$aRow=Language::GetLocalizedRow($aData);

		return $aRow;
	}
	//-----------------------------------------------------------------------------------------------
	public function GetPage($sKey, $bShowInvisible=false)
	{
		$aData=array(
		'table'=>'drop_down',
		'where'=>" and code='$sKey'",
		);
		if (!$bShowInvisible) $aData['where'].=" and visible='1'";

		$aPage=Base::$language->getLocalizedRow($aData);
		if (Base::GetConstant('global:drop_down_crumb',0) && $aPage) Base::$oContent->AddCrumb($aPage['name']); 
		if ($aPage['name']) {
			Base::$aData['template']['sMethodName']=$aPage['name'];
			Base::$tpl->assign('sCrumbsPageName',$aPage['name']);
		}
		// rewrite text?
		if (method_exists(Base::$oContent,'getRewriteStaticContent')) {
			$aPage['text'] = Content::getRewriteStaticContent($aPage['text']);
		} 
		return $aPage;
	}
	//-----------------------------------------------------------------------------------------------
	public function ProcessDropDownAdditional()
	{
		if ($_SERVER['QUERY_STRING']) {
			if (Base::GetConstant('global:drop_down_additional_static',0)){
				$aStaticNameArray=preg_split("/[\s,;]+/",Base::GetConstant('drop_down_additional:static_name_array','static'));
				if ($aStaticNameArray) foreach ($aStaticNameArray as $sValue) {
					$sWhere.="
						or '".$_SERVER['REQUEST_URI']."' = concat('/".$sValue."/',dda.static_rewrite,'/')
						or '".$_SERVER['REQUEST_URI']."' = concat('/".$sValue."/',dda.static_rewrite)";
				}
			}

			$aDropDownAdditional=Db::GetRow(Base::GetSql('CoreDropDownAdditional',array(
			'visible'=>1,
			'where'=>" and (dda.url='".$_SERVER['QUERY_STRING']."'
					".$sWhere.")",
			)));
		}else{
			$aDropDownAdditional=Db::GetRow(Base::GetSql('CoreDropDownAdditional',array(
			'visible'=>1,
			'where'=>" and (dda.url='action=home')",
			)));
		}

		if (!$aDropDownAdditional && $_SERVER['REQUEST_URI']) {
			$aDropDownAdditional=Db::GetRow(Base::GetSql('CoreDropDownAdditional',array(
			'visible'=>1,
			'where'=>" and ('".$_SERVER['REQUEST_URI']."' like dda.url)",
			)));
			
			if ($aDropDownAdditional){
			    $sMethod="GetLocalizedDropDownAdditional";
			    if(method_exists(Language, $sMethod))
			        Language::$sMethod($aDropDownAdditional);
			}
		}

		if ($aDropDownAdditional) {
			if ($aDropDownAdditional['title'])
			Base::$aData['template']['sPageTitle'] = $aDropDownAdditional['title'];
			if ($aDropDownAdditional['page_description'])
			Base::$aData ['template']['sPageDescription']=$aDropDownAdditional['page_description'];
			if ($aDropDownAdditional['page_keyword'])
			Base::$aData ['template']['sPageKeyword']=$aDropDownAdditional['page_keyword'];
			if ($aDropDownAdditional['short_description'])
			Base::$aData ['template']['sShortDescription']=$aDropDownAdditional['short_description'];
			if ($aDropDownAdditional['description'])
			Base::$aData ['template']['sDescription']=$aDropDownAdditional['description'];
			if ($aDropDownAdditional['description_hide'] 
				&& $aDropDownAdditional['description_hide']!='<p><br></p>'
				&& $aDropDownAdditional['description_hide']!='<p>&nbsp;</p>'
			)
			Base::$aData ['template']['sDescriptionHide']=$aDropDownAdditional['description_hide'];
		}
	}
	//-----------------------------------------------------------------------------------------------
	public function ProcessStatic()
	{
		if (Base::$aRequest['static_rewrite']) {
			$aDropDownAdditional=Db::GetRow(Base::GetSql('CoreDropDownAdditional',array(
			'where'=> " and static_rewrite='".Base::$aRequest['static_rewrite']."'",
			'visible'=>1,
			)));
			if ($aDropDownAdditional) {
				parse_str($aDropDownAdditional['url'], $aUrl);
				Base::$aRequest = array_merge(Base::$aRequest, $aUrl);
			}elseif (Base::GetConstant('drop_down_additional:error 404',0)){
				Header("HTTP/1.1 404 Not Found", true, 404);
				Error::GetError(404);
			}
		}
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Get decimal from string
	 *
	 * @param string $sDouble
	 * @return double
	 */
	public function GetDecimal($sDouble)
	{
		return preg_replace ("/[^0-9.]/","",rtrim(str_replace(",",".",$sDouble),"."));
	}
	//-----------------------------------------------------------------------------------------------
	public static function Md5Salt($sPassword,$sSalt)
	{
		return md5(md5($sPassword).$sSalt);
	}
	//-----------------------------------------------------------------------------------------------
	public static function GenerateSalt()
	{
		$iLength = rand(5,10);
		for($i=0; $i<$iLength; $i++) $sSalt.=chr(rand(97,122));
		return $sSalt;
	}
	//-----------------------------------------------------------------------------------------------
	public static function GeneratePref($sPrefIn='')
	{
		$sPref=Base::GetConstant('global:auto_pref_last','ZZ');
		if($sPrefIn){
                    $sPrefIn=Content::Translit($sPrefIn);
                    $sPref=substr(strtoupper($sPrefIn), 0, 3);
					if(strlen($sPref)==2)$sPref=$sPref.'0';
                }else
		$sPref=strtoupper($sPref);
		do {
		if($i>0 && $sPrefIn=='')Base::UpdateConstant('global:auto_pref_last',$sPref);
		$sLastChar1=$sPref[strlen($sPref)-1];
		$sLastChar2=$sPref[strlen($sPref)-2];
		$sLastChar3=$sPref[strlen($sPref)-3];
		if(!$sLastChar3)$sLastChar3='@';
		$sLastChar1=chr(ord($sLastChar1)+1);
		if($sLastChar1=='['){
			$sLastChar1='A';
			$sLastChar2=chr(ord($sLastChar2)+1);
			if($sLastChar2=='['){
				$sLastChar2='A';
				$sLastChar3=chr(ord($sLastChar3)+1);
				if($sLastChar3=='['){
					return false;
				}
			}
		}
		$sPref=$sLastChar3.$sLastChar2.$sLastChar1;
		$i=Db::GetOne("select count(*) c from cat where pref='".$sPref."'");
		} while ($i > 0);
		return $sPref;
	}
	//-----------------------------------------------------------------------------------------------
	public static function GetUcfirst($sString)
	{
		if (!function_exists('mb_ucfirst') && function_exists('mb_substr')) {
			function mb_ucfirst($string) {
				$string = mb_ereg_replace("^[\ ]+","", $string);
				$string = mb_strtoupper(mb_substr($string, 0, 1, "UTF-8"),"UTF-8").mb_substr($string,1,mb_strlen($string),"UTF-8");
				return $string;
			}
		}
		return mb_ucfirst($sString);
	}
	//-----------------------------------------------------------------------------------------------
	public static function GetUcword($sString)
	{
		return  mb_convert_case($sString, MB_CASE_TITLE, "UTF-8");

	}
	//-----------------------------------------------------------------------------------------------
	public function FormatPhoneNumber($sString,$bNeedCat=TRUE){
		$sNumber=preg_replace("/\D/","",$sString);
		if ($bNeedCat) {
			$iLength=Base::GetConstant('global:phone_number_length',10);
			$sNumber=substr($sNumber,-$iLength,$iLength);
		}
		return $sNumber;
	}
	//-----------------------------------------------------------------------------------------------
	public function CheckUaPhone($sString){
		$sPhoneNumberPrefix=Base::GetConstant('global:ua_phone_number_prefix','0');
		$iPhoneLength=Base::GetConstant('global:phone_number_length',10);
		if (substr($sString,0,strlen($sPhoneNumberPrefix))!=$sPhoneNumberPrefix)
			return false;
		if (strlen($sString)==$iPhoneLength)
			return true;
			else 
			return false;
	}
	//-----------------------------------------------------------------------------------------------
	public static function GenerateBarcode($text, $height = 60, $widthScale = 2) {
	    if (!preg_match('/^[A-Z0-9-. $+\/%]+$/i', $text)) {
	        throw new Exception('Invalid text input.');
	    }
	    $text = '*' . strtoupper($text) . '*'; // *UPPERCASE TEXT*
	    $length = strlen($text);
	    $barcode = imageCreate($length * 16 * $widthScale, $height);
	    $bg = imagecolorallocate($barcode, 255, 255, 0); //sets background to yellow
	    imagecolortransparent($barcode, $bg); //makes that yellow transparent
	    $black = imagecolorallocate($barcode, 0, 0, 0); //defines a color for black
	    $chars = str_split($text);
	    $colors = '';
	    foreach ($chars as $char) {
	        $colors .= self::$code39[$char];
	    }
	    foreach (str_split($colors) as $i => $color) {
	        if ($color == 'b') {
	            // imageLine($barcode, $i, 0, $i, $height-13, $black);
	            imageFilledRectangle($barcode, $widthScale * $i, 0, $widthScale * ($i+1) -1 , $height-13, $black);
	        }
	    }
	    //16px per bar-set, halved, minus 6px per char, halved (5*length)
	    // $textcenter = $length * 5 * $widthScale;
	    $textcenter = ($length * 8 * $widthScale) - ($length * 3);
	
	    imageString($barcode, 5, $textcenter, $height-13, $text, $black);
	    
	    $sDate=date('Y')."/".date('m')."/";
	    $sPathToFile=SERVER_PATH."/imgbank/temp_upload/barcode/".$sDate;
	    if (!file_exists($sPathToFile)) {
	        mkdir($sPathToFile, 0755, true);
	    }
	    $sPathToFile.=trim($text,"*").".png";
	    imagePNG($barcode, $sPathToFile);
	    imageDestroy($barcode);
	    return str_replace(SERVER_PATH, "", $sPathToFile);
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Parses a given byte count.
	 *
	 * @param $size
	 *   A size expressed as a number of bytes with optional SI or IEC binary unit
	 *   prefix (e.g. 2, 3K, 5MB, 10G, 6GiB, 8 bytes, 9mbytes).
	 *
	 * @return
	 *   An integer representation of the size in bytes.
	 */
	public function ParseSize($size) {
		$unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
		$size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
		if ($unit) {
			// Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
			return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
		}
		else {
			return round($size);
		}
	}
	/**
	 * Generates a string representation for the given byte count.
	 *
	 * @param $size
	 *   A size in bytes.
	 * @param $langcode
	 *   Optional language code to translate to a language other than what is used
	 *   to display the page.
	 *
	 * @return
	 *   A translated string representation of the size.
	 */
	public function FormatSize($size) {
		
		$iKilobyte = 1024;
		 
		if ($size < $iKilobyte) {
			return (($size == 1) ? '1 '. Language::GetMessage('byte') : $size . Language::GetMessage('bytes'));
		}
		else {
			$size = $size / $iKilobyte; // Convert bytes to kilobytes.
			$units = array('KB','MB','GB','TB','PB','EB','ZB','YB');
			foreach ($units as $unit) {
				if (round($size, 2) >= $iKilobyte) {
					$size = $size / $iKilobyte;
				}
				else {
					break;
				}
			}
			return round($size, 2) . Language::GetMessage($unit);
		}
	}
	/**
	 * Formats a string containing a count of items.
	 *
	 * This function ensures that the string is pluralized correctly. Since t() is
	 * called by this function, make sure not to pass already-localized strings to
	 * it.
	 *
	 * For example:
	 * @code
	 *   $output = format_plural($node->comment_count, '1 comment', '@count comments');
	 * @endcode
	 *
	 * Example with additional replacements:
	 * @code
	 *   $output = format_plural($update_count,
	 *     'Changed the content type of 1 post from %old-type to %new-type.',
	 *     'Changed the content type of @count posts from %old-type to %new-type.',
	 *     array('%old-type' => $info->old_type, '%new-type' => $info->new_type));
	 * @endcode
	 *
	 * @param $count
	 *   The item count to display.
	 * @param $singular
	 *   The string for the singular case. Make sure it is clear this is singular,
	 *   to ease translation (e.g. use "1 new comment" instead of "1 new"). Do not
	 *   use @count in the singular string.
	 * @param $plural
	 *   The string for the plural case. Make sure it is clear this is plural, to
	 *   ease translation. Use @count in place of the item count, as in
	 *   "@count new comments".
	 *
	 * @return
	 *   A translated string.
	 *
	 */
	public function FormatPlural($count, $singular, $plural) {
	  if ($count == 1) {
	    return Language::GetMessage($singular);
	  }
	  
	  return $count . " " . Language::GetMessage($plural);	  
	}
}
?>