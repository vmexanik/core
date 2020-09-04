<?

/**
 * @author Mikhail Starovoyt
 *
 */
class ACurrency extends Admin {

	//-----------------------------------------------------------------------------------------------
	function __construct()
	{
		$this->sTableName='currency';
		$this->sTablePrefix='c';
		$this->sAction='currency';
		$this->sWinHead=Language::getDMessage('Currencies');
		$this->sPath=Language::GetDMessage('>>Configuration >');
		$this->aCheckField=array('code','name','value');
		if(file_exists(SERVER_PATH."/template/mpanel/dtree_new.tpl")) {
		    //use /template/mpanel/***
		} else {
		    $this->sAddonPath='addon/';
		}
		$this->sSqlPath='CoreCurrency';
		$this->Admin();
	}
	//-----------------------------------------------------------------------------------------------
	public function Index()
	{
		$this->PreIndex();

		$this->initLocaleGlobal();

		$oTable=new Table();
		$oTable->aColumn=array(
		'id'=>array('sTitle'=>'Id','sOrder'=>'c.id'),
		'code'=>array('sTitle'=>'Code','sOrder'=>'c.code'),
		'name'=>array('sTitle'=>'CurrencyName','sOrder'=>'c.name'),
		'symbol'=>array('sTitle'=>'Symbol','sOrder'=>'c.symbol'),
		'image'=>array('sTitle'=>'Image','sOrder'=>'c.image'),
		'value'=>array('sTitle'=>'Value','sOrder'=>'c.value'),
		'visible'=>array('sTitle'=>'Visible','sOrder'=>'c.visible'),
		'num'=>array('sTitle'=>'Num','sOrder'=>'c.num'),
		'language'=>array('sTitle' => 'Lang'),
		'action'=>array(),
		);
		$this->SetDefaultTable($oTable);
		Base::$sText.=$oTable->getTable();

		$this->AfterIndex();
	}
	//-----------------------------------------------------------------------------------------------
	public function AfterApply($aBeforeRow,$aAfterRow)
	{
		//$aData=Base::$aRequest ['data'];
		if ($aAfterRow['value'] && $aBeforeRow['value']!=$aAfterRow['value']) {
			$aData['section']='change_currency';
			$aData['created_by']=$_SESSION['admin']['login'];
			$aData['description']=Language::GetDMessage('Currency change')."
			{$aAfterRow['code']}, <b>{$aBeforeRow['value']}</b>=><b>{$aAfterRow['value']}</b>";
			Base::$db->AutoExecute('log_finance',$aData,'INSERT');
		}
	}
	//-----------------------------------------------------------------------------------------------
}
?>