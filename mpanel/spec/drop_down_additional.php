<?

/**
 * @author Mikhail Starovoyt
 *
 */

class ADropDownAdditional extends Admin
{
	//-----------------------------------------------------------------------------------------------
	function __construct()
	{
		$this->sTableName = 'drop_down_additional';
		$this->sTablePrefix = 'dda';
		$this->sAction = 'drop_down_additional';
		$this->sWinHead = Language::getDMessage('drop down additional');
		$this->sPath = Language::GetDMessage('>>Content >');
		$this->aCheckField = array('url');
		$this->aFCKEditors = array('description');
		if(file_exists(SERVER_PATH."/template/mpanel/dtree_new.tpl")) {
		    //use /template/mpanel/***
		} else {
		    $this->sAddonPath='addon/';
		}
		$this->sSqlPath='CoreDropDownAdditional';
		$this->Admin();
	}
	//-----------------------------------------------------------------------------------------------
	public function Index()
	{
		$this->PreIndex ();

		$oTable=new Table();
		$this->initLocaleGlobal ();
		$oTable->aColumn=array(
		'id'=>array('sTitle'=>'Id','sOrder'=>'dda.id'),
		'url'=>array('sTitle'=>'Url','sOrder'=>'dda.url'),
		'title'=>array('sTitle'=>'Title','sOrder'=>'dda.title'),
		'visible'=>array('sTitle'=>'Visible','sOrder'=>'dda.visible'),
		'static_rewrite'=>array('sTitle'=>'static_rewrite','sOrder'=>'dda.static_rewrite'),
		'language' => array ('sTitle'=>'Lang'),
		'action'=>array(),
		);
		$this->SetDefaultTable($oTable);
		Base::$sText.=$oTable->getTable();

		$this->AfterIndex();
	}
	//-----------------------------------------------------------------------------------------------
}
?>