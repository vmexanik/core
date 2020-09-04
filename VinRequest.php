<?

/**
 * DEPRECATED: use /class/module/VinRequest.php instead
 *
 * @author Mikhail Starovoyt
 * @author Alexander Belogura
 *
 */

class VinRequest extends Base
{
	//-----------------------------------------------------------------------------------------------
	public function __construct($bNeedAuth=true)
	{
		Repository::InitDatabase('vin_request',true);
		Base::$bXajaxPresent=true;
		$oContent = new Content();//For template assign hack
		Base::$aData['template']['bWidthLimit']=false;
	}
	//-----------------------------------------------------------------------------------------------
	public function Index()
	{
		$iNumInsertedItems = 30;
		if (Base::$aRequest['is_post']) {
			Base::$aRequest['vin'] = Cart::ParseVinCode( Base::$aRequest['vin'] );
			if ((!Base::$aRequest['mobile'] && !Auth::$aUser['id']) || !Base::$aRequest['vin'] || !Base::$aRequest['model']
			|| !Base::$aRequest['azpDescript1']
			|| (Base::GetConstant('vin_request:has_capcha',0) && !Capcha::CheckMathematic())
			) {
				Form::ShowError("Please, fill the required fields");
				Base::$aRequest['action']='vin_request_add';
				//Base::$aRequest['date'] = date("M-d-Y", mktime(0, 0, 0, Base::$aRequest['Month'], 1, Base::$aRequest['Year']));
				Base::$tpl->assign('aData',Base::$aRequest);
			}
			else {

				if (Base::$aRequest['additional']) $sAdditional=implode(',',Base::$aRequest['additional']);
				//[----- INSERT -----------------------------------------------------]
				for ($i=1; $i <= $iNumInsertedItems; $i++) {
					if (Base::$aRequest['azpDescript'.$i]) {
						$sPartDescription.="<b>$i</b>) ".Base::$aRequest['azpDescript'.$i]." - ".
						Base::$aRequest['azpCnt'.$i]." <br>";

						$aPartList[]=array('i'=>$i
						,'i_visible'=>'1'
						,'name'=>base64_encode(Base::$aRequest['azpDescript'.$i])
						,'number'=>Base::$aRequest['azpCnt'.$i]);
					}
				}
				if (Auth::$aUser['id'] ) {
					$iIdRegisteredUser= Auth::$aUser['id'];
					$iIdManagerFixed=Db::GetOne("select id_manager_fixed from vin_request where 1=1
						".Auth::$sWhere." order by id desc");

					if (!$iIdManagerFixed && Auth::$aUser['id_referer_manager'] ) {
						$iIdManagerFixed= Auth::$aUser['id_referer_manager'];
					}
				}
				else {
					$aRegisteredUser=Auth::AutoCreateUser();
					$iIdRegisteredUser=$aRegisteredUser['id'];

					$oUser=new User();
					//$iIdManagerFixed=$oUser->CheckRefererManager();
				}

				//				$oImageProcess=new ImageProcess();
				//				$aImage=$oImageProcess->GetUploadedImage('passport_image',1
				//,'/imgbank/Image/passport_image/',Auth::$aUser['id']
				//				,Base::GetConstant('passport_image:big_width',800)
				//,Base::GetConstant('passport_image:small_width',150),true);
				//
				//				if ($aImage[1]) {
				//					$aPassportImage=array(
				//					'id_user'=>Auth::$aUser['id'],
				//					'name'=>$aImage[1]['name'],
				//					'name_small'=>$aImage[1]['name_small'],
				//					);
				//					Db::AutoExecute('passport_image',$aPassportImage);
				//				}

				$sPartArray=serialize($aPartList);
				if (Base::$aRequest['mobile']) $sMobile=Base::$aRequest['operator'].Base::$aRequest['mobile'];
				$sQuery="insert into vin_request(id_user,id_manager_fixed,post,marka,vin,model,country_producer,engine
						,month,year
						,volume,body,description,part_description,kpp,additional, customer_comment, part_array
						,mobile
						,wheel,utable,engine_number,engine_code,engine_volume,kpp_number
						,passport_image_name, passport_image_name_small
						)
        			        values('".$iIdRegisteredUser."','$iIdManagerFixed',UNIX_TIMESTAMP(),'".Base::$aRequest['marka']."'
        			        ,'".Base::$aRequest['vin']."','".Base::$aRequest['model']."'
        			        ,'".Base::$aRequest['country_producer']."'
        			        ,'".Base::$aRequest['engine']."','".Base::$aRequest['Month']."','".Base::$aRequest['Year']."'
        			        ,'".Base::$aRequest['volume']."'
        			        ,'".Base::$aRequest['body']."','".Base::$aRequest['description']."'
        			        ,'$sPartDescription','".Base::$aRequest['kpp']."'
        			        ,'$sAdditional','".Base::$aRequest['customer_comment']."','$sPartArray'
        			        ,'".$sMobile."'
							,'".Base::$aRequest['wheel']."','".Base::$aRequest['utable']."','".Base::$aRequest['engine_number']."'
							,'".Base::$aRequest['engine_code']."','".Base::$aRequest['engine_volume']."'
							,'".Base::$aRequest['kpp_number']."'
							,'".$aPassportImage['name']."','".$aPassportImage['name_small']."'
        			        )";
				//[----- END INSERT -------------------------------------------------]

				Db::Execute($sQuery);
				// For Garage
				if (Base::GetConstant("garage:is_available",0)=="1"){
					$sQuery="select * from garage where id_user='".$iIdRegisteredUser."' and
						vin='".Base::$aRequest['vin']."'";
					$aGarage=Db::GetAll($sQuery);
					if (!$aGarage){
						$sQuery="insert into garage (id_user,post,marka,vin,model,country_producer,
							engine,month,year,volume,body,description,kpp,wheel)
							values
							('".$iIdRegisteredUser."',UNIX_TIMESTAMP(),'".Base::$aRequest['marka']."'
							,'".Base::$aRequest['vin']."','".Base::$aRequest['model']."'
							,'".Base::$aRequest['country_producer']."'
							,'".Base::$aRequest['engine']."','".Base::$aRequest['Month']."'
							,'".Base::$aRequest['Year']."','".Base::$aRequest['volume']."'
							,'".Base::$aRequest['body']."','".Base::$aRequest['description']."'
							,'".Base::$aRequest['kpp']."','".Base::$aRequest['wheel']."')";
						Db::Execute($sQuery);
					}
				}
				// END For Garage
				Base::Redirect("./?action=vin_request&is_post_request=1");
			}
		}

		if (Base::$aRequest['action']=='vin_request_add' || Base::$aRequest['action']=='vin_request_copy'
		|| Base::$aRequest['action']=='vin_request_add_from_garage') {
			if (Base::$aRequest['action']=='vin_request_copy') {
				$aVinRequest=Db::GetRow(Base::GetSql('CoreVinRequest',array(
				'where'=> " and vr.id='".Base::$aRequest['id']."' and vr.id_user='".Auth::$aUser['id']."'")));
				Base::$tpl->assign('aData',$aVinRequest);
			}
			// For making VIN from Garage
			if (Base::$aRequest['action']=='vin_request_add_from_garage'){
				if (Base::GetConstant("garage:is_available",0)=="1"){
					$sQuery="select * from garage where id_user='".Auth::$aUser['id']."' and
						id='".Base::$aRequest['car_id']."'";
					$aVinRequest=Db::GetRow($sQuery);
					Base::$tpl->assign('aData',$aVinRequest);
				}
			}
			// END For making VIN from Garage

			$aVinMarka=Db::GetAssoc("Assoc/CatMake");

			$aVinBody=array(
			Language::GetMessage('sedan'),
			Language::GetMessage('hetch'),
			Language::GetMessage('universal'),
			Language::GetMessage('jeep'),
			Language::GetMessage('kupe'),
			Language::GetMessage('cabriolet'),
			Language::GetMessage('minivan'),
			Language::GetMessage('microbus'),
			);

			$aVinKpp=array(
			Language::GetMessage('Automat'),
			Language::GetMessage('Mechanics'),
			Language::GetMessage('Variator'),
			Language::GetMessage('Robotic'),
			);

			$aVinWheel=array(
			Language::GetMessage('Leftside'),
			Language::GetMessage('Rightside'),
			);

			$sVinOperator=Base::GetConstant('vin_request:phone_prefix','+7095,+7343,+7391,+7411,+7424,+7501,+7812,+7831,+7843'
			.',+7865,+7901,+7902,+7903,+7904,+7905,+7906,+7908,+7909,+7910,+7911,+7912,+7913,+7914,+7915,+7916,+7917,+7918,+7919'
			.',+7920,+7921,+7922,+7923,+7924,+7926,+7927,+7928,+7929,+7950,+7960,+7961,+7962');
			$aVinOperator=preg_split("/[\s,;]+/",$sVinOperator);

			$aVinMonth=array(
			'January'=>Language::GetMessage('January'),
			'February'=>Language::GetMessage('February'),
			'March'=>Language::GetMessage('March'),
			'April'=>Language::GetMessage('April'),
			'May'=>Language::GetMessage('May'),
			'June'=>Language::GetMessage('June'),
			'July'=>Language::GetMessage('July'),
			'August'=>Language::GetMessage('August'),
			'September'=>Language::GetMessage('September'),
			'October'=>Language::GetMessage('October'),
			'November'=>Language::GetMessage('November'),
			'December'=>Language::GetMessage('December'),
			);

			Base::$tpl->assign('aVinMarka',$aVinMarka);
			Base::$tpl->assign('aVinBody',$aVinBody);
			Base::$tpl->assign('aVinKpp',$aVinKpp);
			Base::$tpl->assign('aVinWheel',$aVinWheel);
			Base::$tpl->assign('aVinOperator',$aVinOperator);
			Base::$tpl->assign('aManagerHasCustomer',array(''=>'')+Db::GetAssoc(Base::GetSql('Assoc/ManagerHasCustomer')));
			Base::$tpl->assign('aVinMonth',$aVinMonth);

			if (Base::GetConstant('vin_request:has_capcha',0)) {
				$oCpacha= new Capcha();
				Base::$tpl->assign('sCapcha',$oCpacha->GetMathematic());
			}

			$sUserName = Auth::$aUser['id'] ? Auth::$aUser['login'] : "<font color='#ff0000'>".Language::getMessage("unregistered")
			."</font>";
			$aData=array(
			'sHeader'=>"method=post enctype='multipart/form-data' onsubmit=\"return mvr.CheckForm(this);\"",
			'sTitle'=>"",
			'sContent'=>Base::$tpl->fetch('addon/vin_request/customer/form_vin_request.tpl'),
			'sSubmitButton'=>'Send',
			'sSubmitAction'=>'vin_request',
			'sError'=>$sError,
			);
			$oForm=new Form($aData);

			Base::$tpl->assign('sForm',$oForm->GetForm());
			Base::$tpl->assign('sVinHeader',Language::GetMessage('Vin Request Form for -')." ".$sUserName);
			Base::$sText.=Base::$tpl->fetch('addon/vin_request/customer/vin_request_add.tpl');

			return;
		}

		if (Base::$aRequest['action']=='vin_request_delete') {
			Db::Execute("delete from vin_request where id='".Base::$aRequest['id']."'
				and order_status in ('new','refused') ".Auth::$sWhere);
		}

		/** For unregistered vin_requests */
		if (!Auth::$aUser['id']) {
			if (Base::$aRequest['is_post_request'] != 1){
				Auth::NeedAuth();
			}

			$aSmartyTemplate=String::GetSmartyTemplate('unregistered_vin_request', $aData);
			Base::$sText.=$aSmartyTemplate['parsed_text'];
			return;
		}
		//----------------------------------

		Auth::NeedAuth('customer');

		$oTable=new Table();
		$oTable->sSql="select * from vin_request where 1=1 ".Auth::$sWhere;
		$oTable->aOrdered="order by post_date desc";
		$oTable->aColumn=array(
		'id'=>array('sTitle'=>'#','sWidth'=>'15px'),
		'order_status'=>array('sTitle'=>'Order Status','sWidth'=>'15px'),
		'vin'=>array('sTitle'=>'VIN','sWidth'=>'150px'),
		'post'=>array('sTitle'=>'Post','sWidth'=>'150px'),
		'order_status'=>array('sTitle'=>'Status','sWidth'=>'6%'),
		'marka'=>array('sTitle'=>'Marka','sWidth'=>'10%'),
		'manager_comment'=>array('sTitle'=>'Manager Comment','sWidth'=>'25%'),
		'action'=>array(),
		);
		$oTable->sDataTemplate='addon/vin_request/customer/row_vin_request.tpl';
		$oTable->sButtonTemplate='addon/vin_request/customer/button_vin_request.tpl';

		Base::$sText.=$oTable->getTable("Vin requests",'vin_request');
	}
	//-----------------------------------------------------------------------------------------------
	public function Preview()
	{
		Auth::NeedAuth('customer');
		Base::$aTopPageTemplate=array('panel/tab_customer_cart.tpl'=>'vin_request');

		$sHilightColor = 'A52A2A';
		Base::$tpl->assign_by_ref("oCatalog", new Catalog());

		Base::$aData['template']['sPageTitle']=Language::getMessage("Preview Vin request");

		$aVinRequest=Db::GetRow(Base::GetSql('CoreVinRequest',array(
		'where'=> " and vr.id='".Base::$aRequest['id']."' and vr.id_user='".Auth::$aUser['id']."'")));

		if (!$aVinRequest) Base::Redirect('./?action=vin_request');

		Base::$tpl->assign('aData',$aVinRequest);

		$aPartList=unserialize($aVinRequest['part_array']);
		if ($aPartList) {
			foreach ($aPartList as $key => $value) {
				$aPartList[$key]['name']=base64_decode($value[name]);
				$aHilight = Db::GetRow("SELECT COUNT(*) as hilight_item FROM `cart`
					WHERE (`code` = '{$aPartList[$key]['code']}' OR `code` = '{$aPartList[$key]['user_input_code']}')
						AND `type_` = 'cart' AND `id_user` = {$_SESSION['user']['id']}");
				if($aHilight) {
					if(is_array($aHilight)) {
						$aPartList[$key]['hilight_it'] = array_shift($aHilight);
					}
				}
				unset($aHilight);
			}
		}

		Base::$tpl->assign('aPartList',$aPartList);
		Base::$tpl->assign('sHilightColor',$sHilightColor);
		if ($aPartList) foreach ($aPartList as $value) {
			//if ($value['i_visible']) $dSubtotal+=$value['price']*$value['number'];
			$aMultipleCode[]=$value['code'];
		}

		Base::$tpl->assign('iShowRealCodes',Base::GetConstant('vin_request:show_real_codes',0));

		Base::$tpl->assign('sMultipleCode',implode(',',$aMultipleCode));

		Base::$sText.=Base::$tpl->fetch('addon/vin_request/customer/vin_request_preview.tpl');
	}
	//-----------------------------------------------------------------------------------------------
	public function Manager()
	{
		Auth::NeedAuth('manager');
		Base::$aTopPageTemplate=array('panel/tab_manager_cart.tpl'=>'vin_request');

		// ######### Edit #########
		if ( Base::$aRequest['action']=='vin_request_manager_edit') {

			Form::BeforeReturn('vin_request_manager');

			$aVinRequest=Db::GetRow(Base::GetSql('CoreVinRequest',array(
			'id'=>Base::$aRequest['id'],
			//'id_in'=>$this->GetVinIdList(),
			)));

			if (!$aVinRequest) Base::Redirect('./?action=vin_request_manager');
			if ($aVinRequest['order_status']=='new') {
				Db::Execute("update vin_request set order_status='work' $sSet
					where id='".Base::$aRequest['id']."'");
			}
			Base::$tpl->assign('aData',$aVinRequest);

			$aData=array(
			'sHeader'=>"method=post",
			'sTitle'=>"VIN Request Preview",
			'sAdditionalTitle'=>" # ".Base::$aRequest['id'],
			'sContent'=>Base::$tpl->fetch('addon/vin_request/manager/form_vin_request.tpl'),
			'bShowBottomForm'=>false,
			'sError'=>$sError,
			);
			$oForm=new Form($aData);
			Base::$sText.=$oForm->getForm();

			$aPartList=unserialize($aVinRequest['part_array']);
			if ($aPartList) foreach ($aPartList as $key => $value)
			$aPartList[$key]['name']=base64_decode($value[name]);

			Base::$tpl->assign('aPartList',$aPartList);
			if ($aPartList) {
				foreach ($aPartList as $value) {
					$dSubtotal+=floatval($value['number'])*floatval($value['price']);
				}
			}
			Base::$tpl->assign('dSubtotal',$dSubtotal);
			Base::$tpl->assign('iRowCount',count($aPartList));

			//Base::$tpl->assign('aManagerLogin',  Db::GetAssoc(Base::GetSql('addon/vin_request/manager/LoginAssoc')) );

			Base::$sText.=Base::$tpl->fetch('addon/vin_request/manager/form_vin_request_part_list.tpl');
			return;
		}

		// ######### List #########
		$aData=array(
		'sHeader'=>"method=get",
		//'sTitle'=>"Search vin requests",
		'sContent'=>Base::$tpl->fetch('addon/vin_request/manager/form_vin_request_search.tpl'),
		'sSubmitButton'=>'Search',
		'sSubmitAction'=>'vin_request_manager',
		'sReturnButton'=>'Clear',
		'bIsPost'=>0,
		'sError'=>$sError,
		);
		$oForm=new Form($aData);

		Base::$sText.=$oForm->getForm();

		// --- search ---
		if (Base::$aRequest['search']['id']) $sWhere.=" and vr.id = '".Base::$aRequest['search']['id']."'";
		if (Base::$aRequest['search']['login']) $sWhere.=" and u.login ='".Base::$aRequest['search']['login']."'";
		if (Base::$aRequest['search']['is_remember']) $sWhere.=" and vr.is_remember ='1'";
		if (Base::$aRequest['search']['phone']) $sWhere.=" and uc.phone like '%".Base::$aRequest['search']['phone']."%'";
		if (Base::$aRequest['search']['order_status']) $sWhere.=" and vr.order_status = '"
		.Base::$aRequest['search']['order_status']."'";
		if (Base::$aRequest['search']['marka']) $sWhere.=" and vr.marka = '".Base::$aRequest['search']['marka']."'
			and vr.order_status!='new'";
		// --------------

		$oTable=new Table();
		$oTable->sSql=Base::GetSql('CoreVinRequest',array(
		'where'=>$sWhere,
		));

		$oTable->aOrdered="order by vr.id desc";
		$oTable->iRowPerPage=20;
		$oTable->aColumn=array(
		'id'=>array('sTitle'=>'#'),
		'order_status'=>array('sTitle'=>'Order Status'),
		'id_user'=>array('sTitle'=>'Customer/Phone'),
		'vin'=>array('sTitle'=>'VIN'),
		'post'=>array('sTitle'=>'Post Date'),
		'order_status'=>array('sTitle'=>'Status'),
		'marka'=>array('sTitle'=>'Marka'),
		'manager_comment'=>array('sTitle'=>'Manager Comment/Remember'),
		'action'=>array(),
		);
		$oTable->sDataTemplate='addon/vin_request/manager/row_vin_request.tpl';

		Base::$sText.=$oTable->GetTable("Vin requests from customers");
	}
	//-----------------------------------------------------------------------------------------------
	public function ManagerSave($bRedirect=true)
	{
		Auth::NeedAuth('manager');

		if (Base::$aRequest['is_post']) {
			$aUserInputCode = array();
			if (Base::$aRequest['data']['change_login'] && Base::$aRequest['data']['current_login']) {
				Db::Execute("update user set login='".Base::$aRequest['data']['change_login']."'
					where login='".Base::$aRequest['data']['current_login']."'");
			}

			//[----- UPDATE -----------------------------------------------------]
			if (Base::$aRequest['part']) {
				$j = 0;
				foreach(Base::$aRequest['part'] as $value) {
					++ $j;
					if(
					$value['user_input_code'] &&
					(strripos($value['code'], "ZZZ_") !== false)
					)
					{
						$aUserInputCode[$j] = $value['user_input_code'];
					} else {
						$aUserInputCode[$j] = $value['code'];
					}
					$aCode[]="'" . Catalog::StripCode( $value['code'] ) . "'";
				}
				$aCrosHash=Db::GetAssoc("select cp.code, cp.* from cat_part as cp where code in (".implode(',',$aCode).")");
			}

			for ($i=1;$i<=100;$i++) {
				if (Base::$aRequest['part'][$i]) {

					if (Base::$aRequest['part'][$i]['number']<=0) Base::$aRequest['part'][$i]['number']=1;

					$aPriceCodeRequest=Db::GetRow(Base::GetSql('Catalog/Price'
					,array(
					'aCode'=>array(Base::$aRequest['part'][$i]['code'])
					)));
					if ($aPriceCodeRequest && !Base::$aRequest['part'][$i]['code_visible'])
					{
						$sCode = 'zzz_'.$aPriceCodeRequest['id'];
					} else {
						$sCode = Catalog::StripCode( Base::$aRequest['part'][$i]['code'] );
					}

					$aPartList[] = array(
					'i'=>$i,
					'name'=>base64_encode(Base::$aRequest['part'][$i]['name']),
					'marka'=>Base::$aRequest['part'][$i]['marka'],
					'code'=> $sCode,
					'user_input_code' => $aUserInputCode[$i],
					'cat_name'=>Base::$aRequest['part'][$i]['cat_name'],
					'code_visible'=>Base::$aRequest['part'][$i]['code_visible'],
					'i_visible'=>Base::$aRequest['part'][$i]['i'],
					'number'=>Base::$aRequest['part'][$i]['number'],
					'price'=>Base::$aRequest['part'][$i]['price'],
					'price_original'=>Base::$aRequest['part'][$i]['price_original'],
					'term'=>Base::$aRequest['part'][$i]['term'],
					'id_provider'=>Base::$aRequest['part'][$i]['id_provider'],
					'provider'=> $aProviderHash[Base::$aRequest['part'][$i]['id_provider']]['name'],
					'code_delivery'=> $aProviderHash[Base::$aRequest['part'][$i]['id_provider']]['code_delivery'],
					'weight'=>Base::$aRequest['part'][$i]['weight'],
					);
				}
			}
			$sPartArray=serialize($aPartList);

			Db::Execute("update vin_request set
						part_array='$sPartArray',
						manager_comment= '".Base::$aRequest['manager_comment']."',
						remember_text= '".Base::$aRequest['remember_text']."'
					where id='".Base::$aRequest['id']."'
						and id in (".$this->GetVinIdList(true).") ");
			//[----- END UPDATE -------------------------------------------------]
		}
		if ($bRedirect) Base::Redirect('./?action=vin_request_manager_edit&form_message=saved&id='.Base::$aRequest['id']);
	}
	//-----------------------------------------------------------------------------------------------
	public function ManagerSend()
	{
		Auth::NeedAuth('manager');

		if (Base::$aRequest['is_post']) {
			$this->ManagerSave(false);

			$aVinRequest=Db::GetRow(Base::GetSql('CoreVinRequest',array(
			'id'=>Base::$aRequest['id'],
			'id_in'=>$this->GetVinIdList(),
			)));
			if (!$aVinRequest) Base::Redirect('./?action=vin_request_manager');

			$aCustomer=Db::GetRow(Base::GetSql('Customer',array('id'=>$aVinRequest['id_user'])));
			$aManager=Db::GetRow(Base::GetSql('Manager',array('id'=>$aCustomer['id_manager'])));

			$aPartList=unserialize($aVinRequest['part_array']);
			if ($aPartList) foreach ($aPartList as $key => $value)
			$aPartList[$key]['name']=base64_decode($value[name]);
			$aVinRequest['part_list']=$aPartList;

			Db::Execute("update vin_request set order_status='parsed' where
				order_status in ('work','refused')
				and id='".Base::$aRequest['id']."'
				and id in (".$this->GetVinIdList(true).") ");

			$this->ManagerRelease(Base::$aRequest['id']);

			if ($aVinRequest['mobile']) {
				$this->ManagerMobileNotification($aVinRequest);
			}

			if (Base::$aRequest['section']=='customer') {
				Message::CreateDelayedNotification($aVinRequest['id_user'], 'vin_request_sent'
				,array('aVinRequest'=>$aVinRequest,'aManager'=>$aManager,'aCustomer'=>$aCustomer),true);
			}
		}
		Base::Redirect('./?action=vin_request_manager');
	}
	//-----------------------------------------------------------------------------------------------
	public function ManagerRefuse()
	{
		Auth::NeedAuth('manager');

		if (Base::$aRequest['is_post']) {
			$this->ManagerSave(false);

			$aVinRequest=Db::GetRow(Base::GetSql('CoreVinRequest',array(
			'id'=>Base::$aRequest['id'],
			'id_in'=>$this->GetVinIdList(),
			)));
			if (!$aVinRequest) Base::Redirect('./?action=vin_request_manager');

			$aCustomer=Db::GetRow(Base::GetSql('Customer',array('id'=>$aVinRequest['id_user'])));
			$aManager=Db::GetRow(Base::GetSql('Manager',array('id'=>$aCustomer['id_manager'])));

			Db::Execute("update vin_request set order_status='refused' where id='".Base::$aRequest['id']."'");
			$this->ManagerRelease(Base::$aRequest['id']);

			Message::CreateDelayedNotification($aVinRequest['id_user'], 'vin_request_refused'
			,array('aVinRequest'=>$aVinRequest,'aManager'=>$aManager,'aCustomer'=>$aCustomer),true);

			if ($aVinRequest['mobile']) {
				//$this->ManagerMobileNotification($aVinRequest);
			}
		}
		Base::Redirect('./?action=vin_request_manager');
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Makes manager able to take new vin request from general queue
	 */
	public function ManagerRelease($iId)
	{
		Auth::NeedAuth('manager');

		Db::Execute("update user_manager set id_vin_request_fixed='0'
				where id_user='".Auth::$aUser['id']."' and id_vin_request_fixed='$iId'");
	}
	//-----------------------------------------------------------------------------------------------
	public function ManagerMobileNotification($aVinRequest)
	{
		if (!Base::GetConstant('vin_request:sms_notification',0)) return;

		$aCustomer=Db::GetRow( Base::GetSql('Customer',array('id'=>$aVinRequest['id_user'])) );

		$aSmartyTemplate=String::GetSmartyTemplate('parsed_vin_request', array(
		'aVinRequest'=>$aVinRequest,
		'aCustomer'=>$aCustomer
		));
		Sms::AddDelayed($aVinRequest['mobile'],strip_tags($aSmartyTemplate['parsed_text']));

		$aSmartyTemplate=String::GetSmartyTemplate('vin_request_mobile_parsed');
		$sNoteDescription=$aSmartyTemplate['parsed_text'];
		Message::AddNote($aVinRequest['id_user'], Language::GetMessage('Vin request mobile parsed Subject')
		,$sNoteDescription);
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Get the list of id vinrequests which manager can have access
	 *
	 * @return array
	 */
	public function GetVinIdList($bReturnArray=false)
	{
		$sVinRequestQueue=Base::GetSql('VinRequest/MyQueue',array(
		'id_manager'=>Auth::$aUser['id'],
		'view_all'=>(Auth::$aUser['is_super_manager'] || Auth::$aUser['is_sub_manager'] ? "1":"") ,
		'assoc'=>($bReturnArray ? "1":"") ,
		));
		if ($bReturnArray) {
			return implode(',',Db::GetAssoc($sVinRequestQueue));
		}
		return $sVinRequestQueue;
	}
	//-----------------------------------------------------------------------------------------------
	public function ManagerRemember()
	{
		Auth::NeedAuth('manager');

		if (Base::$aRequest['id']) {
			$aVinRequest['is_remember']=(Base::$aRequest['checked']=='true' ? 1:0);
			Db::AutoExecute('vin_request',$aVinRequest,'UPDATE',"id='".Base::$aRequest['id']."'");
		}
	}
	//-----------------------------------------------------------------------------------------------
}
?>