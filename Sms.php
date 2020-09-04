<?
/**
 * @author Dima Valegov
 * @author Mikhail Starovoyt
 */

class Sms extends Base
{
	//-----------------------------------------------------------------------------------------------
	public function __construct()
	{
		Repository::InitDatabase('sms_delayed');
	}
	//-----------------------------------------------------------------------------------------------
	public static function FormatNumber($sPhoneNumber)
	{
		$sPhoneNumber = mb_ereg_replace ( '[^0-9]*', '', $sPhoneNumber );
		if ($sPhoneNumber != '') {
			if (strpos ( ' ' . $sPhoneNumber, '00' ) == 1) {
				$sPhoneNumber = "+" . substr ( $sPhoneNumber, 2 );
			}
			if (strpos ( ' ' . $sPhoneNumber, '0' ) == 1) {
				$sPhoneNumber = "+38$sPhoneNumber";
			}
			if (strpos ( ' ' . $sPhoneNumber, '80' ) == 1) {
				$sPhoneNumber = "+3$sPhoneNumber";
			}
			if (strpos ( ' ' . $sPhoneNumber, '380' ) == 1) {
				$sPhoneNumber = "+$sPhoneNumber";
			}
			if (strpos ( ' ' . $sPhoneNumber, '49') == 1) {
				$sPhoneNumber = "+$sPhoneNumber";
			}
			if (strpos ( ' ' . $sPhoneNumber, '89' ) == 1) { // numbers in the task TUL-30
				$sPhoneNumber {0} = "7";
			}
			if (strpos ( ' ' . $sPhoneNumber, '79' ) == 1) { // numbers in the task TUL-30
				$sPhoneNumber = "+$sPhoneNumber";
			}
			$sPhoneNumber = mb_substr ( $sPhoneNumber, 0, Base::GetConstant('sms:phone_length',13));
			if (strlen ( $sPhoneNumber ) != Base::GetConstant('sms:phone_length',13) || $sPhoneNumber {0} != '+') {
				$sPhoneNumber = '';
			}
		}
		return $sPhoneNumber;
	}
	//-----------------------------------------------------------------------------------------------
	private static function SendGT($sPhoneNumber, $sMessage, $iTimeout = 10)
	{
		$postdata = "";
		$postdata .= "CS=u";
		$postdata .= "&MN=" . urlencode ( $sPhoneNumber );
		$postdata .= "&SM=" . urlencode ( $sMessage );

		$url = "http://sms.gt.com.ua/SendSM.htm";
		$referer = "http://sms.gt.com.ua/";
		$curl = curl_init ( "$url" );
		if ($postdata != '') {
			curl_setopt ( $curl, CURLOPT_POST, 1 );
			curl_setopt ( $curl, CURLOPT_POSTFIELDS, $postdata );
		}
		curl_setopt ( $curl, CURLOPT_USERAGENT, 'User-Agent: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)' );
		curl_setopt ( $curl, CURLOPT_REFERER, $referer );
		curl_setopt ( $curl, CURLOPT_HEADER, 0 );
		curl_setopt ( $curl, CURLOPT_SSL_VERIFYPEER, 0 );
		curl_setopt ( $curl, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt ( $curl, CURLOPT_SSL_VERIFYHOST, 1 );
		curl_setopt ( $curl, CURLOPT_COOKIE, 'UID=4D7A93357E26F792' ); //Your Web ID: 46661062
		curl_setopt ( $curl, CURLOPT_TIMEOUT, $iTimeout );

		$response = curl_exec ( $curl );

		if (curl_errno ( $curl )) {
			curl_close ( $curl );
			return false;
		}
		curl_close ( $curl );

		if ($response != '' && strpos ( $response, 'Message sent' ) !== false) {
			return true;
		}
		return false;
	}
	//-----------------------------------------------------------------------------------------------
	private static function SendTurbo($sPhoneNumber, $sMessage, $sSender = 'Partmaster')
	{
		$client = new SoapClient ( Base::GetConstant('sms:turbo_soap','http://62.149.25.11/service/WebService.wsdl') );
		$auth = Array ('login' => 'mstar', 'password' => 'kjH87T&*w' );

		$result = $client->Auth ( $auth );
		if ($result->AuthResult != 'User succesfully authorized') {
			error_log ( 'Turbo SMS error: ' . $result->AuthResult );
			return false;
		}

		$result = $client->GetCreditBalance ();
		if ($result->GetCreditBalanceResult <= 0) {
			error_log ( 'Turbo SMS error: credit balance is ' . $result->GetCreditBalanceResult );
			return false;
		}

		$sms = Array ('sender' => $sSender, 'destination' => $sPhoneNumber, 'text' => iconv ( 'windows-1251', 'utf-8', $sMessage));
		$result = $client->SendSMS ( $sms );

		if (@$result->SendSMSResult->ResultArray [2] != '') {
			return true;
		} else {
			error_log("Turbo SMS error: SendSMS('$sSender','$sPhoneNumber','$sMessage') - ".$result->SendSMSResult->ResultArray [0] );
			return false;
		}
	}
	//-----------------------------------------------------------------------------------------------
	private static function SendTurboV2($sPhoneNumber, $sMessage, $sSender = 'Partmaster')
	{
		$client = new SoapClient ( 'http://turbosms.in.ua/api/wsdl.html' );
		$auth = Array (
		'login' => Base::GetConstant('sms:turbo_login','mstar'),
		'password' =>  Base::GetConstant('sms:turbo_password','kjH87T&*w'),
		);

		$result = $client->Auth ( $auth );

		$_SESSION['sms_error'] = '';
		
		if ($result->AuthResult != 'Вы успешно авторизировались') {
			error_log ( 'Turbo SMS error: '.$auth['login'].' '.$auth['password'].' (auth) '.$result->AuthResult);
			return false;
		}

		$result = $client->GetCreditBalance ();
		if ($result->GetCreditBalanceResult <= 0) {
		    $_SESSION['sms_error'] = '-3';
			error_log ( 'Turbo SMS error: credit balance is ' . $result->GetCreditBalanceResult );
			return false;
		}

		if (strtolower(Base::GetConstant('global:default_encoding'))=='utf-8') $sEncodedMessage=$sMessage;
		else $sEncodedMessage=iconv('windows-1251', 'utf-8',$sMessage);
		
		$sms = Array ('sender' => $sSender, 'destination' => $sPhoneNumber, 'text' => $sEncodedMessage );
		$result = $client->SendSMS ( $sms );

		if (@$result->SendSMSResult->ResultArray [0] == 'Сообщения успешно отправлены' ) {
			return true;
		} else {
			error_log( "Turbo SMS error: SendSMS('$sSender','$sPhoneNumber','$sMessage') - " .
			print_r( $result->SendSMSResult->ResultArray ,true) );
			return false;
		}
	}
	//-----------------------------------------------------------------------------------------------
	public static function SendNow($sPhoneNumber, $sMessage)
	{
		$sPhoneNumber = Sms::FormatNumber ( $sPhoneNumber );
		$sMessage = trim ( $sMessage );
		if ($sPhoneNumber != '' && $sMessage != '') {
			switch (Base::GetConstant('sms_delivery_type', 'shluz' )) {
				case 'turbosms' :
					return self::SendTurboV2 ( $sPhoneNumber, $sMessage,Base::GetConstant('sms:from','partmaster') );

				case 'clickatell' :
					return self::SendClickatell($sPhoneNumber, $sMessage,Base::GetConstant('sms:from','oem24.com'));

				default: return self::SendTurboV2 ( $sPhoneNumber, $sMessage,Base::GetConstant('sms:from','partmaster') );
			}
		}
		//------------------------------------------------------------------
		return false;
	}
	//-----------------------------------------------------------------------------------------------
	/*
	* require_once SERVER_PATH.'/class/core/Sms.php';
	* Sms::AddDelayed('+380688160516','Privet!!!');
	* Sms::SendDelayed();
	*
	* $sPhoneNumber - phone number can be set in any format:
	* +380688160516
	* 00380688160516
	* 380688160516
	* 80688160516
	* 0688160516
	* 068-816-05-16
	* 8-(068)-816-05-16
	* ...
	*
	* $sMessage -Message russian text cp1251, max. 70 symbols.
	*/
	public static function AddDelayed($sPhoneNumber, $sMessage)
	{
		$sPhoneNumberFormated = Sms::FormatNumber ( $sPhoneNumber );
		Base::$db->Execute ( "insert into sms_delayed (number,message,post,sent_time)
			values (
			'" . mysql_real_escape_string ( $sPhoneNumberFormated ? $sPhoneNumberFormated : $sPhoneNumber ) . "',
			'" . mysql_real_escape_string ( $sMessage ) . "',
			UNIX_TIMESTAMP(),
			'" . ($sPhoneNumberFormated ? '0' : '-2') . "'
			) " );
	}
	//-----------------------------------------------------------------------------------------------
	public static function SendDelayed($iMessage = 1)
	{
		Repository::InitDatabase('sms_delayed');

		if (Base::GetConstant('stop_sms', 0)) return false;

		if (Base::GetConstant('sms:check_send_time', '0')) {
			$iHourNow=date('H');
			if (!($iHourNow> Base::GetConstant('sms:send_time_from', '0') &&
			$iHourNow<Base::GetConstant('sms:send_time_to', '24'))) return false;
		}

		$aSmsList = Base::$db->getAll ( "select * from sms_delayed where sent_time in (0,NULL) order by post
			limit 0,{$iMessage}" );
		if ($aSmsList)
		foreach ( $aSmsList as $aSms ) {
			$bIsSent = Sms::SendNow($aSms['number'],$aSms ['message'] );
			if ($_SESSION['sms_error']) {
			    Base::$db->Execute ( "update sms_delayed set sent_time=".$_SESSION["sms_error"]." where id='{$aSms['id']}'" );
			} else {
			    Base::$db->Execute ( "update sms_delayed set sent_time=" .
			        ($bIsSent ? "UNIX_TIMESTAMP()" : "-1") . " where id='{$aSms['id']}'" );
			}
		}
	}
	//-----------------------------------------------------------------------------------------------
	public static function SendClickatell($sPhoneNumber, $sMessage, $sSender ='')
	{
		require_once(SERVER_PATH."/lib/clickatel_sms_api/sms_api.php");
		$oSmsApi=new sms_api();
		$oSmsApi->api_id=Base::GetConstant('sms:clickatell_api_id','3212834');
		$oSmsApi->user=Base::GetConstant('sms:clickatell_user','mstar');;
		$oSmsApi->password=Base::GetConstant('sms:clickatell_password','BJSafv04');

		$oSmsApi->_auth();

		$sPhoneNumber=str_replace("+","",$sPhoneNumber);
		return $oSmsApi->send($sPhoneNumber, $sSender, $sMessage);
	}
	//-----------------------------------------------------------------------------------------------
}
?>