<?
/**
 * @author Mikhail Strovoyt
 */

class DateFormat
{
	//-----------------------------------------------------------------------------------------------
	private static function GetBaseDate($sFormat,$iTimestamp='',$iTimeZone='')
	{
		if (!$iTimestamp) return;
		//if (!$iTimestamp) $iTimestamp=time();

		/** TODO: Need to add default server timezone*/
		if (!$iTimestamp) $iTimestamp=time();

		$iTimestamp += $iTimeZone * 3600;

		return date($sFormat,$iTimestamp);
	}
	//-----------------------------------------------------------------------------------------------

	public static function GetDate($iTimestamp='',$iTimeZone='')
	{
		return DateFormat::GetBaseDate('d.m.Y',$iTimestamp,$iTimeZone);
	}
	//-----------------------------------------------------------------------------------------------
	public static function GetTime($iTimestamp='',$iTimeZone='')
	{
		return DateFormat::GetBaseDate('H:i',$iTimestamp,$iTimeZone);
	}
	//-----------------------------------------------------------------------------------------------
	public static function GetDateTime($iTimestamp='',$iTimeZone='')
	{
		return DateFormat::GetBaseDate('d.m.Y H:i:s',$iTimestamp,$iTimeZone);;
	}
	//-----------------------------------------------------------------------------------------------
	//	public static function verifyFormatMonYear($sValue) {
	//		$aValues = explode('/', $sValue);
	//		if (count($aValues) != 2 || strlen($aValues[0]) != 2 || strlen($aValues[1]) != 4) {
	//			return false;
	//		}
	//		$sMon = $aValues[0];
	//		$sYear = $aValues[1];
	//		if (intval($sMon) != $sMon || intval($sYear) != $sYear) {
	//			return false;
	//		}
	//		return true;
	//	}
	//
	//	public static function convertDateMonYearToString($sValue) {
	//		if (!DateFormat::verifyFormatMonYear($sValue)) {
	//			return 0;
	//		}
	//		$aValues = explode('/', $sValue);
	//		$iMon = intval($aValues[0]);
	//		$iYear = intval($aValues[1]);
	//		return mktime(0, 0, 0, $iMon, 1, $iYear);
	//	}

	//-----------------------------------------------------------------------------------------------
	public static function FormatSearch($sSearchDate, $sFormat='Y-m-d H:i:s')
	{
		return date($sFormat,strtotime($sSearchDate));
	}
    public static function FormatSearchNow()
	{
		return date('Y-m-d H:i:s',time());
	}
    public static function FormatSearchTomorrow()
	{
		return date('Y-m-d H:i:s',strtotime('+1 DAY'));
	}
	//-----------------------------------------------------------------------------------------------
	public static function GetFileDateTime($iTimestamp='',$iTimeZone='',$bShowSecond=true)
	{
		$sFormat='Y-m-d_H-i';
		if ($bShowSecond) $sFormat.='-s';
		if (!$iTimestamp) $iTimestamp=time();
		return DateFormat::GetBaseDate($sFormat,$iTimestamp,$iTimeZone);;
	}
	//-----------------------------------------------------------------------------------------------
	public static function GetPostDate($sPostDate,$iTimeZone='')
	{
		return DateFormat::GetBaseDate(Base::GetConstant('date_format:post_date','d.m.Y'),strtotime($sPostDate),$iTimeZone);
	}
	//-----------------------------------------------------------------------------------------------
	public static function GetPostDateTime($sPostDate,$iTimeZone='')
	{
		return DateFormat::GetBaseDate(Base::GetConstant('date_format:post_date_time','d.m.Y H:i:s')
		,strtotime($sPostDate),$iTimeZone);
	}
	//-----------------------------------------------------------------------------------------------
	public static function GetSqlDate($sNameField="post_date")
	{
		return " date_format(".$sNameField.",'".Base::GetConstant("date_format")."')";
	}
	//-----------------------------------------------------------------------------------------------
	public static function GetSqlStrToDate($sPostDate)
	{
		return " str_to_date('".$sPostDate."', '".Base::GetConstant("date_format")."') ";
	}
	//-----------------------------------------------------------------------------------------------
	public static function GetNextMonth($sPostDate)
	{
		$aValue=explode("-",$sPostDate);
    	$aValue[1]=$aValue[1]+1;
    	if ($aValue[1]>12){
    		$aValue[1]=1;
        	$aValue[0]=$aValue[0]+1;
    	}
    	if ($aValue[1]<10) $aValue[1]="0".$aValue[1];
		return implode(array($aValue[0],$aValue[1],$aValue[2]),"-");
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Преобразование секунд в секунды/минуты/часы/дни/года
	 *
	 * @param int $seconds - секунды для преобразования
	 *
	 * @return array $times:
	 *        $times[0] - секунды
	 *        $times[1] - минуты
	 *        $times[2] - часы
	 *        $times[3] - дни
	 *        $times[4] - года
	 *
	 */
	//-----------------------------------------------------------------------------------------------
	public static function Seconds2Times($seconds)
	{
		$times = array();
	
		// считать нули в значениях
		$count_zero = false;
	
		// количество секунд в году не учитывает високосный год
		// поэтому функция считает что в году 365 дней
		// секунд в минуте|часе|сутках|году
		$periods = array(60, 3600, 86400, 31536000);
	
		for ($i = 3; $i >= 0; $i--)
		{
		$period = floor($seconds/$periods[$i]);
		if (($period > 0) || ($period == 0 && $count_zero))
		{
		$times[$i+1] = $period;
			$seconds -= $period * $periods[$i];
	
			$count_zero = true;
		}
		}
	
		$times[0] = $seconds;
		
		return $times;
	}
	//-----------------------------------------------------------------------------------------------
	public static function NameIntervalDate($aDiffDate) { 
		$sResult = "";
		foreach($aDiffDate as $key => $iValue) {
			if ($iValue != 0) {
				if ($sResult != '')
					$sResult .= " ";
				switch ($key) {
					case 0:$sResult .= $iValue . Language::GetMessage('sec');break;
					case 1:$sResult .= $iValue . Language::GetMessage('min');break;
					case 2:$sResult .= $iValue . Language::GetMessage('hours');break;
					case 3:$sResult .= $iValue . Language::GetMessage('days');break;
					case 4:$sResult .= $iValue . Language::GetMessage('years');break;
				}
			}
		}
		return $sResult; 
	}
}
?>