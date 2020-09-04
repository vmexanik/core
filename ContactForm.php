<?
/**
 * @author Mikhail Strovoyt
 */

class ContactForm extends Base
{
	//-----------------------------------------------------------------------------------------------
	public function __construct()
	{
		Base::$aData['template']['bWidthLimit']=true;
	}
	//-----------------------------------------------------------------------------------------------
	public function OutputForm($sName)
	{
		$aData=array(
		'table'=>'form',
		'where'=>" and code='".$sName."'",
		);
		$aForm=Base::$language->getLocalizedRow($aData);

		$aData=array(
		'table'=>'form_item',
		'where'=>" and id_form='".$aForm['id']."' order by num",
		);
		$aFormItem=Base::$language->getLocalizedAll($aData);

		if ($aFormItem) foreach ($aFormItem as $sKey => $aValue) {
			if (in_array($aValue['type'], array('select','email_select','multiple_checkbox')) ) {
				$aData=array(
				'table'=>'form_value',
				'where'=>" and id_item='".$aValue['id']."' order by num",
				);
				$aFormItem[$sKey]['value']=Base::$language->getLocalizedAll($aData);
			}
		}

		Base::$tpl->assign('aForm',$aForm);
		Base::$tpl->assign('aFormItem',$aFormItem);

		return Base::$tpl->fetch('contact_form/index.tpl');;
	}
	//-----------------------------------------------------------------------------------------------
	public function ProcessForm($sName)
	{
		require_once(SERVER_PATH.'/class/core/String.php');
		$date=date("d F, Y :: h:i A");
		$sBodyHtml.="<h5>$date</h5>";

		$sQuery="select * from form where code='".Base::$aRequest['form_code']."'";
		$aForm=Base::$db->getRow($sQuery);

		$aFormItem=Base::$db->getAll("select * from form_item where id_form='".$aForm['id']."' order by num");
		foreach($aFormItem as $sKey => $aValue)
		{
			$value="";
			$tmp="field".$aValue[id];

			switch ($aValue[type]) {
				case 'textarea':
					$value=nl2br(Base::$aRequest[$tmp]);
					break;
				case 'separator':
					$value="-------------------------------";
					break;
				default:
					$value=nl2br(Base::$aRequest[$tmp]);
					if (String::CheckEmail(trim(Base::$aRequest[$tmp]))) $sFromEmailUser=trim(Base::$aRequest[$tmp]);
					break;
			}

			$sBodyHtml.="<br><b>".$aValue[caption].":</b> ".$value;
		}

		$sSubject="Contact Form from ".SERVER_NAME.$_SERVER['REQUEST_URI'];

		if (Base::$aRequest['not_bot']) {
			Mail::$bAddedNoRply=false;
			$sFromEmail=Base::GetConstant('global:email_from');
			if ($sFromEmailUser) $sFromEmail=$sFromEmailUser;
			$bSendResult=Mail::SendNow(Base::GetConstant('global:to_email','mstarrr@gmail.com'),$sSubject,$sBodyHtml,$sFromEmail);
		}
		else $bSendResult=true;

		if ($bSendResult) {
			$result=mysql_query("select * from drop_down where code='sendcontact'");
			$row=mysql_fetch_array($result);
			if (!$row[text]) $row[text]=Language::getMessage('You message is successfully sent');
			$sOutput.="<br><div style=\"border: 1px solid #CCCCCC; background-color: #FFFFFF; padding:6px;\">$row[text]</div>";
		}
		else {
			$aEmail=array('text'=>str_replace("@","{-at-}",Base::GetConstant('global:to_email')));
			require_once(SERVER_PATH.'/class/system/Content.php');
			require_once(SERVER_PATH.'/class/core/String.php');
			$sOutput=" ".String::GetSmartyTemplate('contact_form_send_error',array('email'=>$aEmail));
		}
		Base::$tpl->assign('sOutput',$sOutput);

		return $this->OutputForm($sName);
	}
	//-----------------------------------------------------------------------------------------------
}
?>