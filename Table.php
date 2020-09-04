<?
/**
 * @author Mikhail Strovoyt
 */

class Table extends Base
{
	public $sType = 'Sql';
	public $aDataFoTable = array ();

	public $sSql = '';
	public $sTableSql = '';
	public $aItem = array ();
	public $aColumn = array ();
	public $iRowPerPage = 10;
	public $iRowPerFirstPage = 10; // $iRowPerFirstPage == $iRowPerPage
	public $iRowPerPageGeneral = 10;

	public $iPage = 0;
	/** parametres for select row per page */
	public $bShowRowsPerPage = false;
	public $sActionRowPerPage='';
	public $bShowPerPageAll = false;

	public $iStepNumber = 10;
	public $aOrderedColumn = array ();
	public $aOrdered = array ();
	public $sDefaultOrder = '';

	/** Width for table tag  width="$sWidth"  */
	public $sWidth = '99%';
	/** Class for table tag  class="$sClass"  */
	public $sClass = 'datatable';
	public $sStepperClass = 'stepper';
	public $sStepperClassTd = '';
	public $sStepperActiveItemClass = 'active';
	public $sStepperInfoClass = 'stepper_info';
	public $bStepperInfo = false;
	public $bStepperOutTable = false;
	public $bStepperHideNoPages = false;
	public $sCellSpacing = '0';
	public $sDataTemplate;
	public $sButtonTemplate;
	public $sButtonBeforeTemplate;
	public $sSubtotalTemplate;
	public $sSubtotalTemplateTop;

	public $sAddButton = '';
	public $sAddAction = '';

	public $aCallback = array ();
	// run after cut all result 
	public $aCallbackAfter = array ();
	
	public $bSetDefault = true;
	public $bPearStepper = false;
	public $bStepperVisible = true;
	public $bAjaxStepper = false;
	public $bHeaderVisible = true;
	public $bHeaderVisibleGroup = false;
	public $bHeaderNobr = true;
	/** Check boxes visible at the start of tr */
	public $bCheckVisible = false;
	public $bCheckRightVisible = false;
	public $bCheckOnClick = false;
	//onclick for each row (js) in index.tpl
	public $sCheckAction = '';
	/** Check box for checkall visible at the start of th */
	public $bCheckAllVisible = true;
	//onclick for checkall (js) in index.tpl
	public $sCheckAllAction = '';
	public $bDefaultChecked = true;
	public $sCheckAllClass = '';
	
	public $bFormAvailable = true;
	public $sFormAction = 'empty';
	public $sCheckField = 'id';
	public $iAllRow = 0;
	public $bHideTr = false;
	public $sIdiTr = "tr";
	public $sIdForm = "table_form";
	public $sHeaderRight = '';

	public $sTemplateName = 'index.tpl';
	public $sFilterTemplateName = 'admin.tpl';

	public $sPrefix = '';
	public $sQueryString = '';
	public $sOrderAscImage = '/libp/mpanel/images/small/down.png';
	public $sOrderDescImage = '/libp/mpanel/images/small/up.png';
	public $sHeaderClassSelect = '';

	public $bFilterVisible = false;
	public $aFilter = array ('oObject' => NULL, 'sMethod' => '' );
	public $sFormHeader = '';
	public static  $sStepperAlign = 'right';

	/** Columns number for gallery table */
	public $iGallery = 5;
	/** Flag tell if we drow table for cliend side*/
	public $bIsGallery = false;

	public $sStepperType = 'standard';
	public $bStepperOnePageShow = true;

	/** Caching sql_found_rows value in table cache_stepper */
	public $bCacheStepper = false;
	/** Duplicates the stepper on the top of table */
	public $bTopStepper = false;

	/** Code for custom no item template*/
	public $sNoItem = '';

	/** Count(*) stepper type for unsupported by SQL_FOUND_ROWS command */
	public $bCountStepper = false;

	/** If set - this parameter will limit steps count for each table called and shown */
	public $iStepLimit = 0;
	/* need for styling tr of stepper */
	public $bStepperStyling = true;
	public $bTableWithoytStyle = false;

	/** For steppers like 1,2,3... value of this parametere need to be 1*/
	public $iStartStep = 0;

	/** Different designed table headers */
	public static $bHeaderType = 'table';

	/** 	text for check all checkbox at top of table	**/
	public $sMarkAllText='';

	/** If we need manual set of limit section */
	public $sManualLimit = '';

	public static $sButtonSpanClass="";

	/* to add panel to table */
	public $sPanelTemplateTop;

	/* Abcolute url making for project table and url rewriting.
	for old style stepper links need to have this parameter set to "." */
	public static $sLinkPrefix=".";

	// for rewrite url
	public $sLinkRewrite="";
	
	public $sIdTable="";
	
	public $iColspanFilter = 3;
	public $iColspanSearchStrong = 3;
	//-----------------------------------------------------------------------------------------------
	public function __construct()
	{
		$this->Customize ();
		$this->aFilter = array ('oObject' => $this, 'sMethod' => 'getFilter' );
		Base::$tpl->assign_by_ref ( 'oTable', $this );
		$this->iStartStep=Base::GetConstant('table:start_step',0);
		$this->iStepNumber=Base::GetConstant('table:count_step_number',10);
	}
	//-----------------------------------------------------------------------------------------------
	public function GetTable($sHeader = '', $sHint = '',$sStaticHeader='',$sHeaderNT = '')
	{
		if ($this->bSetDefault && method_exists(Base::$oContent,'setTableDefault')) Base::$oContent->setTableDefault($this);

		if (strpos($_SERVER['REQUEST_URI'],'mpanel') !== false) {
			// not for mpanel
		}
		else {
			if (Base::GetConstant('table:s_stepper_type_use_own',0) == 1)
				$this->sStepperType 	= Base::GetConstant('table:s_stepper_type','standard');
			
			if (Base::GetConstant('table:b_stepper_out_table_use_own',0) == 1)
				$this->bStepperOutTable = Base::GetConstant('table:b_stepper_out_table',0);
			
			if (Base::GetConstant('table:b_stepper_one_page_show_use_own',0) == 1)
				$this->bStepperOnePageShow = Base::GetConstant('table:b_stepper_one_page_show',1);
		}
		if ($this->sDataTemplate != '') 
		{
			$this->sClass .= ' '.basename($this->sDataTemplate, '.tpl').'_table';
		}
		
		
		Base::$tpl->assign('sIdTable', $this->sIdTable );
		Base::$tpl->assign('sWidth', $this->sWidth );
		Base::$tpl->assign('sCellSpacing', $this->sCellSpacing );
		Base::$tpl->assign('bTableWithoytStyle', $this->bTableWithoytStyle );
		Base::$tpl->assign('sClass', $this->sClass );
		Base::$tpl->assign('sStepperClass', $this->sStepperClass );
		Base::$tpl->assign('sStepperClassTd', $this->sStepperClassTd );
		Base::$tpl->assign('bStepperInfo', $this->bStepperInfo);
		Base::$tpl->assign('sStepperInfoClass', $this->sStepperInfoClass );
		Base::$tpl->assign('bStepperOutTable', $this->bStepperOutTable);
		Base::$tpl->assign('sStepperType', $this->sStepperType);
		Base::$tpl->assign('sDataTemplate', $this->sDataTemplate );
		Base::$tpl->assign('sButtonBeforeTemplate', $this->sButtonBeforeTemplate );
		Base::$tpl->assign('sButtonTemplate', $this->sButtonTemplate );
		Base::$tpl->assign('sSubtotalTemplate', $this->sSubtotalTemplate );
		Base::$tpl->assign('sSubtotalTemplateTop', $this->sSubtotalTemplateTop );
		Base::$tpl->assign('sAddButton', Language::GetMessage ( $this->sAddButton ) );
		Base::$tpl->assign('sAddAction', $this->sAddAction );
		if($this->bHeaderGroupVisible) $this->bHeaderVisible=false;
		Base::$tpl->assign('bHeaderGroupVisible', $this->bHeaderGroupVisible );
		Base::$tpl->assign('bHeaderVisible', $this->bHeaderVisible );
		Base::$tpl->assign('bHeaderNobr', $this->bHeaderNobr );
		Base::$tpl->assign('bCheckVisible', $this->bCheckVisible );
		Base::$tpl->assign('bCheckRightVisible', $this->bCheckRightVisible );
		Base::$tpl->assign('bCheckOnClick', $this->bCheckOnClick );
		Base::$tpl->assign('sCheckAction', $this->sCheckAction );
		Base::$tpl->assign('bCheckAllVisible', $this->bCheckAllVisible );
		Base::$tpl->assign('sCheckAllAction', $this->sCheckAllAction );
		Base::$tpl->assign('sCheckAllClass', $this->sCheckAllClass );
		Base::$tpl->assign('bDefaultChecked', $this->bDefaultChecked );
		Base::$tpl->assign('bFormAvailable', $this->bFormAvailable );
		Base::$tpl->assign('sFormAction', $this->sFormAction );
		Base::$tpl->assign('sCheckField', $this->sCheckField );
		Base::$tpl->assign('sNoItem', $this->sNoItem );
		Base::$tpl->assign('bAjaxStepper', $this->bAjaxStepper );
		Base::$tpl->assign('bHideTr', $this->bHideTr );
		Base::$tpl->assign('sHeaderRight', $this->sHeaderRight );
		Base::$tpl->assign('sPrefix', $this->sPrefix );
		Base::$tpl->assign('sOrderAscImage', $this->sOrderAscImage );
		Base::$tpl->assign('sOrderDescImage', $this->sOrderDescImage );
		Base::$tpl->assign('sHeaderClassSelect', $this->sHeaderClassSelect );
		Base::$tpl->assign('sFormHeader', $this->sFormHeader );
		Base::$tpl->assign('sStepperAlign', Table::$sStepperAlign );
		Base::$tpl->assign('sActionRowPerPage', $this->sActionRowPerPage);
		Base::$tpl->assign('bShowRowPerPage', $this->bShowRowsPerPage);
		Base::$tpl->assign('iRowPerPage', $this->iRowPerPage );
		Base::$tpl->assign('bShowPerPageAll', $this->bShowPerPageAll);
		Base::$tpl->assign('iGallery', $this->iGallery );
		Base::$tpl->assign('bIsGallery', $this->bIsGallery );
		Base::$tpl->assign('bTopStepper', $this->bTopStepper );
		Base::$tpl->assign('bHeaderType', Table::$bHeaderType );
		Base::$tpl->assign('sMarkAllText',$this->sMarkAllText );
		Base::$tpl->assign('sButtonSpanClass', Table::$sButtonSpanClass);
		Base::$tpl->assign('sPanelTemplateTop', $this->sPanelTemplateTop );
		Base::$tpl->assign('sIdiTr',$this->sIdiTr);
		Base::$tpl->assign('sIdForm',$this->sIdForm);
		Base::$tpl->assign('iColspanFilter', $this->iColspanFilter);
		Base::$tpl->assign('iColspanSearchStrong', $this->iColspanSearchStrong);
		if ($this->sTableMessage) Base::$tpl->assign('sTableMessage', $this->sTableMessage);
		if ($this->sTableMessageClass) Base::$tpl->assign('sTableMessageClass', $this->sTableMessageClass);

		if ($this->bSetDefault && method_exists(Base::$oContent,'setTableDefaultPostAssign')) Base::$oContent->setTableDefaultPostAssign($this);

		if (! $this->sQueryString)
		$this->sQueryString = Base::RemoveMessageFromUrl($_SERVER ['QUERY_STRING']);

		if ($this->aOrdered) $this->sDefaultOrder = $this->aOrdered; //for backward compatibikity
		if ($this->sDefaultOrder) $sOrder = $this->sDefaultOrder;
		//$sDefaultWay=' asc';


		if ($sHint)	Base::$tpl->assign('sHint', Base::$language->getContextHint($sHint));
		if ($sHeaderNT != '')
			Base::$tpl->assign('sHeader', $sHeaderNT.$sStaticHeader.$sHintHtml);
		else
			Base::$tpl->assign('sHeader', Language::GetMessage($sHeader).$sStaticHeader.$sHintHtml);

		$sOrderQueryString = preg_replace ( '/&' . $this->sPrefix . 'order=([^&]*)&'.$this->sPrefix.'way=([^&]*)/', '', $this->sQueryString );
		foreach ( $this->aColumn as $sKey => $aValue ) {
			$sHeader = $aValue ['sTitle'];
			//if ($this->sTemplateName!='index.tpl' && $sHeader) $sHeader=$sHeader;
			if ($sHeader) {
				if ($this->sTemplateName == 'admin.tpl')
				$this->aColumn [$sKey] ['sTitle'] = Base::$language->getDMessage ( $sHeader );
				else
				$this->aColumn [$sKey] ['sTitle'] = Language::GetMessage ( $sHeader );
			}
			if($aValue ['sTitleNT']) $this->aColumn [$sKey] ['sTitle'] = $aValue ['sTitleNT'];
			if($aValue ['sHint']) $this->aColumn [$sKey] ['sHint'] = $aValue ['sHint'];

			if (! $sOrder && $this->aColumn [$sKey] ['sOrder'])
			$sOrder = " order by " . $this->aColumn [$sKey] ['sOrder'];
			if (! $sFirstOrderColumn && $this->aColumn [$sKey] ['sOrder'])
			$sFirstOrderColumn = $sKey;

			if ($this->aColumn [$sKey] ['sOrder'] && Base::$aRequest [$this->sPrefix . 'order'] == $sKey) {
				if (Base::$aRequest [$this->sPrefix . 'way'] == 'asc' || ! Base::$aRequest [$this->sPrefix . 'way'] == 'asc') {
					$sOtherWay = 'desc';
					$this->aColumn [$sKey] ['sOrderImage'] = $this->sOrderAscImage;
					$this->aColumn [$sKey] ['sHeaderClassSelect'] = $this->sHeaderClassSelect;
				} else {
					$sOtherWay = 'asc';
					$this->aColumn [$sKey] ['sOrderImage'] = $this->sOrderDescImage;
					$this->aColumn [$sKey] ['sHeaderClassSelect'] = $this->sHeaderClassSelect;
				}
				$this->aColumn [$sKey] ['sOrderLink'] = $sOrderQueryString . '&' . $this->sPrefix . 'order=' . $sKey . '&'
				. $this->sPrefix . 'way=' . $sOtherWay;

				$sOrder = " order by " . $this->aColumn [$sKey] ['sOrder'];
				if (Base::$aRequest [$this->sPrefix . 'way'])
				$sOrder .= " " . Base::$aRequest [$this->sPrefix . 'way'];
			} else {
				if ($this->aColumn [$sKey] ['sOrder'] && $this->aColumn [$sKey] ['sDefaultOrderWay'] && !Base::$aRequest ['order']) {
					if ($this->aColumn [$sKey] ['sDefaultOrderWay'] == 'desc') {
						$this->aColumn [$sKey] ['sOrderLink'] = $sOrderQueryString . '&' . $this->sPrefix . 'order=' . $sKey . '&'
								. $this->sPrefix . 'way=asc';
						$this->aColumn [$sKey] ['sOrderImage'] = $this->sOrderDescImage;
					}
					else { 
						$this->aColumn [$sKey] ['sOrderLink'] = $sOrderQueryString . '&' . $this->sPrefix . 'order=' . $sKey . '&'
						. $this->sPrefix . 'way=desc';
						$this->aColumn [$sKey] ['sOrderImage'] = $this->sOrderAscImage;
					}
				}
				else {
					if ($this->aColumn [$sKey] ['sOrder'])
						$this->aColumn [$sKey] ['sOrderLink'] = $sOrderQueryString . '&' . $this->sPrefix . 'order=' . $sKey . '&'
							. $this->sPrefix . 'way=asc';
				}
			}
		}
		//first order column for default order
		if (! Base::$aRequest [$this->sPrefix . 'order'] && $sFirstOrderColumn && !$this->sDefaultOrder) {
			$this->aColumn [$sFirstOrderColumn] ['sOrderLink'] = $sOrderQueryString . '&' . $this->sPrefix . 'order='
			. $sFirstOrderColumn . '&' . $this->sPrefix . 'way=desc';
			$this->aColumn [$sFirstOrderColumn] ['sOrderImage'] = $this->sOrderAscImage;
		}

		Base::$tpl->assign ( 'aColumn', $this->aColumn );

		if ($this->sType == 'Sql') {
			$iStep = intval(Base::$aRequest[$this->sPrefix.'step']);
			if ($this->iStepLimit && $iStep>$this->iStepLimit) $iStep=$this->iStepLimit;


			// if we need display in the first page the another count of row
			if ($this->bPearStepper) $iStep --;
			if($this->iRowPerFirstPage != $this->iRowPerPage && $this->iRowPerPageGeneral != $this->iRowPerFirstPage){
				$iPage = $iStep  == 1 ? $this->iRowPerFirstPage : $this->iRowPerPage;
				$iAdding =  $iStep  == 1 ? 0 : $this->iRowPerFirstPage;
				$iSecondAdding = $iStep >= 2 ? 3 : 0;
				$sLimit = ' limit '.(($iStep *$iPage) - $iAdding - $iSecondAdding).',' . $this->iRowPerPage;
			}else{
				if (!Base::$aRequest [$this->sPrefix . 'step']) $sLimit = ' limit 0,' . $this->iRowPerPage;
				else $sLimit = ' limit '.($iStep * $this->iRowPerPage).',' . $this->iRowPerPage;
			}

			if ($this->sManualLimit) $sLimit=$this->sManualLimit;

			if ($this->bCacheStepper) {
				$sCacheSql=$this->sSql;
				$iPageNumber=Cache::GetValue('stepper',md5($sCacheSql));

				//if (!$iPageNumber) $this->sSql = str_replace ( 'select', 'select SQL_CALC_FOUND_ROWS', $this->sSql );
				if (!$iPageNumber) $this->sSql = preg_replace('/select/', 'select SQL_CALC_FOUND_ROWS', $this->sSql, 1);

				$this->sTableSql = $this->sSql . ' ' . $sOrder . ' ' . $sLimit;
				$aItem = Base::$db->getAll ( $this->sTableSql );
				if (!$iPageNumber) {
					$iPageNumber = Base::$db->getOne ( 'SELECT FOUND_ROWS()' );
					Cache::SetValue('stepper',md5($sCacheSql),$iPageNumber);
				}
			}
			else {
				//$this->sSql = str_replace('select', 'select SQL_CALC_FOUND_ROWS', $this->sSql);
				$this->sSql = preg_replace('/select/', 'select SQL_CALC_FOUND_ROWS', $this->sSql, 1);
				
				$this->sTableSql = $this->sSql.' '.$sOrder.' '.$sLimit;
				$aItem = DB::getAll($this->sTableSql);
				if ($this->bCountStepper) {
					$iPageNumber = Base::$db->getOne("select count(*) from (".$this->sSql.") count_table ");
				}
				else $iPageNumber = DB::getOne("SELECT FOUND_ROWS()");
			}
			$this->iAllRow = $iPageNumber;
		} elseif ($this->sType == 'array') {
			$aItem = $this->aDataFoTable;
			$this->iAllRow = count($aItem);
		}

		Base::$tpl->assign('iAllRow', $this->iAllRow);
		Base::$tpl->assign('FilteredProductEnding',$this->GetFilteredProductEnding($this->iAllRow));
		if ($this->aCallback) {
			$sMethod = $this->aCallback [1];
			$aResult = $this->aCallback [0]->$sMethod ( $aItem );
			if ($this->sSubtotalTemplate)
			Base::$tpl->assign('dSubtotal',$aResult['dSubtotal']);
			Base::$tpl->assign('aSubtotalResult', $aResult);
			if($this->sType == 'array') $this->iAllRow = count($aItem);
		}
		//if ($this->bStepperVisible && $this->sType == 'array') {
		if ($this->sType == 'array' && $this->iRowPerPage > 0) {
			$iPageNumber=$this->iAllRow;
			if($aItem)
			$aItem = array_slice ($aItem,intval( Base::$aRequest [$this->sPrefix . 'step'] )*$this->iRowPerPage,$this->iRowPerPage);
		}
		
		if ($this->aCallbackAfter) {
			$sMethod = $this->aCallbackAfter [1];
			$aResult = $this->aCallbackAfter [0]->$sMethod ( $aItem );
		}
		
		// generate empty item if need
		if ($this->bIsGallery){
			$iItemLen = count($aItem);
			if ($iItemLen%$this->iGallery != 0){
				if($iItemLen < $this->iGallery){
					$mod = $this->iGallery-$iItemLen;
				}else{
					$mod = $this->iGallery - ($iItemLen - ($this->iGallery * floor($iItemLen/$this->iGallery)));
				}
			}
			for ($i = $iItemLen; $i<$iItemLen+$mod; $i++){
				$aItem[$i] = array();
			}
		}

		Base::$tpl->assign('aItem',$aItem);
		$this->aItem=$aItem;

		if ($this->bStepperVisible) {
			if ($this->bPearStepper) $sStepper = $this->getStepperPear ( $iPageNumber );
			else $sStepper = $this->getStepper ( $iPageNumber );
		}
		Base::$tpl->assign ( 'sStepper', $sStepper );

		if ($this->bFilterVisible) {
			$oObject = $this->aFilter ['oObject'];
			$sMethod = $this->aFilter ['sMethod'];
			if (method_exists ( $oObject, $sMethod )) {
				Base::$tpl->assign ( 'sLeftFilter', $oObject->$sMethod () );
			}
		}

		Base::$tpl->assign('iStartRow', $this->iPage*$this->iRowPerPage);
		Base::$tpl->assign('iEndRow', ($this->iPage+1)*$this->iRowPerPage);
		
		//Base::$tpl->assign('iPageNumber', $this->iPage);
		if($this->iRowPerPage) Base::$tpl->assign('iPageCount', floor($this->iAllRow/$this->iRowPerPage)+1);
		Base::$tpl->assign('sCurrentPage', "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']);
		preg_match('/(http[^<]*)\'><предыдущий/U', str_replace('&nbsp;', '', $sStepper), $matches);
		Base::$tpl->assign('sPrevPage', $matches[1]);
		preg_match('/(http[^<]*)\'>следующий/U', str_replace('&nbsp;', '', $sStepper), $matches);
		Base::$tpl->assign('sNextPage', $matches[1]);


		Base::$tpl->assign ( 'sReturn', $this->sQueryString );

		if (strpos($this->sTemplateName,'/')===false) $sTemplateName='addon/table/'.$this->sTemplateName;
		else $sTemplateName=$this->sTemplateName;

		return Base::$tpl->fetch($sTemplateName);
	}
	//-----------------------------------------------------------------------------------------------
	public function getStepper($iRowNumber)
	{
		$iPage = intval ( Base::$aRequest [$this->sPrefix . 'step'] );
		$this->iPage=$iPage;
		$iRowPerPage = $this->iRowPerPage;

		if($iRowPerPage) {
    		if (($iRowNumber % $iRowPerPage) > 0) $adding = 0;
    		else $adding = - 1;
    		$iPageNumber = intval ( $iRowNumber / $iRowPerPage ) + $adding;
		}

		if ($this->iStepLimit && $iPageNumber>$this->iStepLimit) $iPageNumber=$this->iStepLimit;
		if ($this->iStepLimit && $iPage>$this->iStepLimit) return Language::GetMessage("Step limit exceeded");
		if (!$this->bStepperOnePageShow && $iPageNumber<1) return false;

		$iAllPageNumber = $iPageNumber;
		Base::$tpl->assign('iAllPageNumber', $iAllPageNumber+1);

		if ($iPageNumber > $this->iStepNumber)
		$iPageNumber = $this->iStepNumber;

		$next = $iPage + 1;
		$previous = $iPage - 1;

		$sQueryString = preg_replace ( '/&' . $this->sPrefix . 'step=(\d+)/', '', $this->sQueryString );
		$bNoneDotUrl = Base::$tpl->get_template_vars('bNoneDotUrl');
		if($bNoneDotUrl) $sPrefUrl=''; else $sPrefUrl=Table::$sLinkPrefix;

		if ($this->bAjaxStepper)
		$sAjaxScript = " onclick=\" xajax_process_browse_url(this.href); return false;\" ";
		
		$iPageCount = ($this->iAllRow && $this->iRowPerPage ? ceil($this->iAllRow/$this->iRowPerPage) : 1);
		$sCanonicalPage = 'http://'.$_SERVER['SERVER_NAME'].'/?'.$sQueryString;
		Base::$tpl->assign('aStepperData', array(
			'iPageNumber' => $this->iPage,
			'iPageCount' => $iPageCount,
			'sCurrentPage' => ($this->iPage>0 ? 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'] : $sCanonicalPage),
			'sPrevPage' => ($previous>=0 ? ($previous>0 ? $sCanonicalPage.'&step='.$previous : $sCanonicalPage) : ''),
			'sNextPage' => ($next<$iPageCount ? $sCanonicalPage.'&step='.$next : ''),
			'sCanonicalPage' => $sCanonicalPage,
			'sPrevious' => ($previous>=0 ? $previous : ''),
			'sNext' => ($next<$iPageCount ? $next : '')
		));
		
		if ($_SERVER['SCRIPT_NAME']!='/mpanel/login.php' && method_exists(Base::$oContent,'getStepper')){
			$aData=array(
			'iPage'=>$iPage,
			'iPageNumber'=>$iPageNumber,
			'iAllPageNumber'=>$iAllPageNumber,
			'next'=>$next,
			'previous'=>$previous,
			'sQueryString'=>$sQueryString,
			'sAjaxScript'=>$sAjaxScript,
			'sPrefUrl'=>$sPrefUrl,
			'iStartStep'=>$this->iStartStep,
			'sLinkRewrite'=>$this->sLinkRewrite
			);
			Base::$oContent->sPrefix=$this->sPrefix;
			return Base::$oContent->getStepper($this,$aData);
		}

		switch ($this->sStepperType) {
			//------------------ Announcement Stepper -----------------
			case 'announcement':
				if ($iPage > 0) {
					$start_text = "<a href='".$sPrefUrl."/?" . $sQueryString . "&" . $this->sPrefix . "step=0' $sAjaxScript>&larr;"
					. Language::GetMessage ( 'Start' ) . "</a>";
					$previous_text = "<a  href='".$sPrefUrl."/?" . $sQueryString . "&" . $this->sPrefix . "step=$previous' $sAjaxScript>&larr;"
					. Language::GetMessage ( 'Prev' ) . "</a>";
				} else {
					$start_text = "<span>&larr;" . Language::GetMessage ( 'Start' ) . "</span>";
					$previous_text = "<span>&larr;" . Language::GetMessage ( 'Prev' ) . "</span>";
				}

				if ($iPage > $iPageNumber) $start = $iPage - $this->iStepNumber;
				else $start = 0;

				for($i = $start; $i <= $iPageNumber + $start; $i ++) {
					if ($bDelimiter) $sDelimiter=' | ';
					if ($iPage == $i) $sPageText .= $sDelimiter."<label>$i</label> ";
					else $sPageText .= $sDelimiter."<a href='".$sPrefUrl."/?" . $sQueryString . "&"
					. $this->sPrefix . "step=$i' $sAjaxScript>$i</a> ";
					$bDelimiter=true;
				}

				if ($iPage < $iAllPageNumber) {
					$next_text = "<a href='".$sPrefUrl."/?" . $sQueryString . "&" . $this->sPrefix
					. "step=$next' $sAjaxScript>" . Language::GetMessage ( 'Next' ) . "&rarr;</a> ";
					$end_text = "<a href='".$sPrefUrl."/?" . $sQueryString . "&" . $this->sPrefix
					. "step=$iAllPageNumber' $sAjaxScript>" . Language::GetMessage ( 'StepEnd' ) . "&rarr;</a> ";
				} else {
					$next_text = "<span>" . Language::GetMessage ( 'Next' ) . "&rarr;</span>";
					$end_text = "<span>" . Language::GetMessage ( 'StepEnd' ) . "&rarr;</span>";
				}
				break;
				//------------------ OnlyNumber Stepper -----------------
			case 'onlynumber':
				$start = $iPage - ceil($iPageNumber/2);
				if ($start<0) $start=0;

				$stop=$iPageNumber + $start;
				if ($stop>$iAllPageNumber) $stop=$iAllPageNumber;

				for($i = $start; $i <= $stop; $i ++) {
					if ($iPage == $i) $sPageText .= "<a class='".$this->sStepperActiveItemClass."' href='".$sPrefUrl."/?"
					. $sQueryString . "&" . $this->sPrefix
					. "step=$i' $sAjaxScript>".($i+$this->iStartStep)."</a>";
					else $sPageText .= "<a href='".$sPrefUrl."/?" . $sQueryString . "&" . $this->sPrefix
					. "step=$i' $sAjaxScript>".($i+$this->iStartStep)."</a>";
				}

				break;
				//------------------ Japancars default Stepper -----------------
			case 'japancars':
					if ($iPage > 0) {
						$start_text = "<a class=list href='".$sPrefUrl."/" . Base::$aRequest['action'] ."' $sAjaxScript>" . Language::GetMessage ( 'Start' ) . "</a>";
								$previous_text = "<a class=list href='".$sPrefUrl."/?" . $sQueryString . "&" . $this->sPrefix
								. "step=$previous' $sAjaxScript>" . Language::GetMessage ( 'Prev' ) . "</a>";
					} else {
								$start_text = "<span class=list>" . Language::GetMessage ( 'Start' ) . "</span>";
					$previous_text = "<span class=list>" . Language::GetMessage ( 'Prev' ) . "</span>";
				}
					if ($iPage > $iPageNumber && count($this->aItem) > 0) $start = $iPage - $this->iStepNumber;
					else $start = 0;

					for($i = $start; $i <= $iPageNumber + $start; $i ++) {
					if ($iPage == $i) $sPageText .= "<span>".($i+$this->iStartStep)."</span>";
					else $sPageText .= "<a href='".$sPrefUrl."/?" . $sQueryString . "&" . $this->sPrefix
									. "step=$i' $sAjaxScript>".($i+$this->iStartStep)."</a>";
				}

				if ($iPage < $iAllPageNumber) {
					$next_text="<a class=list href='".$sPrefUrl."/?".$sQueryString."&".$this->sPrefix
					."step=$next' $sAjaxScript>"
					.Language::GetMessage('Next')."></a>";
					$end_text = "<a class=list href='".$sPrefUrl."/?" . $sQueryString . "&" . $this->sPrefix
					. "step=$iAllPageNumber' $sAjaxScript>" . Language::GetMessage ( 'StepEnd' ) . "</a>";
				} else {
					$next_text = "<span class=list>" . Language::GetMessage ( 'Next' ) . "</span>";
					$end_text = "<span class=list>" . Language::GetMessage ( 'StepEnd' ) . "</span>";
				}
				break;
				//------------------ Romario-auto Stepper -----------------
			case 'romario':
				if ($iPage > 0) {
					$previous_text = "<a class=list href='".$sPrefUrl."/?".$sQueryString."&".$this->sPrefix."step=$previous' $sAjaxScript>&nbsp;".Language::GetMessage('StepPrev')."</a>";
				} else {
					$previous_text = "<span class=list>&nbsp;".Language::GetMessage('StepPrev')."</span>";
				}
					
				$aPageArray=array();
    			if($iAllPageNumber > 3) {
    				$aPageArray[0]=0;
    				$aPageArray[1]=1;
    				$aPageArray[2]="...";
    				$aPageArray[3]=$iPage-1;
    				$aPageArray[4]=$iPage;
    				$aPageArray[5]=$iPage+1;
    				$aPageArray[6]="...";
    				$aPageArray[7]=$iAllPageNumber-1;
    				$aPageArray[8]=$iAllPageNumber;
    				
    				$aPageArray[2]=($aPageArray[3]-$aPageArray[1] > 1 ? "..." : "-");
    				$aPageArray[6]=($aPageArray[7]-$aPageArray[5] > 1 ? "..." : "-");
    				if($aPageArray[7]==$aPageArray[5] || $aPageArray[7]==$aPageArray[4] || $aPageArray[7]==$aPageArray[3]) $aPageArray[7]="-";
    				if($aPageArray[8]==$aPageArray[5] || $aPageArray[8]==$aPageArray[4]) $aPageArray[8]="-";
    				if($aPageArray[5]>$iAllPageNumber) $aPageArray[5]="-";
					if($iPage<=3)
					{
						for($i=5; $i>=0; $i--) $aPageArray[$i]="-";
						for($i=0; $i<=$iPage+1; $i++) $aPageArray[$i]=$i;	
					}
					if($iPage+3>=$iAllPageNumber && $iAllPageNumber>8)
					{
						for($i=3; $i<=8; $i++) $aPageArray[$i]="-";
						for($i=8, $k=0; $i>=7-$iAllPageNumber+$iPage; $i--, $k++) $aPageArray[$i]=$iAllPageNumber-$k;	
					}
				} elseif($iAllPageNumber >= 1) {
					for($n=0; $n<=$iAllPageNumber; $n++)
						$aPageArray[$n]=$n;
				}
				
				if($aPageArray) foreach ($aPageArray as $i) {
					if (strcmp($iPage,$i)==0) {
						$sPageText .= "<span>".($i+$this->iStartStep)."</span>&nbsp;";
					} elseif(strcmp($i,'...')==0) {
						$sPageText .= "&nbsp;<span>...</span>&nbsp;";
					} elseif(strcmp($i,'-')==0) {
						$sPageText .= "";
					} else {
						$sPageText .= "<a href='".$sPrefUrl."/?" . $sQueryString . "&" . $this->sPrefix . "step=$i' $sAjaxScript>".($i+$this->iStartStep)."&nbsp;</a>";
					}
				}

				if ($iPage < $iAllPageNumber) {
					$next_text="<a class=list href='".$sPrefUrl."/?".$sQueryString."&".$this->sPrefix."step=$next' $sAjaxScript>".Language::GetMessage('StepNext')."&nbsp;</a>";
				} else {
					$next_text = "<span class=list>" . Language::GetMessage('StepNext')."&nbsp;</span>";
				}
				
				$start_text="";
				$end_text="";
				break;
				//------------------ Bootstrap -----------------
				case 'bootstrap':
				if ($iPage > 0) {
					$start_text = "
					<li class='page-item'>
                    <a class='page-link' href='".$sPrefUrl."/?" . $sQueryString . "&" . $this->sPrefix
					. "step=0' $sAjaxScript aria-label='Previous'>
                      <span aria-hidden='true'>«</span>
                      <span class='sr-only'>Begin</span>
                    </a>
                  </li>";
					$previous_text ="<li class='page-item'>
                    <a class='page-link' href='".$sPrefUrl."/?" . $sQueryString . "&" . $this->sPrefix
					. "step=$previous' $sAjaxScript aria-label='Previous'>
                      <span aria-hidden='true'>‹</span>
                      <span class='sr-only'>Previous</span>
                    </a>
                  </li>";
					    
					/*    "<a class=list href='".$sPrefUrl."/?" . $sQueryString . "&" . $this->sPrefix
					. "step=0' $sAjaxScript>&lt;&lt;&nbsp;" . Language::GetMessage ( 'Start' ) . "</a>";
					$previous_text = "<a class=list href='".$sPrefUrl."/?" . $sQueryString . "&" . $this->sPrefix
					. "step=$previous' $sAjaxScript>&nbsp;&lt;&nbsp;" . Language::GetMessage ( 'Prev' ) . "</a>";
				*/} else {
					$start_text = "
					<li class='page-item disabled'>
                    <a class='page-link' href='#' aria-label='Previous'>
                      <span aria-hidden='true'>«</span>
                      <span class='sr-only'>Begin</span>
                    </a>
                  </li>";
					$previous_text ="<li class='page-item disabled'>
                    <a class='page-link' href='#' aria-label='Previous'>
                      <span aria-hidden='true'>‹</span>
                      <span class='sr-only'>Previous</span>
                    </a>
                  </li>";
					}
//------------------------------------------------------------------------------------------------------------------------
// 				if ($iPage > $iPageNumber && count($this->aItem) > 0) $start = $iPage - $this->iStepNumber;
// 				else $start = 0;

// 				for($i = $start; $i <= $iPageNumber + $start; $i ++) {
// 					if ($iPage == $i) $sPageText .= "<span>".($i+$this->iStartStep)."</span>&nbsp;";
// 					else $sPageText .= "<a href='".$sPrefUrl."/?" . $sQueryString . "&" . $this->sPrefix
// 					. "step=$i' $sAjaxScript>".($i+$this->iStartStep)."&nbsp;</a>";
// 				}
//------------------------------------------------------------------------------------------------------------------------
				$aPageArray=$this->printPage($iAllPageNumber,$iPage);
				if($aPageArray) foreach ($aPageArray as $i) {
				    if (strcmp($iPage,$i)==0) {
				        $sPageText .= "<li class='page-item active'>
                    <a class='page-link' href='#'>".($i+$this->iStartStep)."<span class='sr-only'>(current)</span></a>
                  </li>";
				           // <span>".($i+$this->iStartStep)."</span>&nbsp;";
				    }
				    elseif(strcmp($i,'...')==0) {
				        $sPageText .= "<li class='page-item disabled'>
                    <a class='page-link' href='#'>...<span class='sr-only'>...</span></a>
                  </li>";
				    }
				    else {
				        $sPageText .= "
				            <li class='page-item'><a class='page-link' href='".$sPrefUrl."/?" . $sQueryString . "&" . $this->sPrefix
				        . "step=$i' $sAjaxScript>".($i+$this->iStartStep)."</a></li>";
				    }
				}
//------------------------------------------------------------------------------------------------------------------------

				if ($iPage < $iAllPageNumber) {
					$next_text="<li class='page-item'>
                    <a class='page-link' href='".$sPrefUrl."/?".$sQueryString."&".$this->sPrefix
					."step=$next' $sAjaxScript' aria-label='Next'>
                      <span aria-hidden='true'>›</span>
                      <span class='sr-only'>Next</span>
                    </a>
                  </li>";
					$end_text ="<li class='page-item'>
                    <a class='page-link' href='".$sPrefUrl."/?" . $sQueryString . "&" . $this->sPrefix
					. "step=$iAllPageNumber' $sAjaxScript' aria-label='Next'>
                      <span aria-hidden='true'>»</span>
                      <span class='sr-only'>End</span>
                    </a>
                  </li>";

					 
					 } else {
					$next_text="<li class='page-item disabled'>
                    <a class='page-link' href='".$sPrefUrl."/?".$sQueryString."&".$this->sPrefix
					."step=$next' $sAjaxScript' aria-label='Next'>
                      <span aria-hidden='true'>›</span>
                      <span class='sr-only'>Next</span>
                    </a>
                  </li>";
					$end_text ="<li class='page-item disabled'>
                    <a class='page-link' href='".$sPrefUrl."/?" . $sQueryString . "&" . $this->sPrefix
					. "step=$iAllPageNumber' $sAjaxScript' aria-label='Next'>
                      <span aria-hidden='true'>»</span>
                      <span class='sr-only'>End</span>
                    </a>
                  </li>";}
				
				if ($_SERVER['SCRIPT_NAME']=='/mpanel/login.php') {
				    $sUrl=$sPrefUrl."/?".$sQueryString."&".$this->sPrefix."step=";
				    Base::$tpl->assign('sCustomStepUrl',$sUrl);
				}
				
				if(!$aPageArray && $this->bStepperHideNoPages){
				    return ;
				}
				elseif(!$aPageArray){
				    return ;
				}
				else{
				    return "<ul class='pagination'>" . $start_text . $previous_text . $sPageText  . $next_text . $end_text ."</ul>";
				}
				break;
				//------------------ Default Stepper -----------------
			default:
				if ($iPage > 0) {
					$start_text = "<a class=list href='".$sPrefUrl."/?" . $sQueryString . "&" . $this->sPrefix
					. "step=0' $sAjaxScript>&lt;&lt;&nbsp;" . Language::GetMessage ( 'Start' ) . "</a>";
					$previous_text = "<a class=list href='".$sPrefUrl."/?" . $sQueryString . "&" . $this->sPrefix
					. "step=$previous' $sAjaxScript>&nbsp;&lt;&nbsp;" . Language::GetMessage ( 'Prev' ) . "</a>";
				} else {
					$start_text = "<span class=list>&lt;&lt;&nbsp;" . Language::GetMessage ( 'Start' ) . "</span>";
					$previous_text = "<span class=list>&lt;&nbsp;" . Language::GetMessage ( 'Prev' ) . "</span>";
				}
//------------------------------------------------------------------------------------------------------------------------
// 				if ($iPage > $iPageNumber && count($this->aItem) > 0) $start = $iPage - $this->iStepNumber;
// 				else $start = 0;

// 				for($i = $start; $i <= $iPageNumber + $start; $i ++) {
// 					if ($iPage == $i) $sPageText .= "<span>".($i+$this->iStartStep)."</span>&nbsp;";
// 					else $sPageText .= "<a href='".$sPrefUrl."/?" . $sQueryString . "&" . $this->sPrefix
// 					. "step=$i' $sAjaxScript>".($i+$this->iStartStep)."&nbsp;</a>";
// 				}
//------------------------------------------------------------------------------------------------------------------------
				$aPageArray=$this->printPage($iAllPageNumber,$iPage);
				if($aPageArray) foreach ($aPageArray as $i) {
				    if (strcmp($iPage,$i)==0) {
				        $sPageText .= "<span>".($i+$this->iStartStep)."</span>&nbsp;";
				    }
				    elseif(strcmp($i,'...')==0) {
				        $sPageText .= "&nbsp;<span>...</span>&nbsp;";
				    }
				    else {
				        $sPageText .= "<a href='".$sPrefUrl."/?" . $sQueryString . "&" . $this->sPrefix
				        . "step=$i' $sAjaxScript>".($i+$this->iStartStep)."&nbsp;</a>";
				    }
				}
//------------------------------------------------------------------------------------------------------------------------

				if ($iPage < $iAllPageNumber) {
					$next_text="<a class=list href='".$sPrefUrl."/?".$sQueryString."&".$this->sPrefix
					."step=$next' $sAjaxScript>"
					.Language::GetMessage('Next')."&nbsp;&gt;</a>";
					$end_text = "<a class=list href='".$sPrefUrl."/?" . $sQueryString . "&" . $this->sPrefix
					. "step=$iAllPageNumber' $sAjaxScript>" . Language::GetMessage ( 'StepEnd' ) . "&nbsp;&gt;&gt;</a>";
				} else {
					$next_text = "<span class=list>" . Language::GetMessage ( 'Next' ) . "&nbsp;&gt;</span>";
					$end_text = "<span class=list>" . Language::GetMessage ( 'StepEnd' ) . "&nbsp;&gt;&gt;</span>";
				}
				
				if ($_SERVER['SCRIPT_NAME']=='/mpanel/login.php') {
				    $sUrl=$sPrefUrl."/?".$sQueryString."&".$this->sPrefix."step=";
				    Base::$tpl->assign('sCustomStepUrl',$sUrl);
				}
				break;
				//---------------------------------------------------
		}
		if(!$aPageArray && $this->bStepperHideNoPages)
			return ;
		else
			return $start_text . '&nbsp;&nbsp;' . $previous_text . '&nbsp;&nbsp;' . $sPageText . '&nbsp;&nbsp;' . $next_text
			. '&nbsp;' . $end_text;
	}
	//-----------------------------------------------------------------------------------------------
	public function printPage($countPage, $actPage)
    {
    	//если страниц 0 или 1, вернём пустой массив (переключатели не выводятся)
    	if ($countPage == 0) return array();
    	if ($countPage > 10) //если страниц больше 10, заполним массив pageArray переключателями в зависимости от активной страницы
    	{
    		//если активная страница - одна из первых  или одна из последних страниц
    		//то запишем в массив первые 5 и последние 5 переключателей, разделив их многоточием
    		if($actPage <= 3 || $actPage + 3 >= $countPage)
    		{
    			for($i = 0; $i <= 4; $i++)
    			{
    				$pageArray[$i] = $i;
    			}
    			$pageArray[$i] = "...";
    			for($j = $i+1, $k = 4; $j <= 10; $j++, $k--)
    			{
    				$pageArray[$j] = $countPage - $k;
    			}			
    		}
    		//в противном случае в массив запишем первые и последние две страницы
    		//а посередине - пять страниц, с обоих сторон обрамлённых многоточием.
    		//активная страница, таким образом, окажется в центре переключателей.
    		else
    		{
    			$pageArray[0] = 0;
    			$pageArray[1] = 1;
    			$pageArray[2] = "...";
    			$pageArray[3] = $actPage - 2;
    			$pageArray[4] = $actPage - 1;
    			$pageArray[5] = $actPage;
    			$pageArray[6] = $actPage + 1;
    			$pageArray[7] = $actPage + 2;
    			$pageArray[8] = "...";
    			$pageArray[9] = $countPage - 1;;
    			$pageArray[10] = $countPage;			
    		}
    	}
    	//если страниц меньше 10, просто заполним массив переключателей всеми номерами страниц подряд
    	else 
    	{
    		for($n = 0; $n < $countPage+1; $n++)
    		{
    			$pageArray[$n] = $n;
    		}
    	}
    	return $pageArray;
    }
	//-----------------------------------------------------------------------------------------------
	public function getStepperPear($iRowNumber)
	{
		$aPagerOption = array ('mode' => 'Sliding', 'totalItems' => $iRowNumber, 'perPage' => $this->iRowPerPage,
		'currentPage' => Base::$aRequest ['step'], 'fileName' => "?"
		. preg_replace ( '/&step=(\d+)/', '', $_SERVER ['QUERY_STRING'] )
		. '&step=%d',
		'delta' => 10, 'append' => false, 'curPageLinkClassName' => 'active_link', 'separator' => '', 'spacesBeforeSeparator' => 1
		, 'spacesAfterSeparator' => 1, 'curPageSpanPre' => '', 'curPageSpanPost' => ''
		, 'linkClass' => 'stepper', 'firstPagePre' => "<span>&lt;&lt;&nbsp;" . Language::GetMessage ( 'Start' )
		. "</span>", 'prevImg' => "<span>&lt;&nbsp;" . Language::GetMessage ( 'Prev' ) . "</span>", 'nextImg' => "<span>"
		. Language::GetMessage ( 'Next' ) . "&nbsp;&gt;</span>", 'lastPagePre' => "<span>"
		. Language::GetMessage ( 'StepEnd' ) . "&nbsp;&gt;&gt;</span>" );

		$oPager = Pager::factory ( $aPagerOption );
		return $oPager->links;

	}
	//-----------------------------------------------------------------------------------------------
	public function getFilter()
	{
		$sQueryString = preg_replace ( '/&' . $this->sPrefix . 'step=(\d+)/', '', $this->sQueryString );
		$sQueryString = preg_replace ( '/&' . $this->sPrefix . 'filter=[^&]*/', '', $sQueryString );
		$sQueryString = preg_replace ( '/&' . $this->sPrefix . 'filter_value=[^&]*/', '', $sQueryString );
		Base::$tpl->assign ( 'sQueryString', $sQueryString );
		Base::$tpl->assign ( 'sFilter', stripslashes(Base::$aRequest [$this->sPrefix . 'filter']) );
		Base::$tpl->assign ( 'sFilterValue', stripslashes(Base::$aRequest [$this->sPrefix . 'filter_value']) );
		return Base::$tpl->fetch ( 'addon/table/filter/' . $this->sFilterTemplateName );
	}
	//-----------------------------------------------------------------------------------------------
	public function Customize()
	{
		include_once (SERVER_PATH . '/class/system/Content.php');
		if (class_exists ( 'Content' )) {
			$oObject = new Content ( );
			if (method_exists ( $oObject, 'CustomizeTable' ))
			$oObject->CustomizeTable ( $this );
		}
	}
	//-----------------------------------------------------------------------------------------------
	public function SetSql($sScript, $aData = array())
	{
		$sFilter = Base::$aRequest [$this->sPrefix . 'filter'];
		if ($sFilter != '') {
			$sFilterValue = Base::$aRequest [$this->sPrefix . 'filter_value'];
			if ($this->aColumn [$sFilter] ['sOrder']=='') {
				trigger_error('SET $oTable->aColumn ARRAY AFTER $oTable = new Table ( ); THEN $this->SetDefaultTable ( $oTable );
				 (example mpanel/spec/log_finance.php)',E_USER_ERROR);
			}
			if( $this->aColumn [$sFilter] ['sMethod'] ) {
				//if( $aData [$sFilter] ['sMethod'] ) {
				switch ( $this->aColumn [$sFilter] ['sMethod'] ) {
					case "one_two":
						$aData ['where'] .= " AND (".$this->aColumn [$sFilter] ['sOrder']." LIKE '%$sFilterValue%' OR ".$this->aColumn [$sFilter] ['sOrder']."2 LIKE '%$sFilterValue%')";
						break;
					case "exact":
						$aData ['where'] .= " AND ".$this->aColumn [$sFilter] ['sOrder']." = '{$sFilterValue}'";
						break;
					case "skip":
						$aData ['where'] .= '';
						break;
					default:
						$aData ['where'] .= " AND ".$this->aColumn [$sFilter] ['sOrder']." LIKE '%$sFilterValue%'";
				}
			} else {
				if( $this->aColumn [$sFilter] ['sOrder'] ) {
					if ($_SERVER['REQUEST_URI']=='/mpanel/login.php') {
						if (Language::getConstant('mpanel_search_strong',0))
							$aData ['where'] .= " AND ".$this->aColumn [$sFilter] ['sOrder']." = '$sFilterValue'";
						else 
							$aData ['where'] .= " AND ".$this->aColumn [$sFilter] ['sOrder']." LIKE '%$sFilterValue%'";
					}
					else
						$aData ['where'] .= " AND ".$this->aColumn [$sFilter] ['sOrder']." LIKE '%$sFilterValue%'";
				} else {
					trigger_error("$this->aColumn [{$sFilter}] ['sOrder'] is empty!",E_USER_ERROR);
				}
			}
		}
		$this->sSql=Base::GetSql($sScript, $aData);
		return count($aData);
	}
	//-----------------------------------------------------------------------------------------------
	private function sortArrayCallback($sA, $sB)
	{
		$sOrder = Base::$aRequest [$this->sPrefix . 'order'];
		$iOrderWay = (Base::$aRequest [$this->sPrefix . 'way'] == 'desc' ? '-1' : '1');
		if ($sA [$sOrder] == $sB [$sOrder])
		return 0;
		if ($sA [$sOrder] > $sB [$sOrder])
		return (1 * $iOrderWay);
		if ($sA [$sOrder] < $sB [$sOrder])
		return (- 1 * $iOrderWay);
	}
	//-----------------------------------------------------------------------------------------------
	public function setArray($aData)
	{
		$this->sType = 'array';
		//---------------------filter
		$sFilter = Base::$aRequest [$this->sPrefix . 'filter'];
		if ($sFilter != '') {
			$sFilterValue = Base::$aRequest [$this->sPrefix . 'filter_value'];
			$aRes = array ();
			foreach ( $aData as $aRow ) {
				if (strpos ( $aRow [$sFilter], $sFilterValue ) !== false) {
					$aRes [] = $aRow;
				}
			}
			$aData = $aRes;
		}
		//---------------------order
		$sOrder = Base::$aRequest [$this->sPrefix . 'order'];
		if ($sOrder != '') {
			usort ( $aData, array ("Table", "sortArrayCallback" ) );
		}
		//---------------------
		$this->aDataFoTable = $aData;
	}
	//-----------------------------------------------------------------------------------------------
	public function GetFilteredProductEnding($iCount)
	{
		$sTmp=strrev($iCount);
			if ($sTmp[1] == 1)
			{$sEnding= Language::GetMessage ('товаров');}
				elseif($sTmp[0]==1)
				{$sEnding=Language::GetMessage ('товар');}
				elseif($sTmp[0]>=2 && $sTmp[0]<=4)
				{$sEnding=Language::GetMessage ('товара');}
				else 	{$sEnding=Language::GetMessage ('товаров');}
		return $sEnding;
	}
}

?>