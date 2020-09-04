<?
/**
 * @author Mikhail Starovoyt
 */

class DirectorySite extends Base
{
	//-----------------------------------------------------------------------------------------------
	public function __construct()
	{
		Base::$aData['template']['bWidthLimit']=false;
	}
	//-----------------------------------------------------------------------------------------------
	function Prepare()
	{
		if (!$_SESSION[directory_site_category]) {
			$_SESSION[directory_site_category]=Base::$db->getRow("select * from directory_site_category
				where level='1' and visible='1' order by num limit 0,1 ");
		}

		if (!$_SESSION[directory_site_order_field]) {
			$row=Base::$db->getRow("select * from directory_site_config");
			$_SESSION[directory_site_order_field]="$row[order_field]";
			$_SESSION[directory_site_order_way]="$row[order_way]";
			$_SESSION[directory_site_display_select]="$row[display_select]";
		}
	}
	//-----------------------------------------------------------------------------------------------
	public function CategoryChange()
	{
		$aRow=Base::$db->getRow("select * from directory_site_category where id='".Base::$aRequest['id']."' and visible='1'");
		if ($aRow[id]) {
			$_SESSION[directory_site_category]=$aRow;
		}
		$this->Index();
	}
	//-----------------------------------------------------------------------------------------------
	public function ListCategory()
	{
		$aCategory=Base::$db->getAll("select * from directory_site_category where id_parent='$id_parent'
			and visible='1' order by num");
		foreach ($aCategory as $row)
		{
			if ($_SESSION[directory_site_category]=="")  $_SESSION[directory_site_category]=$row;

			$text.="$li&nbsp; <a href='?action=directory_site_category_change&id=$row[id]'
				".($_SESSION[directory_site_category][id]==$row[id] ? 'class=category_link_active':'class=category_link')."
				><img src='image/item_category".($_SESSION[directory_site_category][id]==$row[id] ? '_open':'').".gif'
					align=absmiddle hspace=0 vspace=2 border=0>$row[name]</a> <br> ";
		}
		return $text;
	}
	//-----------------------------------------------------------------------------------------------
	public function Index()
	{
		$this->Prepare();

		$oTable=new Table();
		$oTable->sSql="select * from directory_site where id_directory_site_category='".$_SESSION[directory_site_category][id]."'
			and visible=1";
		$oTable->aColumn=array(
		'name'=>array('sTitle'=>'Description'),
		'post'=>array('sTitle'=>'Added Date'),
		);
		$oTable->sDataTemplate='addon/directory_site/row_directory_site.tpl';

		Base::$tpl->assign('sCategory',$this->ListCategory());
		Base::$tpl->assign('sMainContent',$oTable->getTable("Direcotry Site"));
		Base::$sText=Base::$tpl->fetch("addon/directory_site/index.tpl");
	}
	//-----------------------------------------------------------------------------------------------
	public function Preview()
	{
		$aDirectorySite=Base::$db->getRow("select * from directory_site where
			id='".Base::$aRequest['id']."' and visible='1'");
		Base::$tpl->assign('aDirectorySite',$aDirectorySite);

		Base::$sText.=Base::$tpl->fetch("addon/directory_site/preview.tpl");
		Base::$aData['template']['sPageTitle']=$aDirectorySite['name']." - ".String::FirstNwords($aDirectorySite['description'],10);
	}
	//-----------------------------------------------------------------------------------------------

}
?>