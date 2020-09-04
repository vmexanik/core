<?
/**
 * @author Mikhail Strovoyt
 */

/**
 * Language Manager deals with transating localisation data in aps project
 */
class Language extends Base
{
	/**
	 * Translated Data Array
	 */
	private static $aTranslateMessage = array ();
	private static $aTranslateText = array ();
	private static $aContextHint = array ();

	/** Current locale code */
	public static $sLocale = 'ru';
	/** Current locale id for sql queries */
	public static $iLocale = 1;
	public static $aLanguageList;
	public static $aLanguageAssoc;

	public static $sBaseLocale = 'ru';

	/** Buffer for AddOldParser method */
	public static $aOldParser=array();

	function __construct($sBaseLocale = '')
	{
		/** For urls like site.com/nl/ for disabling translations */
		if (Base::$aRequest['locale']=='nl') return;

		Language::$aLanguageAssoc=Base::$db->getAssoc("select code as id,l.id as value from language as l");

		if ($sBaseLocale) Language::$sBaseLocale = $sBaseLocale;
		if (Base::$aRequest ['locale'] && in_array(Base::$aRequest['locale'],array_keys(Language::$aLanguageAssoc)) )	{
			Language::$sLocale = Base::$aRequest['locale'];
		}
		else Language::$sLocale = Language::$sBaseLocale;
		Base::$tpl->assign('sLocale',Language::$sLocale);


		Language::$iLocale=Language::$aLanguageAssoc[Language::$sLocale];
		Base::$tpl->assign('iLocale',Language::$iLocale);
		Base::$tpl->assign('aLanguageAssoc', Language::$aLanguageAssoc);
		Language::$aLanguageList = Base::$db->getAll("select * from language where visible=1 order by num");
		Base::$tpl->assign('aLanguageList', Language::$aLanguageList);


		if (strpos ( $_SERVER ['QUERY_STRING'], 'ocale=' ))
		$_SERVER ['QUERY_STRING'] = substr ( $_SERVER ['QUERY_STRING'], 10, strlen ( $_SERVER ['QUERY_STRING'] ) );
		if ($_SERVER ['QUERY_STRING'])
		Base::$tpl->assign ( 'sQueryString', '?' . $_SERVER ['QUERY_STRING'] );
		
		if(Base::$aGeneralConf['ProjectName'] == 'elit.Mstarproject') {
			$sCache = SERVER_PATH."/cache/translate_message.cache";
			$aData = array ('table' => 'translate_message' );
			if(!Language::$aTranslateMessage = FileCache::GetValue('Language', 'translate_message')) {
				Language::$aTranslateMessage = $this->GetLocalizedAll ( $aData, false, 'lower(t.code), ');
				FileCache::SetValue('Language', 'translate_message', Language::$aTranslateMessage);
			}
				
			$aData = array ('table' => 'translate_text' );
			if(!Language::$aTranslateText = FileCache::GetValue('Language', 'translate_text')) {
				Language::$aTranslateText = Base::$this->GetLocalizedAll ( $aData );
				Language::$aTranslateText = Language::Array2Hash ( Language::$aTranslateText, 'code' );
				FileCache::SetValue('Language', 'translate_text', Language::$aTranslateText);
			}

			$aData = array ('table' => 'context_hint' );
			if(!Language::$aContextHint = FileCache::GetValue('Language', 'context_hint')) {
				Language::$aContextHint = Base::$this->GetLocalizedAll ( $aData );
				Language::$aContextHint = Language::Array2Hash ( Language::$aContextHint, 'key_' );
				FileCache::SetValue('Language', 'context_hint', Language::$aContextHint);
			}

		} else {
			$aData = array ('table' => 'translate_message' );
			Language::$aTranslateMessage = $this->GetLocalizedAll ( $aData, false, 'lower(t.code), ');
			//Language::$aTranslateMessage = Language::Array2Hash ( Language::$aTranslateMessage, 'code' );
	
			$aData = array ('table' => 'translate_text' );
			Language::$aTranslateText = Base::UnescapeAll($this->GetLocalizedAll ( $aData ));
			Language::$aTranslateText = Language::Array2Hash ( Language::$aTranslateText, 'code' );

			$aData = array ('table' => 'context_hint' );
			Language::$aContextHint = $this->GetLocalizedAll ( $aData );
			Language::$aContextHint = Language::Array2Hash ( Language::$aContextHint, 'key_' );
		}
	}
	//-----------------------------------------------------------------------------------------------
	public static function GetLocale()
	{
		$aLocale = array ('sCode' => "_" . Language::$sLocale, 'sAlign' => "left" );
		return $aLocale;
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Get localised value for captions
	 */
	public static function GetMessage($sKey, $sPage = "")
	{
		if (!$sKey) return;
		if (Base::$aRequest['locale']=='nl') return $sKey;

// 		if ($_GET ['language'])	$sKey .= $_GET ['language'];
		$sKey=trim(mb_strtolower($sKey,Base::GetConstant('global:php_mb_encoding','cp1251')));
		if (!Language::$aTranslateMessage[$sKey]['content']) {
			Db::Execute("insert ignore into translate_message (code,page,content)
				value('".mysql_real_escape_string($sKey)."','".$sPage."','".mysql_real_escape_string($sKey)."')");
			Language::$aTranslateMessage[$sKey]['content']=$sKey;
			return $sKey;
		}
		return Language::$aTranslateMessage[$sKey]['content'];
	}
	//-----------------------------------------------------------------------------------------------
	public static function GetDMessage($sKey, $sPage = "")
	{
		return Language::GetMessage ( '~' . $sKey, $sPage );
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Get localised value for html texts
	 */
	public static function GetText($sKey, $sPage = "")
	{
		if (!$sKey) return;
		if (Base::$aRequest['locale']=='nl') return $sKey;

		if (!Language::$aTranslateText [$sKey] ['content']) {
			Base::$db->Execute ( "insert ignore into translate_text (code,content)
				value('" . mysql_real_escape_string ( $sKey ) . "','" . mysql_real_escape_string ( $sKey ) . "') " );
			Language::$aTranslateText [$sKey] ['content'] = $sKey;
			return $sKey;
		}
		return Language::$aTranslateText [$sKey] ['content'];
	}
	//-----------------------------------------------------------------------------------------------
	public static function GetContextHint($sKey, $bUnique = false)
	{
		if (!$sKey) return;
		if (Base::$aRequest['locale']=='nl') return '';

		if (! Language::$aContextHint [$sKey] ['content']) {
			Base::$db->Execute ( "insert ignore into context_hint (key_,content)
				value('" . mysql_real_escape_string ( $sKey ) . "','" . mysql_real_escape_string ( $sKey ) . "') " );
			$aContextHint = array ('key_' => $sKey, 'content' => $sKey, 'visible' => '1' );
		} else $aContextHint = Language::$aContextHint [$sKey];

		if ($aContextHint ['visible']) {
			Base::$tpl->assign ( 'aContextHint', $aContextHint );
			if ($bUnique) Base::$tpl->assign ( 'sUnique', uniqid () );
			else Base::$tpl->assign ( 'sUnique', '' );

			return Base::$tpl->fetch ( 'hint/context.tpl' );
		}
	}
	//-----------------------------------------------------------------------------------------------
	/**
	* Deprecated method - use $oContent->GetOrderStatus() instead
	*/
	public static function GetOrderStatus($sKey)
	{
		switch ($sKey) {
			case 'new' :
				return '<b><font color=red>' . Language::getMessage ( $sKey ) . '</font></b>';
				break;
			case 'work' :
				return '<b><font color=green>' . Language::getMessage ( $sKey ) . '</font></b>';
				break;
			case 'confirmed' :
				return '<b><font color=blue>' . Language::getMessage ( $sKey ) . '</font></b>';
				break;
			case 'road' :
				return '<b><font color=blue>' . Language::getMessage ( $sKey ) . '</font></b>';
				break;
			case 'store' :
				return '<b><font color=blue>' . Language::getMessage ( $sKey ) . '</font></b>';
				break;
			case 'end' :
				return '<b><font color=black>' . Language::getMessage ( $sKey ) . '</font></b>';
				break;
			case 'refused' :
			case 'store_refused' :
				return '<b><font color=braun>' . Language::getMessage ( $sKey ) . '</font></b>';
				break;
			case 'pending' :
				return '<b><font color=blue>' . Language::getMessage ( $sKey ) . '</font></b>';
				break;

			case 'parsed' :
				return '<b><font color=blue>' . Language::getMessage ( $sKey ) . '</font></b>';
				break;

			default :
				return '<b>' . Language::getMessage ( $sKey ) . '</b>';
		}
	}
	//-----------------------------------------------------------------------------------------------
	/**
	* Deprecated method - use Base::$oCurrency->PrintPrice instead
	*/
	public static function PrintPrice($dAmount,$bAbs=false,$bInvert=false,$sOutputType='',$sPriceType='')
	{

		if ($bAbs) $dAmount=abs($dAmount);
		if ($bInvert) $dAmount=-$dAmount;
		return Currency::PrintPrice($dAmount,$sPriceType,Base::GetConstant('currency:round_digit',2),$sOutputType);
	}
	public static function PrintCurrencyPrice($dAmount,$sCurrency='USD')
	{
		return Currency::PrintCurrencyPrice($dAmount,$sCurrency);
	}
	public static function PrintPriceByType($dAmount,$sPriceType='USD')
	{
		return Currency::PrintPriceByType($dAmount,$sPriceType);
	}
	//-----------------------------------------------------------------------------------------------
	public static function Price($dAmount, $sPriceType='',$bAbs=false,$bInvert=false)
	{
		if ($bAbs) $dAmount=abs($dAmount);
		if ($bInvert) $dAmount=-$dAmount;
		if (!$sPriceType) $sPriceType=Base::GetConstant('global:base_currency','USD');

		return Currency::Price($dAmount,$sPriceType);
	}
	//-----------------------------------------------------------------------------------------------
	public static function GetDate($iTimestamp = '', $iTimeZone = '',$bCurrentForce=false)
	{
		if (!$iTimestamp && $bCurrentForce) $iTimestamp=time();
		return DateFormat::getDate ( $iTimestamp, $iTimeZone );
	}
	//-----------------------------------------------------------------------------------------------
	public static function GetDateTime($iTimestamp = '', $iTimeZone = '')
	{
		return DateFormat::getDateTime ( $iTimestamp, $iTimeZone );
	}
	//-----------------------------------------------------------------------------------------------
	public static function GetPostDate($sPostDate,$iTimeZone='')
	{
		return DateFormat::GetPostDate($sPostDate,$iTimeZone);
	}
	//-----------------------------------------------------------------------------------------------
	public static function GetPostDateTime($sPostDate,$iTimeZone='')
	{
		return DateFormat::GetPostDateTime($sPostDate,$iTimeZone);
	}
	//-----------------------------------------------------------------------------------------------
	public static function FormatDate($sSearchDate, $sFormat='Y-m-d H:i:s')
	{
		return DateFormat::FormatSearch($sSearchDate, $sFormat);
	}
	//-----------------------------------------------------------------------------------------------

	public static function GetLocalizedRow($aData)
	{
		$aMainRow = Base::$db->getRow ( "select * from " . $aData ['table'] . " where 1=1 " . $aData ['where'] );
		if (! $aMainRow) return;
		if (Language::$sLocale == Language::$sBaseLocale) return $aMainRow;

		$sQuery = "select *
				from locale_global
				where table_name='" . $aData ['table'] . "'
					and locale='" . Language::$sLocale . "'
					and id_reference='" . $aMainRow ['id'] . "'";
		$aLocalizedRow = Base::$db->getRow( $sQuery );

		$aMap=Language::IncludeLocaleMap($aData ['table']);

		if (!$aLocalizedRow ['table_name']) {
			foreach ( $aMap as $key => $value ) {
				$aFields [$key] = "'" . mysql_real_escape_string ( $aMainRow [$key] ) . "'";
			}
			$sFields = implode ( ',', array_keys ( $aFields ) );
			$sValues = implode ( ',', $aFields );

			$sQuery = "insert into locale_global (table_name,id_reference,locale , " . $sFields . " )
					values('".$aData['table']."',".$aMainRow['id'].",'".Language::$sLocale."',".$sValues.")";
			Base::$db->Execute ( $sQuery );
		} else {
			foreach ( $aMap as $key => $value ) {
				$aMainRow [$key] = $aLocalizedRow [$key];
			}
		}
		return $aMainRow;
	}
	//-----------------------------------------------------------------------------------------------
	public static function GetLocalizedAll($aData, $bReturnSql=false, $sAssocField = '')
	{
		$sAssocField = trim($sAssocField);

		$sQuery = "select ".$sAssocField." t.* from ".$aData ['table']." as t where 1=1 ".$aData ['where']." ".$aData ['order'];
		if ($sAssocField != ''){
			$aMainAll = Base::$db->getAssoc ( $sQuery );
		}else {
			$aMainAll = Base::$db->getAll ( $sQuery );
		}

		if (! $aMainAll) return $bReturnSql ? $sQuery : False;
		if (Language::$sLocale == Language::$sBaseLocale){
			if ($bReturnSql) return $sQuery;
			return $aMainAll;
		}

		$sQuery = "select ".$sAssocField." t.*,l.*
				from " . $aData ['table'] . " t
				inner join locale_global l on (t.id=l.id_reference and l.table_name='" . $aData ['table'] . "'
					and l.locale='" . Language::$sLocale . "')
				where	1=1 ".$aData['where'].' '.$aData['locale_where']
		." ".$aData['order'];

		if ($sAssocField != ''){
			$aLocalizedAll = Base::$db->getAssoc ( $sQuery );
		}else {
			$aLocalizedAll = Base::$db->getAll ( $sQuery );
		}

		if (count($aMainAll) > count($aLocalizedAll)) {
			$aMap=Language::IncludeLocaleMap($aData['table']);

			foreach ( $aMainAll as $aMainRow ) {
				foreach ( $aMap as $key => $value ) {
					$aFields [$key] = "'" . mysql_real_escape_string ( $aMainRow [$key] ) . "'";
				}
				$sFields = implode ( ',', array_keys ( $aFields ) );
				$sValues = implode ( ',', $aFields );

				$sQuery = "insert into locale_global (table_name,id_reference,locale , " . $sFields . " )
							values('".$aData['table']."',".$aMainRow['id'].",'".Language::$sLocale."',".$sValues.")
								ON DUPLICATE KEY UPDATE table_name = '" . $aData ['table'] . "'";
				Base::$db->Execute ( $sQuery );
			}

			$sQuery = "select ".$sAssocField." t.*,l.*
				from " . $aData ['table'] . " t
				inner join locale_global l on (t.id=l.id_reference and l.table_name='" . $aData ['table'] . "'
					and l.locale='" . Language::$sLocale . "')
				where	1=1
					".$aData['where'].' '.$aData['locale_where']
			." ".$aData['order'];

			if ($bReturnSql) return $sQuery;

			if ($sAssocField != ''){
				$aLocalizedAll = Base::$db->getAssoc ( $sQuery );
			}else {
				$aLocalizedAll = Base::$db->getAll ( $sQuery );
			}
		}
		if ($bReturnSql) return $sQuery;
		return $aLocalizedAll;
	}
	//-----------------------------------------------------------------------------------------------
	function Array2Hash($data, $key_array = '')
	{
		if (is_array ( $data ))
		foreach ( $data as $value ) {
			$key = $value [$key_array];
			$out [$key] = $value;
		}
		return $out;
	}
	//-----------------------------------------------------------------------------------------------
	public static function GetCommentLink($sSection, $sId, $sLink,$bHideUnapproved=false)
	{
		require_once (SERVER_PATH . '/class/core/Comment.php');
		return Comment::GetCommentLink ( $sSection, $sId, $sLink ,$bHideUnapproved);
	}
	//-----------------------------------------------------------------------------------------------
	public function GetConstant($sKey,$sDefaultValue='') {
		return Base::GetConstant($sKey,$sDefaultValue);
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Get localised value for array
	 */
	public static function GetMessageArray($aArrayKey, $sPage = "")
	{
		if (! $aArrayKey)
		return;
		foreach ($aArrayKey as $k => $v) {
			$aArray[$k]=Language::GetMessage($v, $sPage);
		}
		return $aArray;
	}
	//-----------------------------------------------------------------------------------------------
	private function IncludeLocaleMap($sMap)
	{
		if (file_exists(SERVER_PATH.'/include/locale_map/'.$sMap.'.php')){
			require(SERVER_PATH.'/include/locale_map/'.$sMap.'.php');
		}
		else require(SERVER_PATH.'/class/core/locale_map/'.$sMap.'.php');

		$sVariableName=$sMap.'_map';
		return $$sVariableName;
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Add data to array for old parsing method and puts identifier to template for replacing
	 */
	public function AddOldParser($sObject,$iId)
	{
		if (!$iId) return '';
 		if (!Language::$aOldParser[$sObject]) Language::$aOldParser[$sObject]=array();

		if (!in_array($iId,Language::$aOldParser[$sObject])) Language::$aOldParser[$sObject][]=$iId;
		return 'old_parser_'.$sObject.'_'.$iId.'_';
	}
	//-----------------------------------------------------------------------------------------------
	public function ReplaceOldParser($sOutput)
	{
		if (Language::$aOldParser) {
			$aObject=array_keys(Language::$aOldParser);
			foreach ($aObject as $sValue) {
				$sOutput=Content::CallOldReplacer($sValue,$sOutput);
			}
		}
		return $sOutput;
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Get localised value for catalog
	 */
	public static function GetCatalogMessage($sKey, $sPage = "")
	{
		if (!$sKey) return;
		if (Base::$aRequest['locale']=='nl') {
			return $sKey;
		} else {
			if (!Base::$aRequest['locale']) $sLanguage="de";
			elseif (in_array(Base::$aRequest['locale'],array("en","ru","it","sp","fr"))) $sLanguage=Base::$aRequest['locale'];
			else return ;
		}

		$aContent=Db::GetRow("select name, name_".$sLanguage." as name_local from cat_unit
		where name='".mysql_real_escape_string($sKey)."'");

		if ($aContent['name_local']) return $aContent['name_local'];
		elseif ($aContent['name']) return $aContent['name'];
		else return $sKey;

	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Get translit text
	 */
	public function GetTranslit($sString, $sCharset='utf-8')
	{
		$aTranslit=array('�'=>'a', '�'=>'b',  '�'=>'v',  '�'=>'g', '�'=>'d', '�'=>'e', '�'=>'e',
		'�'=>'zh',  '�'=>'z',  '�'=>'i',  '�'=>'y',  '�'=>'k', '�'=>'l', '�'=>'m', '�'=>'n',
		'�'=>'o',  '�'=>'p',   '�'=>'r',  '�'=>'s',  '�'=>'t', '�'=>'u', '�'=>'f', '�'=>'kh',
		'�'=>'ts', '�'=>'ch',  '�'=>'sh', '�'=>'shch', '�'=>'', '�'=>'y', '�'=>'',
		'�'=>'eh', '�'=>'yu',  '�'=>'ya',
		'�'=>'A',  '�'=>'B',   '�'=>'V',  '�'=>'G',  '�'=>'D', '�'=>'E', '�'=>'E',
		'�'=>'Zh', '�'=>'Z',   '�'=>'I',  '�'=>'Y',  '�'=>'K', '�'=>'L', '�'=>'M', '�'=>'N',
		'�'=>'O',  '�'=>'P',   '�'=>'R',  '�'=>'S',  '�'=>'T', '�'=>'U', '�'=>'F', '�'=>'Kh',
		'�'=>'Ts', '�'=>'Ch',  '�'=>'Sh', '�'=>'Shch', '�'=>'Eh', '�'=>'Yu', '�'=>'Ya'
		);
		if ($sString)
		{
			$sString=iconv($sCharset, 'windows-1251', $sString);
			$aValue=str_split($sString);
			foreach ($aValue as $key=>$value)
			{
				if (array_key_exists($value, $aTranslit)) $sTranslit.=$aTranslit[$value];
				else{
					$sTranslit.=$value;
				}
			}
		}
		$sTranslit=iconv('windows-1251', $sCharset, $sTranslit);

		return $sTranslit;
	}
	//-----------------------------------------------------------------------------------------------
	public static function PrintPriceCalc($aPrice)
	{
		return Currency::PrintPriceCalc($aPrice);
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Set new value for html texts
	 */
	public static function SetText($sKey, $sContent = "")
	{
		if (!$sKey) return;
		
		$sContent = mysql_real_escape_string ( $sContent );
		$sKey = mysql_real_escape_string ( $sKey );
		Base::$db->Execute ( "update translate_text set content = '" . $sContent . "'
				where code = '" . $sKey . "'");
		
		Language::$aTranslateText [$sKey] ['content'] = $sContent;
		return $sContent;
	}
}

?>