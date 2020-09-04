<?
/**
 * @author Mikhail Strovoyt
 */

class Admin extends Base
{
	public $sTableName;
	/** For tables like user_customer and user_provider bug fix	 */
	public $sAdditionalLink='';
	public $sTablePrefix;
	public $sTableId = 'id';
	public $aChildTable;
	public $sAction;
	public $sWinHead;
	public $sPath;
	public $sSubMenu;
	public $aCheckField;
	public $sBeforeAddMethod = '';
	public $aFCKEditors;
	//public $bIsAddon;
	public $sSqlPath = '';
	public $sScriptForAdd = '';
	public $bAlreadySetMessage = false;
	
	/** After increment num sql for after set when add new record */
	public $sNumSql = '';

	protected $aSearch;
	protected $sSearchSQL;

	public $sAddonPath = '';

	protected $aAdmin;

    static function hasAccessTo($sAction) {
        $privilegeId = Base::$db->GetOne("SELECT id FROM user_manager_privilege WHERE name = '".$sAction."'");

        if ($privilegeId) {
            $access = Base::$db->GetOne("SELECT u.* FROM user u LEFT JOIN user_manager_role umr ON u.id = umr.user_id LEFT JOIN user_manager_role_privilege umrp ON umrp.role_id = umr.role_id WHERE u.id = '" . $_SESSION['user']['id'] . "' AND umrp.privilege_id = '" . $privilegeId . "'");

            if (!$access) {
                die("Нет доступа");
            }
        }
    }

	//-----------------------------------------------------------------------------------------------
	public function Admin()
	{
		Base::$tpl->assign('sBaseAction', $this->sAction);
		Base::$tpl->assign('sTableName', $this->sTableName);
		Base::$tpl->assign('sAction', $this->sAction);
		Base::$tpl->assign_by_ref('oAdmin', $this);
		Base::$tpl->assign('aRequest',Base::$aRequest);
		$this->aAdmin = Base::$db->GetRow("select * from admin
		where login='".$_SESSION["mpanel_auth".Base::$aGeneralConf['ProjectName']]."'");
		Base::$tpl->assign('aAdmin',$this->aAdmin);

        $tableExist = Base::$db->getOne("SHOW TABLES LIKE 'user_manager_privilege'");
        if ($tableExist) {
            //self::hasAccessTo($this->sAction);
        }
	}
	//-----------------------------------------------------------------------------------------------
	//	public function Admin() {
	//		Base::$tpl->assign('sBaseAction',$this->sAction);
	//		Base::$tpl->assign ( 'oAdmin', $this );
	//	}
	//-----------------------------------------------------------------------------------------------
	public function AdminRedirect($sAction='', $aMessage=array())
	{
		if (Base::$aRequest ['return']) {
			$sQueryString = stripslashes ( Base::$aRequest ['return'] );
			parse_str ( $sQueryString, Base::$aRequest );
			Base::FixParseStrBug(Base::$aRequest);
			Base::EscapeAll( Base::$aRequest );
			Base::$sServerQueryString = $sQueryString;
			$action = Base::$aRequest ['action'];
		} else {
			Base::$aRequest ['action'] = $sAction;
			Base::$aRequest ['data'] = '';
			Base::$sServerQueryString = 'action=' . $sAction;
			$action = $sAction;
		}
		if ($aMessage != array())
			Base::$aRequest ['aMessage'] = $aMessage;
		
		include SERVER_PATH.'/class/core/mpanel/includer.php';
	}
	//-----------------------------------------------------------------------------------------------
	public function PreIndex()
	{
		Base::$oResponse->addAssign('path', 'innerHTML', $this->sWinHead );
		Base::$oResponse->addAssign('win_head', 'innerHTML', $this->sPath . $this->sWinHead );
		if (Base::$tpl->template_exists($this->sAddonPath.'mpanel/'.$this->sAction.'/sub_menu.tpl' )) {
			Base::$tpl->assign ( 'sReturn', Base::$sServerQueryString );
			Base::$oResponse->addAssign ( 'sub_menu', 'innerHTML',
			Base::$tpl->fetch($this->sAddonPath.'mpanel/'.$this->sAction.'/sub_menu.tpl'));
		} else Base::$oResponse->addAssign ( 'sub_menu', 'innerHTML', '' );
	}
	//-----------------------------------------------------------------------------------------------
	public function AfterIndex()
	{
		Base::$oResponse->addAssign ( 'win_text', 'innerHTML', Base::$sText );
		$this->Message ();
		$this->OrderChange();
		Base::$oResponse->addScript("if (document.getElementById('admin_itemslist_table')){
		oColorTable.StripeTables();
		oColorTable.HighlightRows();
		oColorTable.LockRow();
		oColorTable.LockRowUsingCheckbox();
		oColorTable.SetUpChekAll();}");
	}
	//-----------------------------------------------------------------------------------------------
	public function SetDefaultTable($oTable, $aWhereData = array())
	{
		if ($this->sSearchSQL) {
			$aWhereData ['where'] .= $this->sSearchSQL;
		}
		$oTable->bAjaxStepper = true;
		$oTable->bShowRowsPerPage=true;
		$oTable->bDefaultChecked = false;
		if(file_exists(SERVER_PATH."/template/mpanel/dtree_new.tpl")) {
		    $oTable->sStepperType ='bootstrap';
		    $oTable->sTemplateName = 'admin_new.tpl';
		    $oTable->sFilterTemplateName='admin_new.tpl';
		} else {
		    $oTable->sTemplateName = 'admin.tpl';
		    $oTable->sStepperType ='default';
		    $oTable->sFilterTemplateName='admin.tpl';
		}
		$oTable->bCheckVisible = true;
		$oTable->sQueryString = Base::$sServerQueryString;
		$oTable->sDataTemplate = $this->sAddonPath.'mpanel/'.$this->sAction.'/row_'.$this->sAction.'.tpl';
		$oTable->bFilterVisible = true;
		if ($oTable->sType == 'Sql') {
			if ($this->sSqlPath) $iWhereCount=$oTable->setSql($this->sSqlPath, $aWhereData);
			else $iWhereCount=$oTable->SetSql($this->ActionToClass($this->sTableName), $aWhereData );
		}
		if($this->sAction=="price" || $this->sAction=='price_install_side') {
			if($iWhereCount==0) {
				$oTable->sSql="select null from (select null as id, null as id_provider, null as price, null as id_price_group, null as code, null as part_rus, null as pref,
				    null as cat, null as post_date,null as term, null as number_min, null as stock) as price";
				$oTable->sTableMessage=Language::GetMessage("select filter for show table");
				$oTable->sTableMessageClass="warning_p alert alert-warning alert-dismissible";
			}
		}
		
		if($this->sAction=="price_names") {
		    if($iWhereCount==0) {
		        $oTable->sSql="select null from (select null as id, null as item_code, null as name) as price_names";
		        $oTable->sTableMessage=Language::GetMessage("select filter for show table");
		        $oTable->sTableMessageClass="warning_p alert alert-warning alert-dismissible";
		    }
		}
		
		if($this->sAction=="price_params") {
		    if($iWhereCount==0) {
		        $oTable->sSql="select null from (select null as id, null as item_code, null as name) as price_params";
		        $oTable->sTableMessage=Language::GetMessage("select filter for show table");
		        $oTable->sTableMessageClass="warning_p alert alert-warning alert-dismissible";
		    }
		}

		if($this->sAction=="sitemap_links") {
		    if($iWhereCount==0) {
		        $oTable->sSql="select null from (select null as id, null as url, null as visible) as sitemap_links";
		        $oTable->sTableMessage=Language::GetMessage("select filter for show table");
		        $oTable->sTableMessageClass="warning_p alert alert-warning alert-dismissible";
		    }
		}
		
		$aDisplay = Db::GetRow("select * from admin_option where
			id_admin='".$_SESSION['admin']['id']."' and module='display' and code='".$this->sTableName.$this->sAdditionalLink."'");
		if ($aDisplay) $sDiplayContent = $aDisplay ['content'];
		else $sDiplayContent = 10;
		$oTable->iRowPerPage = $sDiplayContent;
		$oTable->sNoItem='no items found mpanel';
		//$oTable->bLoadOrder=true;
		if (!Base::$aRequest['order']) {
			$sOrderFeild = Db::GetOne("select content from admin_option where
				id_admin='".$_SESSION['admin']['id']."' and module='order_field'
				and code='".$this->sTableName.$this->sAdditionalLink."'" );
			$sOrderWay = Db::GetOne("select content from admin_option where
				id_admin='".$_SESSION['admin']['id']."' and module='order_way'
				and code='".$this->sTableName.$this->sAdditionalLink."'" );
			if ($sOrderFeild) {
				$oTable->sDefaultOrder=$sOrder=" order by ".$sOrderFeild." ".$sOrderWay;
				Base::$aRequest ['order']=$sOrderFeild;
				Base::$aRequest ['way']=$sOrderWay;
			}
		}
		//Base::$tpl->assign('sDisplayContent',$sDiplayContent);
	}
	//-----------------------------------------------------------------------------------------------
	public function ProcessTemplateForm($sPath)
	{
		Base::$oResponse->addAssign ( 'sub_menu', 'innerHTML', '' );
		Base::$oResponse->addAssign ( 'win_head', 'innerHTML', Language::getDMessage ( $sPath ) );
		//if (!$this->bIsAddon) {
		Base::$sText .= Base::$tpl->fetch($this->sAddonPath.'mpanel/'.$this->sAction.'/form_add.tpl');
		//		} else {
		//			Base::$sText .= Base::$tpl->fetch ( 'addon/mpanel/' . $this->sAction . '/form_add.tpl' );
		//		}
		Base::$oResponse->addScript ( '__FCKeditorNS = null;' );
		Base::$oResponse->addScript ( 'FCKeditorAPI = null;' );
		Base::$oResponse->addAssign ( 'sub_menu', 'innerHTML', '' );
		Base::$oResponse->addAssign ( 'win_text', 'innerHTML', Base::$sText );
		$this->Message ();
	}
	//-----------------------------------------------------------------------------------------------
	public function Add($bEdit = false)
	{
		if ($this->sBeforeAddMethod) {
			$sMethod = $this->sBeforeAddMethod;
			if (method_exists($this,$sMethod))	$this->$sMethod();
		}
		if ($bEdit) {
			$sPath = 'Edit';
			if ($this->sSqlPath) {
				$aData = Db::GetRow(Base::GetSql($this->sSqlPath,array(
				$this->sTableId=>Base::$aRequest[$this->sTableId])));
			}
			else {
				$aData=Db::GetRow(Base::GetSql($this->ActionToClass($this->sTableName),array(
				$this->sTableId => Base::$aRequest [$this->sTableId])));
			}
		} else {
			$sPath = 'Add New';
			$aData ['visible'] = 1;
			if ($this->sNumSql) $aData ['num'] = Db::GetOne($this->sNumSql)+1;
		}
		$this->BeforeAddAssign($aData);
		Base::$tpl->assign ( 'sReturn', stripslashes ( Base::$aRequest ['return'] ) );
		Base::$tpl->assign ( 'aData', $aData );
		$this->ProcessTemplateForm($sPath);
		Base::$oResponse->addScript("CKEDITOR.replaceAll('ckeditor');");
		if($this->sScriptForAdd) Base::$oResponse->addScript($this->sScriptForAdd);
	}
	//-----------------------------------------------------------------------------------------------
	public function Edit()
	{
		$this->Add ( true );
	}
	//-----------------------------------------------------------------------------------------------
	public function Apply()
	{
		$this->ProcessFCKEditors ();
		$this->BeforeApply ();
		$this->ClearExcessSymbol();
		if (! $this->CheckField ()) {
			if (!$this->bAlreadySetMessage)
				$this->Message ( 'MT_ERROR', Language::getDMessage ( 'Please fill out all fields' ) );
			else 
				$this->bAlreadySetMessage = false;
			return;
		} else {
			if($this->aChildTable) {
				if (Base::$aRequest ['data'] [$this->sTableId]) {
					$sMode = 'UPDATE';
					//------------------------
					$sWhereMain = $this->sTableId . "='" . Base::$aRequest ['data'] [$this->sTableId] . "'";
					$aBeforeRowMain = Db::GetRow("select * from ".$this->sTableName."
						where ".$this->sTableId."='".Base::$aRequest ['data'] [$this->sTableId]."'");
					//------------------------
					foreach ($this->aChildTable as $aTable) {
						$aBeforeRows[$aTable['sTableName']]	= Db::GetRow("select * from ".$aTable['sTableName']."
						where ".$aTable['sTableId']."='".Base::$aRequest ['data'] [$this->sTableId]."'");
					}
				} else {
					$sMode = 'INSERT';
					//Unset(Base::$aRequest['data'][$this->sTableId]);
					$sWhereMain = false;
					//$aBeforeRowMain = false;
					foreach(Db::GetAll("desc ".$this->sTableName) as $aRow)
					$aBeforeRowMain[$aRow['Field']] = false;
					foreach ($this->aChildTable as $aTable) {
						foreach(Db::GetAll("desc ".$aTable['sTableName']) as $aRow)
						$aBeforeRows[$aTable['sTableName']][$aRow['Field']] = false;
					}
				};
				//------------------------
				Db::AutoExecute($this->sTableName, array_intersect_key(Base::$aRequest ['data'], $aBeforeRowMain)
				, $sMode, $sWhereMain, true, true );
				if ($sMode=='INSERT')
				Base::$aRequest ['data'] [$this->sTableId]=Db::InsertId();
				//------------------------
				foreach ($this->aChildTable as $aTable) {
					$sWhere = ($sMode=='INSERT') ? false : $aTable['sTableId']."='".Base::$aRequest ['data'] [$this->sTableId]."'";
					Base::$aRequest ['data'] [$aTable['sTableId']]=Base::$aRequest ['data'] [$this->sTableId];
					Db::AutoExecute($aTable['sTableName'], array_intersect_key(Base::$aRequest ['data']
					, $aBeforeRows[$aTable['sTableName']]), $sMode, $sWhere, true, true );
				}
				//------------------------
				$aAfterRowMain = Db::GetRow("select * from ".$this->sTableName."
						where ".$this->sTableId."='".Base::$aRequest ['data'] [$this->sTableId]."'");
				//------------------------
				foreach ($this->aChildTable as $aTable)
				$aAfterRows[$aTable['sTableName']]	= Db::GetRow("select * from ".$aTable['sTableName']."
					where ".$aTable['sTableId']."='".Base::$aRequest ['data'] [$this->sTableId]."'");
				//------------------------
				if($sMode!='INSERT') {
					$aBeforeRow=$aBeforeRowMain;
					foreach ($aBeforeRows as $aRow)
					$aBeforeRow=array_merge($aBeforeRow, $aRow);
				}
				else
				$aBeforeRow = false;
				//------------------------
				$aAfterRow=$aAfterRowMain;
				foreach ($aAfterRows as $aRow)
				$aAfterRow=array_merge($aAfterRow, $aRow);
			}
			else {
				if (Base::$aRequest ['data'] [$this->sTableId]) {
					$sMode = 'UPDATE';
					$sWhere = $this->sTableId . "='" . Base::$aRequest ['data'] [$this->sTableId] . "'";
					$aBeforeRow=Db::GetRow("select * from ".$this->sTableName."
					where ".$this->sTableId."='".Base::$aRequest ['data'] [$this->sTableId]."'");
				} else {
					$sMode = 'INSERT';
					$sWhere = false;
				}
				Db::AutoExecute($this->sTableName, Base::$aRequest ['data'], $sMode, $sWhere);
				if ($sMode=='INSERT') Base::$aRequest ['data'] [$this->sTableId]=Db::InsertId();
				$aAfterRow=Db::GetRow("select * from ".$this->sTableName."
						where ".$this->sTableId."='".Base::$aRequest ['data'] [$this->sTableId]."'");
			}
		}
		$this->AfterApply ($aBeforeRow,$aAfterRow);

		if (Base::$aGeneralConf['LogAdmin']) {
			require_once(SERVER_PATH.'/class/core/Log.php');
			Log::AdminAdd(Base::$aRequest['xajaxargs'][0],$this->sTableName);
		}


		$this->AdminRedirect ( $this->sAction );
	}

	//-----------------------------------------------------------------------------------------------
	public function Search()
	{
		$sSearch = '';
		if (is_array ( Base::$aRequest ['search'] )) {
			foreach ( Base::$aRequest ['search'] as $sKey => $sValue ) {
				if (is_array ($sValue)) {
					foreach ($sValue as $sSubValue) {
						$sSearch .= $sKey . "[]=" . urlencode ( $sSubValue ) . "&";
					}
				} else {
					$sSearch .= "$sKey=" . urlencode ( $sValue ) . "&";
				}
			}
		}
		$sSearch = substr ( $sSearch, 0, - 1 );
		Base::$aRequest ['return'] .= '&search=' . urlencode ( $sSearch );
		$this->AdminRedirect ( $this->sAction );
	}
	//-----------------------------------------------------------------------------------------------
	protected function SearchForm()
	{
		if (Base::$aRequest['search']) {
			parse_str(Base::$aRequest['search'], $aSearch);
			Base::FixParseStrBug($aSearch);
			if (get_magic_quotes_gpc()==1) {
				Base::UnescapeAll($aSearch);
			}
			$this->aSearch = $aSearch;
			foreach ( $aSearch as $sKey => $sValue ) {
				if (!is_array($aSearch[$sKey])) $aSearch[$sKey]=stripslashes ( $sValue );
			}
			Base::$tpl->assign_by_ref('aSearch',$aSearch);
		}
		$sSearchReturn = Base::$sServerQueryString;
		$sSearchReturn = preg_replace ( '/&' . $this->sPrefix . 'step=(\d+)/', '', $sSearchReturn );
		$sSearchReturn = preg_replace ( '/&' . $this->sPrefix . 'search=[^&]*/', '',
		$sSearchReturn );
		Base::$tpl->assign ( 'sSearchReturn', $sSearchReturn );
		return Base::$tpl->fetch ( 'mpanel/' . $this->sAction . '/search.tpl' );
	}
	//-----------------------------------------------------------------------------------------------
	public function BeforeApply()
	{
	}
	//-----------------------------------------------------------------------------------------------
	public function AfterApply($aBeforeRow,$aAfterRow)
	{
	}
	//-----------------------------------------------------------------------------------------------
	public function AfterDelete()
	{
	}
	//-----------------------------------------------------------------------------------------------
	public function BeforeAddAssign(&$aData)
	{
	    //if($aData['value'])
	    //    $aData['value'] = stripslashes($aData['value']);
	        
	    if($aData) foreach ($aData as $sKey=>$sValue)
	    {
		$aData[$sKey]=stripcslashes($sValue);
	    }
	}
	//-----------------------------------------------------------------------------------------------
	public function CheckField()
	{
		if ($this->aCheckField)
		foreach ( $this->aCheckField as $value ) {
			//if (! Base::$aRequest ['data'] [$value])
			if (strlen(Base::$aRequest ['data'] [$value]) == 0)
			return false;
		}
		return true;
	}
	//-----------------------------------------------------------------------------------------------
	public function Delete()
	{
		if (is_array ( Base::$aRequest ['row_check'] )) {
			Db::Execute (
			"delete from " . $this->sTableName . " where " . $this->sTableId . " in(" . implode (
			',', Base::$aRequest ['row_check'] ) . ")" );
			if($this->aChildTable)
			foreach ($this->aChildTable as $aTable)
			Db::Execute (
			"delete from `" . $aTable['sTableName'] . "` where " . $aTable['sTableId'] . " in(" . implode (
			',', Base::$aRequest ['row_check'] ) . ")" );

		} else
		Db::Execute (
		"delete from `" . $this->sTableName . "` where " . $this->sTableId . "='" . Base::$aRequest ['id'] .
		"'" );
		if($this->aChildTable)
		foreach ($this->aChildTable as $aTable)
		Db::Execute (
		"delete from `" . $aTable['sTableName'] . "` where " . $aTable['sTableId'] . "='" . Base::$aRequest ['id'] .
		"'" );
		
		$this->AfterDelete();

		//$this->Index();
		$this->AdminRedirect ( $this->sAction ); //not tested yet
	}
	//-----------------------------------------------------------------------------------------------
	public function Trash()
	{
		if (is_array ( Base::$aRequest ['row_check'] )) {
			$aId = Base::$aRequest ['row_check'];
		} else {
			$aId = array (Base::$aRequest ['id'] );
		}
		foreach ( $aId as $sValue ) {
			if (! $sDelQuery) {
				$sDelQuery = " `id`='$sValue'";
			} else {
				$sDelQuery .= " or `id`='$sValue'";
			}
			$sQuery = "select * from `$this->sTableName` where `id`='$sValue'";
			$aRow = Db::GetRow ( $sQuery );

			if ($aRow [name] == '') {
				$sName = $aRow [id];
			} else {
				$sName = $aRow [name];
			}
			$sData = String::Serialize($aRow);

			$sQuery = "insert into `trash` (
			`name`,`action`,`id_element`,`value`,`trashed_timestamp`,`size`
			) values (
			'$sName','$this->sTableName','$aRow[id]','$sData',
			UNIX_TIMESTAMP(),'" . strlen ( $sData ) . "'
			)";
			Db::Execute ( $sQuery );

		}
		$sQuery = "delete from `$this->sTableName` where " . $sDelQuery;
		Db::Execute ( $sQuery );

		$this->AdminRedirect ( $this->sAction );
	}
	//-----------------------------------------------------------------------------------------------
	public function Archive()
	{
		if (is_array ( Base::$aRequest ['row_check'] )) {
			$aId = Base::$aRequest ['row_check'];
		} else {
			$aId = array (Base::$aRequest ['id'] );
		}
		foreach ( $aId as $sValue ) {
			if (! $sDelQuery) {
				$sDelQuery = " `id`='$sValue'";
			} else {
				$sDelQuery .= " or `id`='$sValue'";
			}

		}
		$sQuery = "update `$this->sTableName` set visible=0 where " . $sDelQuery;
		Db::Execute ( $sQuery );

		$this->AdminRedirect ( $this->sAction );
	}
	//-----------------------------------------------------------------------------------------------
	public function UnArchive()
	{
		if (is_array ( Base::$aRequest ['row_check'] )) {
			$aId = Base::$aRequest ['row_check'];
		} else {
			$aId = array (Base::$aRequest ['id'] );
		}
		foreach ( $aId as $sValue ) {
			if (! $sDelQuery) {
				$sDelQuery = " `id`='$sValue'";
			} else {
				$sDelQuery .= " or `id`='$sValue'";
			}

		}
		$sQuery = "update `$this->sTableName` set visible=1 where " . $sDelQuery;
		Db::Execute ( $sQuery );

		$this->AdminRedirect ( $this->sAction );
	}
	//-----------------------------------------------------------------------------------------------
	public function ActionToClass($sBaseAction)
	{
		$aName = explode ( '_', $sBaseAction );
		if ($aName)
		foreach ( $aName as $value )
		$sClass .= ucfirst ( $value );
		return $sClass;
	}
	//-----------------------------------------------------------------------------------------------
	public function Message($sType = '', $sMessage = '',$sOldObject='')
	{
		$iIdName = "result_text";
		switch ($sType) {
			case "MT_NOTICE" :
				Base::$oResponse->addAssign($iIdName, "innerHTML", "<div class=notice_p>$sMessage</div>");
				break;
			case "MT_WARNING" :
				Base::$oResponse->addAssign($iIdName, "innerHTML", "<div class=warning_p>$sMessage</div>");
				break;
			case "MT_ERROR" :
				Base::$oResponse->addAssign($iIdName, "innerHTML", "<div class=error_p>$sMessage</div>");
				break;
			default :
				Base::$oResponse->addAssign($iIdName, "innerHTML", "<div class=empty_p>$sMessage&nbsp;</div>");
		}
		Base::$oResponse->addScript('fadeOpacity("'.$iIdName.'", "oShowResult");');
		if ($sMessage) {
			Base::$oResponse->addScript('window.location="#right_col"');
			Base::$oResponse->addScript('setTimeout(\'fadeOpacity("'.$iIdName.'", "oHideResult")\', 10000)');
		}
		return;
	}
	//-----------this is unused now i think, real filter method in Table class-----------------------
	public function GetFilter()
	{
		$aField = get_field_names ( $this->sTableName );
		//		if ($aField) foreach ($aField as $value)
		//		{
		//			$aOption[$value['name']]=$value['title'];
		//		}
		//
		//		Base::$tpl->assign('aOption',$aOption);
		Base::$tpl->assign ( 'aField',
		$aField );
		return Base::$tpl->fetch ( 'addon/mpanel/admin_filter.tpl' );
	}
	//-----------------------------------------------------------------------------------------------
	public function ProcessFCKEditors()
	{
		if (is_array ($this->aFCKEditors)) {
			foreach ($this->aFCKEditors as $sName ) {
				Base::$aRequest['data'][$sName]=Base::$aRequest['data_'.$sName];
			}
		}
	}
	//-----------------------------------------------------------------------------------------------
	function initLocaleGlobal()
	{
		$bHasLanguageAccessRules = Base::GetConstant("mpanel:admin_language_denied","0");
		if($bHasLanguageAccessRules){
			$aLocaleGlobal=Db::GetAll(Base::GetSql("CoreLanguage",array(
			'id_admin_denied'=>$this->aAdmin['id'],
			'visible'=>'1',
			'where'=>" and l.id>'1'",

			)));
		}else{
			$aLocaleGlobal=Db::GetAll(Base::GetSql("CoreLanguage",array(
			'visible'=>'1',
			'where'=>" and l.id>'1'",
			)));
		}
		Base::$tpl->assign('aLocaleGlobal', $aLocaleGlobal);
	}
	//-----------------------------------------------------------------------------------------------
	public function GetFCKEditor($sFieldName='description',$sFieldValue='',$iWidth=700,$iHeight=600,$sFCKEditorEnterMode='')
	{
		$sEditorDir = "FCKeditor/";
		include_once (SERVER_PATH . '/libp/' . $sEditorDir . 'fckeditor.php');
		$oFCKeditor = new FCKeditor ( $sFieldName );
		$oFCKeditor->BasePath = '/libp/'.$sEditorDir;
		$oFCKeditor->Height = $iHeight;
		$oFCKeditor->Width = $iWidth;
		$oFCKeditor->Value = $sFieldValue;

		/*if($sFCKEditorEnterMode=='<div>' || in_array($sFCKEditorEnterMode, array('p','br','div'))){
			$oFCKeditor->Config['EnterMode'] = $sFCKEditorEnterMode;
			$oFCKeditor->Config['ShiftEnterMode'] = $sFCKEditorEnterMode;
		}*/
		$oFCKeditor->Config['EnterMode'] = 'br';
		$oFCKeditor->Config['ShiftEnterMode'] = 'p';

		return $oFCKeditor->CreateHtml();
	}
	//-----------------------------------------------------------------------------------------------
	public function GetCKEditor($sFieldName='description',$sFieldValue='',$iWidth=700,$iHeight=600,$sFCKEditorEnterMode='')
	{
		return '<textarea name="'.$sFieldName.'" class="ckeditor">'.$sFieldValue.'</textarea>';
	}
	//-----------------------------------------------------------------------------------------------
	public function DisplayChange()
	{
		Db::Execute ("insert into admin_option (id_admin,module,code,content) values
			('".$_SESSION['admin']['id']."','display','".$this->sTableName
		.$this->sAdditionalLink."','".Base::$aRequest ['content']."')
			on duplicate key update content='".Base::$aRequest['content']."'");
		$this->AdminRedirect ( $this->sAction );
	}
	//-----------------------------------------------------------------------------------------------
	public function OrderChange()
	{
		if (Base::$aRequest['order']) {
			//$sOrder=" order by ".." ".Base::$aRequest['way'];
			Db::Execute ("insert into admin_option (id_admin,module,code,content) values
				('".$_SESSION['admin']['id']."','order_field','".$this->sTableName.$this->sAdditionalLink
			."','".Base::$aRequest['order']."')
				on duplicate key update content='".Base::$aRequest['order']."'" );
			Db::Execute ("insert into admin_option (id_admin,module,code,content) values
				('".$_SESSION['admin']['id']."','order_way','".$this->sTableName.$this->sAdditionalLink."'
					,'".Base::$aRequest['way']."')
				on duplicate key update content='".Base::$aRequest['way']."'" );
		}
	}
	//-----------------------------------------------------------------------------------------------
	function IsMpanelUser($sLogin,$sPassword)
	{
		$aAdmin=Db::GetRow(Base::GetSql('CoreAdmin',array('login'=>$sLogin)));

		if ('4.5.1'==Language::GetConstant('module_version:aadmin','4.5.0')) {
			$bAdminPasswordCheck=!strcmp(String::Md5Salt($sPassword,$aAdmin['salt']),$aAdmin['password']);
		}
		else {
			$bAdminPasswordCheck=!strcmp(md5($sPassword),trim($aAdmin['passwd']));
		}

		if ($aAdmin && $bAdminPasswordCheck)
		{
			$_SESSION['admin']=$aAdmin;
			Db::Execute("update admin set last_login=now_login,last_referer=now_referer  where id='".$aAdmin['id']."'");

			Db::Execute("update admin set
                                now_login='".date(Base::GetConstant('date_format:post_date_time','d.m.Y'))."',
                                now_referer='".Auth::GetIp()."'
                                where id='".$aAdmin['id']."'");
			return true;
		}
		return false;
	}
	//-----------------------------------------------------------------------------------------------
	public function SearchStrongChange()
	{
		if (Base::$aRequest['status'] == 'true')
			Language::UpdateConstant('mpanel_search_strong',1);
		else 
			Language::UpdateConstant('mpanel_search_strong',0);
	}
	//-----------------------------------------------------------------------------------------------
	public function ManagerPanelRedirect($sAction='', $aMessage=array())
	{
		if (Base::$aRequest ['return']) {
			$sQueryString = urldecode(stripslashes ( Base::$aRequest ['return'] ));
			parse_str ( $sQueryString, Base::$aRequest );
			Base::FixParseStrBug(Base::$aRequest);
			Base::EscapeAll( Base::$aRequest );
			Base::$sServerQueryString = $sQueryString;
			$action = Base::$aRequest ['action'];
		} else {
			Base::$aRequest ['action'] = $sAction;
			Base::$aRequest ['data'] = '';
			Base::$sServerQueryString = 'action=' . $sAction;
			$action = $sAction;
		}
		if ($aMessage != array())
			Base::$aRequest ['aMessage'] = $aMessage;
	
		include SERVER_PATH.'/include/manager_panel/manager_panel_includer.php';
	}
	//-----------------------------------------------------------------------------------------------
	public function ManagerPanelMessage($sType = '', $sMessage = '',$iIdName='',$isClosePopup=0)
	{
		if (!$iIdName)
			$iIdName = "result_text";
		switch ($sType) {
			case "MT_SUCCESS" :
				Base::$oResponse->addAssign($iIdName, "innerHTML", "<div class='alert alert-success' role='alert'><span class='glyphicon glyphicon-ok' aria-hidden='true'></span> ".$sMessage."</div>");
				break;
			case "MT_NOTICE" :
				Base::$oResponse->addAssign($iIdName, "innerHTML", "<div class='alert alert-info' role='alert'><span class='glyphicon glyphicon-info-sign' aria-hidden='true'></span> ".$sMessage."</div>");
				break;
			case "MT_WARNING" :
				Base::$oResponse->addAssign($iIdName, "innerHTML", "<div class='alert alert-warning' role='alert'><span class='glyphicon glyphicon-warning-sign' aria-hidden='true'></span> ".$sMessage."</div>");
				break;
			case "MT_ERROR" :
				Base::$oResponse->addAssign($iIdName, "innerHTML", "<div class='alert alert-danger' role='alert'><span class='glyphicon glyphicon-exclamation-sign' aria-hidden='true'></span> ".$sMessage."</div>");
				break;
			default :
				Base::$oResponse->addAssign($iIdName, "innerHTML", "<div class='alert alert-info' role='alert'>$sMessage&nbsp;</div>");
		}

		if ($isClosePopup)
			Base::$oResponse->addScript('$("#'.$iIdName.'").fadeOut(3000,function(){$("#'.$iIdName.'").html("");$("#'.$iIdName.'").show();$(".js_manager_panel_popup").hide();});');
		else
			Base::$oResponse->addScript('$("#'.$iIdName.'").fadeOut(3000,function(){$("#'.$iIdName.'").html("");$("#'.$iIdName.'").show();});');
		
		return;
	}
	//-----------------------------------------------------------------------------------------------
	private function ClearExcessSymbol()
	{
	    $aExcessSymbols = array(0=>'\\');
	    if (Base::$aRequest['data']) foreach (Base::$aRequest['data'] as $sKey=>$aValue) {
	        foreach ($aExcessSymbols as $aValueSymbols) {
	            Base::$aRequest['data'][$sKey] = str_replace($aValueSymbols, '', $aValue);
	        }
	    }
	}
	//-----------------------------------------------------------------------------------------------
}
?>