<?
require_once(SERVER_PATH.'/class/core/Admin.php');
class ATrash extends Admin {

	//-----------------------------------------------------------------------------------------------
	function ATrash() {
		$this->sTableName='trash';
		$this->sTablePrefix='t';
		$this->sAction='trash';
		$this->sWinHead=Language::getDMessage('Trash Manager');
		if(file_exists(SERVER_PATH."/template/mpanel/dtree_new.tpl")) {
		    //use /template/mpanel/***
		} else {
		    $this->sAddonPath='addon/';
		}
		$this->sSqlPath='CoreTrash';
		$this->Admin();
	}
	//-----------------------------------------------------------------------------------------------
	public function Index() {
		$this->PreIndex();

		require_once(SERVER_PATH.'/class/core/Table.php');
		$oTable=new Table();
		$oTable->aColumn=array(
		'id'=>array('sTitle'=>'Id','sOrder'=>'t.id'),
		'name'=>array('sTitle'=>'Name','sOrder'=>'t.name'),
		'trash_action'=>array('sTitle'=>'Trash action', 'sOrder'=>'t.action'),
		'id_element'=>array('sTitle'=>"Elements' id",'sOrder'=>'t.id_element'),
		'trashed_timestamp'=>array('sTitle'=>'Date','sOrder'=>'t.trashed_timestamp'),
		'size'=>array('sTitle'=>'Size','sOrder'=>'t.size'),
		'action'=>array(),
		);
		$this->SetDefaultTable($oTable);
		Base::$sText.=$oTable->getTable();

		$this->AfterIndex();
	}

	//-----------------------------------------------------------------------------------------------
	public function Restore(){
		$aIds = array();
		if (Base::$aRequest['id']){
			$aIds[] = Base::$aRequest['id'];
		}elseif (Base::$aRequest['row_check'] && is_array(Base::$aRequest['row_check'])){
			$aIds = Base::$aRequest['row_check'];
		}
		foreach ($aIds as $value){
			$aTrash = Base::$db->GetRow(Base::GetSql('CoreTrash',array('id'=>$value)));
			if ($aTrash){
				unset($aData);
				unset($aKeys);
				unset($aValues);
				$sKeys = "";
				$sValues="";
				$aData=unserialize($aTrash['value']);
				if ($aData) foreach ($aData as $key =>$value){
					$aData[$key]=base64_decode($value);
				}
				$aKeys = array_keys ($aData);
				if ($aKeys) foreach ($aKeys as $val){
					$sKeys .= $sKeys == "" ? $val : ",".$val;
				}

				$aValues = array_values ($aData);
				if ($aValues) foreach ($aValues as $val){
					$sValues .= $sValues == "" ? "'".$val."'" : ",'".$val."'";
				}

				$sSql =  "insert into ".$aTrash['action']." (".$sKeys.") values (".$sValues.")";
				$bResult = Base::$db->Execute ($sSql);
				if ($bResult){
					$bResult = Base::$db->Execute ( "delete from ".$this->sTableName." where id=".$aTrash['id']);
				}
			}
		}
		$this->Index();
	}
}
?>