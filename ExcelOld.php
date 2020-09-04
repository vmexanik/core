<?
/**
 * @author Aleksandr Starovoit
 */

require_once(SERVER_PATH.'/class/core/Base.php');
require_once(SERVER_PATH.'/lib/PHPExcel/PHPExcel.php');

class ExcelOld extends Base {

	var $oExcel;
	//var $oExcelWorkSheet;
	var $aStyle;
	var $aStyleBold;
	var $aStyleRight;
	var $aStyleLeft;
	var $aStyleBorderThickLeft;
	var $aStyleBorderThickRight;
	var $aStyleFormatText;
	var $oWriter;
	var $oDrawing;
	//-----------------------------------------------------------------------------------------------
	public function Excel(){
		$this->oExcel = new PHPExcel();
		$this->SetActiveSheetIndex();
		//$this->GetActiveSheet();
		$this->aStyle=	array(
		'font' => array('bold' => true),
		'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,),
		'borders' => array( 'top' => array( 'style' => PHPExcel_Style_Border::BORDER_THIN )),
		'fill' => array( 'type' => PHPExcel_Style_Fill::FILL_SOLID,
		'rotation'   => 90,
		'startcolor' => array('argb' => 'FFA0A0A0'),
		'endcolor'   => array('argb' => 'FFFFFFFF')
		)
		);
		$this->aStyleBold['font']['bold']=true;
		$this->aStyleLeft['alignment']=array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
		$this->aStyleRight['alignment']=array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$this->aStyleBorderThickLeft['borders']['left']=array( 'style' => PHPExcel_Style_Border::BORDER_THICK);
		$this->aStyleBorderThickRight['borders']['right']=array( 'style' => PHPExcel_Style_Border::BORDER_THICK);
		$this->aStyleFormatText['numberformat']=array('code'=> PHPExcel_Style_NumberFormat::FORMAT_TEXT);
		$this->aStyleFormatNumber00['numberformat']=array('code'=> PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
	}
	//-----------------------------------------------------------------------------------------------
	public function SetActiveSheetIndex($i=0){
		$this->oExcel->setActiveSheetIndex($i);
	}
	//-----------------------------------------------------------------------------------------------
	public function GetActiveSheet(){
		return $this->oExcel->getActiveSheet();
	}
    //-----------------------------------------------------------------------------------------------
    /*
     * show expand button at top
     */
	public function SetShowSummaryBelow($sState=true){
		$this->oExcel->getActiveSheet()->setShowSummaryBelow($sState);
	}
    //-----------------------------------------------------------------------------------------------
    /*
     * Add row to collapsed/expanded group 
     */
	public function SetRowOutlineLevel($iRow,$iLevel=1,$bVisible=false){
		$this->oExcel->GetActiveSheet()->GetRowDimension($iRow)->setOutlineLevel($iLevel);
        $this->oExcel->GetActiveSheet()->GetRowDimension($iRow)->setVisible($bVisible);
	}
	//-----------------------------------------------------------------------------------------------
	public function SetCellValue($sCordinate, $sValue, $sCharset="", $aStyle=array(), $iRound=""){
		if ($sCharset) $sValue=iconv($sCharset,'utf-8', $sValue);
		if ($aStyle) $this->DuplicateStyleArray($sCordinate,"",$aStyle);
		if ($iRound!="") $sValue=round($sValue,$iRound);

		$this->oExcel->getActiveSheet()->setCellValue($sCordinate,$sValue);
	}
	//-----------------------------------------------------------------------------------------------
	public function SetCellValueExplicit($sCordinate, $sValue, $sCharset="", $aStyle=array(), $iRound="",
		$pType=PHPExcel_Cell_DataType::TYPE_STRING){
		if ($sCharset) $sValue=iconv($sCharset,'utf-8', $sValue);
		if ($aStyle) $this->DuplicateStyleArray($sCordinate,"",$aStyle);
		if ($iRound!="") $sValue=round($sValue,$iRound);

		$this->oExcel->getActiveSheet()->setCellValueExplicit($sCordinate,$sValue,$pType);
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Set header value
	 *
	 * @param array $aHeader array(sCordinate=>sValue)
	 */
	public function SetHeaderValue($aHeader=array(), $iRow, $bUtfEncode=true){
		if ($aHeader) foreach ($aHeader as $sKey => $aValue) {
			if ($bUtfEncode) {
				$this->SetCellValue($sKey.$iRow, String::UtfEncode(Language::GetMessage('XLS_'.$aValue['value'])));
			} else {
				$this->SetCellValue($sKey.$iRow, Language::GetMessage('XLS_'.$aValue['value']));
			}

		}
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Set autosize
	 *
	 * @param array $aHeader array(sCordinate=>sValue)
	 */
	public function SetAutoSize($aHeader){
		if ($aHeader) foreach ($aHeader as $sKey => $aValue) {
			if ($aValue['autosize']==true) {
				$this->oExcel->getActiveSheet()->getColumnDimension($sKey)->setAutoSize(true);
			}
		}
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Set style for range
	 *
	 * @param string $sRange (A1:B4) or coordinat A1 
	 * @param string $sColor
	 * @param array $aStyle to change default
	 */
	public function DuplicateStyleArray($sRange="", $sColor="", $aStyle=array()){
		if ($sColor)
		{
			$aStyle['fill']['type']=PHPExcel_Style_Fill::FILL_SOLID;
			$aStyle['fill']['startcolor']=array('argb' => $sColor);
		}

		if (!$aStyle) $aStyle=$this->aStyle;

		//this method has now been deprecated since getStyle() has started to accept a cell range.
		//$this->oExcel->getActiveSheet()->duplicateStyleArray($aStyle,$sRange);  
		$this->oExcel->getActiveSheet()->getStyle($sRange)->applyFromArray($aStyle);
	}
	//-----------------------------------------------------------------------------------------------
	public function SetTitle($sTitle="") {
		if (!$sTitle) {
			$sTitle="sheet1";
		}
		$this->oExcel->getActiveSheet()->setTitle($sTitle);
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Write in 2003 excel format
	 *
	 * @param string $sFileNameFull
	 * @param boolean $bOutput
	 */
	public function WriterExcel5($sFileNameFull,$bOutput=false){
		if ($bOutput) {
			require_once(SERVER_PATH.'/lib/PHPExcel/PHPExcel/IOFactory.php');

			header('Content-Type: application/vnd.ms-excel');
			header('Content-Disposition: attachment;filename="'.basename($sFileNameFull).'"');
			header('Cache-Control: max-age=0');

			$this->oWriter = PHPExcel_IOFactory::createWriter($this->oExcel, 'Excel5');
			$this->oWriter->save('php://output');
			die();
		} else {
			require_once(SERVER_PATH.'/lib/PHPExcel/PHPExcel/Writer/Excel5.php');
			$this->oWriter = new PHPExcel_Writer_Excel5($this->oExcel);
			$this->oWriter->save($sFileNameFull);
		}
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Write in 2007 excel format
	 *
	 * @param string $sFileNameFull
	 */
	public function WriterExcel7($sFileNameFull){
		require_once(SERVER_PATH.'/lib/PHPExcel/PHPExcel/Writer/Excel2007.php');
		$this->oWriter = new PHPExcel_Writer_Excel2007($this->oExcel);
		$this->oWriter->save($sFileNameFull);
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Set font for list
	 *
	 * @param string $sName
	 * @param integer $iSize 
	 */
	public function SetDefaultFont($sName, $iSize=0) {
		$this->oExcel->getDefaultStyle()->getFont()->setName($sName);
		if ($iSize) $this->oExcel->getDefaultStyle()->getFont()->setSize($iSize);
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 *  Read excel 98-2003 and put it in to class
	 *
	 * @param string $sFileNameFull path to excel
	 */
	public function ReadExcel5($sFileNameFull, $bReadDataOnly=false, $bCacheOnMemory=true ) {
		require_once(SERVER_PATH.'/lib/PHPExcel/PHPExcel/Reader/Excel5.php');
		
		if(!$bCacheOnMemory){
			$cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
			$cacheSettings = array( 'memoryCacheSize' => '8GB');
			PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);
		}
		
		$oReader= new PHPExcel_Reader_Excel5();
		if ($bReadDataOnly) $oReader->setReadDataOnly(true);
		$this->oExcel=$oReader->load($sFileNameFull);
		unset($oReader);
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 *  Read excel 2007 and put it in to class
	 *
	 * @param string $sFileNameFull path to excel
	 */
	public function ReadExcel7($sFileNameFull, $bReadDataOnly=false, $bCacheOnMemory=true ) {
		require_once(SERVER_PATH.'/lib/PHPExcel/PHPExcel/Reader/Excel2007.php');
		
		if(!$bCacheOnMemory){
			$cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
			$cacheSettings = array( 'memoryCacheSize' => '8GB');
			PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);
		}elseif($bCacheOnMemory==2){
			$cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_in_memory_gzip;
			PHPExcel_Settings::setCacheStorageMethod($cacheMethod);
		}
		
		$oReader= new PHPExcel_Reader_Excel2007();
		if ($bReadDataOnly) $oReader->setReadDataOnly(true);
		$this->oExcel=$oReader->load($sFileNameFull);
		unset($oReader);
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Add break for page for print
	 *
	 * @param string $sCordinate to break
	 */
	public function SetPrintBreak($sCordinate) {
		$this->oExcel->getActiveSheet()->setBreak( $sCordinate , PHPExcel_Worksheet::BREAK_ROW );
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Set rows to repeat for each page for print
	 *
	 * @param integer $iRowStart
	 * @param integer $iRowEnd
	 */
	public function SetRowToRepeat($iRowStart,$iRowEnd) {
		$this->oExcel->getActiveSheet()->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd($iRowStart, $iRowEnd);
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Merge cells 
	 *
	 * @param string $sRange A1:A10
	 */
	public function MergeCell($sRange) {
		$this->oExcel->getActiveSheet()->mergeCells($sRange);
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Write to pdf format
	 *
	 * @param string $sFileNameFull
	 * @param boolean $bOutput
	 */
	public function WriterPdf($sFileNameFull,$bOutput=false){
		if ($bOutput) {
			require_once(SERVER_PATH.'/lib/PHPExcel/PHPExcel/IOFactory.php');

			header('Content-Type: application/pdf');
			header('Content-Disposition: attachment;filename="'.basename($sFileNameFull).'"');
			header('Cache-Control: max-age=0');

			$this->oWriter = PHPExcel_IOFactory::createWriter($this->oExcel, 'Pdf');
			$this->oWriter->save('php://output');
			die();
		} else {

			require_once(SERVER_PATH.'/lib/PHPExcel/PHPExcel/IOFactory.php');
			$this->oWriter = PHPExcel_IOFactory::createWriter($this->oExcel, 'PDF');
			$this->oWriter->setSheetIndex(0);
			$this->oWriter->save($sFileNameFull);
		}
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Write to CSV format
	 *
	 * @param string $sFileNameFull
	 * @param boolean $bOutput
	 */
	public function WriterCSV($sFileNameFull,$bOutput=false, $iSheet=0){
		require_once(SERVER_PATH.'/lib/PHPExcel/PHPExcel/IOFactory.php');
		$this->oWriter = PHPExcel_IOFactory::createWriter($this->oExcel, 'CSV');
		$this->oWriter->setSheetIndex($iSheet);
		$this->oWriter->setDelimiter(';');
	
		if ($bOutput) {
			header('Content-Type: application/csv');
			header('Content-Disposition: attachment;filename="'.basename($sFileNameFull).'"');
			header('Cache-Control: max-age=0');
			$this->oWriter->save('php://output');
			die();
		} else {
			$this->oWriter->save($sFileNameFull);
		}
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Write to html format
	 *
	 * @param string $sFileNameFull
	 */
	public function WriterHtml($sFileNameFull){
		require_once(SERVER_PATH.'/lib/PHPExcel/PHPExcel/IOFactory.php');
		$this->oWriter = PHPExcel_IOFactory::createWriter($this->oExcel, 'HTML');
		$this->oWriter->setSheetIndex(0);
		$this->oWriter->save($sFileNameFull);
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Get Row Height
	 *
	 * @param ineger $iRow
	 * @return double
	 */
	public function GetRowHeight($iRow) {
		return $this->oExcel->getActiveSheet()->getRowDimension($iRow)->getRowHeight();
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Setting a row�s height
	 *
	 * @param integer $iRow
	 * @param double $dSize
	 */
	public function SetRowHeight($iRow,$dSize) {
		$this->oExcel->getActiveSheet()->getRowDimension($iRow)->setRowHeight($dSize);
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Setting a column width
	 *
	 * @param string $sCol
	 * @param double $dSize
	 */
	public function SetColWidth($sCol,$dSize) {
		$this->oExcel->getActiveSheet()->getColumnDimension($sCol)->setWidth($dSize);
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Get array of data by coordinate
	 *
	 * @return array (coordinate=>array(value=>..., row=>..., col=>...))
	 */
	public function GetReadData() {
		foreach ($this->oExcel->getActiveSheet()->getRowIterator() as $row) {
			$cellIterator = $row->getCellIterator();
			$cellIterator->setIterateOnlyExistingCells(false); // This loops all cells,

			foreach ($cellIterator as $cell) {
				$coordinate=$cell->getColumn() .$cell->getRow();
				if ($cell->getValue())
				{
					$aDataTpl[$coordinate]['value']=$cell->getValue();
					$aDataTpl[$coordinate]['row']=$cell->getRow();
					$aDataTpl[$coordinate]['col']=$cell->getColumn();
				}
			}
		}
		return $aDataTpl;
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Get array of data as excel_reader
	 *
	 * @return array (1=>array(1=>value, 2=>value, ...), 2=>array(1=>value, 2=>value, ...))
	 */
	public function GetSpreadsheetData() {
		foreach ($this->oExcel->getActiveSheet()->getRowIterator() as $row) {
			$cellIterator = $row->getCellIterator();
			$cellIterator->setIterateOnlyExistingCells(false); // This loops all cells,
			foreach ($cellIterator as $cell) {
				if ($cell->getValue())
				{
					$aDataTpl[$cell->getRow()][$cell->columnIndexFromString($cell->getColumn())]=$cell->getValue();
				}
			}
		}
		return $aDataTpl;
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Get array of merge cell by coordinate
	 *
	 * @return array
	 */
	public function GetMergeCell() {
		$aMergeTmp=$this->oExcel->getActiveSheet()->getMergeCells();
		foreach ($aMergeTmp as $sKey => $sValue) {
			preg_match('/(([^\d]+)(\d+)):([^\d]+)(\d+)/',$sValue,$m);
			$aMerge[$m[1]]=$m;
		}
		return $aMerge;
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Create a new worksheet, after the default sheet
	 *
	 */
	public function CreateSheet() {
		return $this->oExcel->createSheet();
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Remove a worksheet
	 *
	 */
	public function RemoveSheet($iSheet) {
		return $this->oExcel->removeSheetByIndex($iSheet);
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Set protection of excel file
	 *
	 * @param string $sPassword
	 */
	public function SetProtectionFile($sPassword="12345") {
		$this->oExcel->getSecurity()->setLockWindows(true);
		$this->oExcel->getSecurity()->setLockStructure(true);
		$this->oExcel->getSecurity()->setWorkbookPassword($sPassword);

		$this->oExcel->getActiveSheet()->getProtection()->setPassword($sPassword);
		$this->oExcel->getActiveSheet()->getProtection()->setSheet(true);
		$this->oExcel->getActiveSheet()->getProtection()->setSort(true);
		$this->oExcel->getActiveSheet()->getProtection()->setInsertColumns(true);
		$this->oExcel->getActiveSheet()->getProtection()->setInsertRows(true);
		$this->oExcel->getActiveSheet()->getProtection()->setFormatCells(true);
		$this->oExcel->getActiveSheet()->getProtection()->setFormatColumns(true);
		$this->oExcel->getActiveSheet()->getProtection()->setFormatRows(true);
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Set image $sFullPath to $sCordinate 
	 *
	 * @param string $sCordinate
	 * @param string $sFullPath to image
	 */
	public function SetDrawing($sCordinate,$sFullPath,$iHeight=0){
		require_once(SERVER_PATH.'/lib/PHPExcel/PHPExcel/Worksheet/Drawing.php');
		$this->oDrawing = new PHPExcel_Worksheet_Drawing();
		$this->oDrawing->setPath($sFullPath);
		if ($iHeight)
			$this->oDrawing->setHeight($iHeight);
		$this->oDrawing->setCoordinates($sCordinate);
		$this->oDrawing->setWorksheet($this->oExcel->getActiveSheet());

	}
	//-----------------------------------------------------------------------------------------------
	public function freezePane($sCell){
	    $this->oExcel->getActiveSheet()->freezePane($sCell);
	}
	//-----------------------------------------------------------------------------------------------
	public function RemoveRow($iRow) {
		$this->oExcel->getActiveSheet()->RemoveRow($iRow);
	}
	//-----------------------------------------------------------------------------------------------
	public function CopyRange($sRangeFrom,$sCellTo,$bInsertRow=true) {
		$aArray=$this->oExcel->getActiveSheet()->rangeToArray($sRangeFrom);
		if($bInsertRow)	{
			list($rangeStart, $rangeEnd) = PHPExcel_Cell::rangeBoundaries($sCellTo);
			$this->oExcel->getActiveSheet()->insertNewRowBefore($rangeStart[1]);
		}
		$this->oExcel->getActiveSheet()->fromArray($aArray, null, $sCellTo);
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Set wrap text 
	 *
	 * @param string $sRange A1:A10
	 */	
	public function SetWrapText($sRange) {
		$this->oExcel->getActiveSheet()->getStyle($sRange)->getAlignment()->setWrapText(true);
	}
	//-----------------------------------------------------------------------------------------------
	public function GetNextColumn($sColumn=""){
		if ($sColumn){
			$sAlphabet="ABCDEFGHIJKLMNOPQRSTUVWXYZ";
			
			$sFirstChar=substr($sColumn,0,strlen($sColumn)-1);
			$sLastChar=substr($sColumn,strlen($sColumn)-1,1);
			
			$iNow=strpos($sAlphabet,$sLastChar);
			
			$sNewLastChar=substr($sAlphabet,$iNow+1,1);
			if ($sNewLastChar){
				$sNewFirstChar=$sFirstChar;
			}else{
				$sNewFirstChar=$this->GetNextColumn($sFirstChar);
				$sNewLastChar="A";
			}
			$sRerurn=$sNewFirstChar.$sNewLastChar;
			
		}else {
			$sRerurn="A";
		}
		
		return $sRerurn;
	}
	
	//-----------------------------------------------------------------------------------------------
	public function SetCreateReader() {
		$objReader = PHPExcel_IOFactory::createReader('Excel2007');
		return $objReader;
	}
	//-----------------------------------------------------------------------------------------------
	public function CreateObjectExcel2007() {
		$oObject = new PHPExcel_Reader_Excel2007();
		return $oObject;
	}
}


/**  Define a Read Filter class implementing PHPExcel_Reader_IReadFilter  */
class chunkReadFilter implements PHPExcel_Reader_IReadFilter {
	private $_startRow = 0;
	
	private $_endRow = 0;
	
	public function __construct() {

	}
	
	public function readCell($column, $row, $worksheetName = '') {
		//  Only read the heading row, and the rows that were configured in the constructor
		if ($row >= $this->_startRow && $row < $this->_endRow) {
			return true;
		}
		return false;
	}
	/**  Set the list of rows that we want to read  */
	public function setRows($startRow, $chunkSize) {
		$this->_startRow= $startRow;
		$this->_endRow = $startRow + $chunkSize;
	}
	
}

?>