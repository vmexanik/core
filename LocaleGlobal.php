<?
/**
 * @author Mikhail Strovoyt
 */

class ALocaleGlobal extends Admin {

	//-----------------------------------------------------------------------------------------------
	function __construct()
	{
		$this->sTableName = 'locale_global';
		$this->sAction = 'locale_global';
		$this->sWinHead = Language::getDMessage ( 'Locale Global' );
		$this->sPath = Language::getMessage('>>Content >' . $sWinHead );
		$this->sTableId = 'i';
		$this->sAddonPath='addon/';
		$this->Admin ();
	}
	//-----------------------------------------------------------------------------------------------
	public function GetLocaleMap($sTableName)
	{
		if (file_exists(SERVER_PATH.'/include/locale_map/'.$sTableName.'.php')){
			require(SERVER_PATH.'/include/locale_map/'.$sTableName.'.php');
		}
		else require(SERVER_PATH.'/class/core/locale_map/'.$sTableName.'.php');

		$sVariableName = $sTableName.'_map';
		$aMap = $$sVariableName;
		return $aMap;
	}
	//-----------------------------------------------------------------------------------------------
	public function Edit()
	{
		$sPath = 'Edit';
		//-------------
		$aMap = $this->getLocaleMap ( Base::$aRequest ['table_name'] );
		Base::$tpl->assign ( 'aMap', $aMap );
		//-------------
		$sText = '';
		foreach ( $aMap as $sKey => $sValue ) {
			if ($sValue == 'editor') {
				$sText .= "'data_$sKey',";
			}
		}
		$sText = substr ( $sText, 0, - 1 );
		Base::$tpl->assign ( 'sFCKArray', $sText );
		//-------------
		$sQuery = "select * from `locale_global`
		where `table_name`='" . Base::$aRequest ['table_name'] . "' AND
		`locale`='" . Base::$aRequest ['locale'] . "' AND
		`id_reference`='" . Base::$aRequest ['id'] . "'";

		$aLocaleRow = Base::$db->getRow ( $sQuery );
		$aLocaleRow['content']=stripslashes($aLocaleRow['content']);
		$aLocaleRow['description']=stripslashes($aLocaleRow['description']);
		$aLocaleRow['descr']=stripslashes($aLocaleRow['descr']);
		$aLocaleRow['name']=stripslashes($aLocaleRow['name']);
		$aLocaleRow['short_title']=stripslashes($aLocaleRow['short_title']);
		$aLocaleRow['bottom_text']=stripslashes($aLocaleRow['bottom_text']);
		$aLocaleRow['top_text']=stripslashes($aLocaleRow['top_text']);
		$aLocaleRow['page_keyword']=stripslashes($aLocaleRow['page_keyword']);
		$aRow = $aLocaleRow;

		if (! $aLocaleRow ['i']) {
			$sQuery = "select * from " . Base::$aRequest ['table_name'] . " where id='" . Base::$aRequest ['id'] . "'";
			$aRow = Base::$db->getRow ( $sQuery );
			$aFields = array ();
			foreach ( $aMap as $key => $value ) {
				$aFields [$key] = "'" . mysql_real_escape_string ( $aRow [$key] ) . "'";
			}
			$sFields = implode ( ',', array_keys ( $aFields ) );
			$sValues = implode ( ',', $aFields );

			$sQuery = "insert into locale_global (table_name,id_reference,locale, " . $sFields . " )
			values (
			'" . Base::$aRequest ['table_name'] . "',
			'" . Base::$aRequest ['id'] . "',
			'" . Base::$aRequest ['locale'] . "',
			" . $sValues . ")
			 ";
			Base::$db->Execute ( $sQuery );
			$aRow [$this->sTableId] = Base::$db->Insert_ID ();
		}
		// current locale use
		$sSubmit = stripslashes ( Base::$aRequest ['return'] );
		if (strpos($sSubmit,'locale='.Base::$aRequest ['locale'])===FALSE)
			$sSubmit = str_replace('locale=','locale='.Base::$aRequest ['locale'].'&',$sSubmit);
		
		Base::$tpl->assign ( 'sReturn',  $sSubmit);
		Base::$tpl->assign ( 'aData', $aRow );
		//-------------
		Base::$tpl->assign ( 'sTableName', Base::$aRequest ['table_name'] );
		//-------------
		$aLanguage = Base::$db->getRow ( "select * from language where code='" . Base::$aRequest ['locale'] . "'" );
		$sLanguageTitle = $aLanguage [name] . "<img src='$aLanguage[image]' width=32 hspace=2 border=0 align=absmiddle>";
		Base::$tpl->assign ( 'sLanguageTitle', $sLanguageTitle );
		//-------------
		$this->processTemplateForm ( $sPath );
		Base::$oResponse->addScript("CKEDITOR.replaceAll('ckeditor');");
		if($this->sScriptForAdd) Base::$oResponse->addScript($this->sScriptForAdd);
	}
	//-----------------------------------------------------------------------------------------------
	public function Apply() {
		$aMap = $this->getLocaleMap ( Base::$aRequest ['table_name'] );
		foreach ( $aMap as $sKey => $sValue ) {
			if ($sValue == 'editor') {
				$this->aFCKEditors [] = $sKey;
			}
		}
		if (Base::$aRequest['data']['use_code_html'] && Base::$aRequest['data']['i'] && Base::$aRequest['data']['content_html']) {
		    Db::Execute("update locale_global set name='".Base::$aRequest['data']['name']."', content='".mysql_real_escape_string(Base::$aRequest['data']['content_html'])."' where i='".Base::$aRequest['data']['i']."'");
		    $this->AdminRedirect ( Base::$aRequest['return'] );
		    return;
		}
		if(!get_magic_quotes_gpc()){
			Base::$aRequest['data_text']=stripslashes(Base::$aRequest['data_text']);
		}
		parent::Apply ();
	}
	//-----------------------------------------------------------------------------------------------
}

?>
