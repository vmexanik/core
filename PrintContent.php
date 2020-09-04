<?
/**
 * @author Mikhail Strovoyt
 */

class PrintContent extends Base
{

	//-----------------------------------------------------------------------------------------------
	public function Append($sContent)
	{
		$sContent=mysql_real_escape_string($sContent);

		Base::$db->Execute("insert into print_content (id_user,content) values ('".Auth::$aUser['id']."','".$sContent."')
			ON DUPLICATE KEY UPDATE content='".$sContent."';");
	}
	//-----------------------------------------------------------------------------------------------
	public function Index()
	{
		$aRow=Base::$db->getRow("select * from print_content where 1=1 ".Auth::$sWhere);

		Base::$sText.=$aRow['content'];
		Base::$tpl->assign('sContent',$aRow['content']);
		
		if (Base::GetConstant('print_content:autoprint',0)==1)
			Base::$tpl->assign('sOnloadPrint',"onload='window.print(); return false;'");
		
		if (Base::$aRequest['before_content_buttons_print']) 
			Base::$tpl->assign('iBeforeContentButtonsPrint',1);
		
		Base::$tpl->assign('iCloseButton',Base::GetConstant('print_content:close_button',1));
		Base::$tpl->assign('bCloseButtonAsReturn',Base::GetConstant('print_content:close_button_as_return',0));
		
		Base::$sBaseTemplate='addon/print_content/index.tpl';
	}
	//-----------------------------------------------------------------------------------------------
}
?>