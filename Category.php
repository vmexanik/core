<?
/**
 * @author Irina Miroshnichenko
 */
require_once(SERVER_PATH.'/class/core/Admin.php');
class Category extends Admin {
	/**Max count of subcategories for parent*/
	private $iMaxSubCategorie = 2;
	
	//-----------------------------------------------------------------------------------------------
	function __construct() {
		Base::$tpl->assign('iMaxSubCategorie', $this->iMaxSubCategorie);
		$this->sBeforeAddMethod='BeforeAdd';
		$this->sAct="apply";

		require_once(SERVER_PATH.'/class/core/Dbtree.php');
		$this->tree = new  dbtree($this->sTableName);
		$this->tree->res = Base::$db;
	}
	//-----------------------------------------------------------------------------------------------
	public function Index() {
		$this->PreIndex();
		require_once(SERVER_PATH.'/class/core/Table.php');
		$oTable=new Table();
		$oTable->bCheckVisible=false;
		$oTable->sType='array';
		$oTable->aDataFoTable=$this->tree->FullTree(true);
		
		$this->SetAddingColumn();
		$this->initLocaleGlobal ();
		$oTable->aColumn=array(
			''=>array('sTitle'=>'#'),
			'name'=>array('sTitle'=>'Name'),
			'code'=>array('sTitle'=>'Code'),
			'visible'=>array('sTitle'=>'Status'),
			'language'=>array ('sTitle' => 'Lang' ),
			'action'=>array(),
		);
		$this->SetDefaultTable ($oTable);
		Base::$sText .= $oTable->getTable();
		$this->AfterIndex ();
	}
	//-----------------------------------------------------------------------------------------------
	public function SetAddingColumn(){
		
	}
	//-----------------------------------------------------------------------------------------------
	function initLocaleGlobal() {
		Base::$tpl->assign('aLocaleGlobal', Base::$db->getAll("select * from language order by num"));
	}
	//-----------------------------------------------------------------------------------------------
	public function BeforeAdd() {
		if (Base::$aRequest['id']!="") {
			$aScope = array('change'=>Language::getMessage('change on'));
			Base::$aRequest['idtree']=Base::$aRequest['id'];
		} else {
			$aScope = array(
			'after'=>Language::getMessage('after'),
			'sub'=>Language::getMessage('sub')
			);
		}
		Base::$tpl->assign('aReq', Base::$aRequest);
		Base::$tpl->assign('aScope',$aScope);
		Base::$tpl->assign('aTree', $this->tree->FullFoSelect(true));

	}
	//-----------------------------------------------------------------------------------------------
	public function Move () {
		$this->tree->MoveItem(Base::$aRequest['id'],Base::$aRequest['to']);
		$this->Index();
	}
	//-----------------------------------------------------------------------------------------------
	public function Apply() {
		if (!$this->CheckField()) {
			$this->Message('MT_ERROR', Language::getMessage('Please fill out all fields'));
			return;
		} else {
			if (Base::$aRequest['data']['id']) {
				$sMode='UPDATE';
				$sWhere=$this->sTableId."='".Base::$aRequest['data']['id']."'";
				Base::$db->AutoExecute($this->sTableName, Base::$aRequest['data'], $sMode, $sWhere);
				if (Base::$aRequest['data']['id']!=Base::$aRequest['idtree']) {
					$this->tree->ChangePosition(Base::$aRequest['data']['id'],Base::$aRequest['idtree']);
				}
			} else {
				// check maybe we are adding the first element
				$iCount = Base::$db->GetOne ('select count(*) from ' . $this->sTableName);
				if($iCount<2){
					$this->tree->Clear();
					$aResult =  Base::$db->GetRow ('select * from ' . $this->sTableName);
					Base::$aRequest['idtree'] = $aResult['id'];
					$this->tree->Insert(Base::$aRequest['idtree'],'',Base::$aRequest['data']);
				}else{
					if (in_array(Base::$aRequest['scope'],array('after'))) {
						$this->tree->InsertNear(Base::$aRequest['idtree'],'',Base::$aRequest['data']);
					} elseif (in_array(Base::$aRequest['scope'],array('sub'))){
						$this->tree->Insert(Base::$aRequest['idtree'],'',Base::$aRequest['data']);
					}
				}
			}

		}
		$this->AdminRedirect($this->sAction);
	}
	
	//-----------------------------------------------------------------------------------------------
	public function Delete() {
		if (!Base::$aRequest['id'] && !Base::$aRequest['row_check'])
			return;
			
		if (is_array(Base::$aRequest['row_check']) && count(Base::$aRequest['row_check'])>0) foreach (Base::$aRequest['row_check'] as $value){
			$this->tree->DeleteAll($value);
		}else if(Base::$aRequest['id']) {
 			$this->tree->DeleteAll(Base::$aRequest['id']);
		}
		$this->AdminRedirect($this->sAction);
	}
}
?>