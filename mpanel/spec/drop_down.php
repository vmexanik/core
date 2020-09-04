<?
/**
 * @author Irina Miroshnichenko
 * @author Mikhail Starovoyt
 */
class ADropDown extends Admin
{
	public $iDataLevel=2;

	//-----------------------------------------------------------------------------------------------
	function __construct()
	{
		$this->sTableName = 'drop_down';
		$this->sTablePrefix = 'dd';
		$this->sAction = 'drop_down';
		$this->sWinHead = Language::getDMessage('Dropdown Manager');
		$this->aCheckField=array('name','code');
		$this->sPath = Language::GetDMessage('>>Content >');
		if(file_exists(SERVER_PATH."/template/mpanel/dtree_new.tpl")) {
		    //use /template/mpanel/***
		} else {
		    $this->sAddonPath='addon/';
		}
		$this->sSqlPath='CoreDropDown';
		$this->Admin ();
	}
	//-----------------------------------------------------------------------------------------------
	public function GetPageManagerData($iIdParent = 0, $sNiceNum = "")
	{
		$aRes = array ();
		$sQuery = "SELECT `id`,`id_parent`,`level`,`num`,`name`,`code`,`visible` FROM `drop_down`
		WHERE `id_parent`='$iIdParent' and level<='".$this->iDataLevel."' ORDER BY `num`";
		$oRes = Base::$db->Execute ( $sQuery );
		$j = 1;
		foreach ( $oRes as $iKey => $aRow ) {
			$aRow ['nice_num'] = ($sNiceNum ? "$sNiceNum." : "") . $j;
			$aRes [] = $aRow;
			$aRes = array_merge ( $aRes, $this->GetPageManagerData ( $aRow ['id'], $aRow ['nice_num'] ) );
			$j ++;
		}
		return $aRes;
	}
	//-----------------------------------------------------------------------------------------------
	public function Index()
	{
		$this->PreIndex ();

		$oTable = new Table ( );
		$oTable->aColumn = array ();
		$oTable->aColumn ['num'] = array ('sTitle' => '#' );
		$oTable->aColumn ['name'] = array ('sTitle' => 'Name', 'sOrder' => 'dd.name');
		$oTable->aColumn ['code'] = array ('sTitle' => 'Code', 'sOrder' => 'code');
		$oTable->aColumn ['visible'] = array ('sTitle' => 'Visible', 'sOrder' => 'visible');
		$this->initLocaleGlobal ();
		$oTable->aColumn ['language'] = array ('sTitle' => 'Lang' );
		$oTable->aColumn ['action'] = array ();
		$oTable->setArray ( $this->getPageManagerData () );
		$this->SetDefaultTable ( $oTable );
		Base::$sText .= $oTable->getTable ();

		$this->AfterIndex ();
	}
	//-----------------------------------------------------------------------------------------------
	public function BeforeAddAssign(&$aData)
	{
		if (Base::$aRequest ['add_sub']) {
			$aData ['id_parent'] = Base::$aRequest ['add_sub'];
		} elseif (Base::$aRequest ['add_after']) {
			$aData ['id_parent'] = Base::$aRequest ['add_after'];
			$aData ['num'] = Base::$aRequest ['num'];
		}
		$aParent = $this->getPageManagerData ();
		Base::$tpl->assign ( 'aParent', $aParent );
	}
	//-----------------------------------------------------------------------------------------------
	public function Apply()
	{
		// check for unique 'code' field
		$aData['code'] = Base::$aRequest ['data']['code'];
		$bResult = Base::$db->GetRow(Base::GetSql('CoreDropDown',$aData));
		if (!Base::$aRequest ['data']['id']){
			if ($bResult){
				$this->Message('MT_ERROR', Language::getDMessage('Must be unique field')." ".Language::getDMessage('Code'));
				return;
			}
		}else{
			if ($bResult && $bResult['id'] != Base::$aRequest ['data']['id'])	{
				$this->Message('MT_ERROR', Language::getDMessage('Must be unique field')." ".Language::getDMessage('Code'));
				return;
			}
		}
		parent::Apply();
	}
	//-----------------------------------------------------------------------------------------------
	public function BeforeApply()
	{
		Base::$aRequest ['data'] ['level'] = 1 + $this->getLevel ( Base::$aRequest ['data'] ['id_parent'] );
		if (Base::$aRequest ['data'] ['id'] == Base::$aRequest ['data'] ['id_parent']) {
			unset(Base::$aRequest['data']['id_parent']);
			unset(Base::$aRequest['data']['level']);
		}

		Base::$aRequest ['data'] ['name'] = trim(Base::$aRequest ['data'] ['name']);
		if (Base::$aRequest ['data'] ['code'] == '') {
			Base::$aRequest ['data'] ['code'] = preg_replace ( '/[^a-z0-9]+/', '_',strtolower(Base::$aRequest['data'] ['name']) );
		}
		//Base::$aRequest ['data'] ['num'] = $this->GetItem(Base::$aRequest ['data'] ['num'],Base::$aRequest['data']['id_parent']);
	}

	//-----------------------------------------------------------------------------------------------
	public function AfterApply($aBeforeRow,$aAfterRow)
	{
		//$this->UpdateNum($aAfterRow['level'], '', $aAfterRow['id_parent']);
		
	    //remove cache
	    if(file_exists(SERVER_PATH."/cache/Home/account_menu_customer.cache")) unlink(SERVER_PATH."/cache/Home/account_menu_customer.cache");
	    if(file_exists(SERVER_PATH."/cache/Home/account_menu_manager.cache")) unlink(SERVER_PATH."/cache/Home/account_menu_manager.cache");
	    if(file_exists(SERVER_PATH."/cache/Home/drop_down.cache")) unlink(SERVER_PATH."/cache/Home/drop_down.cache");
	}
	//-----------------------------------------------------------------------------------------------
	public function Edit()
	{
		if (Base::$aRequest ['move_up'] || Base::$aRequest ['move_down'] ){
			$aData = Base::$db->GetRow ("select * from ".$this->sTableName." where id='".Base::$aRequest ['id']."'");
			$iMaxNum = Base::$db->getOne("select max(num) from ".$this->sTableName." where level='".$aData['level']."'");
			$iId = Db::GetOne("select id from ".$this->sTableName." where num='".$aData['num']."' and level='".$aData['level']."'");
			if(Base::$aRequest ['move_down']){
				if ($aData['num'] < $iMaxNum){
					$this->MoveItem($aData['id'], $aData['num'], $aData['level'], true, $aData['id_parent']);
				}
			}elseif($aData['num'] > 1){
				$this->MoveItem($aData['id'], $aData['num'], $aData['level'], false, $aData['id_parent']);
			}
			$this->Index();
		}else{
			parent::Edit();
		}
	}
	//-----------------------------------------------------------------------------------------------
	public function MoveItem($iId, $iNum, $iLevel, $bIsDown, $iIdParent='')
	{
		$aData['num'] = $iNum;
		$iNewNum = $bIsDown ? $iNum + 1 : $iNum - 1;
		Db::AutoExecute($this->sTableName, $aData , "UPDATE", "num='".$iNewNum."' and level='".$iLevel."'
					 and id_parent='".$iIdParent."'");
		$aData['num'] = $iNewNum;
		Db::AutoExecute($this->sTableName, $aData , "UPDATE", "id=".$iId." and level=".$iLevel);// num = +1
	}

	//-----------------------------------------------------------------------------------------------
	public function Delete()
	{
		$aIds = array();
		if (Base::$aRequest['id']){
			$aIds[] = Base::$aRequest['id'];
		}elseif (Base::$aRequest['row_check'] && is_array(Base::$aRequest['row_check'])){
			$aIds = Base::$aRequest['row_check'];
		}
		$this->DeleteItem($aIds);
		$this->AdminRedirect ( $this->sAction );
	}

	//-----------------------------------------------------------------------------------------------
	/**
	 * Recursive delete items
	 *
	 * @param array $aData of ids
	 */
	public function DeleteItem($aData)
	{
		if ($aData && count($aData) > 0) foreach ($aData as $value){
			$aSubData = Base::$db->GetAll(Base::GetSql('CoreDropDown',array('id_parent'=>$value)));
			if($aSubData) foreach ($aSubData as $subKey => $aSubValue){
				$aId[] = $aSubValue['id'];
			}
			if ($aId && count($aId) > 0)
			$this->DeleteItem($aId);

			// delete item and update all nums in this branch
			$aItem = Base::$db->GetRow(Base::GetSql('CoreDropDown',array('id'=>$value)));
			Db::Execute("delete from ".$this->sTableName." where id=".$value."");
			$this->UpdateNum($aItem['level'], '', $aItem['id_parent']);
		}
	}

	//-----------------------------------------------------------------------------------------------
	/**
	 * Update sorted num of elem
	 *
	 * @param int $iLevel - level of item
	 * @param int $iPreferNum - prefer num. Set by the customer
	 * @param int $iIdParent - parent id of current element
	 * @return int $num
	 */
	public function UpdateNum($iLevel, $iPreferNum = '', $iIdParent='')
	{
		$aData['level'] = $iLevel;
		$aData['order'] = 'order by num';
		$aData['id_parent'] = $iIdParent;
		$aItems = Db::GetAll(Base::GetSql('CoreDropDown',$aData));
		$bIsUpdated = false;
		$i = 1;
		if ($aItems) foreach ($aItems as $value){
			//			if( $iPreferNum == $i || 1){
			//				$i++;
			//				$bIsUpdated = true;
			//			}
			$sUpdate.="$i id=".$value['id']."\n <br>";
			Db::AutoExecute('drop_down', array("num"=>$i), 'UPDATE', "id='".$value['id']."'");

			$i++;
		}

		//Base::$oResponse->AddAlert($sUpdate);
		if ($bIsUpdated) return $iPreferNum;
		else return $i;
	}
	//-----------------------------------------------------------------------------------------------
	function getLevel($iIdParent)
	{
		$sQuery = "SELECT `level` FROM `drop_down` WHERE `id`='$iIdParent'";
		return Base::$db->getOne ( $sQuery );
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * If move to down we need inc($iPreferNum) because:
	 * For example: [1 2 3 4 5] we push element from pos 2 to 4. We update all num to [1 2 3 5 6]. Oure 4-th pos is empty.
	 * We change num of oure element to 4 and 2-th pos is empty [1 3 4 5 6]. After apply action we renum oure array
	 * and after that we have sequance of [1 2 3 4 5]. Oure elem set to 3-th position.
	 *
	 * @param unknown_type $iPreferNum
	 * @param unknown_type $iIdParent
	 * @return unknown
	 */
	public function GetItem($iPreferNum, $iIdParent)
	{
		if ($iPreferNum == null) $iPreferNum = 0;
		$iResultNum = $iPreferNum;
		$iMaxNum = Base::$db->getOne ( "SELECT max(num) FROM `drop_down` WHERE `id_parent`=$iIdParent" );

		if (Base::$aRequest['data']['id']){
			$iCurNum = Base::$db->getOne ( "SELECT num FROM `drop_down` WHERE `id`=".Base::$aRequest['data']['id']);
			if ($iPreferNum && $iPreferNum > $iCurNum && Base::$aRequest ['data'] ['level']){
				$iPreferNum = $iPreferNum + 1;
			}
		}
		// add to center
		if ($iPreferNum < $iMaxNum && $iPreferNum > 0){
			$iResultNum = $this->UpdateNum(Base::$aRequest ['data'] ['level'], $iPreferNum, $iIdParent);
		}elseif( $iPreferNum > $iMaxNum + 1 || $iPreferNum == 0){
			$iResultNum = $iMaxNum + 1;
		}
		return $iResultNum;
	}
	//-----------------------------------------------------------------------------------------------
}
?>