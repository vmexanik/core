<?
/**
 * @author Oleksandr Starovoit
 */
class Debug {
	//-----------------------------------------------------------------------------------------------
	/**
	 * Display value of variable for debug.
	 *
	 * @param all_type $sVariable
	 * @param boolean $bDie=true - stop script
	 */
	public function PrintPre($sVariable,$bDie=true,$bReturn=false)
	{
		$sReturn="<pre>".print_r($sVariable,true)."</pre>";
		if ($bReturn) return $sReturn;

		print $sReturn;
		$bDie ? die():"";
	}
	/**
	 * Display value of template variable for debug.
	 *
	 * @param string $sVariable
	 * @param boolean $bDie=true - stop script
	 */
	public function PrintPreTpl($sVariable,$bDie=true)
	{
		Debug::PrintPre(Base::$tpl->get_template_vars($sVariable));
	}

	/**
	 * Return second.microsecond
	 *
	 * @return float
	 */
	public function GetMicrotime() {
		list($usec, $sec) = explode(" ",microtime());
		return ((float)$usec + (float)$sec);
	}
	
	public function WriteToLog($sFile,$data)
	{
	    $f = fopen($sFile, "a");
        fwrite($f,date("[Y-m-d H:i:s] (".getmypid().") ").print_r($data,true)."\n");
        fclose($f);
	}
}
?>