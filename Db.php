<?
/**
 * @author Aleksandr Starovoit
 * @author Mikhail Starovoit
 */
class Db extends Base
{
	/**
	 * Execute SQL
	 *
	 * @param sql		SQL statement to execute, or possibly an array holding prepared statement ($sql[0] will hold sql text)
	 * @param [inputarr]	holds the input data to bind to. Null elements will be set to null.
	 * @return 		RecordSet or false
	 */
	static public function Execute($sSql,$aInput=false)
	{
	    if(Base::$db->debug) {
	        $start_time = microtime();
	        	
	        $aResult=Base::$db->Execute($sSql,$aInput);
	        	
	        $end_time = microtime();
	        $elapsed_time = $end_time - $start_time;
	        $trace = debug_backtrace();
    	    Debug::PrintPre("Execution time: ".$elapsed_time." File: ". $trace[0]['file']." on line: ".$trace[0]['line'],false);
	        	
	        return $aResult;
	    } 
	    elseif (Base::GetConstant('db:is_table_logged','0')) {
	    	$aResult=Base::$db->Execute($sSql,$aInput);
			$aTableArray=preg_split("/[\s,;]+/", Base::GetConstant('db:table_logged_array'));
			if ($aTableArray) {
			    $sSql=str_replace(array("\r\n","\r","\n")," ",$sSql);
			    $sTable='';
			    foreach($aTableArray as $sName) {
				if (strpos($sSql," ".$sName." ")!==false || strpos($sSql," '".$sName."' ")!==false 
				    || strpos($sSql," `".$sName."` ")!==false || strpos($sSql,' "'.$sName.'" ')!==false) {
				    $sTable=$sName;
				    break;
				}
			    }
			    if ($sTable) {
				$trace=debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
				$sTrace = date("Y-m-d H:i:s").";\n".$trace[0]['file']." line: ".$trace[0]['line'].";\n uri:".$_SERVER['REQUEST_URI'];
									
				$aLogTable=array(
				'table_name'=>$sTable,
				'mode_name'=>'',
				'description'=>$sSql,
				'where_name'=>'',
				'trace' => $sTrace
				);
				Base::$db->AutoExecute('log_table', $aLogTable);
			    }
			}
			return $aResult;
		} 
	    elseif(!empty(Base::$aGeneralConf['SQLLog'])) {
			$start_time=microtime(1);	
			$aResult=Base::$db->Execute($sSql,$aInput);
			$end_time=microtime(1);
			$elapsed_time=number_format($end_time - $start_time,4);
			$trace=debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
			$sSql=str_replace(array("\r\n","\r","\n")," ",$sSql);
			$sFile=str_replace($_SERVER['DOCUMENT_ROOT'].'/','',str_replace('\\','/',$trace[0]['file']));

			if(preg_match(Base::$aGeneralConf['SQLLogFilter'], $sSql))
				file_put_contents(Base::$aGeneralConf['SQLLog'], date("Y-m-d H:i:s").";".$elapsed_time.";".$sFile.";".$trace[0]['line'].";".$_SERVER['REQUEST_URI'].";".$sSql."\n", FILE_APPEND);

			return $aResult;
	    } else {
	        return Base::$db->Execute($sSql,$aInput);
	    }
	}
	//--------------------------------------------------------------------------------------------------

	/**
	* Execute SQL and get result array ([0]=>array(field=>value, ...),[1]=>array(....))
	*
	* @param sql
	* @return array ([0]=>array(field=>value, ...),[1]=>array(....))
	*/
	static public function GetAll($sSql)
	{
	    if(Base::$db->debug) {
    	    $start_time = microtime();
    	    
    	    $aResult=Base::$db->GetAll($sSql);
    	    
    	    $end_time = microtime();
    	    $elapsed_time = $end_time - $start_time;
    	    
    	    $trace = debug_backtrace();
    	    Debug::PrintPre("Execution time: ".$elapsed_time." File: ". $trace[0]['file']." on line: ".$trace[0]['line'],false);
    	    
    		return $aResult;
	    } elseif(!empty(Base::$aGeneralConf['SQLLog'])) {
			$start_time=microtime(1);	
			$aResult=Base::$db->GetAll($sSql);
			$end_time=microtime(1);
			$elapsed_time=number_format($end_time - $start_time,4);
			$trace=debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
			$sSql=str_replace(array("\r\n","\r","\n")," ",$sSql);
			$sFile=str_replace($_SERVER['DOCUMENT_ROOT'].'/','',str_replace('\\','/',$trace[0]['file']));

			if(preg_match(Base::$aGeneralConf['SQLLogFilter'], $sSql))
				file_put_contents(Base::$aGeneralConf['SQLLog'], date("Y-m-d H:i:s").";".$elapsed_time.";".$sFile.";".$trace[0]['line'].";".$_SERVER['REQUEST_URI'].";".$sSql."\n", FILE_APPEND);

			return $aResult;
	    } else {
			return Base::$db->GetAll($sSql);
	    }
	}
	//--------------------------------------------------------------------------------------------------

	/**
	 * Execute SQL and get result array(id1=>array(f1,f2 ...),id2=>array(f1,f2 ...))
	 *
	 * @param string $sSql or Assoc/Name
	 * @param array $aData for Base::GetSql
	 * @return array (id1=>array(f1,f2 ...),id2=>array(f1,f2 ...))
	 */
	static public function GetAssoc($sSql, $aData=array(), $bReturnSql=false)
	{
		if ("Assoc/"==substr($sSql,0,6)) $sSql=Base::GetSql($sSql,$aData);
		if(!empty(Base::$aGeneralConf['SQLLog'])) {
			$start_time=microtime(1);	
			$aResult=$bReturnSql?$sSql:Base::$db->GetAssoc($sSql);
			$end_time=microtime(1);
			$elapsed_time=number_format($end_time - $start_time,4);
			$trace=debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
			$sSql=str_replace(array("\r\n","\r","\n")," ",$sSql);
			$sFile=str_replace($_SERVER['DOCUMENT_ROOT'].'/','',str_replace('\\','/',$trace[0]['file']));

			if(preg_match(Base::$aGeneralConf['SQLLogFilter'], $sSql))
				file_put_contents(Base::$aGeneralConf['SQLLog'], date("Y-m-d H:i:s").";".$elapsed_time.";".$sFile.";".$trace[0]['line'].";".$_SERVER['REQUEST_URI'].";".$sSql."\n", FILE_APPEND);

			return $aResult;
		} else {
			if(Base::$db->debug) {
				$start_time = microtime();
					
				if ($bReturnSql)
					$aResult = $sSql;
				else
					$aResult=Base::$db->GetAssoc($sSql);
					
				$end_time = microtime();
				$elapsed_time = $end_time - $start_time;
					
				$trace = debug_backtrace();
				Debug::PrintPre("Execution time: ".$elapsed_time." File: ". $trace[0]['file']." on line: ".$trace[0]['line'],false);
					
				return $aResult;
			}
			else
				return $bReturnSql?$sSql:Base::$db->GetAssoc($sSql);	
		}
	}
	//--------------------------------------------------------------------------------------------------

	/**
	* Execute SQL and get Row
	*
	* @param sql
	* @return array (fild=>value, fild2=>value2 ...)
	*/
	static public function GetRow($sSql)
	{
	    if(Base::$db->debug) {
	        $start_time = microtime();
	    
	        $aResult=Base::$db->GetRow($sSql);
	    
	        $end_time = microtime();
	        $elapsed_time = $end_time - $start_time;
	        $trace = debug_backtrace();
    	    Debug::PrintPre("Execution time: ".$elapsed_time." File: ". $trace[0]['file']." on line: ".$trace[0]['line'],false);
	    
	        return $aResult;
	    } elseif(!empty(Base::$aGeneralConf['SQLLog'])) {
			$start_time=microtime(1);	
			$aResult=Base::$db->GetRow($sSql);
			$end_time=microtime(1);
			$elapsed_time=number_format($end_time - $start_time,4);
			$trace=debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
			$sSql=str_replace(array("\r\n","\r","\n")," ",$sSql);
			$sFile=str_replace($_SERVER['DOCUMENT_ROOT'].'/','',str_replace('\\','/',$trace[0]['file']));

			if(preg_match(Base::$aGeneralConf['SQLLogFilter'], $sSql))
				file_put_contents(Base::$aGeneralConf['SQLLog'], date("Y-m-d H:i:s").";".$elapsed_time.";".$sFile.";".$trace[0]['line'].";".$_SERVER['REQUEST_URI'].";".$sSql."\n", FILE_APPEND);

			return $aResult;
	    } else {
		    $aResult=Base::$db->GetRow($sSql);
		    return $aResult;
	    }
	}
	//--------------------------------------------------------------------------------------------------

	/**
	* Execute SQL and one item
	*
	* @param sql
	* @return string item
	*/
	static public function GetOne($sSql)
	{
	    if(Base::$db->debug) {
	        $start_time = microtime();
	    
	        $aResult=Base::$db->GetOne($sSql);
	    
	        $end_time = microtime();
	        $elapsed_time = $end_time - $start_time;
	        $trace = debug_backtrace();
    	    Debug::PrintPre("Execution time: ".$elapsed_time." File: ". $trace[0]['file']." on line: ".$trace[0]['line'],false);
	    
	        return $aResult;
	    } elseif(!empty(Base::$aGeneralConf['SQLLog'])) {
			$start_time=microtime(1);	
			$aResult=Base::$db->GetOne($sSql);
			$end_time=microtime(1);
			$elapsed_time=number_format($end_time - $start_time,4);
			$trace=debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
			$sSql=str_replace(array("\r\n","\r","\n")," ",$sSql);
			$sFile=str_replace($_SERVER['DOCUMENT_ROOT'].'/','',str_replace('\\','/',$trace[0]['file']));

			if(preg_match(Base::$aGeneralConf['SQLLogFilter'], $sSql))
				file_put_contents(Base::$aGeneralConf['SQLLog'], date("Y-m-d H:i:s").";".$elapsed_time.";".$sFile.";".$trace[0]['line'].";".$_SERVER['REQUEST_URI'].";".$sSql."\n", FILE_APPEND);

			return $aResult;
	    } else {
	        $aResult=Base::$db->GetOne($sSql);
		    return $aResult;
	    }
	}
	//--------------------------------------------------------------------------------------------------

	/**
	 *
	 * Similar to PEAR DB's autoExecute(), except that
	 * $mode can be 'INSERT' or 'UPDATE' or DB_AUTOQUERY_INSERT or DB_AUTOQUERY_UPDATE
	 * If $mode == 'UPDATE', then $where is compulsory as a safety measure.
	 *
	 * $forceUpdate means that even if the data has not changed, perform update.
	 */
	static public function AutoExecute($sTable, $aFieldValue, $sMode = 'INSERT', $sWhere = FALSE, $bForceUpdate=true, $bMagicQuote=false)
	{
		if (Base::GetConstant('db:is_table_logged','0')) {
			$aTableArray=preg_split("/[\s,;]+/", Base::GetConstant('db:table_logged_array'));
			if (in_array($sTable, $aTableArray)) {
				$trace=debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
				$sTrace = date("Y-m-d H:i:s").";\n".$trace[0]['file']." line: ".$trace[0]['line'].";\n uri:".$_SERVER['REQUEST_URI'];
									
				$aLogTable=array(
				'table_name'=>$sTable,
				'mode_name'=>$sMode,
				'description'=>print_r($aFieldValue,true),
				'where_name'=>$sWhere,
				'trace' => $sTrace
				);
				Base::$db->AutoExecute('log_table', $aLogTable);
			}	
		} 
		if(!empty(Base::$aGeneralConf['SQLLog'])) {
			$start_time=microtime(1);	
			$aResult=Base::$db->AutoExecute($sTable, $aFieldValue, $sMode, $sWhere, $bForceUpdate, $bMagicQuote);
			$end_time=microtime(1);
			$elapsed_time=number_format($end_time - $start_time,4);
			$trace=debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
			$sSql=str_replace(array("\r\n","\r","\n")," ",$sTable.' : '.$sMode.' : '.print_r($aFieldValue,true).' : '.$sWhere);
			$sFile=str_replace($_SERVER['DOCUMENT_ROOT'].'/','',str_replace('\\','/',$trace[0]['file']));

			if(preg_match(Base::$aGeneralConf['SQLLogFilter'], $sSql))
				file_put_contents(Base::$aGeneralConf['SQLLog'], date("Y-m-d H:i:s").";".$elapsed_time.";".$sFile.";".$trace[0]['line'].";".$_SERVER['REQUEST_URI'].";".$sSql."\n", FILE_APPEND);

			return $aResult;
		} else {
			return Base::$db->AutoExecute($sTable, $aFieldValue, $sMode, $sWhere, $bForceUpdate, $bMagicQuote);	
		}
	}
	//--------------------------------------------------------------------------------------------------

	/**
	 * Show debug sql
	 */
	static public function Debug()
	{
		Base::$db->debug=true;
	}
	//--------------------------------------------------------------------------------------------------

	/**
	 * Write Log Sql to table adodb_logsql
	 */
	static public function LogSql($bEnable=true)
	{
		Base::$db->LogSQL($bEnable);
	}
	//--------------------------------------------------------------------------------------------------

	/**
	 * Last insert ID
	 *
	 * @return integer ID
	 */
	static public function InsertId()
	{
		return Base::$db->Insert_ID();
	}
	//--------------------------------------------------------------------------------------------------

	/**
	 * Number of row
	 *
	 * @return integer Col
	 */
	static public function AffectedRow()
	{
		return Base::$db->Affected_Rows();
	}
	//--------------------------------------------------------------------------------------------------

	/**
	 * Start transaction
	 *
	 */
	static public function StartTrans()
	{
		Base::$db->StartTrans();
	}
	//--------------------------------------------------------------------------------------------------

	/**
	 * Fail transaction
	 *
	 */
	static public function FailTrans()
	{
		Base::$db->FailTrans();
	}
	//--------------------------------------------------------------------------------------------------

	/**
	 * Complete transaction
	 *
	 */
	static public function CompleteTrans()
	{
		return Base::$db->CompleteTrans();
	}
	//--------------------------------------------------------------------------------------------------

	/**
	 * escape array function mysql_escape_string
	 *
	 * @param array $aData
	 * @return array
	 */
	static public function Escape($aData)
	{
		if ($aData) {
			foreach ($aData as $sKey => $aValue) {
				$aDataNew[$sKey]=mysql_escape_string($aValue);
			}
			return $aDataNew;
		} else return false;
	}
	//--------------------------------------------------------------------------------------------------

	/**
	 * Get Insert Sql
	 *
	 * @param object $oSql
	 * @param array $aField
	 * @param boolen $bMagicq
	 * @param string $force
	 * @return string
	 */
	static public function GetInsertSql($oSql, $aField, $bMagicq=true, $sForce=null)
	{
		return Base::$db->GetInsertSQL($oSql, $aField, $bMagicq, $sForce);
	}
	//--------------------------------------------------------------------------------------------------

	/**
	* Will select, getting rows from $offset (1-based), for $nrows.
	* This simulates the MySQL "select * from table limit $offset,$nrows" , and
	* the PostgreSQL "select * from table limit $nrows offset $offset". Note that
	* MySQL and PostgreSQL parameter ordering is the opposite of the other.
	* eg.
	*  SelectLimit('select * from table',3); will return rows 1 to 3 (1-based)
	*  SelectLimit('select * from table',3,2); will return rows 3 to 5 (1-based)
	*
	* Uses SELECT TOP for Microsoft databases (when $this->hasTop is set)
	* BUG: Currently SelectLimit fails with $sql with LIMIT or TOP clause already set
	*
	* @param sSql
	* @param iRow [nrows]		is the number of rows to get
	* @param iStart [offset]	is the row to start calculations from (1-based)
	* @param [inputarr]	array of bind variables
	* @param [secs2cache]		is a private parameter only used by jlim
	* @return object the recordset ($rs->databaseType == 'array')
 	*/
	static public function SelectLimit($sSql, $iRow=-1, $iStart=-1, $inputarr=false,$secs2cache=0)
	{
		return Base::$db->SelectLimit($sSql, $iRow, $iStart, $inputarr, $secs2cache);
	}
	//--------------------------------------------------------------------------------------------------
	/**
	 * Return aditional info about table of current database
	 *
	 * @param string $sType
	 * @return array
	 */
	static public function GetTableInfo($sType='')
	{
		$aRow= Db::GetAll("SHOW TABLE STATUS");
		foreach ($aRow as $sKey => $aValue) {
			switch ($sType) {
				case 'name':
					$aRowReturn[0].=' '.$aValue['Name'];
					$aRowReturn[]=$aValue['Name'];
					break;

				case 'dump':
					if ($sOldName && substr($aValue['Name'],0,3)!=substr($sOldName,0,3)) {
						$aRowReturn[0].="\\\n";
					}
					$aRowReturn[0].=$aValue['Name']." ";
					$sOldName=$aValue['Name'];
					break;

				default:
					$aRowReturn=$aRow;
					break;
			}
		}
		return $aRowReturn;
	}
	//--------------------------------------------------------------------------------------------------
	/**
	 * Set sWhere for include/sql function
	 *
	 * @param string $sWhere
	 * @param array $aData
	 * @param string $sDataField
	 * @param string $sPrefix
	 * @param string $sTableField
	 */
	public static function SetWhere(&$sWhere,$aData,$sDataField,$sPrefix,$sTableField="")
	{
		if ($aData[$sDataField]) {
			if ($sTableField=="") $sTableField=$sDataField;
			$s="='"; $ss="'";
			if (strpos($aData[$sDataField],'>')===0 || strpos($aData[$sDataField],'<')===0) {
				$s=""; $ss="";
			}
			$sWhere.=" and ".$sPrefix.".".$sTableField.$s.$aData[$sDataField].$ss;
		}
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Get sql for convert date from sql to normal format
	 *
	 * @param string $sNameField
	 * @return srting
	 */
	public static function GetDateFormat($sNameField="post_date", $sFormat="")
	{
		if (!$sFormat) $sFormat=Base::GetConstant("date_format");
		return " date_format(".$sNameField.",'".$sFormat."')";
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Get date or sql to convert data from normal to sql format
	 *
	 * @param string $sPostDate
	 * @param boolen $bReturnDate
	 * @return string
	 */
	public static function GetStrToDate($sPostDate, $bReturnDate=false, $sFormat="")
	{
		if (!$sFormat) $sFormat=Base::GetConstant("date_format");
		$sSql=" str_to_date('".$sPostDate."', '".$sFormat."') ";
		if ($bReturnDate) return Db::GetOne("select".$sSql); else return $sSql;
	}
}
?>