<?
/**
 * @author Aleksandr Strovoyt
 */

require_once(SERVER_PATH.'/class/core/Base.php');
class Dbtree extends Base  {
	/**
    * Detailed errors of a class (for the programmer and log-files)
    * array('error type (1 - fatal (write log), 2 - fatal (write log, send email)',
    * 'error info string', 'function', 'info 1', 'info 2').
    *
    * @var array
    */
	var $ERRORS = array();

	/**
    * The information on a error for the user
    * array('string (error information)').
    *
    * @var array
    */
	var $ERRORS_MES = array();

	/**
    * Name of the table where tree is stored.
    *
    * @var string
    */
	var $table;

	/**
    * Unique number of node.
    *
    * @var bigint
    */
	var $table_id;

	/**
    * @var integer
    */
	var $table_left;

	/**
    * @var integer
    */
	var $table_right;

	/**
    * Level of nesting.
    *
    * @var integer
    */
	var $table_level;

	/**
    * DB resource object.
    *
    * @var object
    */
	var $res;


	/**
    * The class constructor: initializes dbtree variables.
    *
    * @param string $table Name of the table
    * @param string $prefix A prefix for fields of the table(the example, mytree_id. 'mytree' is prefix)
    * @param object $db
    * @return object
    */
	function Dbtree($table="", $prefix="") 
	{
		$this->table = $table;
		$prefix=="" ? $this->table_id = 'id' : $this->table_id = $prefix . '_id';
		$this->table_left = $prefix . '_left';
		$this->table_right = $prefix . '_right';
		$this->table_level = $prefix . '_level';
		unset($prefix, $table);
	}

	/**
	CREATE TABLE `cat__retro` (
	  `id` int(11) NOT NULL default '0',
	  `_left` int(11) NOT NULL default 0,
	  `_right` int(11) NOT NULL default 0,
	  `_level` int(11) NOT NULL default 0,
	  `name` varchar(255)  default '',
	  `descr` varchar(255) default '',
	  `url` varchar(255)  default '',
	  `visible` tinyint(3) default '0',
	  UNIQUE KEY `id` (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=cp1251;
    
	CREATE TABLE `cat__retro_seq` (
  		`id` int(11) default NULL
	) ENGINE=MyISAM DEFAULT CHARSET=cp1251;
	replace into `cat__retro_seq` (id) values (0);
	
	* Sets initial parameters of a tree and creates root of tree
    * ATTENTION, all previous values in table are destroyed.
    *
    * @param array $data Contains parameters for additional fields of a tree (if is): 'filed name' => 'importance'
    * @return bool TRUE if successful, FALSE otherwise.
    */
	function Clear($data = array()) 
	{
		$sql = 'TRUNCATE ' . $this->table;
		$res = Base::$db->Execute($sql);
		if (FALSE === $res) {
			$this->ERRORS[] = array(2, 'SQL query error.', __FILE__ . '::' . __CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__, 'SQL QUERY: ' . $sql, 'SQL ERROR: ' . Base::$db->ErrorMsg());
			$this->ERRORS_MES[] = extension_loaded('gettext') ? _('internal_error') : 'internal_error';
			return FALSE;
		}
		$sql = 'DELETE FROM ' . $this->table;
		$res = Base::$db->Execute($sql);
		if (FALSE === $res) {
			$this->ERRORS[] = array(2, 'SQL query error.', __FILE__ . '::' . __CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__, 'SQL QUERY: ' . $sql, 'SQL ERROR: ' . Base::$db->ErrorMsg());
			$this->ERRORS_MES[] = extension_loaded('gettext') ? _('internal_error') : 'internal_error';
			return FALSE;
		}
		if (!empty($data)) {
			$fld_names = implode(', ', array_keys($data)) . ', ';
			$fld_values = '\'' . implode('\', \'', array_values($data)) . '\', ';
		}
		$fld_names .= $this->table_left . ', ' . $this->table_right . ', ' . $this->table_level;
		$fld_values .= '1, 2, 0';
		$id = Base::$db->GenID($this->table . '_seq', 1);
		$sql = 'INSERT INTO ' . $this->table . ' (' . $this->table_id . ', ' . $fld_names . ') VALUES (' . $id . ', ' . $fld_values . ')';
		$res = Base::$db->Execute($sql);
		if (FALSE === $res) {
			$this->ERRORS[] = array(2, 'SQL query error.', __FILE__ . '::' . __CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__, 'SQL QUERY: ' . $sql, 'SQL ERROR: ' . Base::$db->ErrorMsg());
			$this->ERRORS_MES[] = extension_loaded('gettext') ? _('internal_error') : 'internal_error';
			return FALSE;
		}
		return TRUE;
	}

	/**
    * Receives left, right and level for unit with number id.
    *
    * @param integer $section_id Unique section id
    * @param integer $cache Recordset is cached for $cache microseconds
    * @return array - left, right, level
    */
	function GetNodeInfo($section_id, $cache = FALSE) 
	{
		$sql = 'SELECT ' . $this->table_left . ', ' . $this->table_right . ', ' . $this->table_level . ' FROM ' . $this->table . ' WHERE ' . $this->table_id . ' = ' . (int)$section_id;
		if (FALSE === DB_CACHE || FALSE === $cache || 0 == (int)$cache) {
			$res = Base::$db->Execute($sql);
		} else {
			$res = Base::$db->CacheExecute((int)$cache, $sql);
		}
		if (FALSE === $res) {
			$this->ERRORS[] = array(2, 'SQL query error.', __FILE__ . '::' . __CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__, 'SQL QUERY: ' . $sql, 'SQL ERROR: ' . Base::$db->ErrorMsg());
			$this->ERRORS_MES[] = extension_loaded('gettext') ? _('internal_error') : 'internal_error';
			return FALSE;
		}
		if (0 == $res->RecordCount()) {
			$this->ERRORS_MES[] = extension_loaded('gettext') ? _('no_element_in_tree') : 'no_element_in_tree';
			return FALSE;
		}
		$data = $res->FetchRow();
		unset($res);
		return array($data[$this->table_left], $data[$this->table_right], $data[$this->table_level]);
	}

	/**
    * Receives parent left, right and level for unit with number $id.
    *
    * @param integer $section_id
    * @param integer $cache Recordset is cached for $cache microseconds
    * @param array $condition Array structure: array('and' => array('id = 0', 'id2 >= 3'), 'or' => array('sec = \'www\'', 'sec2 <> \'erere\'')), etc where array key - condition (AND, OR, etc), value - condition string
    * @return array - left, right, level
    */
	function GetParentInfo($section_id, $condition = '', $cache = FALSE) 
	{
		$node_info = $this->GetNodeInfo($section_id);
		if (FALSE === $node_info) {
			return FALSE;
		}
		list($leftId, $rightId, $level) = $node_info;
		$level--;
		if (!empty($condition)) {
			$condition = $this->_PrepareCondition($condition);
		}
		$sql = 'SELECT * FROM ' . $this->table
		. ' WHERE ' . $this->table_left . ' < ' . $leftId
		. ' AND ' . $this->table_right . ' > ' . $rightId
		. ' AND ' . $this->table_level . ' = ' . $level
		. $condition
		. ' ORDER BY ' . $this->table_left;
		if (FALSE === DB_CACHE || FALSE === $cache || 0 == (int)$cache) {
			$res = Base::$db->Execute($sql);
		} else {
			$res = Base::$db->CacheExecute((int)$cache, $sql);
		}
		if (FALSE === $res) {
			$this->ERRORS[] = array(2, 'SQL query error.', __FILE__ . '::' . __CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__, 'SQL QUERY: ' . $sql, 'SQL ERROR: ' . Base::$db->ErrorMsg());
			$this->ERRORS_MES[] = extension_loaded('gettext') ? _('internal_error') : 'internal_error';
			return FALSE;
		}
		return $res->FetchRow();
	}


	/**
    * Add a new element in the tree to element with number $section_id.
    *
    * @param integer $section_id Number of a parental element
    * @param array $condition Array structure: array('and' => array('id = 0', 'id2 >= 3'), 'or' => array('sec = \'www\'', 'sec2 <> \'erere\'')), etc where array key - condition (AND, OR, etc), value - condition string
    * @param array $data Contains parameters for additional fields of a tree (if is): array('filed name' => 'importance', etc)
    * @return integer Inserted element id
    */
	function Insert($section_id, $condition = '', $data = array()) 
	{
		$node_info = $this->GetNodeInfo($section_id);
		if (FALSE === $node_info) {
			return FALSE;
		}
		list($leftId, $rightId, $level) = $node_info;
		$data[$this->table_left] = $rightId;
		$data[$this->table_right] = ($rightId + 1);
		$data[$this->table_level] = ($level + 1);
		if (!empty($condition)) {
			$condition = $this->_PrepareCondition($condition);
		}
		$sql = 'UPDATE ' . $this->table . ' SET '
		. $this->table_left . '=CASE WHEN ' . $this->table_left . '>' . $rightId . ' THEN ' . $this->table_left . '+2 ELSE ' . $this->table_left . ' END, '
		. $this->table_right . '=CASE WHEN ' . $this->table_right . '>=' . $rightId . ' THEN ' . $this->table_right . '+2 ELSE ' . $this->table_right . ' END '
		. 'WHERE ' . $this->table_right . '>=' . $rightId;
		$sql .= $condition;
		Base::$db->StartTrans();
		$res = Base::$db->Execute($sql);
		if (FALSE === $res) {
			$this->ERRORS[] = array(2, 'SQL query error.', __FILE__ . '::' . __CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__, 'SQL QUERY: ' . $sql, 'SQL ERROR: ' . Base::$db->ErrorMsg());
			$this->ERRORS_MES[] = extension_loaded('gettext') ? _('internal_error') : 'internal_error';
			Base::$db->FailTrans();
			return FALSE;
		}
		$sql = 'SELECT * FROM ' . $this->table . ' WHERE ' . $this->table_id . ' = -1';
		$res = Base::$db->Execute($sql);
		if (FALSE === $res) {
			$this->ERRORS[] = array(2, 'SQL query error', __FILE__ . '::' . __CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__, 'SQL QUERY: ' . $sql, 'SQL ERROR: ' . Base::$db->ErrorMsg());
			$this->ERRORS_MES[] = extension_loaded('gettext') ? _('internal_error') : 'internal_error';
			Base::$db->FailTrans();
			return FALSE;
		}
		$data[$this->table_id] = Base::$db->GenID($this->table . '_seq', 2);
		$sql = Base::$db->GetInsertSQL($res, $data);
		if (!empty($sql)) {
			$res = Base::$db->Execute($sql);
			if (FALSE === $res) {
				$this->ERRORS[] = array(2, 'SQL query error', __FILE__ . '::' . __CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__, 'SQL QUERY: ' . $sql, 'SQL ERROR: ' . Base::$db->ErrorMsg());
				$this->ERRORS_MES[] = extension_loaded('gettext') ? _('internal_error') : 'internal_error';
				Base::$db->FailTrans();
				return FALSE;
			}
		}
		Base::$db->CompleteTrans();
		return $data[$this->table_id];
	}

	/**
    * Add a new element in the tree near element with number id.
    *
    * @param integer $ID Number of a parental element
    * @param array $condition Array structure: array('and' => array('id = 0', 'id2 >= 3'), 'or' => array('sec = \'www\'', 'sec2 <> \'erere\'')), etc where array key - condition (AND, OR, etc), value - condition string
    * @param array $data Contains parameters for additional fields of a tree (if is): array('filed name' => 'importance', etc)
    * @return integer Inserted element id
    */
	function InsertNear($ID, $condition = '', $data = array()) 
	{
		$node_info = $this->GetNodeInfo($ID);
		if (FALSE === $node_info) {
			return FALSE;
		}
		list($leftId, $rightId, $level) = $node_info;
		$data[$this->table_left] = ($rightId + 1);
		$data[$this->table_right] = ($rightId + 2);
		$data[$this->table_level] = ($level);
		if (!empty($condition)) {
			$condition = $this->_PrepareCondition($condition);
		}
		$sql = 'UPDATE ' . $this->table . ' SET '
		. $this->table_left . ' = CASE WHEN ' . $this->table_left . ' > ' . $rightId . ' THEN ' . $this->table_left . ' + 2 ELSE ' . $this->table_left . ' END, '
		. $this->table_right . ' = CASE WHEN ' . $this->table_right . '> ' . $rightId . ' THEN ' . $this->table_right . ' + 2 ELSE ' . $this->table_right . ' END '
		. 'WHERE ' . $this->table_right . ' > ' . $rightId;
		$sql .= $condition;
		Base::$db->StartTrans();
		$res = Base::$db->Execute($sql);
		if (FALSE === $res) {
			$this->ERRORS[] = array(2, 'SQL query error.', __FILE__ . '::' . __CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__, 'SQL QUERY: ' . $sql, 'SQL ERROR: ' . Base::$db->ErrorMsg());
			$this->ERRORS_MES[] = extension_loaded('gettext') ? _('internal_error') : 'internal_error';
			Base::$db->FailTrans();
			return FALSE;
		}
		$sql = 'SELECT * FROM ' . $this->table . ' WHERE ' . $this->table_id . ' = -1';
		$res = Base::$db->Execute($sql);
		if (FALSE === $res) {
			$this->ERRORS[] = array(2, 'SQL query error', __FILE__ . '::' . __CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__, 'SQL QUERY: ' . $sql, 'SQL ERROR: ' . Base::$db->ErrorMsg());
			$this->ERRORS_MES[] = extension_loaded('gettext') ? _('internal_error') : 'internal_error';
			Base::$db->FailTrans();
			return FALSE;
		}
		$data[$this->table_id] = Base::$db->GenID($this->table . '_seq', 2);
		$sql = Base::$db->GetInsertSQL($res, $data);
		if (!empty($sql)) {
			$res = Base::$db->Execute($sql);
			if (FALSE === $res) {
				$this->ERRORS[] = array(2, 'SQL query error', __FILE__ . '::' . __CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__, 'SQL QUERY: ' . $sql, 'SQL ERROR: ' . Base::$db->ErrorMsg());
				$this->ERRORS_MES[] = extension_loaded('gettext') ? _('internal_error') : 'internal_error';
				Base::$db->FailTrans();
				return FALSE;
			}
		}
		Base::$db->CompleteTrans();
		return $data[$this->table_id];
	}

	/**
    * Assigns a node with all its children to another parent.
    *
    * @param integer $ID node ID
    * @param integer $newParentId ID of new parent node
    * @param array $condition Array structure: array('and' => array('id = 0', 'id2 >= 3'), 'or' => array('sec = \'www\'', 'sec2 <> \'erere\'')), etc where array key - condition (AND, OR, etc), value - condition string
    * @return bool TRUE if successful, FALSE otherwise.
    */
	function MoveAll($ID, $newParentId, $condition = '') 
	{
		$node_info = $this->GetNodeInfo($ID);
		if (FALSE === $node_info) {
			return FALSE;
		}
		list($leftId, $rightId, $level) = $node_info;
		$node_info = $this->GetNodeInfo($newParentId);
		if (FALSE === $node_info) {
			return FALSE;
		}
		list($leftIdP, $rightIdP, $levelP) = $node_info;
		if ($ID == $newParentId || $leftId == $leftIdP || ($leftIdP >= $leftId && $leftIdP <= $rightId) || ($level == $levelP+1 && $leftId > $leftIdP && $rightId < $rightIdP)) {
			$this->ERRORS_MES[] = extension_loaded('gettext') ? _('cant_move_tree') : 'cant_move_tree';
			return FALSE;
		}
		if (!empty($condition)) {
			$condition = $this->_PrepareCondition($condition);
		}
		if ($leftIdP < $leftId && $rightIdP > $rightId && $levelP < $level - 1) {
			$sql = 'UPDATE ' . $this->table . ' SET '
			. $this->table_level . ' = CASE WHEN ' . $this->table_left . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->table_level.sprintf('%+d', -($level-1)+$levelP) . ' ELSE ' . $this->table_level . ' END, '
			. $this->table_right . ' = CASE WHEN ' . $this->table_right . ' BETWEEN ' . ($rightId+1) . ' AND ' . ($rightIdP-1) . ' THEN ' . $this->table_right . '-' . ($rightId-$leftId+1) . ' '
			. 'WHEN ' . $this->table_left . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->table_right . '+' . ((($rightIdP-$rightId-$level+$levelP)/2)*2+$level-$levelP-1) . ' ELSE ' . $this->table_right . ' END, '
			. $this->table_left . ' = CASE WHEN ' . $this->table_left . ' BETWEEN ' . ($rightId+1) . ' AND ' . ($rightIdP-1) . ' THEN ' . $this->table_left . '-' . ($rightId-$leftId+1) . ' '
			. 'WHEN ' . $this->table_left . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->table_left . '+' . ((($rightIdP-$rightId-$level+$levelP)/2)*2+$level-$levelP-1) . ' ELSE ' . $this->table_left . ' END '
			. 'WHERE ' . $this->table_left . ' BETWEEN ' . ($leftIdP+1) . ' AND ' . ($rightIdP-1);
		} elseif ($leftIdP < $leftId) {
			$sql = 'UPDATE ' . $this->table . ' SET '
			. $this->table_level . ' = CASE WHEN ' . $this->table_left . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->table_level.sprintf('%+d', -($level-1)+$levelP) . ' ELSE ' . $this->table_level . ' END, '
			. $this->table_left . ' = CASE WHEN ' . $this->table_left . ' BETWEEN ' . $rightIdP . ' AND ' . ($leftId-1) . ' THEN ' . $this->table_left . '+' . ($rightId-$leftId+1) . ' '
			. 'WHEN ' . $this->table_left . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->table_left . '-' . ($leftId-$rightIdP) . ' ELSE ' . $this->table_left . ' END, '
			. $this->table_right . ' = CASE WHEN ' . $this->table_right . ' BETWEEN ' . $rightIdP . ' AND ' . $leftId . ' THEN ' . $this->table_right . '+' . ($rightId-$leftId+1) . ' '
			. 'WHEN ' . $this->table_right . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->table_right . '-' . ($leftId-$rightIdP) . ' ELSE ' . $this->table_right . ' END '
			. 'WHERE (' . $this->table_left . ' BETWEEN ' . $leftIdP . ' AND ' . $rightId. ' '
			. 'OR ' . $this->table_right . ' BETWEEN ' . $leftIdP . ' AND ' . $rightId . ')';
		} else {
			$sql = 'UPDATE ' . $this->table . ' SET '
			. $this->table_level . ' = CASE WHEN ' . $this->table_left . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->table_level.sprintf('%+d', -($level-1)+$levelP) . ' ELSE ' . $this->table_level . ' END, '
			. $this->table_left . ' = CASE WHEN ' . $this->table_left . ' BETWEEN ' . $rightId . ' AND ' . $rightIdP . ' THEN ' . $this->table_left . '-' . ($rightId-$leftId+1) . ' '
			. 'WHEN ' . $this->table_left . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->table_left . '+' . ($rightIdP-1-$rightId) . ' ELSE ' . $this->table_left . ' END, '
			. $this->table_right . ' = CASE WHEN ' . $this->table_right . ' BETWEEN ' . ($rightId+1) . ' AND ' . ($rightIdP-1) . ' THEN ' . $this->table_right . '-' . ($rightId-$leftId+1) . ' '
			. 'WHEN ' . $this->table_right . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->table_right . '+' . ($rightIdP-1-$rightId) . ' ELSE ' . $this->table_right . ' END '
			. 'WHERE (' . $this->table_left . ' BETWEEN ' . $leftId . ' AND ' . $rightIdP . ' '
			. 'OR ' . $this->table_right . ' BETWEEN ' . $leftId . ' AND ' . $rightIdP . ')';
		}
		$sql .= $condition;
		Base::$db->StartTrans();
		$res = Base::$db->Execute($sql);
		if (FALSE === $res) {
			$this->ERRORS[] = array(2, 'SQL query error.', __FILE__ . '::' . __CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__, 'SQL QUERY: ' . $sql, 'SQL ERROR: ' . Base::$db->ErrorMsg());
			$this->ERRORS_MES[] = extension_loaded('gettext') ? _('internal_error') : 'internal_error';
			Base::$db->FailTrans();
			return FALSE;
		}
		Base::$db->CompleteTrans();
		return TRUE;
	}

	/**
    * Change items position.
    *
    * @param integer $id1 first item ID
    * @param integer $id2 second item ID
    * @return bool TRUE if successful, FALSE otherwise.
    */
	function ChangePosition($id1, $id2) 
	{
		$node_info = $this->GetNodeInfo($id1);
		if (FALSE === $node_info) {
			return FALSE;
		}
		list($leftId1, $rightId1, $level1) = $node_info;
		$node_info = $this->GetNodeInfo($id2);
		if (FALSE === $node_info) {
			return FALSE;
		}
		list($leftId2, $rightId2, $level2) = $node_info;
		$sql = 'UPDATE ' . $this->table . ' SET '
		. $this->table_left . ' = ' . $leftId2 .', '
		. $this->table_right . ' = ' . $rightId2 .', '
		. $this->table_level . ' = ' . $level2 .' '
		. 'WHERE ' . $this->table_id . ' = ' . (int)$id1;
		Base::$db->StartTrans();
		$res = Base::$db->Execute($sql);
		if (FALSE === $res) {
			$this->ERRORS[] = array(2, 'SQL query error.', __FILE__ . '::' . __CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__, 'SQL QUERY: ' . $sql, 'SQL ERROR: ' . Base::$db->ErrorMsg());
			$this->ERRORS_MES[] = extension_loaded('gettext') ? _('internal_error') : 'internal_error';
			Base::$db->FailTrans();
			return FALSE;
		}
		$sql = 'UPDATE ' . $this->table . ' SET '
		. $this->table_left . ' = ' . $leftId1 .', '
		. $this->table_right . ' = ' . $rightId1 .', '
		. $this->table_level . ' = ' . $level1 .' '
		. 'WHERE ' . $this->table_id . ' = ' . (int)$id2;
		$res = Base::$db->Execute($sql);
		if (FALSE === $res) {
			$this->ERRORS[] = array(2, 'SQL query error.', __FILE__ . '::' . __CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__, 'SQL QUERY: ' . $sql, 'SQL ERROR: ' . Base::$db->ErrorMsg());
			$this->ERRORS_MES[] = extension_loaded('gettext') ? _('internal_error') : 'internal_error';
			Base::$db->FailTrans();
			return FALSE;
		}
		Base::$db->CompleteTrans();
		return TRUE;
	}

	/**
    * Swapping nodes within the same level and limits of one parent with all its children: $id1 placed before or after $id2.
    *
    * @param integer $id1 first item ID
    * @param integer $id2 second item ID
    * @param string $position 'before' or 'after' $id2
    * @param array $condition Array structure: array('and' => array('id = 0', 'id2 >= 3'), 'or' => array('sec = \'www\'', 'sec2 <> \'erere\'')), etc where array key - condition (AND, OR, etc), value - condition string
    * @return bool TRUE if successful, FALSE otherwise.
    */
	function ChangePositionAll($id1, $id2, $position = 'after', $condition = '') 
	{
		$node_info = $this->GetNodeInfo($id1);
		if (FALSE === $node_info) {
			$this->ERRORS_MES[] = extension_loaded('gettext') ? _('cant_change_position') : 'cant_change_position';
			return FALSE;
		}
		list($leftId1, $rightId1, $level1) = $node_info;
		$node_info = $this->GetNodeInfo($id2);
		if (FALSE === $node_info) {
			$this->ERRORS_MES[] = extension_loaded('gettext') ? _('cant_change_position') : 'cant_change_position';
			return FALSE;
		}
		list($leftId2, $rightId2, $level2) = $node_info;
		if ($level1 <> $level2) {
			$this->ERRORS_MES[] = extension_loaded('gettext') ? _('cant_change_position') : 'cant_change_position';
			return FALSE;
		}
		if ('before' == $position) {
			if ($leftId1 > $leftId2) {
				$sql = 'UPDATE ' . $this->table . ' SET '
				. $this->table_right . ' = CASE WHEN ' . $this->table_left . ' BETWEEN ' . $leftId1 . ' AND ' . $rightId1 . ' THEN ' . $this->table_right . ' - ' . ($leftId1 - $leftId2) . ' '
				. 'WHEN ' . $this->table_left . ' BETWEEN ' . $leftId2 . ' AND ' . ($leftId1 - 1) . ' THEN ' . $this->table_right . ' +  ' . ($rightId1 - $leftId1 + 1) . ' ELSE ' . $this->table_right . ' END, '
				. $this->table_left . ' = CASE WHEN ' . $this->table_left . ' BETWEEN ' . $leftId1 . ' AND ' . $rightId1 . ' THEN ' . $this->table_left . ' - ' . ($leftId1 - $leftId2) . ' '
				. 'WHEN ' . $this->table_left . ' BETWEEN ' . $leftId2 . ' AND ' . ($leftId1 - 1) . ' THEN ' . $this->table_left . ' + ' . ($rightId1 - $leftId1 + 1) . ' ELSE ' . $this->table_left . ' END '
				. 'WHERE ' . $this->table_left . ' BETWEEN ' . $leftId2 . ' AND ' . $rightId1;
			} else {
				$sql = 'UPDATE ' . $this->table . ' SET '
				. $this->table_right . ' = CASE WHEN ' . $this->table_left . ' BETWEEN ' . $leftId1 . ' AND ' . $rightId1 . ' THEN ' . $this->table_right . ' + ' . (($leftId2 - $leftId1) - ($rightId1 - $leftId1 + 1)) . ' '
				. 'WHEN ' . $this->table_left . ' BETWEEN ' . ($rightId1 + 1) . ' AND ' . ($leftId2 - 1) . ' THEN ' . $this->table_right . ' - ' . (($rightId1 - $leftId1 + 1)) . ' ELSE ' . $this->table_right . ' END, '
				. $this->table_left . ' = CASE WHEN ' . $this->table_left . ' BETWEEN ' . $leftId1 . ' AND ' . $rightId1 . ' THEN ' . $this->table_left . ' + ' . (($leftId2 - $leftId1) - ($rightId1 - $leftId1 + 1)) . ' '
				. 'WHEN ' . $this->table_left . ' BETWEEN ' . ($rightId1 + 1) . ' AND ' . ($leftId2 - 1) . ' THEN ' . $this->table_left . ' - ' . ($rightId1 - $leftId1 + 1) . ' ELSE ' . $this->table_left . ' END '
				. 'WHERE ' . $this->table_left . ' BETWEEN ' . $leftId1 . ' AND ' . ($leftId2 - 1);
			}
		}
		if ('after' == $position) {
			if ($leftId1 > $leftId2) {
				$sql = 'UPDATE ' . $this->table . ' SET '
				. $this->table_right . ' = CASE WHEN ' . $this->table_left . ' BETWEEN ' . $leftId1 . ' AND ' . $rightId1 . ' THEN ' . $this->table_right . ' - ' . ($leftId1 - $leftId2 - ($rightId2 - $leftId2 + 1)) . ' '
				. 'WHEN ' . $this->table_left . ' BETWEEN ' . ($rightId2 + 1) . ' AND ' . ($leftId1 - 1) . ' THEN ' . $this->table_right . ' +  ' . ($rightId1 - $leftId1 + 1) . ' ELSE ' . $this->table_right . ' END, '
				. $this->table_left . ' = CASE WHEN ' . $this->table_left . ' BETWEEN ' . $leftId1 . ' AND ' . $rightId1 . ' THEN ' . $this->table_left . ' - ' . ($leftId1 - $leftId2 - ($rightId2 - $leftId2 + 1)) . ' '
				. 'WHEN ' . $this->table_left . ' BETWEEN ' . ($rightId2 + 1) . ' AND ' . ($leftId1 - 1) . ' THEN ' . $this->table_left . ' + ' . ($rightId1 - $leftId1 + 1) . ' ELSE ' . $this->table_left . ' END '
				. 'WHERE ' . $this->table_left . ' BETWEEN ' . ($rightId2 + 1) . ' AND ' . $rightId1;
			} else {
				$sql = 'UPDATE ' . $this->table . ' SET '
				. $this->table_right . ' = CASE WHEN ' . $this->table_left . ' BETWEEN ' . $leftId1 . ' AND ' . $rightId1 . ' THEN ' . $this->table_right . ' + ' . ($rightId2 - $rightId1) . ' '
				. 'WHEN ' . $this->table_left . ' BETWEEN ' . ($rightId1 + 1) . ' AND ' . $rightId2 . ' THEN ' . $this->table_right . ' - ' . (($rightId1 - $leftId1 + 1)) . ' ELSE ' . $this->table_right . ' END, '
				. $this->table_left . ' = CASE WHEN ' . $this->table_left . ' BETWEEN ' . $leftId1 . ' AND ' . $rightId1 . ' THEN ' . $this->table_left . ' + ' . ($rightId2 - $rightId1) . ' '
				. 'WHEN ' . $this->table_left . ' BETWEEN ' . ($rightId1 + 1) . ' AND ' . $rightId2 . ' THEN ' . $this->table_left . ' - ' . ($rightId1 - $leftId1 + 1) . ' ELSE ' . $this->table_left . ' END '
				. 'WHERE ' . $this->table_left . ' BETWEEN ' . $leftId1 . ' AND ' . $rightId2;
			}
		}
		if (!empty($condition)) {
			$condition = $this->_PrepareCondition($condition);
		}
		$sql .= $condition;
		Base::$db->StartTrans();
		$res = Base::$db->Execute($sql);
		if (FALSE === $res) {
			$this->ERRORS[] = array(2, 'SQL query error.', __FILE__ . '::' . __CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__, 'SQL QUERY: ' . $sql, 'SQL ERROR: ' . Base::$db->ErrorMsg());
			$this->ERRORS_MES[] = extension_loaded('gettext') ? _('internal_error') : 'internal_error';
			Base::$db->FailTrans();
			return FALSE;
		}
		Base::$db->CompleteTrans();
		return TRUE;
	}

	/**
    * Delete element with number $id from the tree wihtout deleting it's children.
    *
    * @param integer $ID Number of element
    * @param array $condition Array structure: array('and' => array('id = 0', 'id2 >= 3'), 'or' => array('sec = \'www\'', 'sec2 <> \'erere\'')), etc where array key - condition (AND, OR, etc), value - condition string
    * @return bool TRUE if successful, FALSE otherwise.
    */
	function Delete($ID, $condition = '') 
	{
		$node_info = $this->GetNodeInfo($ID);
		if (FALSE === $node_info) {
			return FALSE;
		}
		list($leftId, $rightId) = $node_info;
		if (!empty($condition)) {
			$condition = $this->_PrepareCondition($condition);
		}
		$sql = 'DELETE FROM ' . $this->table . ' WHERE ' . $this->table_id . ' = ' . (int)$ID;
		Base::$db->StartTrans();
		$res = Base::$db->Execute($sql);
		if (FALSE === $res) {
			$this->ERRORS[] = array(2, 'SQL query error.', __FILE__ . '::' . __CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__, 'SQL QUERY: ' . $sql, 'SQL ERROR: ' . Base::$db->ErrorMsg());
			$this->ERRORS_MES[] = extension_loaded('gettext') ? _('internal_error') : 'internal_error';
			Base::$db->FailTrans();
			return FALSE;
		}
		$sql = 'UPDATE ' . $this->table . ' SET '
		. $this->table_level . ' = CASE WHEN ' . $this->table_left . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->table_level . ' - 1 ELSE ' . $this->table_level . ' END, '
		. $this->table_right . ' = CASE WHEN ' . $this->table_right . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->table_right . ' - 1 '
		. 'WHEN ' . $this->table_right . ' > ' . $rightId . ' THEN ' . $this->table_right . ' - 2 ELSE ' . $this->table_right . ' END, '
		. $this->table_left . ' = CASE WHEN ' . $this->table_left . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->table_left . ' - 1 '
		. 'WHEN ' . $this->table_left . ' > ' . $rightId . ' THEN ' . $this->table_left . ' - 2 ELSE ' . $this->table_left . ' END '
		. 'WHERE ' . $this->table_right . ' > ' . $leftId;
		$sql .= $condition;
		$res = Base::$db->Execute($sql);
		if (FALSE === $res) {
			$this->ERRORS[] = array(2, 'SQL query error.', __FILE__ . '::' . __CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__, 'SQL QUERY: ' . $sql, 'SQL ERROR: ' . Base::$db->ErrorMsg());
			$this->ERRORS_MES[] = extension_loaded('gettext') ? _('internal_error') : 'internal_error';
			Base::$db->FailTrans();
			return FALSE;
		}
		Base::$db->CompleteTrans();
		return TRUE;
	}

	/**
    * Delete element with number $ID from the tree and all it childret.
    *
    * @param integer $ID Number of element
    * @param array $condition Array structure: array('and' => array('id = 0', 'id2 >= 3'), 'or' => array('sec = \'www\'', 'sec2 <> \'erere\'')), etc where array key - condition (AND, OR, etc), value - condition string
    * @return bool TRUE if successful, FALSE otherwise.
    */
	function DeleteAll($ID, $condition = '') 
	{
		$node_info = $this->GetNodeInfo($ID);
		if (FALSE === $node_info) {
			return FALSE;
		}
		list($leftId, $rightId) = $node_info;
		if (!empty($condition)) {
			$condition = $this->_PrepareCondition($condition);
		}
		$sql = 'DELETE FROM ' . $this->table . ' WHERE ' . $this->table_left . ' BETWEEN ' . $leftId . ' AND ' . $rightId;
		Base::$db->StartTrans();
		$res = Base::$db->Execute($sql);
		if (FALSE === $res) {
			$this->ERRORS[] = array(2, 'SQL query error.', __FILE__ . '::' . __CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__, 'SQL QUERY: ' . $sql, 'SQL ERROR: ' . Base::$db->ErrorMsg());
			$this->ERRORS_MES[] = extension_loaded('gettext') ? _('internal_error') : 'internal_error';
			Base::$db->FailTrans();
			return FALSE;
		}
		$deltaId = (($rightId - $leftId) + 1);
		$sql = 'UPDATE ' . $this->table . ' SET '
		. $this->table_left . ' = CASE WHEN ' . $this->table_left . ' > ' . $leftId.' THEN ' . $this->table_left . ' - ' . $deltaId . ' ELSE ' . $this->table_left . ' END, '
		. $this->table_right . ' = CASE WHEN ' . $this->table_right . ' > ' . $leftId . ' THEN ' . $this->table_right . ' - ' . $deltaId . ' ELSE ' . $this->table_right . ' END '
		. 'WHERE ' . $this->table_right . ' > ' . $rightId;
		$sql .= $condition;
		$res = Base::$db->Execute($sql);
		if (FALSE === $res) {
			$this->ERRORS[] = array(2, 'SQL query error.', __FILE__ . '::' . __CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__, 'SQL QUERY: ' . $sql, 'SQL ERROR: ' . Base::$db->ErrorMsg());
			$this->ERRORS_MES[] = extension_loaded('gettext') ? _('internal_error') : 'internal_error';
			Base::$db->FailTrans();
			return FALSE;
		}
		Base::$db->CompleteTrans();
		return TRUE;
	}

	/**
    * Returns all elements of the tree sortet by left.
    *
    * @param array $condition Array structure: array('and' => array('id = 0', 'id2 >= 3'), 'or' => array('sec = \'www\'', 'sec2 <> \'erere\'')), etc where array key - condition (AND, OR, etc), value - condition string
    * @param array $fields needed fields (if is): array('filed1 name', 'filed2 name', etc)
    * @param integer $cache Recordset is cached for $cache microseconds
    * @return array needed fields
    */
	function Full($fields, $condition = '', $cache = FALSE) 
	{
		if (!empty($condition)) {
			$condition = $this->_PrepareCondition($condition, TRUE);
		}
		if (is_array($fields)) {
			$fields = implode(', ', $fields);
		} else {
			$fields = '*';
		}
		$sql = 'SELECT ' . $fields . ' FROM ' . $this->table;
		$sql .= $condition;
		$sql .= ' ORDER BY ' . $this->table_left;
		if (FALSE === DB_CACHE || FALSE === $cache || 0 == (int)$cache) {
			$res = Base::$db->Execute($sql);
		} else {
			$res = Base::$db->CacheExecute((int)$cache, $sql);
		}
		if (FALSE === $res) {
			$this->ERRORS[] = array(2, 'SQL query error.', __FILE__ . '::' . __CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__, 'SQL QUERY: ' . $sql, 'SQL ERROR: ' . Base::$db->ErrorMsg());
			$this->ERRORS_MES[] = extension_loaded('gettext') ? _('internal_error') : 'internal_error';
			return FALSE;
		}
		$this->res = $res;
		return TRUE;
	}

	/**
    * Returns all elements of a branch starting from an element with number $ID.
    *
    * @param array $condition Array structure: array('and' => array('id = 0', 'id2 >= 3'), 'or' => array('sec = \'www\'', 'sec2 <> \'erere\'')), etc where array key - condition (AND, OR, etc), value - condition string
    * @param array $fields needed fields (if is): array('filed1 name', 'filed2 name', etc)
    * @param integer $cache Recordset is cached for $cache microseconds
    * @param integer $ID Node unique id
    * @return array - [0] => array(id, left, right, level, additional fields), [1] => array(...), etc.
    */
	function Branch($ID, $fields, $condition = '', $cache = FALSE) 
	{
		if (is_array($fields)) {
			$fields = 'A.' . implode(', A.', $fields);
		} else {
			$fields = 'A.*';
		}
		if (!empty($condition)) {
			$condition = $this->_PrepareCondition($condition, FALSE, 'A.');
		}
		$sql = 'SELECT ' . $fields . ', CASE WHEN A.' . $this->table_left . ' + 1 < A.' . $this->table_right . ' THEN 1 ELSE 0 END AS nflag FROM ' . $this->table . ' A, ' . $this->table . ' B WHERE B.' . $this->table_id . ' = ' . (int)$ID . ' AND A.' . $this->table_left . ' >= B.' . $this->table_left . ' AND A.' . $this->table_right . ' <= B.' . $this->table_right;
		$sql .= $condition;
		$sql .= ' ORDER BY A.' . $this->table_left;
		if (FALSE === DB_CACHE || FALSE === $cache || 0 == (int)$cache) {
			$res = Base::$db->Execute($sql);
		} else {
			$res = Base::$db->CacheExecute((int)$cache, $sql);
		}
		if (FALSE === $res) {
			$this->ERRORS[] = array(2, 'SQL query error.', __FILE__ . '::' . __CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__, 'SQL QUERY: ' . $sql, 'SQL ERROR: ' . Base::$db->ErrorMsg());
			$this->ERRORS_MES[] = extension_loaded('gettext') ? _('internal_error') : 'internal_error';
			return FALSE;
		}
		$this->res = $res;
		return TRUE;
	}

	/**
    * Returns all parents of element with number $ID.
    *
    * @param array $condition Array structure: array('and' => array('id = 0', 'id2 >= 3'), 'or' => array('sec = \'www\'', 'sec2 <> \'erere\'')), etc where array key - condition (AND, OR, etc), value - condition string
    * @param array $fields needed fields (if is): array('filed1 name', 'filed2 name', etc)
    * @param integer $cache Recordset is cached for $cache microseconds
    * @param integer $ID Node unique id
    * @return array - [0] => array(id, left, right, level, additional fields), [1] => array(...), etc.
    */
	function Parents($ID, $fields, $condition = '', $cache = FALSE) 
	{
		if (is_array($fields)) {
			$fields = 'A.' . implode(', A.', $fields);
		} else {
			$fields = 'A.*';
		}
		if (!empty($condition)) {
			$condition = $this->_PrepareCondition($condition, FALSE, 'A.');
		}
		$sql = 'SELECT ' . $fields . ', CASE WHEN A.' . $this->table_left . ' + 1 < A.' . $this->table_right . ' THEN 1 ELSE 0 END AS nflag FROM ' . $this->table . ' A, ' . $this->table . ' B WHERE B.' . $this->table_id . ' = ' . (int)$ID . ' AND B.' . $this->table_left . ' BETWEEN A.' . $this->table_left . ' AND A.' . $this->table_right;
		$sql .= $condition;
		$sql .= ' ORDER BY A.' . $this->table_left;
		if (FALSE === DB_CACHE || FALSE === $cache || 0 == (int)$cache) {
			$res = Base::$db->Execute($sql);
		} else {
			$res = Base::$db->CacheExecute((int)$cache, $sql);
		}
		if (FALSE === $res) {
			$this->ERRORS[] = array(2, 'SQL query error.', __FILE__ . '::' . __CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__, 'SQL QUERY: ' . $sql, 'SQL ERROR: ' . Base::$db->ErrorMsg());
			$this->ERRORS_MES[] = extension_loaded('gettext') ? _('internal_error') : 'internal_error';
			return FALSE;
		}
		$this->res = $res;
		return TRUE;
	}

	/**
    * Returns a slightly opened tree from an element with number $ID.
    *
    * @param array $condition Array structure: array('and' => array('id = 0', 'id2 >= 3'), 'or' => array('sec = \'www\'', 'sec2 <> \'erere\'')), etc where array key - condition (AND, OR, etc), value - condition string
    * @param array $fields needed fields (if is): array('filed1 name', 'filed2 name', etc)
    * @param integer $cache Recordset is cached for $cache microseconds
    * @param integer $ID Node unique id
    * @return array - [0] => array(id, left, right, level, additional fields), [1] => array(...), etc.
    */
	function Ajar($ID, $fields, $condition = '', $cache = FALSE, $sOpenLevel='1') 
	{
		if (is_array($fields)) {
			$fields = 'A.' . implode(', A.', $fields);
		} else {
			$fields = 'A.*';
		}
		$condition1 = '';
		if (!empty($condition)) {
			$condition1 = $this->_PrepareCondition($condition, FALSE, 'B.');
		}
		$sql = 'SELECT A.' . $this->table_left . ', A.' . $this->table_right . ', A.' . $this->table_level . ' FROM ' . $this->table . ' A, ' . $this->table . ' B '
		. 'WHERE B.' . $this->table_id . ' = ' . (int)$ID . ' AND B.' . $this->table_left . ' BETWEEN A.' . $this->table_left . ' AND A.' . $this->table_right;
		$sql .= $condition1;
		$sql .= ' ORDER BY A.' . $this->table_left;
		if (FALSE === DB_CACHE || FALSE === $cache || 0 == (int)$cache) {
			$res = Base::$db->Execute($sql);
		} else {
			$res = Base::$db->CacheExecute((int)$cache, $sql);
		}
		if (FALSE === $res) {
			$this->ERRORS[] = array(2, 'SQL query error.', __FILE__ . '::' . __CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__, 'SQL QUERY: ' . $sql, 'SQL ERROR: ' . Base::$db->ErrorMsg());
			$this->ERRORS_MES[] = extension_loaded('gettext') ? _('internal_error') : 'internal_error';
			return FALSE;
		}
		if (0 == $res->RecordCount()) {
			$this->ERRORS_MES[] = _('no_element_in_tree');
			return FALSE;
		}
		$alen = $res->RecordCount();
		$i = 0;
		if (is_array($fields)) {
			$fields = implode(', ', $fields);
		} else {
			$fields = '*';
		}
		if (!empty($condition)) {
			$condition1 = $this->_PrepareCondition($condition, FALSE);
		}
		$sql = 'SELECT ' . $fields . ' FROM ' . $this->table . ' WHERE (' . $this->table_level . ' in ('.$sOpenLevel.')';
		while ($row = $res->FetchRow()) {
			if ((++$i == $alen) && ($row[$this->table_left] + 1) == $row[$this->table_right]) {
				break;
			}
			$sql .= ' OR (' . $this->table_level . ' = ' . ($row[$this->table_level] + 1)
			. ' AND ' . $this->table_left . ' > ' . $row[$this->table_left]
			. ' AND ' . $this->table_right . ' < ' . $row[$this->table_right] . ')';
		}
		$sql .= ') ' . $condition1;
		$sql .= ' ORDER BY ' . $this->table_left;
		if (FALSE === DB_CACHE || FALSE === $cache || 0 == (int)$cache) {
			$res = Base::$db->Execute($sql);
		} else {
			$res = Base::$db->CacheExecute($cache, $sql);
		}
		if (FALSE === $res) {
			$this->ERRORS[] = array(2, 'SQL query error.', __FILE__ . '::' . __CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__, 'SQL QUERY: ' . $sql, 'SQL ERROR: ' . Base::$db->ErrorMsg());
			$this->ERRORS_MES[] = extension_loaded('gettext') ? _('internal_error') : 'internal_error';
			return FALSE;
		}
		$this->res = $res;
		return TRUE;
	}
	
	/**
    * Returns a slightly opened tree from an element with number $ID.
    *
    * @param array $condition Array structure: array('and' => array('id = 0', 'id2 >= 3'), 'or' => array('sec = \'www\'', 'sec2 <> \'erere\'')), etc where array key - condition (AND, OR, etc), value - condition string
    * @param array $fields needed fields (if is): array('filed1 name', 'filed2 name', etc)
    * @param integer $cache Recordset is cached for $cache microseconds
    * @param integer $ID Node unique id
    * @return array - [0] => array(id, left, right, level, additional fields), [1] => array(...), etc.
    */
	function AjarBranch($ID, $fields, $condition = '', $cache = FALSE, $sOpenLevel='1') 
	{
		if (is_array($fields)) {
			$fields = 'A.' . implode(', A.', $fields);
		} else {
			$fields = 'A.*';
		}
		$condition1 = '';
		if (!empty($condition)) {
			$condition1 = $this->_PrepareCondition($condition, FALSE, 'B.');
		}
		$sql = 'SELECT A.' . $this->table_left . ', A.' . $this->table_right . ', A.' . $this->table_level . ' FROM ' . $this->table . ' A, ' . $this->table . ' B '
		. 'WHERE B.' . $this->table_id . ' = ' . (int)$ID . ' AND B.' . $this->table_left . ' BETWEEN A.' . $this->table_left . ' AND A.' . $this->table_right;
		$sql .= $condition1;
		$sql .= ' ORDER BY A.' . $this->table_left;
		if (FALSE === DB_CACHE || FALSE === $cache || 0 == (int)$cache) {
			$res = Base::$db->Execute($sql);
		} else {
			$res = Base::$db->CacheExecute((int)$cache, $sql);
		}
		if (FALSE === $res) {
			$this->ERRORS[] = array(2, 'SQL query error.', __FILE__ . '::' . __CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__, 'SQL QUERY: ' . $sql, 'SQL ERROR: ' . Base::$db->ErrorMsg());
			$this->ERRORS_MES[] = extension_loaded('gettext') ? _('internal_error') : 'internal_error';
			return FALSE;
		}
		if (0 == $res->RecordCount()) {
			$this->ERRORS_MES[] = _('no_element_in_tree');
			return FALSE;
		}
		$alen = $res->RecordCount();
		$i = 0;
		if (is_array($fields)) {
			$fields = implode(', ', $fields);
		} else {
			$fields = '*';
		}
		if (!empty($condition)) {
			$condition1 = $this->_PrepareCondition($condition, FALSE);
		}
		
		$aParents=$this->GetData('Parents',$ID);
		if ($aParents[1]) {
			$sql = 'SELECT ' . $fields . ' FROM ' . $this->table . ' WHERE (' . $this->table_level . ' in ('.$sOpenLevel.') 
			and ' . $this->table_right . '>=' . $aParents[1][$this->table_left].'
			and ' . $this->table_left . '<=' . $aParents[1][$this->table_right]
			;
		} else {
			$sql = 'SELECT ' . $fields . ' FROM ' . $this->table . ' WHERE (' . $this->table_level . ' in ('.$sOpenLevel.')';
		}
		
		while ($row = $res->FetchRow()) {
			if ((++$i == $alen) && ($row[$this->table_left] + 1) == $row[$this->table_right]) {
				break;
			}
			$sql .= ' OR (' . $this->table_level . ' = ' . ($row[$this->table_level] + 1)
			. ' AND ' . $this->table_left . ' > ' . $row[$this->table_left]
			. ' AND ' . $this->table_right . ' < ' . $row[$this->table_right] . ')';
		}
		$sql .= ') ' . $condition1;
		$sql .= ' ORDER BY ' . $this->table_left;
		if (FALSE === DB_CACHE || FALSE === $cache || 0 == (int)$cache) {
			$res = Base::$db->Execute($sql);
		} else {
			$res = Base::$db->CacheExecute($cache, $sql);
		}
		if (FALSE === $res) {
			$this->ERRORS[] = array(2, 'SQL query error.', __FILE__ . '::' . __CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__, 'SQL QUERY: ' . $sql, 'SQL ERROR: ' . Base::$db->ErrorMsg());
			$this->ERRORS_MES[] = extension_loaded('gettext') ? _('internal_error') : 'internal_error';
			return FALSE;
		}
		$this->res = $res;
		return TRUE;
	}

	/**
    * Returns amount of lines in result.
    *
    * @return integer
    */
	function RecordCount() 
	{
		return $this->res->RecordCount();
	}

	/**
    * Returns the current row.
    *
    * @return array
    */
	function NextRow() 
	{
		return $this->res->FetchRow();
	}

	/**
    * Transform array with conditions to SQL query
    * Array structure:
    * array('and' => array('id = 0', 'id2 >= 3'), 'or' => array('sec = \'www\'', 'sec2 <> \'erere\'')), etc
    * where array key - condition (AND, OR, etc), value - condition string.
    *
    * @param array $condition
    * @param string $prefix
    * @param bool $where - True - yes, flase - not
    * @return string
    */
	function _PrepareCondition($condition, $where = FALSE, $prefix = '') 
	{
		if (!is_array($condition)) {
			return $condition;
		}
		$sql = ' ';
		if (TRUE === $where) {
			$sql .= 'WHERE ' . $prefix;
		}
		$keys = array_keys($condition);
		for ($i = 0;$i < count($keys);$i++) {
			if (FALSE === $where || (TRUE === $where && $i > 0)) {
				$sql .= ' ' . strtoupper($keys[$i]) . ' ' . $prefix;
			}
			$sql .= implode(' ' . strtoupper($keys[$i]) . ' ' . $prefix, $condition[$keys[$i]]);
		}
		return $sql;
	}

	/**
    * Returns the array.
    *
    * @return array
    */
	function GetAllData() 
	{
		return $this->res->GetAll();
	}

	/**
    * Returns the array assoc.
    *
    * @return array
    */
	function GetAssocData() 
	{
		return $this->res->GetAssoc();
	}

	/**
	 * Return array of tree  with or without first spaces
	 *
	 * @param boolen $with_space
	 */

	function FullTree($with_space=false) 
	{
		$this->Full('',array('and'=>array($this->table_level.'>0'))) ;
		if ($with_space) {
			$a=$this->res->GetAll();
			unset($aI);
			$cnt=count($a);
			for ($i=0;$i<$cnt;$i++) {
				$a[$i]['name']=str_repeat('&nbsp;', 6 * ($a[$i]['_level']-1)).$a[$i]['name'];

				//-- postrornie numerovanogo spiska tipa 1.2.1
				isset($aI[$a[$i]['_level']])?$aI[$a[$i]['_level']]++:$aI[$a[$i]['_level']]=1;
				foreach ($aI as $k => $v) {
					if ($k>$a[$i]['_level']) unset($aI[$k]);
				}
				$a[$i]['spisok_num']=implode(".",$aI);
				$a[$i]['spisok_col']=count($aI);
				//-- end

			}
			return $a;
		} else {
			return $this->res->GetAll();
		}
	}

	/**
	 * Return array of tree for tag select with or without first spaces
	 *
	 * @param boolen $with_space
	 */

	function FullFoSelect($with_space=false) 
	{
		if ($with_space) {
			$this->Full('',array('and'=>array($this->table_level.'>0'))) ;
			$a=$this->res->GetAssoc();
			foreach ($a as $k=>$v) {
				$b[$k]=str_repeat('&nbsp;', 6 * ($v['_level']-1)).$v['name'];
			}
			return $b;
		} else {
			$this->Full(array($this->table_id,'name'),array('and'=>array($this->table_level.'>0'))) ;
			return $this->res->GetAssoc();
		}
	}

	/**
	 * Move item with all children
	 *
	 * @param int $id
	 * @param int $to (-1,1)
	 */
	function MoveItem($id,$to=-1) 
	{
		$aCurrent = $this->GetNodeInfo((int)$id);

		$this->Parents((int)$id, array('id'), array('and' => array('_level = ' . ($aCurrent[2] - 1))));
		$aParent=$this->NextRow();

		$this->Branch($aParent['id'], array('id', 'name'), array('and' => array('_level = ' . $aCurrent[2])));
		$aBranch=$this->getAllData();

		foreach ($aBranch as $k => $v) {
			if ($v['id']==$id && isset($aBranch[$k+$to])) {
				$this->ChangePositionAll($id, $aBranch[$k+$to]['id'], $to==1?"after":"before");
				break;
			}
		}
	}

	
	/**
	 * Add space for data
	 *
	 * @param array $a
	 * @return array with add space to name and new field spisok_num spisok_col
	 */

	function AddSpaceItem($a,$iSpace=6 ) 
	{
		unset($aI);
		$cnt=count($a);
		for ($i=0;$i<$cnt;$i++) {
			if ($iSpace * ($a[$i]['_level']-1)>0) 
			$a[$i]['name']=str_repeat('&nbsp;', $iSpace * ($a[$i]['_level']-1)).$a[$i]['name'];

			//-- postrornie numerovanogo spiska tipa 1.2.1
			isset($aI[$a[$i]['_level']])?$aI[$a[$i]['_level']]++:$aI[$a[$i]['_level']]=1;
			foreach ($aI as $k => $v) {
				if ($k>$a[$i]['_level']) unset($aI[$k]);
			}
			$a[$i]['spisok_num']=implode(".",$aI);
			$a[$i]['spisok_col']=count($aI);
			//-- end

		}
		return $a;
	}
	
	/**
	 * Get data from request
	 *
	 * @param string $sType
	 * @param int $id
	 * @param array $fields
	 * @param string $condition
	 * @param boolen $cache
	 * @param int $iSpace
	 * @return array
	 */
	function GetData($sType, $id=1, $fields = '', $condition = '', $cache = false, $iSpace=0, $bAssoc=false, $sOpenLevel='1') 
	{
		switch ($sType) {
			case "Branch":
				$this->Branch($id, $fields , $condition, $cache);
				break;
			
			case "BranchLevel":
				$this->Branch($id, $fields , " and A.".$this->table_level."=B.".$this->table_level."+1 ".$condition, $cache);
				break;	
				
			case "Ajar":
				$this->Ajar($id, $fields , $condition, $cache, $sOpenLevel);
				break;
			
			case "AjarBranch":
				$this->AjarBranch($id, $fields , $condition, $cache, $sOpenLevel);
				break;
			
			case "Parents":
				$this->Parents($id, $fields , $condition, $cache);
				break;
			
			case "Full":	
				$this->Full($fields , $condition, $cache);
				break;
			
			default:
				break;
		}
		
		if ($iSpace) return  $this->AddSpaceItem($this->GetAllData());
		elseif ($bAssoc) {
			$aData=$this->GetAllData();
			foreach ($aData as $aValue) {
				$aDataAssoc[$aValue[$this->table_id]]=$aValue['name'];
			}
			return $aDataAssoc;
		}
		else return $this->GetAllData();
	}
}
?>