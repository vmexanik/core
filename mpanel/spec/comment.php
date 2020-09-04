<?

/**
 * @author Mikhail Starovoyt
 */

class AComment extends Admin
{
	//-----------------------------------------------------------------------------------------------
	function __construct()
	{
		$this->sTableName = 'comment';
		$this->sTablePrefix = 'c';
		$this->sAction = 'comment';
		$this->sWinHead = Language::getDMessage('Comments');
		$this->sPath = Language::GetDMessage('>>Content >' );
		$this->aCheckField = array('section', 'name', 'content');
		if(file_exists(SERVER_PATH."/template/mpanel/dtree_new.tpl")) {
		    //use /template/mpanel/***
		} else {
		    $this->sAddonPath='addon/';
		}
		$this->sSqlPath='CoreComment';
		$this->Admin();
	}
	//-----------------------------------------------------------------------------------------------
	public function Index()
	{
		$this->PreIndex ();

		$oTable = new Table();
		$oTable->aColumn = array (
		'id' => array ('sTitle' => 'Id', 'sOrder' => 'c.id' ),
		'section' => array ('sTitle' => 'Section', 'sOrder' => 'c.section' ),
		'ref_id' => array ('sTitle' => 'RefId', 'sOrder' => 'c.ref_id' ),
		'name' => array ('sTitle' => 'CommentName', 'sOrder' => 'c.name' ),
		'visible' => array ('sTitle' => 'Visible', 'sOrder' => 'c.visible' ),
		'content' => array ('sTitle' => 'Content' , 'sOrder' => 'c.content'),
		'action' => array());
		$this->SetDefaultTable($oTable);
		Base::$sText.=$oTable->getTable();

		$this->AfterIndex();
	}
	//-----------------------------------------------------------------------------------------------
	public function Approve()
	{
		if (is_array ( Base::$aRequest ['row_check'] )) {
			Db::Execute("update comment set is_approved='1' where id in(".implode(',', Base::$aRequest ['row_check']).")");
		}
		$this->AdminRedirect($this->sAction);
	}
	//-----------------------------------------------------------------------------------------------
}
?>