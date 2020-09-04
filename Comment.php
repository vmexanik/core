<?
/**
 * @author Oleg Maki
 * @author Mikhail Strovoyt
 */

class Comment extends Base
{
	//-----------------------------------------------------------------------------------------------
	public function __construct()
	{
	}
	//-----------------------------------------------------------------------------------------------
	public function GetCommentList($sSection,$sId,$bXajaxResponse=false,$bHideUnapproved=false)
	{
		Base::$tpl->assign('sRefId',$sId);
		Base::$tpl->assign('sSection',$sSection);
		Base::$tpl->assign('bXajaxResponse',$bXajaxResponse);

		Base::$bXajaxPresent=true;

		//require_once(SERVER_PATH.'/class/core/Table.php');
		$oTable=new Table();
		$oTable->sSql="select * from comment where section='$sSection' and ref_id='$sId' ";
		if ($bHideUnapproved) $oTable->sSql.=" and is_approved='1'";
		$oTable->aOrdered="order by post";
		$oTable->aColumn=array(
		'full'=>array('sTitle'=>'Comments','sWidth'=>'100%'),
		);
		$oTable->sDataTemplate='comment/row_comment.tpl';
		//$oTable->aCallback=array($this,'CallParseSiteNews');
		$oTable->bStepperVisible=true;
		$oTable->bAjaxStepper=true;
		$oTable->iRowPerPage=30;
		$oTable->bHeaderVisible=false;

		$sTableText=$oTable->getTable();
		Base::$tpl->assign('iCommentNumber',$oTable->iAllRow);
		$sCommentText.=Base::$tpl->fetch('comment/list.tpl');
		$sCommentText.=$sTableText;

		$sCommentText.=Base::$tpl->fetch('comment/new_comment.tpl');
		return $sCommentText;
	}
	//-----------------------------------------------------------------------------------------------
	public function GetCommentListTree($sSection,$sId,$bXajaxResponse=false)
	{
		Base::$tpl->assign('sRefId',$sId);
		Base::$tpl->assign('sSection',$sSection);
		Base::$tpl->assign('bXajaxResponse',$bXajaxResponse);

		Base::$bXajaxPresent=true;

		require_once(SERVER_PATH.'/class/core/Tree.php');
		$oTree=new Tree();
		$oTree->sSql="select * from comment_tree where section='$sSection' and ref_id='$sId'";
		$oTree->aOrdered="order by post";
		$oTree->aColumn=array(
		'full'=>array('sTitle'=>'Comments','sWidth'=>'100%'),
		);
		$oTree->sDataTemplate='comment_tree/row_comment.tpl';
		//$oTable->aCallback=array($this,'CallParseSiteNews');

		$sTreeText=$oTree->getTree();
		Base::$tpl->assign('iCommentNumber',$oTree->iAllRow);
		$sCommentText.=Base::$tpl->fetch('comment_tree/list.tpl');
		$sCommentText.=$sTreeText;

		return $sCommentText;
	}
	//-----------------------------------------------------------------------------------------------
	public function GetCommentLink($sSection,$sId,$sLink,$bHideUnapproved=false)
	{
		Base::$tpl->assign('sLink',$sLink);
		Base::$tpl->assign('sId',$sId);

		$sCountSql="select count(*) from comment where section='$sSection' and ref_id='$sId'";
		if ($bHideUnapproved) $sCountSql.=" and is_approved='1'";

		Base::$tpl->assign('iCommentNumber',Db::GetOne($sCountSql));
		return Base::$tpl->fetch('comment/link.tpl');
	}
	//-----------------------------------------------------------------------------------------------
	public function Post()
	{
		Base::$db->Execute("insert into comment (section,ref_id,name,email,site,content,post,ip)
			value('".Base::$aRequest['section']."','".Base::$aRequest['ref_id']."','".Base::$aRequest['name']."'
				,'".Base::$aRequest['email']."',
				'".Base::$aRequest['site']."','".strip_tags(Base::$aRequest['content'])
		."',UNIX_TIMESTAMP(),'".Auth::GetIp()."')");

		Base::$oResponse->addAlert(Language::getMessage('Your comment successfully added.'));

		$sCommentList=$this->GetCommentList(Base::$aRequest['section'],Base::$aRequest['ref_id'],true);
		Base::$oResponse->addAssign('comment_div','innerHTML', $sCommentList);
	}
	//-----------------------------------------------------------------------------------------------
	public function PopupPost()
	{
		$aRequestComment=String::FilterRequestData(Base::$aRequest['data'],
			array('content','section','ref_id','num_rating'));
		$aRequestComment['ip']=Auth::GetIp();
		$aRequestComment['name']=Auth::$aUser['login'];
		Base::$db->Autoexecute('comment',$aRequestComment);

		$aComment=Db::GetAll(Base::GetSql('Comment',array(
			'section'=> Base::$aRequest['data']['section'],
			'ref_id'=> Base::$aRequest['data']['ref_id'],
			'order' => "order by c.post_date desc",
			'use_rating'=>true
		)));

		if ($aRequestComment['num_rating']) {
			Rating::Change('user_quality',$aRequestComment['ref_id'],$aRequestComment['num_rating']);
			Base::$tpl->assign('iRatingNumCurrent',$aRequestComment['num_rating']);
			$aRatingList=Db::GetAll(Base::GetSql('Rating',array(
				'section'=> 'user_quality',
				'order'=>'order by r.num'
			)));
			Base::$tpl->assign('aRatingList',$aRatingList);
			Base::$tpl->assign('bCustomerPopup',true);
		}else 
			Base::$tpl->assign('bCustomerPopup',false);
			
		if(Base::$aRequest['data']['section']=='cart'){
			$aCart=Db::GetRow(Base::GetSql('Cart',array(
				'id'=>Base::$aRequest['data']['ref_id'],
				'join_cart_delay'=>true)));
			Base::$tpl->assign('aCartSign',$aCart);
		}
		Base::$tpl->assign('aComment',$aComment);
		Base::$tpl->assign('sSection',Base::$aRequest['data']['section']);
		Base::$tpl->assign('iRefId',Base::$aRequest['data']['ref_id']);
		Base::$tpl->assign('bXajaxAssign',true);
		Base::$oResponse->addAssign('comment_link_popup_id_'.Base::$aRequest['data']['ref_id'],'innerHTML',
		Language::GetMessage("Comment link popup ".Base::$aRequest['data']['section']));
		$sCommentPopup=Base::$tpl->fetch('hint/comment.tpl');

		$sSpanId='comment_'.Base::$aRequest['data']['section'].'_'.Base::$aRequest['data']['ref_id'];
		Base::$oResponse->addAssign($sSpanId,'innerHTML', $sCommentPopup);
	}
	//-----------------------------------------------------------------------------------------------
	public function GetCommentHash($sSection,$aId)
	{
		$aComment=Db::GetAll(Base::GetSql('Comment',array(
		'section'=>$sSection,
		'where'=>" and c.ref_id in (".implode(',',$aId).")",
		'order'=>" order by c.ref_id, c.post_date desc",
		'use_rating'=>true
		)));
		if ($aComment) foreach ($aComment as $aValue) $aCommentHash[$aValue['ref_id']][]=$aValue;

		return $aCommentHash;
	}
	//-----------------------------------------------------------------------------------------------
}
?>