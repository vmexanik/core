<?

require_once(SERVER_PATH.'/class/core/Admin.php');
class ADirectorySite extends Admin {

	//-----------------------------------------------------------------------------------------------
	function ADirectorySite() {
		$this->sTableName='directory_site';
		$this->sTablePrefix='ds';
		$this->sAction='directory_site';
		$this->sWinHead=Language::getDMessage('DirectorySites');
		$this->sPath=Language::GetDMessage('>>Directory >');
		$this->aCheckField=array('name','url','description');
		$this->sBeforeAddMethod='BeforeAdd';
		if(file_exists(SERVER_PATH."/template/mpanel/dtree_new.tpl")) {
		    //use /template/mpanel/***
		} else {
		    $this->sAddonPath='addon/';
		}
		$this->sSqlPath='CoreDirectorySite';
		$this->Admin();
	}
	//-----------------------------------------------------------------------------------------------
	public function Index() {
		$this->PreIndex();

		require_once(SERVER_PATH.'/class/core/Table.php');
		$oTable=new Table();
		$oTable->aColumn=array(
		'id'=>array('sTitle'=>'Id','sOrder'=>'ds.id'),
		'directory_site_category_name'=>array('sTitle'=>'DirectorySiteCategoryName','sOrder'=>'directory_site_category_name'),
		'name'=>array('sTitle'=>'Name','sOrder'=>'ds.name'),
		'url'=>array('sTitle'=>'Url','sOrder'=>'ds.url'),
		'visible'=>array('sTitle'=>'Visible','sOrder'=>'visible'),
		'direct_link'=>array('sTitle'=>'DirectLink','sOrder'=>'ds.direct_link'),
		'action'=>array(),
		);
		$this->SetDefaultTable($oTable);
		Base::$sText.=$oTable->getTable();

		$this->AfterIndex();
	}
	//-----------------------------------------------------------------------------------------------
	public function BeforeAdd() {

		$aRecordSet=Base::$db->getAll("select * from directory_site_category as dsc where 1=1");
		foreach ($aRecordSet as $aItem) {$aDirectorySiteCategory[$aItem["id"]]=$aItem["name"];};

		Base::$tpl->assign('aDirectorySiteCategory',$aDirectorySiteCategory);

		$aDirectLink = array(
		            0=>Language::getDMessage('No'),
		            1=>Language::getDMessage('Yes')
		                     );
		Base::$tpl->assign('aDirectLink',$aDirectLink);
	}
	//-----------------------------------------------------------------------------------------------
	public function CheckField() {

		if (!strtotime(Base::$aRequest['data']['creation_time'])) {
			Base::$aRequest['data']['creation_time']=time();
		} else {
				Base::$aRequest['data']['creation_time']=strtotime(Base::$aRequest['data']['creation_time']);
		        };

		return Admin::CheckField();
	}
	//-----------------------------------------------------------------------------------------------
}
?>