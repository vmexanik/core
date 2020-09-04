<?
require_once(SERVER_PATH.'/class/core/Category.php');
class ADirectorySiteCategory extends Category {
	//-----------------------------------------------------------------------------------------------
	function ADirectorySiteCategory() {
		$this->sTableName='directory_site_category';
		$this->sTablePrefix='dsc';
		$this->sAction='directory_site_category';
		$this->sWinHead=Language::GetDMessage('Site Categories');
		$this->sPath=Language::GetDMessage('>>Directory >');
		$this->aCheckField=array(code, name);

		if(file_exists(SERVER_PATH."/template/mpanel/dtree_new.tpl")) {
		    //use /template/mpanel/***
		} else {
		    $this->sAddonPath='addon/';
		}
		$this->sSqlPath='CoreDirectorySiteCategory';

		$this->Admin();

		parent::__construct();
	}
}
?>