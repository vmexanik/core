<?
/**
 * @author Mikhail Strovoyt
 * @author Roman Dehtyarev
 * @version 4.5.2
 */


class Mail extends Base {

	public static $bAddedNoRply=true;

	//-----------------------------------------------------------------------------------------------
	public function SendNow($sAddress,$sSubject,$sBody,$sFrom='',$sFromName='',$sCc='',$iLanguage=null, $bFromDelayed=false, $iPriority=5)
	{
		if (Base::GetConstant('mail:stop_send_now',0)) return;
		
		if (!$sAddress) return false;
		
		if (!$bFromDelayed) {
			$aMailDelayed=array(
			'address'=>$sAddress,
			'subject'=>$sSubject,
			'body'=>$sBody,
			'from_email'=>$sFrom,
			'from_name'=>$sFromName,
			'priority'=>$iPriority,
			'description'=>$sDescription,
			);
			
			if (version_compare(Language::GetConstant('module_version:mail','4.5.0'),'4.5.2','>=') ){
				$aMailDelayed['send_date']=date("yy-mm-dd hh:ii:ss");
			}
			else {
				$aMailDelayed['sent_time']=time();
			}
			Db::AutoExecute('mail_delayed',$aMailDelayed);
		}

		if (Base::GetConstant('mail:use_google_smtp',0)) {
			require_once(SERVER_PATH."/lib/phpmailer/class.phpmailer.php");
			$oMailer = new PHPMailer();

			$oMailer->CharSet = Base::GetConstant('global:default_encoding','UTF-8');
			if (!$sFrom) $sFrom=Base::GetConstant('mail:from','info@mstarproject.com');
			$oMailer->From = $sFrom;
			if (!$sFromName) $sFromName=Base::GetConstant('mail:from_name','Info');
			$oMailer->FromName = $sFromName;
			if ($sCc) $oMailer->AddCC($sCc);

			$oMailer->Subject= $sSubject;
			$oMailer->Body = $sBody;
			$oMailer->AltBody = html_entity_decode(strip_tags($sBody));
			$oMailer->Mailer='smtp';
			$oMailer->Host='smtp.gmail.com';
			$oMailer->Port='465';
			$oMailer->SMTPAuth=true;
			$oMailer->SMTPSecure='ssl';
			$oMailer->Username=Base::GetConstant('mail:smtp_username','info@modoza.com');
			$oMailer->Password=Base::GetConstant('mail:smtp_password','infoinfo');

			$aAddress=preg_split("/[\s,;]+/", $sAddress);
			if ($aAddress) foreach ($aAddress as $sValue) {
				$oMailer->AddAddress($sValue);
				$oMailer->Send();
				$oMailer->ClearAddresses();
			}
			return;
		}
		if (Base::GetConstant('mail:use_general_smtp',0)) {
			require_once(SERVER_PATH."/lib/phpmailer/class.phpmailer.php");
			$oMailer = new PHPMailer();
			$oMailer->IsSMTP();

			$oMailer->CharSet = Base::GetConstant('global:default_encoding','UTF-8');
			if (!$sFrom) $sFrom=Base::GetConstant('mail:from','info@mstarproject.com');
			$oMailer->From = $sFrom;
			if (!$sFromName) $sFromName=Base::GetConstant('mail:from_name','Info');
			$oMailer->FromName = $sFromName;
			if ($sCc) $oMailer->AddCC($sCc);

			$oMailer->Subject= $sSubject;
			$oMailer->Body = $sBody;
			$oMailer->AltBody = html_entity_decode(strip_tags($sBody));
			$oMailer->Host=Base::GetConstant('mail:smtp_host','smtp.gmail.com');
			$oMailer->Port=Base::GetConstant('mail:smtp_port','465');
			$oMailer->SMTPAuth=(Base::GetConstant('mail:smtp_auth','1')==1)?true:false;
			$oMailer->SMTPSecure=trim(Base::GetConstant('mail:smtp_secure','ssl'));
			$oMailer->Username=Base::GetConstant('mail:smtp_username','info@domen.com');
			$oMailer->Password=Base::GetConstant('mail:smtp_password','pass');

			$aAddress=preg_split("/[\s,;]+/", $sAddress);
			if ($aAddress) foreach ($aAddress as $sValue) {
				$oMailer->AddAddress($sValue);
				$oMailer->Send();
				$oMailer->ClearAddresses();
			}
			return;
		}

		$sHeader  = 'MIME-Version: 1.0' . "\r\n";
		$sHeader .= 'Content-type: text/html; charset='. Base::GetConstant('global:default_encoding','UTF-8') . "\r\n";

		if (!$sFrom) $sHeader .= "From:  ".Base::GetConstant('global:email_from','Info <noreply@partmaster.com.ua>')."\r\n";
		else {
			require_once(SERVER_PATH.'/class/core/String.php');
			if (String::CheckEmail($sFrom))	$sHeader .= "From:  $sFromName <$sFrom>\r\n";
		}

		if ($sCc!='') $sHeader .= "Cc: $sCc \r\n";

		if ($iPriority<7){
		if ($iLanguage) {
			$sLocale=Db::GetOne("select code from language where id='".$iLanguage."'");
			$sBody.=Language::GetUserTranslateText('added_no_reply',0,$sLocale);
		}
		else
		if (Mail::$bAddedNoRply) $sBody.=Language::GetText('added_no_reply');
		}
		
		// parse text constants
		$sPattern="!const::\((.*?)\)!si";
		preg_match_all($sPattern,$sBody,$aMatches);
		if (count($aMatches[1]) > 0) {
			$aMatches[1] = array_unique($aMatches[1]);
			foreach ($aMatches[1] as $sConstant)
				$sBody = str_replace('const::('.$sConstant.')', Language::getConstant($sConstant), $sBody);
		}
		
		if (Base::$aGeneralConf['IsLive']!=='false') {
			if (Base::GetConstant('global:default_encoding','UTF-8')=='UTF-8')
			$sSubject='=?UTF-8?B?'.base64_encode($sSubject).'?=';

			if(!mail($sAddress,$sSubject,$sBody,$sHeader)) return true;
		}
		
		return true;
	}
	//-----------------------------------------------------------------------------------------------
	function SendAttach($sAddress,$sSubject,$sBody,$aAttachment=array(),$sCc='',$sFrom='',$sFromName='',$sCharSet = 'cp1251')
	{
		if (Base::GetConstant('mail:use_general_smtp',0)) {
			require_once(SERVER_PATH."/lib/phpmailer/class.phpmailer.php");
			$oMailer = new PHPMailer();
			$oMailer->IsSMTP();

			$oMailer->CharSet = Base::GetConstant('global:default_encoding','UTF-8');
			if (!$sFrom) $sFrom=Base::GetConstant('mail:from','info@mstarproject.com');
			$oMailer->From = $sFrom;
			if (!$sFromName) $sFromName=Base::GetConstant('mail:from_name','Info');
			$oMailer->FromName = $sFromName;
			if ($sCc) $oMailer->AddCC($sCc);

			$oMailer->Subject= $sSubject;
			$oMailer->Body = $sBody;
			$oMailer->AltBody = html_entity_decode(strip_tags($sBody));
			$oMailer->Host=Base::GetConstant('mail:smtp_host','smtp.gmail.com');
			$oMailer->Port=Base::GetConstant('mail:smtp_port','465');
			$oMailer->SMTPAuth=(Base::GetConstant('mail:smtp_auth','1')==1)?true:false;
			$oMailer->SMTPSecure=trim(Base::GetConstant('mail:smtp_secure','ssl'));
			$oMailer->Username=Base::GetConstant('mail:smtp_username','info@domen.com');
			$oMailer->Password=Base::GetConstant('mail:smtp_password','pass');

			if ($aAttachment) foreach ($aAttachment as $sKey=>$sValue)
			$oMailer->AddAttachment($sKey,$sValue);

			$aAddress=preg_split("/[\s,;]+/", $sAddress);
			if ($aAddress) foreach ($aAddress as $sValue) {
				$oMailer->AddAddress($sValue);
				$oMailer->Send();
				$oMailer->ClearAddresses();
				$oMailer->ClearAttachments();
			}
			return;
		}
		require_once(SERVER_PATH."/lib/phpmailer/class.phpmailer.php");
		$oMailer = new PHPMailer();

		//$oMailer->Encoding=Base::GetConstant('global:default_encoding','UTF-8');

		$oMailer->CharSet = Base::GetConstant('global:default_encoding','UTF-8');
		if ($sFrom) $oMailer->From = $sFrom;
		else $oMailer->From = Base::GetConstant('mail:from','info@mstarproject.com');

		if ($sFromName) $oMailer->FromName = $sFromName;
		if ($sCc) $oMailer->AddCC($sCc);

		if (strpos($sAddress,",")>0)
		{
			$aAddress=explode(",",$sAddress);
			foreach ($aAddress as $sValue) {
				$oMailer->AddAddress($sValue,$sValue);
			}
		}
		else
		{
			$oMailer->AddAddress($sAddress,$sAddress);
		}

		if ($aAttachment) foreach ($aAttachment as $sKey=>$sValue)
		$oMailer->AddAttachment($sKey,$sValue);

		//$oMailer->IsMail();

		$oMailer->Subject= $sSubject;
		$oMailer->Body    = $sBody;
		$oMailer->AltBody = html_entity_decode(strip_tags($sBody));

		if(!$oMailer->Send()) {
			$rez=false;
			//echo $oMailer->ErrorInfo;
		} else $rez=true;

		// Clear all addresses and attachments for next loop
		$oMailer->ClearAddresses();
		$oMailer->ClearAttachments();

		return $rez;
	}
	//-----------------------------------------------------------------------------------------------
	public function AddDelayed($sAddress,$sSubject,$sBody,$sFromEmail='',$sFromName='',$bCheckEmail=true, $iPriority=5
	,$sAttachCode='',$sDescription='',$sSendAfterDate='')
	{
		if ($bCheckEmail && !String::CheckEmail($sAddress)) return false;

		$aMailDelayed=array(
		'address'=>$sAddress,
		'subject'=>$sSubject,
		'body'=>$sBody,
		'from_email'=>$sFromEmail,
		'from_name'=>$sFromName,
		'priority'=>$iPriority,
		'attach_code'=>$sAttachCode,
		'description'=>$sDescription,
		'send_after_date'=>$sSendAfterDate,
		);
		Db::AutoExecute('mail_delayed',$aMailDelayed);
		return Db::InsertId();
	}
	//-----------------------------------------------------------------------------------------------
	public function SendDelayed($iLetter=1)
	{
		if (Base::GetConstant('mail:stop_send_delayed',0)) return;

		if (version_compare(Language::GetConstant('module_version:mail','4.5.0'),'4.5.2','>=') ){
			/**
			 * To upgrade - need to alter table manually, additional info in MD-485
			 */
			$aLetterList=Db::GetAll("select md.*
				from mail_delayed as md
				where md.sent_date IS NULL and (md.send_after_date<now() or md.send_after_date IS NULL)
				order by md.priority, md.id limit 0,".$iLetter."");
		}
		else $aLetterList=Db::GetAll("select * from mail_delayed where sent_time in (0,NULL)
			order by priority, id limit 0,".$iLetter."");

		$aAttachMail = array();
		if ($aLetterList)
		foreach ($aLetterList as $aLetter) {

			if ($aLetter['attach_code']) {
				if (!isset($aAttachMail[$aLetter['attach_code']])) {
					$aAttachMail[$aLetter['attach_code']] = array();
					$aAttachList = Base::$db->getAll("select * from attachment where owner_code='".$aLetter['attach_code']."'");
					if ($aAttachList)
					foreach ($aAttachList as $aAttach){
						$aAttachMail[$aLetter['attach_code']][SERVER_PATH.$aAttach['attach_file']]='';
					}
				}
			}

			if (version_compare(Language::GetConstant('module_version:mail','4.5.0'),'4.5.2','>=') ){
				//if (0)
				Db::Execute("update mail_delayed set sent_date=NOW() where id='".$aLetter['id']."'");
			}
			else Db::Execute("update mail_delayed set sent_time=UNIX_TIMESTAMP() where id='".$aLetter['id']."'");

			if ($aLetter['attach_code'] && (count($aAttachMail[$aLetter['attach_code']])>0)) {
				Mail::SendAttach($aLetter['address'], $aLetter['subject'], $aLetter['body'], $aAttachMail[$aLetter['attach_code']]
				,'',$aLetter['from_email'],$aLetter['from_name']);
			}
			else {
				Mail::SendNow($aLetter['address'],$aLetter['subject'],$aLetter['body'],$aLetter['from_email'],$aLetter['from_name']
				,'',$aLetter['id_language'],true,$aLetter['priority']);
			}

		}
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Open identificator to main
	 *
	 * @param string $sHost
	 * @param integer $iPort
	 * @param string $sUsername
	 * @param string $sPassword
	 * @param string $sType
	 * @param string $sFolder
	 * @param string $bSsl
	 * @return object
	 */
	function OpenAccount($sHost,$iPort,$sUsername,$sPassword,$sType="pop3",$sFolder="",$bSsl=false)
	{
		$sSsl=($bSsl==false)?"/novalidate-cert":"";
		return (imap_open("{".$sHost.":".$iPort."/".$sType.$sSsl."}".$sFolder,$sUsername,$sPassword));
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Get an array of headers
	 *
	 * @param object $oAccount
	 * @param innteger $iNumberEmail
	 * @return array
	 */
	function GetEmailCount($oAccount)
	{
		if($oAccount===FALSE)imap_errors();
		return imap_num_msg ($oAccount);
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Get an array of headers
	 *
	 * @param object $oAccount
	 * @param innteger $iNumberEmail
	 * @return array
	 */
	function GetEmailHeader($oAccount,$iNumberEmail)
	{
		$aDecodedHeader = Array();
		if (imap_num_msg ($oAccount)) {
			$sHeader=imap_fetchheader($oAccount,$iNumberEmail);
			$aHeader = explode("\r\n",$sHeader);
			for($i=0;$i<count($aHeader);$i++) {
				$sHeaderCurent = trim($aHeader[$i]);
				if(!empty($sHeaderCurent))
				if(!preg_match('/^[A-Z0-9a-z_-]+:/',$sHeaderCurent)) {
					$aDecodedHeader[$lasthead] .= " ".$sHeaderCurent;
				} else {
					$dbpoint = strpos($sHeaderCurent,":");
					$headname = strtolower(substr($sHeaderCurent,0,$dbpoint));
					$headvalue = trim(substr($sHeaderCurent,$dbpoint+1));
					if($aDecodedHeader[$headname]!= "") $aDecodedHeader[$headname] .= "; ".$headvalue;
					else $aDecodedHeader[$headname] = $headvalue;
					$lasthead = $headname;
				}
			}
		}
		return $aDecodedHeader;
	}
	//-----------------------------------------------------------------------------------------------
	function GetAttachment($oAccount,$iNumberEmail)
	{
		$oStructure = imap_fetchstructure($oAccount, $iNumberEmail);
		$aAttachment = array();
		if(isset($oStructure->parts) && count($oStructure->parts)) {

			for($i = 0; $i < count($oStructure->parts); $i++) {

				$aAttachment[$i] = array(
				'is_attachment' => false,
				'filename' => '',
				'name' => '',
				'attachment' => ''
				);

				if($oStructure->parts[$i]->parts){
					foreach ($oStructure->parts[$i]->parts as $key=>$value) {
						if($value->ifdparameters) {
							foreach($value->dparameters as $oObject) {
								if(strtolower($oObject->attribute) == 'filename') {
									$aAttachment[$i]['is_attachment'] = true;
									$aAttachment[$i]['filename'] = Mail::DecodeMimeString($oObject->value);
								}
							}
						}

						if($value->ifparameters) {
							foreach($value->parameters as $oObject) {
								if(strtolower($oObject->attribute) == 'name') {
									$aAttachment[$i]['is_attachment'] = true;
									$aAttachment[$i]['name'] = Mail::DecodeMimeString($oObject->value);
								}
							}
						}

						if($aAttachment[$i]['is_attachment']) {
							$aAttachment[$i]['attachment'] = imap_fetchbody($oAccount, $iNumberEmail, ($i+1) . '.' . ($key+1));
							if($value->encoding == 3) { // 3 = BASE64
								$aAttachment[$i]['attachment'] = base64_decode($aAttachment[$i]['attachment']);
							}
							elseif($value->encoding == 4) { // 4 = QUOTED-PRINTABLE
								$aAttachment[$i]['attachment'] = quoted_printable_decode($aAttachment[$i]['attachment']);
							}
							break;
						}
					}
				}else{
				if($oStructure->parts[$i]->ifdparameters) {
					foreach($oStructure->parts[$i]->dparameters as $oObject) {
						if(strtolower($oObject->attribute) == 'filename') {
							$aAttachment[$i]['is_attachment'] = true;
							$aAttachment[$i]['filename'] = Mail::DecodeMimeString($oObject->value);
						}
					}
				}

				if($oStructure->parts[$i]->ifparameters) {
					foreach($oStructure->parts[$i]->parameters as $oObject) {
						if(strtolower($oObject->attribute) == 'name') {
							$aAttachment[$i]['is_attachment'] = true;
							$aAttachment[$i]['name'] = Mail::DecodeMimeString($oObject->value);
						}
					}
				}

				if($aAttachment[$i]['is_attachment']) {
					$aAttachment[$i]['attachment'] = imap_fetchbody($oAccount, $iNumberEmail, $i+1);
					if($oStructure->parts[$i]->encoding == 3) { // 3 = BASE64
						$aAttachment[$i]['attachment'] = base64_decode($aAttachment[$i]['attachment']);
					}
					elseif($oStructure->parts[$i]->encoding == 4) { // 4 = QUOTED-PRINTABLE
						$aAttachment[$i]['attachment'] = quoted_printable_decode($aAttachment[$i]['attachment']);
					}
				}
				}
				if(!$aAttachment[$i]['name'])$aAttachment[$i]['name']=$aAttachment[$i]['filename'];
			}
		}else{
			$i = 0;
			if($oStructure->ifdparameters) {
				foreach($oStructure->dparameters as $oObject) {
					if(strtolower($oObject->attribute) == 'filename') {
						$aAttachment[$i]['is_attachment'] = true;
						$aAttachment[$i]['filename'] = Mail::DecodeMimeString($oObject->value);
					}
				}
			}

			if($oStructure->ifparameters) {
				foreach($oStructure->parameters as $oObject) {
					if(strtolower($oObject->attribute) == 'name') {
						$aAttachment[$i]['is_attachment'] = true;
						$aAttachment[$i]['name'] = Mail::DecodeMimeString($oObject->value);
					}
				}
			}

			if($aAttachment[$i]['is_attachment']) {
				$aAttachment[$i]['attachment'] = imap_fetchbody($oAccount, $iNumberEmail, $i+1);
				if($oStructure->encoding == 3) { // 3 = BASE64
					$aAttachment[$i]['attachment'] = base64_decode($aAttachment[$i]['attachment']);
				}
				elseif($oStructure->encoding == 4) { // 4 = QUOTED-PRINTABLE
					$aAttachment[$i]['attachment'] = quoted_printable_decode($aAttachment[$i]['attachment']);
				}
			}
			if(!$aAttachment[$i]['name'])$aAttachment[$i]['name']=$aAttachment[$i]['filename'];
		}
		return $aAttachment;
	}
	//-----------------------------------------------------------------------------------------------
	function DecodeMimeString($sString) {
		$string = $sString;
		if(($pos = strpos($string,"=?")) === false) return iconv("WINDOWS-1251", "UTF-8",$string);
		// =?UTF-8?Q?=D0=9F=D1=80=D0=B0=D0=B9=D1=81-=D0=BB=D0=B8=D1=81?= =?UTF-8?Q?=D1=82_AsiaPart?= =?UTF-8?Q?s_=D0=BE=D1=82_14.09.2017.xlsx?=
		// расшифрует => Прайс-лис т AsiaPart s от 14.09.2017.xlsx - лишние пробелы?
		$string = str_replace(" =?UTF-8?Q?","=?UTF-8?Q?",$string);	
		while(!($pos === false)) {
			$newresult .= substr($string,0,$pos);
			$string = substr($string,$pos+2,strlen($string));
			$intpos = strpos($string,"?");
			$charset = substr($string,0,$intpos);
			$enctype = strtolower(substr($string,$intpos+1,1));
			$string = substr($string,$intpos+3,strlen($string));
			$endpos = strpos($string,"?=");
			$mystring = substr($string,0,$endpos);
			$string = substr($string,$endpos+2,strlen($string));
			if($enctype == "q") $mystring = quoted_printable_decode(str_replace("_"," ",$mystring));
			else if ($enctype == "b") $mystring = base64_decode($mystring);
			$newresult .= $mystring;
			$pos = strpos($string,"=?");
		}

		$result = $newresult.$string;
		if(preg_match('/koi8/', strtolower($sString))) $result = iconv("KOI8-R", "UTF-8",$result);
		elseif (preg_match('/iso-8859-5/', strtolower($sString))) $result = iconv("iso-8859-5", "UTF-8",$result);
		elseif (preg_match('/utf-8/', strtolower($sString))) $result = $result;
		else $result = iconv("WINDOWS-1251", "UTF-8",$result);

		//if(preg_match('/windows/', strtolower($sString))) $result = iconv("WINDOWS-1251", "UTF-8",$result);

		return $result;
	}
	//-----------------------------------------------------------------------------------------------
	function DeleteEmail($oAccount,$iNumberEmail){
		imap_delete($oAccount,$iNumberEmail);
		return imap_expunge($oAccount);
	}
	//-----------------------------------------------------------------------------------------------
	function CloseAcount($oAccount){
		return imap_close($oAccount);
	}
	//-----------------------------------------------------------------------------------------------
	function GetSenderEmail($oAccount,$iNumberEmail){
		$aHeaderForEmail = imap_headerinfo($oAccount,$iNumberEmail);
		return $aHeaderForEmail->from[0]->mailbox . "@" . $aHeaderForEmail->from[0]->host;
	}
	
}
?>
