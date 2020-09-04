<?
/**
 * @author Mikhail Strovoyt
 */

class Message extends Base
{
	//-----------------------------------------------------------------------------------------------
	public function __construct()
	{
		Repository::InitDatabase('message');
		Base::$aData['template']['bWidthLimit']=true;
		Base::$aTopPageTemplate=array('panel/tab_'.Auth::$aUser['type_'].'.tpl'=>'message');
	}
	//-----------------------------------------------------------------------------------------------
	function Index()
	{
		$this->Prepare();

		Base::$aData['template']['sPageTitle']=Language::getMessage('title:message_list');

		$aData=array(
		'sHeader'=>"method=get",
		'sContent'=>Base::$tpl->fetch('addon/message/form_message_search.tpl'),
		'sSubmitButton'=>'Search',
		'sSubmitAction'=>'message',
		'sReturnButton'=>'Clear',
		'sReturnAction'=>'message',
		'bIsPost'=>0,
		'sError'=>$sError,
		);
		$oForm=new Form($aData);

		Base::$tpl->assign('sSearchForm',$oForm->getForm());

		// --- search ---
		if (Base::$aRequest['search_from']) $sWhere.=" and m.from ='".Base::$aRequest['search_from']."'";
		if (Base::$aRequest['search_to']) $sWhere.=" and m.to ='".Base::$aRequest['search_to']."'";
		if (Base::$aRequest['search_date']) {
			$sWhere.=" and m.timestamp>='".strtotime(Base::$aRequest['date_from'])."'
				and m.timestamp<='".strtotime(Base::$aRequest['date_to'])."'";
		}
		$sWhere.=" and is_old='0'";
		// --------------

		$oTable=new Table();
		$oTable->iRowPerPage=20;
		$oTable->sSql="select m.* from message m where
			 id_message_folder='".$_SESSION[message][current_folder_id]."'
			 ".$sWhere.Auth::$sWhere ;
		$oTable->aOrdered="order by id desc ";
		$oTable->aColumn=array(
		//'checkbox'=>array('sTitle'=>'','sWidth'=>'4%'),
		'subject'=>array('sTitle'=>'Subject','sWidth'=>'40%'),
		'from'=>array('sTitle'=>'From','sWidth'=>'70'),
		'to'=>array('sTitle'=>'To','sWidth'=>'70'),
		'post'=>array('sTitle'=>'Post Date'),
		'action'=>array(),
		);
		$oTable->sDataTemplate='addon/message/row_message.tpl';
		//$oTable->aCallback=array($this,'CallParseMessage');
		$oTable->bFormAvailable=false;
		$oTable->bCheckAllVisible=true;
		$oTable->bCheckVisible=true;

		$sMainSection=$oTable->getTable().Base::$tpl->fetch('addon/message/browse_bottom.tpl');
		Base::$tpl->assign('sMainSection',$sMainSection);

		Base::$sText.=Base::$tpl->fetch('addon/message/index.tpl');
	}
	//-----------------------------------------------------------------------------------------------
	function Prepare()
	{
		Auth::NeedAuth();
		if (!$_SESSION[message] || !$_SESSION[message][current_folder_id]) {
			$_SESSION[message][current_folder_id]="1";
			$_SESSION[message][current_folder_name]="Inbox";
			$_SESSION[message][current_step]="1";
			$_SESSION[message][order_field]="timestamp";
			$_SESSION[message][order_way]="desc";
		}
		Base::$aRequest[text]=substr(Base::$aRequest[text],0,1024);
		Base::$aRequest[to]=substr(Base::$aRequest[to],0,50);
		Base::$aRequest[subject]=substr(Base::$aRequest[subject],0,50);
		$aMessageNumber['inbox']=$this->MessageNumber(Auth::$aUser['id'],1);
		$aMessageNumber['outbox']=$this->MessageNumber(Auth::$aUser['id'],2);
		$aMessageNumber['draft']=$this->MessageNumber(Auth::$aUser['id'],3);
		$aMessageNumber['deleted']=$this->MessageNumber(Auth::$aUser['id'],4);
		Base::$tpl->assign('aMessageNumber',$aMessageNumber);
	}
	//-----------------------------------------------------------------------------------------------
	function MoveToFolder()
	{
		if ($_REQUEST[id_message]=="") {
			foreach(Base::$aRequest['row_check'] as $value) $this->MoveMessage($value,$_REQUEST[move_to_folder]);
		}
		else $this->MoveMessage($_REQUEST[id_message],$_REQUEST[move_to_folder]);
		$this->Index();
	}
	//-----------------------------------------------------------------------------------------------
	function Reply()
	{
		if ($_REQUEST[id_message]) {
			$this->Prepare();

			Base::$tpl->assign("user_id",Auth::$aUser[id]);

			$message = Base::$db->getRow("SELECT * FROM message where id='$_REQUEST[id_message]'".Auth::$sWhere);

			$aMessage['to']= $message[from];
			$aMessage['subject']= "RE:".$message[subject];
			$aMessage['from']= Auth::$aUser[login];
			Base::$tpl->assign('aMessage',$aMessage);

			Base::$tpl->assign("text","\n\n");
			Base::$tpl->assign("post_date", DateFormat::getDateTime($message[timestamp]));

			Base::$tpl->assign('to_input',1);
			Base::$tpl->assign('send_button',1);
			Base::$tpl->assign('draft_button',1);
			Base::$tpl->assign('discard_button',1);
			Base::$tpl->assign('subject_input',1);
			Base::$tpl->assign('textarea_begin',1);
			Base::$tpl->assign('textarea_end',1);
			Base::$tpl->assign('textarea_reply',1);

			Base::$tpl->assign("reply_text","On ".DateFormat::getDateTime($message[timestamp])
			." \n$message[from] wrote : \n----------------------------\n$message[text]");

			Base::$tpl->assign('section', "Messages - Reply");
			Base::$tpl->assign('sMainSection',Base::$tpl->fetch('addon/message/view.tpl'));

			Base::$sText.=Base::$tpl->fetch('addon/message/index.tpl');
		}
		else {
			$this->Index();
		}
	}
	//-----------------------------------------------------------------------------------------------
	function Forward()
	{
		if ($_REQUEST[id_message] || $_REQUEST[id]) {
			$this->Prepare();

			if ($_REQUEST[id_message]=="") {
				$_REQUEST[id_message]=array_shift($_REQUEST[id]);
			}
			Base::$tpl->assign("user_id",Auth::$aUser[id]);

			$message = Base::$db->getRow("SELECT * FROM message where
						id='$_REQUEST[id_message]'".Auth::$sWhere);
			$aMessage['subject']= "Fwd:".$message[subject];
			$aMessage['from']= Auth::$aUser[login];
			Base::$tpl->assign('aMessage',$aMessage);
			Base::$tpl->assign("to",'');


			Base::$tpl->assign("text","---------- Forwarded message ----------\nDate: "
			.DateFormat::getDateTime($message[timestamp])
			."\nSubject: $message[subject]\nFrom: $message[from]\nTo: $message[to]"
			."\n--------------------------------------\n$message[text]");
			Base::$tpl->assign("post_date", DateFormat::getDateTime($message[timestamp]));

			Base::$tpl->assign('to_input',1);
			Base::$tpl->assign('send_button',1);
			Base::$tpl->assign('draft_button',1);
			Base::$tpl->assign('discard_button',1);
			Base::$tpl->assign('subject_input',1);
			Base::$tpl->assign('textarea_begin',1);
			Base::$tpl->assign('textarea_end',1);

			Base::$tpl->assign('section', "Messages - Forward Message");
			Base::$tpl->assign('sMainSection',Base::$tpl->fetch('addon/message/view.tpl'));

			Base::$sText.=Base::$tpl->fetch('addon/message/index.tpl');
		}
		else {
			$this->Index();
		}
	}
	//-----------------------------------------------------------------------------------------------
	function Send()
	{
		if ($_REQUEST[reply_text]!='') $_REQUEST[reply_text]="\n\n$_REQUEST[reply_text]";//adding delimiters
		$this->SendMessage($_REQUEST[to],$_REQUEST[subject],$_REQUEST[text].$_REQUEST[reply_text],$_REQUEST[id_message]);

		$this->Index();
	}
	//-----------------------------------------------------------------------------------------------
	function Draft()
	{
		if ($_REQUEST[reply_text]!='') $_REQUEST[reply_text]="\n\n".$_REQUEST[reply_text];//adding delimiters

		$this->CreateMessage($_REQUEST[to],$_REQUEST[subject],3,1,$_REQUEST[text].$_REQUEST[reply_text]);
		$this->Index();
	}
	//-----------------------------------------------------------------------------------------------
	function Compose()
	{
		$this->Prepare();

		Base::$aData['template']['sPageTitle']= Language::getMessage('title:message_compose');

		Base::$tpl->assign('to_input',1);
		Base::$tpl->assign('subject_input',1);
		Base::$tpl->assign('send_button',1);
		Base::$tpl->assign('draft_button',1);
		Base::$tpl->assign('discard_button',1);
		Base::$tpl->assign('post_date',DateFormat::getDateTime());

		$aMessage['from']=Auth::$aUser['login'];
		$aMessage['to']= $_REQUEST[message_to];
		Base::$tpl->assign('aMessage',$aMessage);

		Base::$tpl->assign('user_id',Auth::$aUser['user_id']);
		Base::$tpl->assign('textarea_begin',1);
		Base::$tpl->assign('textarea_end',1);

		Base::$tpl->assign('section', "Messages - Compose Message");
		Base::$tpl->assign('sMainSection',Base::$tpl->fetch('addon/message/view.tpl'));

		Base::$sText.=Base::$tpl->fetch('addon/message/index.tpl');
	}
	//-----------------------------------------------------------------------------------------------
	function Delete()
	{
		if (Base::$aRequest['row_check']) {
			foreach(Base::$aRequest['row_check'] as $value) $this->DeleteMessage($value);
		}
		else $this->DeleteMessage($_REQUEST['id']);

		$this->Index();
	}
	//-----------------------------------------------------------------------------------------------
	function Clear()
	{
		//		Base::$db->Execute("delete from message where id_message_folder='4' ".Auth::$sWhere);
		//		$this->Index();
	}
	//-----------------------------------------------------------------------------------------------
	function Preview()
	{
		$aMessage = Base::$db->getRow("SELECT * FROM message where
					id='".Base::$aRequest[id]."' ".Auth::$sWhere);
		if ($aMessage) {
			$this->Prepare();

			$aUserList=$this->getUserList(array("'".$aMessage['from']."'"));
			$aUser=$aUserList[0];
			Base::$tpl->assign("aUser",$aUser);
			Base::$tpl->assign("aMessage", $aMessage);
			Base::$tpl->assign("id_message", $aMessage['id']);
			Base::$tpl->assign("post_date", DateFormat::getDateTime($aMessage[timestamp]));

			if ($aMessage[id_message_folder]==3) {
				//Preview draft messages.
				Base::$tpl->assign('to_input',1);
				Base::$tpl->assign('subject_input',1);
				Base::$tpl->assign('send_button',1);
				Base::$tpl->assign('textarea_begin',1);
				Base::$tpl->assign('textarea_end',1);
				Base::$tpl->assign('text', $aMessage['text']);
			}
			else {
				if (Base::GetConstant("message:nl2br",1)) Base::$tpl->assign('text', nl2br($aMessage['text']));
				else Base::$tpl->assign('text', $aMessage['text']);

				Base::$tpl->assign('subject_text',1);
				Base::$tpl->assign('to_text',1);

				if ($aMessage[id_message_folder]==1) Base::$tpl->assign('reply_button',1);
			}

			Base::$tpl->assign('discard_button',1);
			Base::$tpl->assign('forward_button',1);

			Base::$tpl->assign('aMessage', $aMessage);

			Base::$tpl->assign('section', "Messages - View");
			Base::$tpl->assign('sMainSection',Base::$tpl->fetch('addon/message/view.tpl'));

			Base::$sText.=Base::$tpl->fetch('addon/message/index.tpl');

			$this->ReadMessage(Base::$aRequest[id]);
		}
		else {
			$this->Index();
		}
	}
	//-----------------------------------------------------------------------------------------------
	function ChangeCurrentFolder()
	{
		if (Base::$aRequest[id_message_folder]!="") {
			$_SESSION[message][current_folder_id]=Base::$aRequest[id_message_folder];
			switch (Base::$aRequest[id_message_folder]) {
				case 2: $sTitle="Outbox"; break;
				case 3: $sTitle="Draft"; break;
				case 4: $sTitle="Deleted"; break;
				default: $sTitle="Inbox";
			}
			$_SESSION[message][current_folder_name]=$sTitle;
		}
		$this->Index();
	}
	//-----------------------------------------------------------------------------------------------
	function MessageNumber($iIdUser,$iIdMessageFolder)
	{
		$sWhere.=" and is_old='0'";
		return Base::$db->getOne("select count(*) from message where
			id_user='".$iIdUser."'
			and id_message_folder='".$iIdMessageFolder."'
			".$sWhere);
	}
	//---------------------------------------------------------------------------------------------------------
	function CreateMessage($sTo,$sSubject,$iIdMessageFolder,$iIsRead,$sText,$iIdUser='',$sUserFrom='')
	{
		if (!$iIdUser) $iIdUser=Auth::$aUser['id'];

		if (!$iIdUser && $iIdMessageFolder=2) return;//don't create messages from unknown

		if (!$sUserFrom) $sUserFrom=Auth::$aUser['login'];

		Base::$db->Execute("insert into message (`from`, `to`, subject, timestamp, id_user, id_message_folder,is_read, text)
			values
	    	     ('$sUserFrom', '$sTo', '$sSubject', UNIX_TIMESTAMP(), '$iIdUser',
	    	     	'$iIdMessageFolder', '$iIsRead',  '".mysql_real_escape_string($sText)."')");
		return Base::$db->Insert_ID();
	}
	//---------------------------------------------------------------------------------------------------------
	function SendMessage($sTo,$sSubject,$sText,$iIdMessage='',$bSendEmail=true)
	{
		$aWord=preg_split("/[\s,]+/",trim($sTo));
		if ($aWord) {
			foreach ($aWord as $key => $value) $aWord[$key]="'".$value."'";

			$aUser=Message::getUserList($aWord);
			if ($aUser) {
				foreach ($aUser as $key => $value) {
					Message::CreateMessage($value['login'],$sSubject,1,0,$sText,$value['id']);
					//Send a copy of message to uesr's email
					if ($value[copy_message] && $bSendEmail) {
						if (Base::GetConstant('message:added_user_template',0)) {
							$aTemplate=String::GetSmartyTemplate('message_added_user_template',array(
							'aFrom'=>Auth::$aUser,
							'aTo'=>$value,
							));
							$sText.=$aTemplate['parsed_text'];
						}

						Mail::SendNow($value['email'],$sSubject,stripslashes($sText));
					}
				}
				Message::CreateMessage($sTo,$sSubject,2,1,$sText);
				Message::EraseMessage($iIdMessage);
			}
		}
		else Message::ShowError();
	}
	//---------------------------------------------------------------------------------------------------------
	function DeleteMessage($iId)
	{
		$aMessage=Base::$db->getRow("select * from message where id='$iId'");
		//		if ($aMessage[id_message_folder]==4)
		//		Base::$db->Execute("delete from message where id='$iId' and id_user='".Auth::$aUser[id]."'");
		//		else
		Base::$db->Execute("update message set id_message_folder='4' where id='$iId' and id_user='".Auth::$aUser[id]."'");
	}
	//---------------------------------------------------------------------------------------------------------
	function EraseMessage($iId)
	{
		if ($iId) Base::$db->Execute("delete from message where id='$iId' and id_user='".Auth::$aUser[id]."'");
	}
	//---------------------------------------------------------------------------------------------------------
	function MoveMessage($iId,$iIdMessageFolder)
	{
		if ($iId && $iIdMessageFolder)
		Base::$db->Execute("update message set id_message_folder='$iIdMessageFolder'
			where id='$iId' and id_user='".Auth::$aUser[id]."'");
	}
	//---------------------------------------------------------------------------------------------------------
	function getUserList($aLogin)
	{
		//$admin=Base::$db->getRow("select * from admins where login='$sLogin'");
		//if ($admin[id]!="") return $admin;
		$sWhere.=" or login in (".implode(",", $aLogin).")";
		return Base::$db->getAll("select * from user where 1!=1 ".$sWhere);
	}

	//---------------------------------------------------------------------------------------------------------
	function ReadMessage($iId)
	{
		if ($iId) Base::$db->Execute("update message set is_read='1' where id='$iId' and id_user='".Auth::$aUser[id]."'");
	}
	//---------------------------------------------------------------------------------------------------------
	//	function ShowError($sLogin='')
	//	{
	//		$xtpl->assign("login", $sLogin);
	//		//$xtpl->assign("ERR_MSG", "$error_text");
	//		$xtpl->parse("main.register_error.error");
	//		$xtpl->parse("main.register_error");
	//	}
	//---------------------------------------------------------------------------------------------------------
	function CreateNotification($sTo,$sCode,$sType='customer',$aUserData=array(),$sToId='',$iPriority=3)
	{
		if (is_numeric($sToId)) $sWhere.=" or u.id='$sToId'";

		if ($sType=='customer') {
			$aUser=Base::$db->getRow("select u.*, uc.*, cg.name as customer_group_name
				from user u
				inner join user_customer uc on u.id=uc.id_user
				inner join customer_group cg on cg.id=uc.id_customer_group
				inner join user_account ua on u.id=ua.id_user
				where (u.login='$sTo' $sWhere)
				group by u.id");
		}
		else $aUser=Base::$db->getRow("select u.*	from user u	where (u.login='$sTo' $sWhere)");
		if (!$aUser) return false;

		$aData=array('user'=>$aUser,'user_data'=>$aUserData);
		require_once(SERVER_PATH.'/class/system/Content.php');
		require_once(SERVER_PATH.'/class/core/String.php');
		$sText=String::GetSmartyTemplate($sCode,$aData);

		$sSubject=String::FirstNwords($sText,5)." [".$aUser['login']."]";
		//$sSubject=Base::$aConstant['notification_subject']['value'];
		Message::SendMessage($aUser['login'],$sSubject,$sText);
		//echo("<br>".$aUser['login'].$sSubject.$sText);
	}
	//---------------------------------------------------------------------------------------------------------
	/**
	 * New method for sending notifications with delay, priority and bulk queue
	 */
	function CreateDelayedNotification($iIdUser,$sCode,$aUserData=array(),$bSmartyTemplate=false,$iIdCart=0)
	{
		$aUser=Base::$db->getRow("select u.* from user as u	where u.id='$iIdUser'");
		if (!$aUser) return false;

		switch($aUser['type_']) {
			case 'customer':
			default: $aUser=Db::GetRow(Base::GetSql('Customer',array(
			'id'=>$aUser['id'],
			'join_language'=>1,
			)));
		}

		$aUserNotificationRefused=Base::$db->GetAssoc("select code as id,id_user as value from user_notification_refused
			 where id_user='$iIdUser'");
		if ($aUser['receive_notification'] && !in_array($sCode,array_keys($aUserNotificationRefused)) ) {
			if ($bSmartyTemplate) {

				if ($aUser['code_language'] && $aUser['code_language']!=Language::$sBaseLocale) {
					$sBeforeLocale=Language::$sLocale;
					Language::$sLocale=$aUser['code_language'];
					$bChagedLocale=true;
				}
				$aTemplate=String::GetSmartyTemplate($sCode,$aUserData);
				if ($bChagedLocale) {
					Language::$sLocale=$sBeforeLocale;
				}

				$sText=$aTemplate['parsed_text'];
				$sSubject=$aTemplate['name']." [".$aUser['login']."]";
			}
			else {
				$aData=array('user'=>$aUser,'user_data'=>$aUserData);
				$aTemplate=Base::$db->GetRow(Base::GetSql('Template',array('code'=>$sCode)));
				$sText=String::GetSmartyTemplate($sCode,$aData);
				$sSubject=String::FirstNwords($sText,7)." [".$aUser['login']."]";
			}


			if ($aUser['notification_type']=='single') {
				Message::SendMessage($aUser['login'],$sSubject,$sText,'',false);
				if ($aUser['copy_message']) {
					Mail::AddDelayed($aUser['email'],$sSubject,stripslashes($sText),'','',true,$aTemplate['priority']);
				}
			}
			else {
				$aUserNotification=array(
				'id_user'=>$aUser['id'],
				'subject'=>$sSubject,
				'description'=>$sText,
				'id_cart'=>$iIdCart,
				);
				Db::AutoExecute('user_notification',$aUserNotification);
			}
		}
	}
	//---------------------------------------------------------------------------------------------------------
	function SendBulkUserNotification($sAdditionalWhere='')
	{
		$aUserNotification=Db::GetAll(Base::GetSql('UserNotification',array(
		'is_sent'=>'0',
		'where'=>" and u.notification_hour like '%".ltrim(Date('H'),'0').",%' ".$sAdditionalWhere,
		'order'=>" order by un.id_user,un.post_date",
		)));
		$aIdUser=array();
		if ($aUserNotification) foreach ($aUserNotification as $value) {
			$aUserNotificationGrouped[$value['id_user']][]=$value;
			if (!in_array($value['id_user'],$aIdUser)) $aIdUser[]=$value['id_user'];
			$aIdUserNotification[]=$value['id'];
		}
		if ($aIdUser) {
			foreach ($aIdUser as $value) {
				//if ($aUserNotificationGrouped[$value]) foreach ($aUserNotificationGrouped[$value] as $aNotification)
				$sSubject=Language::GetMessage('bulk_messages').' - '.DateFormat::getDateTime(time());
				Base::$tpl->assign('aUserNotification',$aUserNotificationGrouped[$value]);
				$sText=Base::$tpl->fetch('addon/message/user_notification_list.tpl');
				Base::$tpl->assign('sLetterUrl','http://'.SERVER_NAME.'/');
				$sLetterText=Base::$tpl->fetch('addon/message/user_notification_list.tpl');

				Message::SendMessage($aUserNotificationGrouped[$value][0]['login'],$sSubject,$sText,'',false);
				if ($aUserNotificationGrouped[$value][0]['copy_message']) {
					if ($aUserNotificationGrouped[$value][0]['include_reference_excel']) {
						$sAttachmentCode=Message::CreateCustomerIdExcel($aUserNotificationGrouped[$value]);
					}

					Mail::AddDelayed($aUserNotificationGrouped[$value][0]['email'],$sSubject,$sLetterText,'','',true,2
					,$sAttachmentCode);
				}
			}

			//if (0)
			Db::Execute("update user_notification set is_sent='1' where id in (".implode(',',$aIdUserNotification).")");
		}
	}
	//---------------------------------------------------------------------------------------------------------
	function PreviewUserNotification()
	{
		Auth::NeedAuth();

		$aUserNotification=Base::$db->GetRow(Base::GetSql('UserNotification',array(
		'id'=>Base::$aRequest['id'],
		'id_user'=>Auth::$aUser['id'],
		)));
		Base::$tpl->assign('aUserNotification',$aUserNotification);
		Base::$sText.=Base::$tpl->fetch('addon/message/preview_user_notification.tpl');
	}
	//---------------------------------------------------------------------------------------------------------
	/**
	 * Send Alert to user when he is logged onto site. Odnoklasniki analog
	 * Name - message header
	 * Description - message text (can be html)
	 * Url - link for demo// not implemented yet
	 * ReplyTo button or link to reply// not implemented yet
	 */
	function AddNote($iIdUser,$sName,$sDescription,$sUrl='',$sReplyTo='')
	{
		$aMessageNote=array(
		'id_user'=>$iIdUser,
		'name'=>$sName,
		'description'=>$sDescription,
		'url'=>$sUrl,
		'reply_to'=>$sReplyTo,
		'post'=>time(),
		);
		Base::$db->AutoExecute('message_note', $aMessageNote, 'INSERT');
	}
	//---------------------------------------------------------------------------------------------------------
	function CheckNote()
	{
		if (!Auth::$aUser['id']) return;
		return Base::$db->getRow(Base::GetSql('MessageNote',array('id_user'=>Auth::$aUser['id'],'is_closed'=>0)));
	}
	//---------------------------------------------------------------------------------------------------------
	function NoteClose()
	{
		if (!Auth::$aUser['id']) return;
		$aMessageNote=Base::$db->getRow(Base::GetSql('MessageNote',
		array(
		'id_user'=>Auth::$aUser['id']
		,'is_closed'=>0
		,'id'=>Base::$aRequest['id']
		)));
		Base::$oResponse->addAssign('message_note','innerHTML','');
		Base::$db->Execute("update message_note set is_closed='1' where id='".Base::$aRequest['id']."'");
		if ($aMessageNote['url']) {
			Base::$oResponse->addScript("location.href='".$aMessageNote['url']."'");
		}
	}
	//---------------------------------------------------------------------------------------------------------
	/**
	 * Create attachment excel file and puts code for delayed message
	 *
	 * @param string $sOwnerCode of created attachment in 'attachment' table after file creation
	 */
	function CreateCustomerIdExcel($aUserNotification)
	{
		if (!$aUserNotification) return;
		$aCartId=array();
		foreach ($aUserNotification as $aValue) {
			if ($aValue['id_cart'] && !in_array($aValue['id_cart'],$aCartId) ) $aCartId[]=$aValue['id_cart'];
		}
		if (!$aCartId) return;

		$oExcel = new Excel();
		$aHeader=array(
		'A'=>array("value"=>'ci_id_cart'),
		'B'=>array("value"=>'ci_id_cart_package'),
		'C'=>array("value"=>'ci_cat_name'),
		'D'=>array("value"=>'ci_code', 'autosize'=>true),
		'E'=>array("value"=>'ci_order_status', 'autosize'=>true),
		'F'=>array("value"=>'ci_name', 'autosize'=>true),
		'G'=>array("value"=>'ci_customer_id', 'autosize'=>true),
		'H'=>array("value"=>'ci_comment', 'autosize'=>true),
		'I'=>array("value"=>'ci_region_name'),
		'J'=>array("value"=>'ci_number'),
		'K'=>array("value"=>'ci_price'),
		'L'=>array("value"=>'ci_total'),
		'M'=>array("value"=>'ci_sign'),
		'N'=>array("value"=>'ci_current_weight'),
		'O'=>array("value"=>'ci_post_weight'),
		'P'=>array("value"=>'ci_weight_delivery_cost_post'),
		);

		$oExcel->SetHeaderValue($aHeader,1);
		$oExcel->SetAutoSize($aHeader);
		$oExcel->DuplicateStyleArray("A1:P1");

		$sSql=Base::GetSql("Part/Search",array(
		"where"=>" and c.id in (".implode(',',$aCartId).")",
		"order"=>" order by c.customer_id, pr.name, c.post_date",
		));
		$aCart=Db::GetAll($sSql);
		if ($aCart) {
			$i=$j=2;
			foreach ($aCart as $aValue) {
				$oExcel->setCellValue('A'.$i, $aValue['id']);
				$oExcel->setCellValue('B'.$i, $aValue['id_cart_package']);
				$oExcel->setCellValue('C'.$i, $aValue['cat_name']);

				$sCode = (Base::GetConstant('global:default_encoding','UTF-8')=='UTF-8' ?
				' '.$aValue['code'].($aValue['code_changed'] ? '/'.$aValue['code_changed']:'')
				: String::UtfEncode(' '.$aValue['code'].($aValue['code_changed'] ? '/'.$aValue['code_changed']:'')));

				$oExcel->setCellValue('D'.$i, $sCode);
				$oExcel->setCellValue('E'.$i, $aValue['order_status']);

				$sRussianName=(Base::GetConstant('global:default_encoding','UTF-8')=='UTF-8' ?
				$aValue['russian_name']	: String::UtfEncode($aValue['russian_name']) );
				$oExcel->setCellValue('F'.$i,$sRussianName );

				$sCustomerId=(Base::GetConstant('global:default_encoding','UTF-8')=='UTF-8' ?
				$aValue['customer_id']:String::UtfEncode($aValue['customer_id']));
				$oExcel->setCellValue('G'.$i, $sCustomerId);

				$sManagerComment=(Base::GetConstant('global:default_encoding','UTF-8')=='UTF-8' ?
				$aValue['manager_comment']:String::UtfEncode($aValue['manager_comment']));
				$oExcel->setCellValue('H'.$i, $sManagerComment);

				$sRegion=(Base::GetConstant('global:default_encoding','UTF-8')=='UTF-8' ?
				$aValue['pr_name'].' '.$aValue['prw_name']:String::UtfEncode($aValue['pr_name'].' '.$aValue['prw_name']));
				$oExcel->setCellValue('I'.$i, $sRegion);

				$oExcel->setCellValue('J'.$i, $aValue['number']);
				$oExcel->setCellValue('K'.$i, $aValue['price']);
				$oExcel->setCellValue('L'.$i, $aValue['number']*$aValue['price']);
				$oExcel->setCellValue('M'.$i, $aValue['sign']);
				$oExcel->setCellValue('N'.$i, $aValue['cpt_weight']);
				$oExcel->setCellValue('O'.$i, $aValue['weight_post']);
				$oExcel->setCellValue('P'.$i, $aValue['weight_delivery_cost_post']);
				$i++;
			}
			$sFileName=DateFormat::GetFileDateTime(time()).'_'.$aCart[0]['login'].'_customer_id.xls';
			$sFullFileName='/imgbank/temp_upload/attachment/'.$sFileName;
			$oExcel->WriterExcel5(SERVER_PATH.$sFullFileName);

			$aAttachment=array(
			'owner_code'=>$sFileName,
			'attach_file'=>$sFullFileName,
			'is_temporary'=>1,
			);
			Db::AutoExecute('attachment',$aAttachment);

			return $sFileName;
		}

	}
	//---------------------------------------------------------------------------------------------------------
}
?>