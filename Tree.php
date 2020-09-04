<?
/**
 * @author Anatoly Udod
 */

require_once (SERVER_PATH . '/class/core/Base.php');
//require_once(SERVER_PATH.'/lib/Pager/Pager.php');
class Tree extends Base {

	public $sType = 'Sql';
	public $aDataFoTable = array ();

	public $sSql = '';
	public $sTableSql = '';
	public $aItem = array ();
	public $aColumn = array ();
	public $iRowPerPage = 10;
	public $iPage = 0;
	public $iStepNumber = 10;
	public $aOrderedColumn = array ();
	public $aOrdered = array ();
	public $sDefaultOrder = '';

	/** Width for table tag  width="$sWidth"  */
	public $sWidth = '99%';
	/** Class for table tag  class="$sClass"  */
	public $sClass = '';
	public $sDataTemplate;
	public $sButtonTemplate;
	public $sSubtotalTemplate;

	public $sAddButton = '';
	public $sAddAction = '';

	public $aCallback = array ();

	public $bPearStepper = false;
	public $bStepperVisible = true;
	public $bAjaxStepper = false;
	public $bHeaderVisible = true;
	/** Check boxes visible at the start of tr */
	public $bCheckVisible = false;
	/** Check box for checkall visible at the start of th */
	public $bCheckAllVisible = true;
	public $bDefaultChecked = true;

	public $bFormAvailable = true;
	public $sCheckField = 'id';
	public $iAllRow = 0;
	public $bHideTr = false;

	public $sHeaderRight = '';

	public $sTemplateName = 'index.tpl';
	public $sFilterTemplateName = 'admin.tpl';

	public $sPrefix = '';
	public $sQueryString = '';
	public $sOrderAscImage = 'images/small/down.png';
	public $sOrderDescImage = 'images/small/up.png';

	public $bFilterVisible = false;
	public $aFilter = array ('oObject' => NULL, 'sMethod' => '' );
	public $sFormHeader = '';
	public $sStepperAlign = 'right';

	/** Columns number for gallery table */
	public $iGallery = 5;
	/** Flag tell if we drow table for cliend side*/
	public $bIsGallery = false;

	public $sStepperType = 'standard';

	/** Caching sql_found_rows value in table cache_stepper */
	public $bCacheStepper = false;
	/** Duplicates the stepper on the top of table */
	public $bTopStepper = false;

	/** Code for custom no item template*/
	public $sNoItem = '';

	/** Count(*) stepper type for unsupported by SQL_FOUND_ROWS command */
	public $bCountStepper = false;


	//-----------------------------------------------------------------------------------------------
	public function __construct() {
		$this->Customize();
		$this->aFilter = array ('oObject' => $this, 'sMethod' => 'getFilter' );
		Base::$tpl->assign_by_ref( 'oTree', $this );
	}
	//-----------------------------------------------------------------------------------------------
	public function getTree($sHeader = '', $sHint = '') {

		Base::$tpl->assign ( 'sWidth', $this->sWidth );
		Base::$tpl->assign ( 'sClass', $this->sClass );
		Base::$tpl->assign ( 'sDataTemplate', $this->sDataTemplate );
		Base::$tpl->assign ( 'sButtonTemplate', $this->sButtonTemplate );
		Base::$tpl->assign ( 'sSubtotalTemplate', $this->sSubtotalTemplate );
		Base::$tpl->assign ( 'sAddButton', Base::$language->getMessage ( $this->sAddButton ) );
		Base::$tpl->assign ( 'sAddAction', $this->sAddAction );
		Base::$tpl->assign ( 'bHeaderVisible', $this->bHeaderVisible );
		Base::$tpl->assign ( 'bCheckVisible', $this->bCheckVisible );
		Base::$tpl->assign ( 'bCheckAllVisible', $this->bCheckAllVisible );
		Base::$tpl->assign ( 'bDefaultChecked', $this->bDefaultChecked );
		Base::$tpl->assign ( 'bFormAvailable', $this->bFormAvailable );
		Base::$tpl->assign ( 'sCheckField', $this->sCheckField );
		Base::$tpl->assign ( 'sNoItem', $this->sNoItem );
		Base::$tpl->assign ( 'bAjaxStepper', $this->bAjaxStepper );
		Base::$tpl->assign ( 'bHideTr', $this->bHideTr );
		Base::$tpl->assign ( 'sHeaderRight', $this->sHeaderRight );
		Base::$tpl->assign ( 'sPrefix', $this->sPrefix );
		Base::$tpl->assign ( 'sOrderAscImage', $this->sOrderAscImage );
		Base::$tpl->assign ( 'sOrderDescImage', $this->sOrderDescImage );
		Base::$tpl->assign ( 'sFormHeader', $this->sFormHeader );
		Base::$tpl->assign ( 'sStepperAlign', $this->sStepperAlign );
		Base::$tpl->assign ( 'iRowPerPage', $this->iRowPerPage );
		Base::$tpl->assign ( 'iGallery', $this->iGallery );
		Base::$tpl->assign ( 'bIsGallery', $this->bIsGallery );
		Base::$tpl->assign ( 'bTopStepper', $this->bTopStepper );

		if (! $this->sQueryString)
		$this->sQueryString = $_SERVER ['QUERY_STRING'];

		$sOrderQueryString = preg_replace ( '/&' . $this->sPrefix . 'order=([^&]*)/', '', $this->sQueryString );
		foreach ( $this->aColumn as $sKey => $aValue ) {
			$sHeader = $aValue ['sTitle'];
			if ($sHeader) {
				if ($this->sTemplateName == 'admin.tpl')
				$this->aColumn [$sKey] ['sTitle'] = Base::$language->getDMessage ( $sHeader );
				else
				$this->aColumn [$sKey] ['sTitle'] = Base::$language->getMessage ( $sHeader );
			}

			if (! $sOrder && $this->aColumn [$sKey] ['sOrder'])
			$sOrder = " order by " . $this->aColumn [$sKey] ['sOrder'];
			if (! $sFirstOrderColumn && $this->aColumn [$sKey] ['sOrder'])
			$sFirstOrderColumn = $sKey;

			if (Base::$aRequest [$this->sPrefix . 'order'] == $sKey) {
				if (Base::$aRequest [$this->sPrefix . 'way'] == 'asc' || ! Base::$aRequest [$this->sPrefix . 'way'] == 'asc') {
					$sOtherWay = 'desc';
					$this->aColumn [$sKey] ['sOrderImage'] = $this->sOrderAscImage;
				} else {
					$sOtherWay = 'asc';
					$this->aColumn [$sKey] ['sOrderImage'] = $this->sOrderDescImage;
				}
				$this->aColumn [$sKey] ['sOrderLink'] = $sOrderQueryString . '&' . $this->sPrefix . 'order=' . $sKey . '&' . $this->sPrefix . 'way=' . $sOtherWay;

				$sOrder = " order by " . $this->aColumn [$sKey] ['sOrder'];
				if (Base::$aRequest [$this->sPrefix . 'way'])
				$sOrder .= " " . Base::$aRequest [$this->sPrefix . 'way'];
			} else {
				if ($this->aColumn [$sKey] ['sOrder'])
				$this->aColumn [$sKey] ['sOrderLink'] = $sOrderQueryString . '&' . $this->sPrefix . 'order=' . $sKey . '&' . $this->sPrefix . 'way=asc';
			}
		}
		//first order column for default order
		if (! Base::$aRequest [$this->sPrefix . 'order'] && $sFirstOrderColumn) {
			$this->aColumn [$sFirstOrderColumn] ['sOrderLink'] = $sOrderQueryString . '&' . $this->sPrefix . 'order=' . $sFirstOrderColumn . '&' . $this->sPrefix . 'way=desc';
			$this->aColumn [$sFirstOrderColumn] ['sOrderImage'] = $this->sOrderAscImage;
		}

		Base::$tpl->assign ( 'aColumn', $this->aColumn );

		if ($this->sType == 'Sql') {
			$iStep = intval ( Base::$aRequest [$this->sPrefix . 'step'] );
			if ($this->bPearStepper) $iStep --;
			if (! Base::$aRequest [$this->sPrefix . 'step']) $sLimit = ' limit 0,' . $this->iRowPerPage;
			else $sLimit = ' limit ' . ($iStep * $this->iRowPerPage) . ',' . $this->iRowPerPage;

			if ($this->bCacheStepper) {
				require_once(SERVER_PATH.'/class/core/Cache.php');
				$sCacheSql=$this->sSql;
				$iPageNumber=Cache::GetValue('stepper',$sCacheSql);

				if (!$iPageNumber) $this->sSql = str_replace ( 'select', 'select SQL_CALC_FOUND_ROWS', $this->sSql );

				$this->sTableSql = $this->sSql . ' ' . $sOrder . ' ' . $sLimit;
				$aItem = Base::$db->getAll ( $this->sTableSql );
				if (!$iPageNumber) {
					$iPageNumber = Base::$db->getOne ( 'SELECT FOUND_ROWS()' );
					Cache::SetValue('stepper',$sCacheSql,$iPageNumber);
				}
			}
			else {
				$this->sSql = str_replace ( 'select', 'select SQL_CALC_FOUND_ROWS', $this->sSql );
				$this->sTableSql = $this->sSql . ' ' . $sOrder . ' ' . $sLimit;
				$aItem = Base::$db->getAll ( $this->sTableSql );
				if ($this->bCountStepper) {
					$iPageNumber = Base::$db->getOne ("select count(*) from (".$this->sSql.") count_table ");
				}
				else $iPageNumber = Base::$db->getOne ( 'SELECT FOUND_ROWS()' );
			}
			$this->iAllRow = $iPageNumber;
		} elseif ($this->sType == 'array') {
			$aItem = $this->aDataFoTable;
			$this->iAllRow = count ( $aItem );
		}

		Base::$tpl->assign ( 'iAllRow', $this->iAllRow );
		if ($this->aCallback) {
			$sMethod = $this->aCallback [1];
			$aResult = $this->aCallback [0]->$sMethod ( $aItem );
			if ($this->sSubtotalTemplate)
			Base::$tpl->assign ( 'dSubtotal', $aResult ['dSubtotal'] );
		}

		// generate empty item if need
		if ($this->bIsGallery){
			$iItemLen = count($aItem);
			if ($iItemLen%$this->iGallery != 0){
				if($iItemLen < $this->iGallery){
					$mod = $this->iGallery-$iItemLen;
				}else{
					$mod = $this->iGallery - round($iItemLen/$this->iGallery);
				}
			}
			for ($i = $iItemLen; $i<$iItemLen+$mod; $i++){
				$aItem[$i] = array();
			}
		}

		Base::$tpl->assign ( 'aItem', $aItem );
		$this->aItem = $aItem;

		if ($this->bStepperVisible) {
			if ($this->bPearStepper) $sStepper = $this->getStepperPear ( $iPageNumber );
			else $sStepper = $this->getStepper ( $iPageNumber );
			Base::$tpl->assign ( 'sStepper', $sStepper );
		}

		if ($this->bFilterVisible) {
			$oObject = $this->aFilter ['oObject'];
			$sMethod = $this->aFilter ['sMethod'];
			if (method_exists ( $oObject, $sMethod )) {
				Base::$tpl->assign ( 'sLeftFilter', $oObject->$sMethod () );
			}
		}

		Base::$tpl->assign('iStartRow', $this->iPage*$this->iRowPerPage);
		Base::$tpl->assign('iEndRow',($this->iPage+1)*$this->iRowPerPage );

		Base::$tpl->assign ( 'sReturn', $this->sQueryString );

		return Base::$tpl->fetch ( 'tree/' . $this->sTemplateName );
	}
	//-----------------------------------------------------------------------------------------------
	public function getStepper($iRowNumber) {
		$iPage = intval ( Base::$aRequest [$this->sPrefix . 'step'] );
		$this->iPage=$iPage;
		$iRowPerPage = $this->iRowPerPage;

		if (($iRowNumber % $iRowPerPage) > 0) $adding = 0;
		else $adding = - 1;
		$iPageNumber = intval ( $iRowNumber / $iRowPerPage ) + $adding;

		$iAllPageNumber = $iPageNumber;
		if ($iPageNumber > $this->iStepNumber)
		$iPageNumber = $this->iStepNumber;

		$next = $iPage + 1;
		$previous = $iPage - 1;

		$sQueryString = preg_replace ( '/&' . $this->sPrefix . 'step=(\d+)/', '', $this->sQueryString );

		if ($this->bAjaxStepper)
		$sAjaxScript = " onclick=\" xajax_process_browse_url(this.href); return false;\" ";

		switch ($this->sStepperType) {
			//------------------ Announcement Stepper -----------------
			case 'announcement':
				if ($iPage > 0) {
					$start_text = "<a href='./?" . $sQueryString . "&" . $this->sPrefix . "step=0' $sAjaxScript>&larr;"
					. Base::$language->getMessage ( 'Start' ) . "</a>";
					$previous_text = "<a  href='./?" . $sQueryString . "&" . $this->sPrefix . "step=$previous' $sAjaxScript>&larr;"
					. Base::$language->getMessage ( 'Prev' ) . "</a>";
				} else {
					$start_text = "<span>&larr;" . Base::$language->getMessage ( 'Start' ) . "</span>";
					$previous_text = "<span>&larr;" . Base::$language->getMessage ( 'Prev' ) . "</span>";
				}

				if ($iPage > $iPageNumber) $start = $iPage - $this->iStepNumber;
				else $start = 0;

				for($i = $start; $i <= $iPageNumber + $start; $i ++) {
					if ($bDelimiter) $sDelimiter=' | ';
					if ($iPage == $i) $sPageText .= $sDelimiter."<label>$i</label> ";
					else $sPageText .= $sDelimiter."<a href='./?" . $sQueryString . "&" . $this->sPrefix . "step=$i' $sAjaxScript>$i</a> ";
					$bDelimiter=true;
				}

				if ($iPage < $iAllPageNumber) {
					$next_text = "<a href='./?" . $sQueryString . "&" . $this->sPrefix
					. "step=$next' $sAjaxScript>" . Base::$language->getMessage ( 'Next' ) . "&rarr;</a> ";
					$end_text = "<a href='./?" . $sQueryString . "&" . $this->sPrefix
					. "step=$iAllPageNumber' $sAjaxScript>" . Base::$language->getMessage ( 'End' ) . "&rarr;</a> ";
				} else {
					$next_text = "<span>" . Base::$language->getMessage ( 'Next' ) . "&rarr;</span>";
					$end_text = "<span>" . Base::$language->getMessage ( 'End' ) . "&rarr;</span>";
				}
				break;
				//------------------ Default Stepper -----------------
			default:
				if ($iPage > 0) {
					$start_text = "<a class=list href='./?" . $sQueryString . "&" . $this->sPrefix . "step=0' $sAjaxScript><<&nbsp;" . Base::$language->getMessage ( 'Start' ) . "</a>";
					$previous_text = "<a class=list href='./?" . $sQueryString . "&" . $this->sPrefix . "step=$previous' $sAjaxScript>&nbsp;<&nbsp;" . Base::$language->getMessage ( 'Prev' ) . "</a>";
				} else {
					$start_text = "<span class=list><<&nbsp;" . Base::$language->getMessage ( 'Start' ) . "</span>";
					$previous_text = "<span class=list><&nbsp;" . Base::$language->getMessage ( 'Prev' ) . "</span>";
				}

				if ($iPage > $iPageNumber) $start = $iPage - $this->iStepNumber;
				else $start = 0;

				for($i = $start; $i <= $iPageNumber + $start; $i ++) {
					if ($iPage == $i) $sPageText .= "<span>$i</span>&nbsp;";
					else $sPageText .= "<a href='./?" . $sQueryString . "&" . $this->sPrefix . "step=$i' $sAjaxScript>$i&nbsp;</a>";
				}

				if ($iPage < $iAllPageNumber) {
					$next_text = "<a class=list href='./?" . $sQueryString . "&" . $this->sPrefix . "step=$next' $sAjaxScript>" . Base::$language->getMessage ( 'Next' ) . "&nbsp;></a>";
					$end_text = "<a class=list href='./?" . $sQueryString . "&" . $this->sPrefix . "step=$iAllPageNumber' $sAjaxScript>" . Base::$language->getMessage ( 'End' ) . "&nbsp;>></a>";
				} else {
					$next_text = "<span class=list>" . Base::$language->getMessage ( 'Next' ) . "&nbsp;></span>";
					$end_text = "<span class=list>" . Base::$language->getMessage ( 'End' ) . "&nbsp;>></span>";
				}
				break;
				//---------------------------------------------------
		}

		return $start_text . '&nbsp;&nbsp;' . $previous_text . '&nbsp;&nbsp;' . $sPageText . '&nbsp;&nbsp;' . $next_text . '&nbsp;' . $end_text;
	}

	//-----------------------------------------------------------------------------------------------
	public function getStepperPear($iRowNumber) {
		$aPagerOption = array ('mode' => 'Sliding', 'totalItems' => $iRowNumber, 'perPage' => $this->iRowPerPage, //'path' => SYSTEM_HOST_URL,
		'currentPage' => Base::$aRequest ['step'], 'fileName' => "?" . preg_replace ( '/&step=(\d+)/', '', $_SERVER ['QUERY_STRING'] ) . '&step=%d', // ��� ����������� ���� page=%d
		'delta' => 10, 'append' => false, 'curPageLinkClassName' => 'active_link', 'separator' => '', 'spacesBeforeSeparator' => 1, 'spacesAfterSeparator' => 1, 'curPageSpanPre' => '', 'curPageSpanPost' => '', 'linkClass' => 'stepper', 'firstPagePre' => "<span><<&nbsp;" . Base::$language->getMessage ( 'Start' ) . "</span>", 'prevImg' => "<span><&nbsp;" . Base::$language->getMessage ( 'Prev' ) . "</span>", 'nextImg' => "<span>" . Base::$language->getMessage ( 'Next' ) . "&nbsp;></span>", 'lastPagePre' => "<span>" . Base::$language->getMessage ( 'End' ) . "&nbsp;>></span>" );

		$oPager = Pager::factory ( $aPagerOption );
		return $oPager->links;

	}
	//-----------------------------------------------------------------------------------------------
	public function getFilter() {
		$sQueryString = preg_replace ( '/&' . $this->sPrefix . 'step=(\d+)/', '', $this->sQueryString );
		$sQueryString = preg_replace ( '/&' . $this->sPrefix . 'filter=[^&]*/', '', $sQueryString );
		$sQueryString = preg_replace ( '/&' . $this->sPrefix . 'filter_value=[^&]*/', '', $sQueryString );
		Base::$tpl->assign ( 'sQueryString', $sQueryString );
		Base::$tpl->assign ( 'sFilter', stripslashes(Base::$aRequest [$this->sPrefix . 'filter']) );
		Base::$tpl->assign ( 'sFilterValue', stripslashes(Base::$aRequest [$this->sPrefix . 'filter_value']) );
		return Base::$tpl->fetch ( 'tree/filter/' . $this->sFilterTemplateName );
	}
	//-----------------------------------------------------------------------------------------------
	public function Customize() {
		include_once (SERVER_PATH . '/class/system/Content.php');
		if (class_exists ( 'Content' )) {
			$oObject = new Content ( );
			if (method_exists( $oObject, 'CustomizeTree' ))
			$oObject->CustomizeTree( $this );
		}
	}
	//-----------------------------------------------------------------------------------------------
	public function setSql($sScript, $aData = array()) {
		$sFilter = Base::$aRequest [$this->sPrefix . 'filter'];
		if ($sFilter != '') {
			$sFilterValue = Base::$aRequest [$this->sPrefix . 'filter_value'];
			if ($this->aColumn [$sFilter] ['sOrder']=='') {
				trigger_error('SET $oTable->aColumn ARRAY AFTER $oTable = new Table ( ); THEN $this->SetDefaultTable ( $oTable ); (example mpanel/spec/log_finance.php)',E_USER_ERROR);
			}
			if( $this->aColumn [$sFilter] ['sMethod'] ) {
			//if( $aData [$sFilter] ['sMethod'] ) {
                switch ( $this->aColumn [$sFilter] ['sMethod'] ) {
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
                    $aData ['where'] .= " AND ".$this->aColumn [$sFilter] ['sOrder']." LIKE '%$sFilterValue%'";
			    } else {
                    trigger_error("$this->aColumn [{$sFilter}] ['sOrder'] is empty!",E_USER_ERROR);
			    }
			}
		}
		$this->sSql = Base::GetSql ( $sScript, $aData );
	}
	//-----------------------------------------------------------------------------------------------
	private function sortArrayCallback($sA, $sB) {
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
	public function setArray($aData) {
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
}

?>