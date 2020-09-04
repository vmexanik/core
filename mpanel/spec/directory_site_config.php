<?

require_once(SERVER_PATH.'/class/core/Admin.php');
class ADirectorySiteConfig extends Admin {

	//-----------------------------------------------------------------------------------------------
	function ADirectorySiteConfig() {
		$this->sTableName='directory_site_config';
		$this->sAction='directory_site_config';
		$this->sWinHead=Language::getDMessage('DirectorySiteConfig');
		$this->sPath=Language::GetDMessage('>>Directory >');
		//$this->aCheckField=array('name','code');

		if(file_exists(SERVER_PATH."/template/mpanel/dtree_new.tpl")) {
		    //use /template/mpanel/***
		} else {
		    $this->sAddonPath='addon/';
		}
		//Base::$sSqlScriptPath='/class/core/sql/';

		$this->Admin();
	}
	//-----------------------------------------------------------------------------------------------
	public function Index() {
		$this->PreIndex();

		$aData=Base::$db->getRow("select * from directory_site_config where id='1'");
		Base::$tpl->assign('aData',$aData);
		$aDisplaySelect=array(
			'5'=>'5',
			'10'=>'10',
			'20'=>'20',
			'50'=>'50',
			'100'=>'100',
		);
		Base::$tpl->assign('aDisplaySelect',$aDisplaySelect);
		$aOrderWay=array(
			'asc'=>Language::getDMessage('Asc'),
			'desc'=>Language::getDMessage('Desc'),
		);
		Base::$tpl->assign('aOrderWay',$aOrderWay);
		Base::$sText.=Base::$tpl->fetch('mpanel/'.$this->sAction.'/form_add.tpl');

		$this->AfterIndex();
	}
	//-----------------------------------------------------------------------------------------------
}
?>