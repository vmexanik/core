<?

require_once(SERVER_PATH.'/class/core/Admin.php');
class AConstant extends Admin {

	//-----------------------------------------------------------------------------------------------
	function AConstant() {
		$this->sTableName='constant';
		$this->sTablePrefix='c';
		$this->sAction='constant';
		$this->sWinHead=Language::getDMessage('Constants');
		$this->sPath=Language::GetDMessage('>>Configuration >');
		$this->aCheckField=array('key_','value');
		if(file_exists(SERVER_PATH."/template/mpanel/dtree_new.tpl")) {
		    //use /template/mpanel/***
		} else {
		    $this->sAddonPath='addon/';
		}
		$this->sSqlPath='CoreConstant';
		$this->Admin();
	}
	//-----------------------------------------------------------------------------------------------
	public function Index() {
		$this->PreIndex();

		require_once(SERVER_PATH.'/class/core/Table.php');
		$oTable=new Table();
		$oTable->aColumn=array(
		'id'=>array('sTitle'=>'Id','sOrder'=>'c.id'),
		'key_'=>array('sTitle'=>'Key','sOrder'=>'c.key_'),
		'value'=>array('sTitle'=>'Value','sOrder'=>'c.value'),
		'description'=>array('sTitle'=>'Description','sOrder'=>'c.description'),
		'action'=>array(),
		);
		$this->SetDefaultTable($oTable);
		Base::$sText.=$oTable->getTable();

		$this->AfterIndex();
	}
	//-----------------------------------------------------------------------------------------------
}
?>