<?
/**
 * @author Mikhail Strovoyt
 */


class Form extends Base
{
	public $sHeader="method=get";
	public $sAdditionalTitle;
	public $sTitle;
	public $sContent='empty form';
	public $sClass='form';
	public $sSubmitButton='';
	public $sSubmitAction='';
	public $sReturnButton='';
	public $sReturnAction='';
	public $sError='';
	public $sErrorNT='';
	public $sHidden='';
	public $sConfirmText='Are you sure you want to submit form?';
	public $bIsPost=true;
	public $sWidth='500px';
	public $bShowBottomForm=true;
	public $bConfirmSubmit=false;
	//padding between form and buttons
	public $sButtonsPadding=5;
	public $bAutoReturn=false;
	public $sAdditionalButton='';
	/* add other button to form */
	public $sAdditionalButtonTemplate='';
	/* for return for current page */
	public $sReturn='';
	public $sRightTemplate='';
	public $bSetDefault = true;

	public  $sTemplatePath='addon/form/index.tpl';
	public static $sBeforeContent='';
	public static $sAfterContent='';
	public static $sTitleDivHeader="class='form_header'";

	public $sReturnButtonClass="btn";
	public $sSubmitButtonClass="btn";

	public $bCustomOnClick='';
    public $sCustomOnClick='';

	/* place button return */
	public $bReturnAfterSubmit=false;

	public static $sButtonSpanClass="";
	
	public $sButtonDivClass = '';
	public $aField = array();
	public $bType = 'static';
	public $sGenerateTpl = 'form/index.tpl';
	
	/* AOT-64 need disable submit button form*/
	public $sSubmitActionDisable = false;
	/* EMG-20 if form design own */
	public $sDisableStyleForm = false;
	//-----------------------------------------------------------------------------------------------
	public function __construct($aData=array())
	{
		if ($aData) foreach ($aData as $key => $value) {
			$this->$key = $value;
		}
	}
	//-----------------------------------------------------------------------------------------------
	public function GetForm($sContent='')
	{
	    if($this->bType=='generate') {
	       Base::$tpl->assign('aField',$this->aField);
	       if($this->sGenerateTpl=='form/index_search.tpl') $this->sWidth='100%';
	       $this->sContent=Base::$tpl->fetch($this->sGenerateTpl);
	    }
		if ($this->bSetDefault && method_exists(Base::$oContent,'setFormDefault')) Base::$oContent->setFormDefault($this);
		if ($sContent) $this->sContent=$sContent;
		Base::$tpl->assign('sHeader',$this->sHeader);
		Base::$tpl->assign('sTitle',Base::$language->getMessage($this->sTitle));
		Base::$tpl->assign('sAdditionalTitle',$this->sAdditionalTitle);
		Base::$tpl->assign('sSubmitButton',Base::$language->getMessage($this->sSubmitButton));
		Base::$tpl->assign('sSubmitAction',$this->sSubmitAction);
		Base::$tpl->assign('sConfirmText',$this->sConfirmText);
		Base::$tpl->assign('sReturnButton',Base::$language->getMessage($this->sReturnButton));
		if ($this->bAutoReturn)
		{   //for autoreturn
			Base::$tpl->assign('bAutoReturn',$this->bAutoReturn);
			if (!$this->sReturnAction) $this->sReturnAction="?".$this->RemoveMessageFromUrl(Base::$aRequest['return']);
		}
		if (!$this->sReturnAction) $this->sReturnAction=$this->sSubmitAction;//default action is submit
		Base::$tpl->assign('sReturnAction',$this->sReturnAction);
		if ($this->sError) Base::$tpl->assign('sFormError',Language::GetText($this->sError));
		elseif ($this->sErrorNT) Base::$tpl->assign('sFormError',$this->sErrorNT);
		else 
			Base::$tpl->assign('sFormError','');
		Base::$tpl->assign('sContent',$this->sContent);
		Base::$tpl->assign('sClass',$this->sClass);
		Base::$tpl->assign('sHidden',$this->sHidden);
		Base::$tpl->assign('bIsPost',$this->bIsPost);
		Base::$tpl->assign('sWidth',$this->sWidth);
		Base::$tpl->assign('bShowBottomForm',$this->bShowBottomForm);
		if ($this->sHint) Base::$tpl->assign('sHint',Base::$language->getContextHint($this->sHint));
		Base::$tpl->assign('bConfirmSubmit',$this->bConfirmSubmit);
		Base::$tpl->assign('sAdditionalButton',$this->sAdditionalButton);
		Base::$tpl->assign('sAdditionalButtonTemplate',$this->sAdditionalButtonTemplate);
		if ($this->sReturn) Base::$tpl->assign('sReturn',$this->sReturn);
		Base::$tpl->assign('sRightTemplate',$this->sRightTemplate);
		Base::$tpl->assign('sBeforeContent',Form::$sBeforeContent);
		Base::$tpl->assign('sAfterContent',Form::$sAfterContent);
		Base::$tpl->assign('sTitleDivHeader',Form::$sTitleDivHeader);

		Base::$tpl->assign('sReturnButtonClass',$this->sReturnButtonClass);
		Base::$tpl->assign('sSubmitButtonClass',$this->sSubmitButtonClass);
		Base::$tpl->assign('sButtonsPadding',$this->sButtonsPadding);

		Base::$tpl->assign('sButtonSpanClass',Form::$sButtonSpanClass);
		Base::$tpl->assign('bReturnAfterSubmit',$this->bReturnAfterSubmit);
		
		Base::$tpl->assign('sButtonDivClass',$this->sButtonDivClass);
		Base::$tpl->assign('sSubmitActionDisable',$this->sSubmitActionDisable);
		Base::$tpl->assign('sDisableStyleForm',$this->sDisableStyleForm);
		
		return Base::$tpl->fetch($this->sTemplatePath);
	}
	//-----------------------------------------------------------------------------------------------
	public function ShowError($sError)
	{
		Base::$tpl->assign('sFormError',Base::$language->getMessage($sError));
		//$this->sError=Base::$language->getMessage($sError);
	}
	//-----------------------------------------------------------------------------------------------
	public static function BeforeReturn($sAction,$sEditAction='')
	{
		if (stripos($_SESSION['referer_page'],$sEditAction)===false)
		$_SESSION['form'][$sAction]['return_url']="./?".$_SESSION['referer_page'];
	}
	//-----------------------------------------------------------------------------------------------
	public static function AfterReturn($sAction,$sMessage='')
	{
		if ($_SESSION['form'][$sAction]['return_url'] ) Base::Redirect($_SESSION['form'][$sAction]['return_url'].$sMessage);
		else Base::Redirect("./?action=$sAction".$sMessage);
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Redirect to before page
	 *
	 * @param string $sMessage - for display message after redirect
	 */
	public static function RedirectAuto($sMessage="")
	{
		Base::Redirect("?".Base::RemoveMessageFromUrl(Base::$aRequest['return']).$sMessage);
	}
	//-----------------------------------------------------------------------------------------------
	public static function Error404($bRedirectMissing=false)
	{
		if (method_exists(Base::$oContent,'Error404')) return Content::Error404($bRedirectMissing);
		if (!Base::GetConstant('global:404_empty_page',0)) return;

		if (Base::GetConstant('global:404_exclude_query')) {
			$aExcludeQuery=preg_split("/[\s,]+/", Base::GetConstant('global:404_exclude_query'));
			foreach ($aExcludeQuery as $aValue) {
				if (strpos($_SERVER['QUERY_STRING'],$aValue)!==false) return;
			}
		}
		if ($bRedirectMissing) {
			Base::Redirect('/missing/');
		} else {
			$aMissing=Db::GetRow("select * from drop_down where code='missing'");
			Base::$sText.=$aMissing['text'];
			Base::$aData['template']['sPageKeyword'] = $aMissing['page_keyword'];
			Base::$aData['template']['sPageDescription'] = $aMissing['page_description'];
			Base::$aData['template']['sPageTitle'] = $aMissing['title'];
		}

		$bRedirectSent=false;
		$aHeader=headers_list();
		foreach($aHeader as $aValue)
		if (stripos($aValue,'Location:')!==false) {
			$bRedirectSent=true;
			break;
		}
		if (!$bRedirectSent) header("HTTP/1.0 404 Not Found");
	}
	//-----------------------------------------------------------------------------------------------
}
?>