<?

/**
 * @author Mikhail Starovoyt
 * @author Mikhail Kuleshov
 * @author Alex Belogura
 */

class Payment extends Base
{
	public $aWebmoneyPurse;
	public $aMoneyBookersCurrency;
	//private $aMoneybookersConfig;

	//-----------------------------------------------------------------------------------------------
	public function __construct()
	{
		$this->aWebmoneyPurse=array(
		Base::GetConstant('wmr','R337662899050')=>Language::GetMessage('wmr option'),
		Base::GetConstant('wmz','Z360530032700')=>Language::GetMessage('wmz option'),
		Base::GetConstant('wmu','U114535866785')=>Language::GetMessage('wmu option'),
		Base::GetConstant('wme','E327357032978')=>Language::GetMessage('wme option'),
		);
		$this->aWebmoneyPurseCurrency=array(
		Base::GetConstant('wmz','Z360530032700')=>'USD',
		Base::GetConstant('wmu','U114535866785')=>'UAH',
		Base::GetConstant('wmr','R337662899050')=>'RUB',
		Base::GetConstant('wme','E327357032978')=>'EUR',
		);
		$this->aWebmoneyCardPurse=array(
			'sPurse'=>Base::GetConstant('wmr','R337662899050'),
			'sCurrency'=>'RUR',
		);

		$this->aMoneyBookersCurrency=array(
		'USD'=>'USD',
		'EUR'=>'EUR',
		);

		$this->aPaypalCurrency=array(
		'USD'=>'USD',
		'EUR'=>'EUR',
		);

		$this->aLiqpayCurrency=array(
		'UAH'=>'UAH',
		);

		$this->aWebtopayCurrency=array(
		'LTL'=>'LTL',
		'RUR'=>'RUR',
		'USD'=>'USD',
		'EUR'=>'EUR',
		);

		$this->aQiwiCurrency=array(
		'RUR'=>'RUR',
		);

		$this->aMonexyCurrency=array(
		'UAH'=>'UAH',
		);

	}
	//-----------------------------------------------------------------------------------------------
	public function Index()
	{
		Auth::NeedAuth();

		switch (Base::$aRequest['action']) {
			case 'payment_webmoney':
			case 'payment_webmoney_card':
				if ($_SESSION['bill']){
					Base::$tpl->assign('iBillId',$_SESSION['bill']['id']);
					Base::$tpl->assign('iBillAmount',$_SESSION['bill']['amount']);
				}
				if (Base::$aRequest['action']=='payment_webmoney_card') {
					Base::$tpl->assign('iAuthType',16);
					Base::$aData['template']['sPageTitle']=Language::getMessage('title:payment').
						" ".Language::getMessage('plastic_card');
					$sMethod='webmoney_card';
					$sFormAction="?at=authtype_16";
					Base::$tpl->assign('aWebmoneyCardPurse',$this->aWebmoneyCardPurse);
				} else {
					Base::$aData['template']['sPageTitle']=Language::getMessage('title:payment').
						" WebMoney";
					$sMethod='webmoney';
					$sFormAction="?at=authtype_1";
				}
				Base::$tpl->assign('aWebmoneyPurse',$this->aWebmoneyPurse);
				$iUniqId=Base::GetConstant('Payment::UniqIdPrefix','000').microtime(TRUE)*10000;
				Base::$tpl->assign('LMI_PAYMENT_NO',$iUniqId );
				$aData=array(
				'sHeader'=>"method=post action=\"https://merchant.webmoney.ru/lmi/payment.asp".
					$sFormAction."\"",
				'sContent'=>Base::$tpl->fetch('addon/payment/webmoney.tpl'),
				'sSubmitButton'=>'Payment Process',
				'sSubmitAction'=>'payment_webmoney_result',
				'sReturnButton'=>'Return',
				'sReturnAction'=>'finance_payforaccount',
				);
				$oForm=new Form($aData);

				$sForm=$oForm->getForm();
				break;

			case 'payment_moneybookers':
				Base::$aData['template']['sPageTitle']=Language::getMessage('title:payment')." MoneyBookers";
				$sMethod='moneybookers';
				Base::$tpl->assign('aMoneyBookersCurrency',$this->aMoneyBookersCurrency);
				Base::$tpl->assign('sTransactionId',uniqid() );

				$aData=array(
				'sHeader'=>"method=post target=_blank action=\"https://www.moneybookers.com/app/payment.pl\"",
				'sContent'=>Base::$tpl->fetch('addon/payment/moneybookers.tpl'),
				'sSubmitButton'=>'Payment Process',
				//'sSubmitAction'=>'payment_moneybookers_result',
				'sReturnButton'=>'Return',
				'sReturnAction'=>'finance_payforaccount',
				);
				$oForm=new Form($aData);

				$sForm=$oForm->getForm();
				break;

			case 'payment_paypal':
				Base::$aData['template']['sPageTitle']=Language::getMessage('title:payment')." PayPal";
				$sMethod='paypal';
				Base::$tpl->assign('aPaypalCurrency',$this->aPaypalCurrency);

				$aData=array(
				'sHeader'=>" action='https://".Base::GetConstant('payment:paypal_domain','sandbox.paypal.com')."/cgi-bin/webscr'
					method='post' name='paypal' id='paypal' ",
				'sContent'=>Base::$tpl->fetch('addon/payment/paypal.tpl'),
				'sSubmitButton'=>'Payment Process',
				'sReturnButton'=>'Return',
				'sReturnAction'=>'finance_payforaccount',
				);
				$oForm=new Form($aData);

				$sForm=$oForm->getForm();
				break;

			case 'payment_liqpay':
				Base::$aData['template']['sPageTitle']=Language::getMessage('title:payment')." LiqPay";
				$sMethod='liqpay';
				Base::$tpl->assign('aLiqpayCurrency',$this->aLiqpayCurrency);

				//Base::$tpl->assign('sUniqid',uniqid());

				$aData=array(
				'sHeader'=>" method='POST' ",
				'sContent'=>Base::$tpl->fetch('addon/payment/liqpay.tpl'),
				'sSubmitAction'=>'payment_liqpay_confirm',
				'sSubmitButton'=>'Payment Process',
				'sReturnButton'=>'Return',
				'sReturnAction'=>'finance_payforaccount',
				);
				$oForm=new Form($aData);

				$sForm=$oForm->getForm();
				break;

			case 'payment_liqpay_confirm':
				Base::$aData['template']['sPageTitle']=Language::getMessage('title:payment')." LiqPay Confirm";
				$sMethod='liqpay_confirm';
				Base::$tpl->assign('aLiqpayCurrency',$this->aLiqpayCurrency);

				$sOperationXml=$this->GetLiqpayOperationXml();
				$sSignature=base64_encode(sha1(Base::GetConstant('payment:liqpay_merchant_password',
				'wvuDB6rYgZVp1N1zBYd4nhljaXAIstlRbR')
				.$sOperationXml
				.Base::GetConstant('payment:liqpay_merchant_password','wvuDB6rYgZVp1N1zBYd4nhljaXAIstlRbR'),1));

				Base::$tpl->assign('sOperationXml',base64_encode($sOperationXml));
				Base::$tpl->assign('sSignature',$sSignature);

				$aData=array(
				'sHeader'=>" action='".Base::GetConstant('payment:liqpay_url','https://liqpay.com/?do=clickNbuy')."'
					method='POST' accept-charset='utf-8' ",
				'sContent'=>Base::$tpl->fetch('addon/payment/liqpay_confirm.tpl'),
				'sSubmitButton'=>'Payment Process',
				'sReturnButton'=>'Return',
				'sReturnAction'=>'payment_liqpay',
				);
				$oForm=new Form($aData);

				$sForm=$oForm->getForm();
				break;

			case 'payment_webtopay':
				Base::$aData['template']['sPageTitle']=Language::getMessage('title:payment')." WebToPay";
				$sMethod='webtopay';
				require_once( SERVER_PATH.'/lib/payment/WebToPay.php');

				Base::$tpl->assign('aWebtopayCurrency',$this->aWebtopayCurrency);
				$aData=array(
				'sContent'=>Base::$tpl->fetch('addon/payment/webtopay.tpl'),
				'sSubmitButton'=>'Payment Process',
				'sSubmitAction'=>'payment_webtopay_confirm',
				'sReturnButton'=>'Return',
				'sReturnAction'=>'finance_payforaccount',
				);
				$oForm=new Form($aData);

				$sForm=$oForm->GetForm();
				break;

			case 'payment_webtopay_confirm':
				Base::$aData['template']['sPageTitle']=Language::getMessage('title:payment')." WebToPay Confirm";
				$sMethod='webtopay_confirm';
				require_once( SERVER_PATH.'/lib/payment/WebToPay.php');

				$aHiddenInput = WebToPay::buildRequest(array(
				'projectid'     => Base::GetConstant('payment:webtopay_projectid','3801'),
				'sign_password' => Base::GetConstant('payment:webtopay_sign_password','8d436f9ddac32c8266cf110061432e0f'),
				'orderid'       => Auth::$aUser['id'].'_'.uniqid(),
				'amount'        => Base::$aRequest['amount']*100,
				'currency'      => Base::$aRequest['currency'],
				'accepturl'     => 'http://'.SERVER_NAME.'/?action=payment_webtopay_success',
				'cancelurl'     => 'http://'.SERVER_NAME.'/?action=payment_webtopay_fail',
				'callbackurl'   => 'http://'.SERVER_NAME.'/?action=payment_webtopay_result',
				'test'          => Base::GetConstant('payment:webtopay_test','1'),
				));
				Base::$tpl->assign('aHiddenInput',$aHiddenInput);

				$aData=array(
				'sHeader'=>" action='".WebToPay::PAY_URL."'	method='POST'  ",
				'sContent'=>Base::$tpl->fetch('addon/payment/webtopay_confirm.tpl'),
				'sSubmitButton'=>'Payment Confirm',
				'sReturnButton'=>'Return',
				'sReturnAction'=>'payment_webtopay',
				);
				$oForm=new Form($aData);

				$sForm=$oForm->GetForm();
				break;

				//----------------------------------------------------------
			case 'payment_qiwi':
				if ($_SESSION['bill']){
					Base::$tpl->assign('iBillId',$_SESSION['bill']['id']);
					Base::$tpl->assign('iBillAmount',$_SESSION['bill']['amount']);
				}
				Base::$aData['template']['sPageTitle']=Language::getMessage('title:payment')." Qiwi";
				$sMethod='qiwi';
				Base::$tpl->assign('aQiwiCurrency',$this->aQiwiCurrency);
				Base::$tpl->assign('aUser',Auth::$aUser);

				$aData=array(
				'sHeader'=>"method='post'",
				'sContent'=>Base::$tpl->fetch('addon/payment/qiwi.tpl'),
				'sSubmitButton'=>'Payment Process',
				'sSubmitAction'=>'payment_qiwi_confirm',
				'sReturnButton'=>'Return',
				'sReturnAction'=>'finance_payforaccount',
				);
				$oForm=new Form($aData);
				$sForm=$oForm->getForm();

				break;

			case 'payment_qiwi_confirm':
				Base::$aData['template']['sPageTitle']=Language::getMessage('title:payment')." Qiwi Confirm";
				$sMethod='qiwi_confirm';
				require_once( SERVER_PATH.'/lib/payment/Qiwi.php');
				/*
				* Если мы не используем автоопределение модулей PHP, доступных нам,
				* то просто создаем класс и настраиваем его.
				*
				* Если нужно определить модули автоматически, то делаем так:
				*
				*
				* $q = QIWI::getInstance($qiwiConfig);
				*
				*/
				//$q = new QIWI($qiwiConfig);
				//$q->setEncrypter(new QIWIMcryptEncrypter());
				//$q->setRequester(new QIWICurlRequester());

				$qiwiConfig = array(
				'shopID' => Base::GetConstant('payment:qiwi_id','8409'),
				'password' => Base::GetConstant('payment:qiwi_password','mstarproject_117'),
				'lifetime' => Base::GetConstant('payment:qiwi_lifetime','QIWI_BILL_LIFETIME_UNLIMITED'),
				'txn-prefix' => Base::GetConstant('payment:qiwi_txn_prefix','LINE-PART-'),
				'encrypt' => FALSE,
				'url' => Base::GetConstant('payment:qiwi_url','https://ishop.qiwi.ru/xml'),
				'create-agt' => QIWI_BILL_CREATE_CLIENT,
				'alarm-sms' => '0',
				'alarm-call' => '0',
				'log' => FALSE
				);
				$q = QIWI::getInstance($qiwiConfig);
				$sPhone=str_replace('+', '', Base::$aRequest['phone']);

				if ($_SESSION['current_cart_package']['id'])
					$iQiwiTxnId=$_SESSION['current_cart_package']['id'];
				elseif ($_SESSION['bill']['id'])
					$iQiwiTxnId=$_SESSION['bill']['id'];
				else
					$iQiwiTxnId=Base::$db->GetOne("select max(id) from payment_qiwi")+1;

				try
				{
					if ($q->createBill(
					array(
					'phone' => $sPhone,
					'amount' => Base::$aRequest['amount'],
					'comment' => Base::GetConstant('payment:qiwi_comment','LINEPART.RU'),
					'lifetime' => Base::GetConstant('payment:qiwi_lifetime',21600),
					'txn-id' => $iQiwiTxnId,
					'create-agt'=> 1
					)))
					{
						Db::Execute("insert into payment_qiwi (id_user, id_cart_package, amount)
						values ('".Auth::$aUser['id']."', '".$_SESSION['current_cart_package']['id']."', '".Base::$aRequest['amount']."'
						)");
						Base::$tpl->assign('sQiwiAnswer',Language::GetMessage('QIWI OK'));

					}
					else
					{
						Base::$tpl->assign('sQiwiAnswer',Language::GetMessage('QIWI FAIL'));
					}

				}
				catch (QIWIMortalCombatException $e)
				{
					Base::$tpl->assign('sQiwiError','Failed: '.$e->code.', '.($e->fatality?"true":"false").', '.QIWI::$errors[$e->code]);
				}
				$sForm.=Base::$tpl->fetch('addon/payment/qiwi_confirm.tpl');

				break;

				//----------------------------------------------------------
			case 'payment_monexy':

				Base::$aData['template']['sPageTitle']=Language::getMessage('title:payment')." Monexy";
				$sMethod='monexy';
				Base::$tpl->assign('aMonexyCurrency',$this->aMonexyCurrency);

				$aData=array(
				'sHeader'=>"method='post'",
				'sContent'=>Base::$tpl->fetch('addon/payment/monexy.tpl'),
				'sSubmitAction'=>'payment_monexy_confirm',
				'sSubmitButton'=>'Payment Process',
				'sReturnButton'=>'Return',
				'sReturnAction'=>'finance_payforaccount',
				);
				$oForm=new Form($aData);
				$sForm=$oForm->getForm();

				break;

			case 'payment_monexy_confirm':

				Base::$aData['template']['sPageTitle']=Language::getMessage('title:payment')." Monexy Confirm";
				$sMethod='monexy_confirm';
				Base::$tpl->assign('aMonexyCurrency',$this->aMonexyCurrency);
				Base::$tpl->assign('aUser',Auth::$aUser);

				$aHiddenInput=array(
				'MonexyMerchantID'=>Base::GetConstant('payment:monexy_merchantid','100088749'),
				'MonexyMerchantInfoShopName'=>Base::GetConstant('payment:monexy_merchantinfo','Partmaster Spare parts'),
				'MonexyMerchantOrderId'=>Auth::$aUser['id'],
				'MonexyMerchantOrderDesc'=>Base::GetConstant('payment:monexy_orderdesc','Пополнение счета Партмастер'),
				'MonexyMerchantSum'=>Base::$aRequest['amount'],
				'MonexyMerchantResultUrl'=>'http://'.SERVER_NAME.'/?action=payment_monexy_result',
				'MonexyMerchantResultMethod'=>'post',
				'MonexyMerchantSucessUrl'=>'http://'.SERVER_NAME.'/?action=payment_monexy_success',
				'MonexyMerchantSucessMethod'=>'post',
				'MonexyMerchantFailUrl'=>'http://'.SERVER_NAME.'/?action=payment_monexy_fail',
				'MonexyMerchantFailMethod'=>'post',
				);
				$aHiddenInput['MonexyMerchantHash']=md5($aHiddenInput['MonexyMerchantID'].';'.$aHiddenInput['MonexyMerchantOrderId']
				.';'.$aHiddenInput['MonexyMerchantSum'].';'.Base::GetConstant('payment:monexy_secretkey','part100088749master'));
				Base::$tpl->assign('aHiddenInput',$aHiddenInput);

				$aData=array(
				'sHeader'=>"method='post' action='".Base::GetConstant('payment:monexy_url','https://www.monexy.com/app/mobi.php')."'",
				'sContent'=>Base::$tpl->fetch('addon/payment/monexy_confirm.tpl'),
				'sSubmitButton'=>'Payment Process',
				'sReturnButton'=>'Return',
				'sReturnAction'=>'finance_payforaccount',
				);
				$oForm=new Form($aData);
				$sForm=$oForm->getForm();

				break;

				//----------------------------------------------------------
			case 'payment_uniteller':
				Base::$aData['template']['sPageTitle']=Language::getMessage('title:payment')." Uniteller";
				$sMethod='uniteller';
				Base::$tpl->assign('aUnitellerCurrency',
				array(Base::GetConstant('payment:uniteller_currency','RUR')=>Base::GetConstant('payment:uniteller_currency','RUB')));
				$aData=array(
				'sHeader'=>"method='post'",
				'sContent'=>Base::$tpl->fetch('addon/payment/uniteller.tpl'),
				'sSubmitButton'=>'Payment Process',
				'sSubmitAction'=>'payment_uniteller_confirm',
				'sReturnButton'=>'Return',
				'sReturnAction'=>'finance_payforaccount',
				);
				$oForm=new Form($aData);
				$sForm=$oForm->getForm();

				break;

			case 'payment_uniteller_confirm':
				Base::$aData['template']['sPageTitle']=Language::getMessage('title:payment')." Uniteller Confirm";
				$sMethod='uniteller_confirm';

				$aHiddenInput=array(
				'Shop_IDP'=>Base::GetConstant('payment:uniteller_shop_id','8181359319-186'),
				'Order_IDP'=>Base::$aRequest['cart_package_id'],
				'Subtotal_P'=>Base::$aRequest['amount'],
				'URL_RETURN_OK'=>'http://'.SERVER_NAME.'/?action=payment_uniteller_success',
				'URL_RETURN_NO'=>'http://'.SERVER_NAME.'/?action=payment_uniteller_fail',
				'Email'=>Auth::$aUser['email'],
				'Country'=>Auth::$aUser['country'],
				'City'=>Auth::$aUser['city'],
				'Address'=>Auth::$aUser['address'],
				'Zip'=>Auth::$aUser['zip'],
				'Phone'=>Auth::$aUser['phone'],
				);
				Base::$tpl->assign('aHiddenInput',$aHiddenInput);

				$aData=array(
				'sHeader'=>"method='post' action='".Base::GetConstant('payment:uniteller_url','https://wpay.uniteller.ru/pay/')."'",
				'sContent'=>Base::$tpl->fetch('addon/payment/uniteller_confirm.tpl'),
				'sSubmitButton'=>'Payment Process',
				'sReturnButton'=>'Return',
				'sReturnAction'=>'payment_uniteller&amount='.Base::$aRequest['amount'].'&cart_package_id='
				.Base::$aRequest['cart_package_id'],
				);
				$oForm=new Form($aData);
				$sForm=$oForm->getForm();

				Db::Execute("insert into payment_uniteller (id_user, id_cart_package, amount, currency, status)
				values ('".Auth::$aUser['id']."', '".Base::$aRequest['cart_package_id']."', '".Base::$aRequest['amount'].
				"', '".Base::GetConstant('payment:uniteller_currency','RUR')."', 'created')");

				break;

			case 'payment_bank':
				Base::$aData['template']['sPageTitle']=Language::getMessage('title:payment_bank');
				$sMethod='bank';
				if ($_SESSION['bill']){
					Base::$tpl->assign('iBillId',$_SESSION['bill']['id']);
					Base::$tpl->assign('iBillAmount',$_SESSION['bill']['amount']);
				}
				Base::$tpl->assign('aBankCurrency',
				array(Base::GetConstant('payment:bank_currency','RUR')=>Base::GetConstant('payment:bank_currency','RUB')));
				if (Base::$aRequest['return'])
					$sReturnAction=Base::$aRequest['return'];
					else 
					$sReturnAction='finance_payforaccount';
				$aData=array(
				'sHeader'=>"method='post'",
				'sContent'=>Base::$tpl->fetch('addon/payment/bank.tpl'),
				'sSubmitButton'=>'Print Bill',
				'sSubmitAction'=>'payment_bank_confirm',
				'sReturnButton'=>'Return',
				'sReturnAction'=>$sReturnAction,
				);
				$oForm=new Form($aData);
				$sForm=$oForm->getForm();

				break;
			case 'payment_bank_confirm':
					$aBill=$_SESSION['bill'];
					$aUser=Db::GetRow(Base::GetSql('Customer',array('id'=>Auth::$aUser['id'])));
					$aUserCart=Db::GetAll(Base::GetSql('Cart',array(
						'id_user'=>$aBill['id_user'],
						'id_cart_package'=>$aBill['id_cart_package']
					)));
					$aCartPackage=Db::GetRow(Base::GetSql('CartPackage',array(
						'id_user'=>$aBill['id_user'],
						'id'=>$aBill['id_cart_package']
					)));
					$aBill['amount_string']=Currency::CurrecyConvert($aBill['amount'],
						Base::GetConstant('global:base_currency'));

					Base::$tpl->assign('aActiveAccount',Db::GetRow(Base::GetSql('Account',
						array('is_active'=>'1'))));
					Base::$tpl->assign('sDate', date ("d.m.Y"));
					Base::$tpl->assign('aUserCart',$aUserCart);
					Base::$tpl->assign('aCartPackage',$aCartPackage);
					
					switch (Base::$aRequest['bill_type']) {
					case 'rect':
						Base::$tpl->assign('aUser',$aUser);
						$sContent=Base::$tpl->fetch('finance/print_simple_bill.tpl');
						break;
					case 'bill':
						Base::$tpl->assign('aCustomer',$aUser);
						$sContent=Base::$tpl->fetch('cart/package_print.tpl');
						break;
					}
					PrintContent::Append($sContent);
					Base::Redirect('?action=print_content');
				break;

		}

		Base::$sText.=Language::GetText('top_'.$sMethod.'_payment');
		Base::$sText.=$sForm;
	}
	//-----------------------------------------------------------------------------------------------
	public function WebmoneyResult()
	{
		//if (Base::$aRequest['LMI_HASH']) {
		$this->WebMoneyPayment();
		//}
		Base::$sText.="Result";
	}
	//-----------------------------------------------------------------------------------------------
	public function WebmoneySuccess()
	{
		Base::$sText.=Language::GetText("Webmoney Success");
	}
	//-----------------------------------------------------------------------------------------------
	public function WebmoneyFail()
	{
		Base::$sText.=Language::GetText("Webmoney Fail");
	}
	//-----------------------------------------------------------------------------------------------
	function Log($sMethod='webmoney', $sMessage='')
	{
		$sDescription='remote_addr: '.$_SERVER["REMOTE_ADDR"];

		$aLogPayment=array(
		'method'=>$sMethod,
		'message'=>mysql_real_escape_string($sMessage),
		'description'=>$sDescription,
		);
		Db::AutoExecute('log_payment',$aLogPayment);
	}
	//-----------------------------------------------------------------------------------------------
	private function WebMoneyPayment()
	{
		$bSuccess=true;
		$sError='';
		if (!in_array(Base::$aRequest['LMI_PAYEE_PURSE'],array_keys($this->aWebmoneyPurse)))
		{
			$sError.="Merchant purse (".Base::$aRequest['LMI_PAYEE_PURSE'].") is incorrect\n";
			$bSuccess=false;
		}

		if (floatval(Base::$aRequest['LMI_PAYMENT_AMOUNT'])==0){
			$sError.="Payed amount is 0";
			$bSuccess=false;
		}

		if (!Base::$aRequest['LMI_SECRET_KEY']) Base::$aRequest['LMI_SECRET_KEY']=Base::GetConstant('webmoney:secret_key');

		$sHash=strtoupper(md5(Base::$aRequest['LMI_PAYEE_PURSE'].Base::$aRequest['LMI_PAYMENT_AMOUNT']
		.Base::$aRequest['LMI_PAYMENT_NO']
		.Base::$aRequest['LMI_MODE'].Base::$aRequest['LMI_SYS_INVS_NO'].Base::$aRequest['LMI_SYS_TRANS_NO']
		.Base::$aRequest['LMI_SYS_TRANS_DATE'].Base::$aRequest['LMI_SECRET_KEY']
		.Base::$aRequest['LMI_PAYER_PURSE'].Base::$aRequest['LMI_PAYER_WM']));

		if (Base::$aRequest['LMI_HASH']!=$sHash){
			$sError.="LMI_HASH ".Base::$aRequest['LMI_HASH']." is not equal to ".$sHash;
			$bSuccess=false;
		}

		if (Base::$aRequest['LMI_MODE']==1){
			$sError.="The payment is processed in test mode";
			$bSuccess=false;
		}

		if ($bSuccess)
		{
			$this->Log('webmoney', "Success: $sError \n\n".print_r(Base::$aRequest, true)." \n\n secret key:".$sHash);

			$dAmount=Currency::BasePrice(Base::$aRequest['LMI_PAYMENT_AMOUNT'],
			$this->aWebmoneyPurseCurrency[Base::$aRequest['LMI_PAYEE_PURSE']] );

			//todo: parameters and testing
			$aTransactionAccount=$this->GetTransactionAccount(Base::$aRequest['LMI_PAYEE_PURSE']);

			if (Base::GetConstant('payment:finance_module','finance')=='finance'){
				//$this->Log('webmoney', "Log entry1: ");

				// ------------------------------------------------------
				// Base::GetConstant('payment:finance_module') == 'finance'
				$iInsertedId=Finance::Deposit(Base::$aRequest['id_user']
				,$dAmount
				,Language::GetMessage('Webmoney auto payment').': '.Base::$aRequest['LMI_PAYMENT_AMOUNT'].' '.
				$this->aWebmoneyPurseCurrency[Base::$aRequest['LMI_PAYEE_PURSE']]
				,'','',
				$this->aWebmoneyPurseCurrency[Base::$aRequest['LMI_PAYEE_PURSE']].' '
				.Base::$aRequest['LMI_PAYMENT_AMOUNT'].' '
				.Language::GetMessage(Base::GetConstant('payment:webmoney_service','webmoney_service')),
				361,$aTransactionAccount['id_user_account_log_type'],$aTransactionAccount['id']);

				//Invoice Account log add
				if ($iInsertedId) {
					InvoiceAccountLog::Add(Base::$aRequest['id_user'],$iInsertedId,'user_account_log',$dAmount);
				}
				// ------------------------------------------------------
			}
			else {
				// ------------------------------------------------------
				$sDescription=Language::GetMessage('Webmoney auto payment').': '.Base::$aRequest['LMI_PAYMENT_AMOUNT'].' '.
				$this->aWebmoneyPurseCurrency[Base::$aRequest['LMI_PAYEE_PURSE']];

				$oBuh = new Buh();
				$oBuh->EntrySingle(array(), $aTransactionAccount['id_buh'], 361, $dAmount, $sDescription
				, $aTransactionAccount['id'], 0, 0,	Base::$aRequest['id_user'], 0, 0, $aTransactionAccount['id_currency'],'',"");
				// ------------------------------------------------------
			}
		}
		else{
			$this->Log('webmoney', "ERROR: $sError \n\n".print_r(Base::$aRequest, true));
		}
		return $bSuccess;
	}
	//-----------------------------------------------------------------------------------------------
	public function MoneybookersResult()
	{
		$this->MoneybookersPayment();
		Base::$sText.=Language::GetText("Moneybookers Result");
	}
	//-----------------------------------------------------------------------------------------------
	public function MoneybookersSuccess()
	{
		Base::$sText.=Language::GetText("Moneybookers Success");
	}
	//-----------------------------------------------------------------------------------------------
	public function MoneybookersFail()
	{
		Base::$sText.=Language::GetText("Moneybookers Fail");
	}
	//-----------------------------------------------------------------------------------------------
	private function MoneybookersPayment()
	{
		$moneybook_attrs = array
		(
		"EMAIL"     	=> Base::GetConstant('moneybookers_email',"mstar@partmaster.com.ua"),
		"CURRENCY"  	=> array('USD','EUR'),
		);
		$bSuccess=false;

		// assign posted variables to local variables
		$pay_to_email           = Base::$aRequest['pay_to_email'];
		$pay_from_email         = Base::$aRequest['pay_from_email'];
		$user_id                = (int)(Base::$aRequest['user_id']);
		$mb_transaction_id      = (int)(Base::$aRequest['mb_transaction_id']);
		$payment_status         = Base::$aRequest['status'];
		$payment_amount         = round(Base::$aRequest['mb_amount'],2);
		$payment_currency       = Base::$aRequest['mb_currency'];

		$this->Log("moneybookers", print_r(Base::$aRequest,true));

		if ($payment_status === 0 ){
			//pending - do nothing - waiting
			$this->Log('moneybookers', "Status  0");
		} else if ($payment_status == -1 ){
			//pending - do nothing - waiting
			$this->Log('moneybookers', "Status  -1");
		} else if ($payment_status == -2 ){
			//pending - do nothing - waiting
			$this->Log('moneybookers', "Status  -2");
		} else if ($payment_status == 2 ){
			if ($pay_to_email==$moneybook_attrs["EMAIL"]){
				$sTransactionId=Db::GetOne("select id from moneybooker_txn where id='".$mb_transaction_id."'");
				if($sTransactionId)
				{
					$this->Log("moneybookers", "Old txn id '$mb_transaction_id'");
					$status = "OLD";
				} else {
					Db::Execute("INSERT INTO `moneybooker_txn` (`id`, `date`) VALUES({$mb_transaction_id}, NOW())");
					if(!in_array($payment_currency,$moneybook_attrs["CURRENCY"]))
					{
						$this->Log("moneybookers", "Bad currency ({$payment_currency} not in th list)");
					} else {
						if($payment_status == 2)
						{
							$this->Log("moneybookers", "Accepted summ ".$payment_amount.' '
							.Base::$aRequest['mb_currency']."");

							$dAmount=Currency::BasePrice($payment_amount, Base::$aRequest['mb_currency']);

							$aTransactionAccount=$this->GetTransactionAccount(Base::GetConstant('moneybookers_email'));

							$iInsertedId=Finance::Deposit(Base::$aRequest['payedto_id_user']
							,$dAmount
							,Language::GetMessage('Moneybookers auto payment').': '.$payment_amount.' '.
							Base::$aRequest['mb_currency'],'','',
							Base::$aRequest['mb_currency'].' '.$payment_amount.' '
							.Language::GetMessage(Base::GetConstant('payment:moneybookers_service','moneybookers_service'))
							,361,$aTransactionAccount['id_user_account_log_type']
							,$aTransactionAccount['id']);

							//Invoice Account log add
							if ($iInsertedId) {
								InvoiceAccountLog::Add(Base::$aRequest['payedto_id_user'],$iInsertedId,'user_account_log',$dAmount);
							}

							$bSuccess=true;
						} else {
							$this->Log("moneybookers", "Bad status '{$payment_status}'");
						}
					}
				}
			} else {
				$this->Log("fraud", "Bad receiver ('{$pay_to_email}' != '{$moneybook_attrs['EMAIL']}')");
			}
		} else {
			$this->Log("moneybookers", "Unknown status".$payment_status);
		}
		return $bSuccess;
	}
	//-----------------------------------------------------------------------------------------------
	public function PaypalResult()
	{
		$this->PaypalPayment();
		Base::$sText.=Language::GetText("Paypal Result");
	}
	//-----------------------------------------------------------------------------------------------
	public function PaypalSuccess()
	{
		Base::$sText.=Language::GetText("Paypal Success");
	}
	//-----------------------------------------------------------------------------------------------
	public function PaypalFail()
	{
		Base::$sText.=Language::GetText("Paypal Fail");
	}
	//-----------------------------------------------------------------------------------------------
	private function PaypalPayment()
	{
		$this->Log("Paypal", print_r(Base::$aRequest,true));

		$req = 'cmd=_notify-validate';
		if ($_POST) foreach ($_POST as $key => $value)
		{
			$value = urlencode($this->decodeGPC($value));
			$req .= "&".$key."=".$value;
		}

		$payment_id             = (int)($_POST['item_number']);
		$payment_status         = $_POST['payment_status'];
		$payment_amount         = $_POST['mc_gross'];
		$payment_currency       = $_POST['mc_currency'];
		$persone_type           = $_POST['custom'];

		$txn_id                 = $_POST['txn_id'];
		$receiver_email         = $_POST['receiver_email'];

		// post back to PayPal system to validate
		$header .= "POST /cgi-bin/webscr HTTP/1.0\r\n";
		$header .= "Host: ".Base::GetConstant('payment:paypal_domain','sandbox.paypal.com')."\r\n";
		$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$header .= "Content-Length: " . strlen($req) . "\r\n\r\n";

		$rstr = "";
		$status = "";

		$fp = fsockopen(Base::GetConstant('payment:paypal_domain','sandbox.paypal.com'), 80, $errno, $errstr, 30);
		if( $fp )
		{
			fputs ($fp, $header.$req);
			while(!feof($fp))
			{
				$res = trim( fgets($fp, 1024) );
				if(strcmp($res, "VERIFIED") == 0)
				{
					$sDbIdTxn = Db::GetRow("select * from paypal_txn where id='".$txn_id."'");
					if ($sDbIdTxn) {
						$this->Log("Paypal", "Old txn id '$txn_id'");
						$status = "OLD";
						break;
					}

					$payment_status = strtoupper( $payment_status );
					if(strcmp($payment_status, "COMPLETED") == 0)
					{
						$aPaypalTxn['id']=$txn_id;
						Db::AutoExecute('paypal_txn',$aPaypalTxn);

						$this->Log("Paypal", "Accepted summ ".$payment_amount.' '.Base::$aRequest['mc_currency']."");
						$dAmount=Currency::BasePrice($payment_amount, Base::$aRequest['mc_currency']);

						Finance::Deposit(Base::$aRequest['item_number'],$dAmount
						,Language::GetMessage('Paypal auto payment').': '.$payment_amount.' '.
						Base::$aRequest['mc_currency'],'','paypal',
						Base::$aRequest['mc_currency'].' '.$payment_amount.' '.
						Language::GetMessage(Base::GetConstant('payment:paypal_service','paypal_service')),'',1);

						$status = $payment_status;
					}
					else
					{
						$this->Log("Paypal",  "Bad status '{$payment_status}'");
						$status = $payment_status;
					}
					break;
				}
				else
				{
					$rstr .= "$res\n";
				}
			}
			fclose ($fp);
		}
		else
		{
			$this->Log("Paypal", "Socket error");
			$status = "SYSTEM ERROR";
		}
		if($status == "")
		{
			$this->Log("Paypal", "Bad paypal answer '{$rstr}'");
			$status = "SYSTEM ERROR";
		}
		return $status;
	}
	//-----------------------------------------------------------------------------------------------
	function decodeQouta( $str )
	{
		if( ini_get("magic_quotes_sybase") )
		{
			$str = ereg_replace("''", "'", $str);
		}
		else
		{
			$str = ereg_replace("\\\\\\\\", "\\", $str);
			$str = ereg_replace("\\\\\"", "\"", $str);
			$str = ereg_replace("\\\\'", "'", $str);
		}
		return $str;
	}
	//-----------------------------------------------------------------------------------------------
	function decodeGPC( $str )
	{
		if( ini_get("magic_quotes_gpc") )
		{
			$str = $this->decodeQouta( $str );
		}
		return $str;
	}
	//-----------------------------------------------------------------------------------------------
	public function LiqpayResult()
	{
		$this->LiqpayPayment();
		Base::$sText.=Language::GetText("Liqpay Result");
	}
	//-----------------------------------------------------------------------------------------------
	public function LiqpaySuccess()
	{
		Base::$sText.=Language::GetText("Liqpay Success");
	}
	//-----------------------------------------------------------------------------------------------
	public function LiqpayFail()
	{
		Base::$sText.=Language::GetText("Liqpay Fail");
	}
	//-----------------------------------------------------------------------------------------------
	public function LiqpayPayment()
	{
		//i9477382803
		//wvuDB6rYgZVp1N1zBYd4nhljaXAIstlRbR

		$sXmlDecoded=base64_decode(Base::$aRequest['operation_xml']);
		$sRequestMerchantId=$this->ParseTag($sXmlDecoded, 'merchant_id');
		$sRequestOrderId=$this->ParseTag($sXmlDecoded, 'order_id');
		$sRequestAmount=$this->ParseTag($sXmlDecoded, 'amount');
		$sRequestCurrency=$this->ParseTag($sXmlDecoded, 'currency');
		$sRequestDescription=$this->ParseTag($sXmlDecoded, 'description');
		$sRequestStatus=$this->ParseTag($sXmlDecoded, 'status');
		$sRequestCode=$this->ParseTag($sXmlDecoded, 'code');
		$sRequestTransactionId=$this->ParseTag($sXmlDecoded, 'transaction_id');
		$sRequestPayWay=$this->ParseTag($sXmlDecoded, 'pay_way');
		$sRequestSenderPhone=$this->ParseTag($sXmlDecoded, 'sender_phone');

		Base::$aRequest['decoded_string']=$sRequestOrderId." ".$sRequestAmount." ".$sRequestCurrency." ".$sRequestDescription.
		" ".$sRequestPayWay." ".$sRequestStatus." ".$sRequestTransactionId." ".$sXmlDecoded;

		$this->Log("Liqpay", print_r(Base::$aRequest,true));


		if ($sRequestStatus=='success'){
			$sTransactionId=Db::GetOne("select id from liqpay_txn where id='".$sRequestTransactionId."'");
			if($sTransactionId)
			{
				$this->Log("liqpay", "Old txn id '".$sRequestTransactionId."'");
			} else {
				Db::Execute("INSERT INTO `liqpay_txn` (`id`, `date`) VALUES('".$sRequestTransactionId."', NOW())");
				if(!in_array($sRequestCurrency,array_keys($this->aLiqpayCurrency) ))
				{
					$this->Log("liqpay", "Bad currency (".$sRequestCurrency." not in the list)");
				} else {

					$sCheckSignature=base64_encode(sha1(Base::GetConstant('payment:liqpay_merchant_password')
					.$sXmlDecoded.Base::GetConstant('payment:liqpay_merchant_password',1)));

					$sCheckSignature2=base64_encode(sha1(Base::GetConstant('payment:liqpay_merchant_password')
					.Base::$aRequest['operation_xml'].Base::GetConstant('payment:liqpay_merchant_password',1)));

					if ($sCheckSignature==Base::$aRequest['signature'] || 1) {

						$aOrderId=explode('_',$sRequestOrderId);
						$iIdUser=$aOrderId[0];

						$dAmount=Currency::BasePrice($sRequestAmount, $sRequestCurrency);

						$this->Log("liqpay", "Accepted summ $sCheckSignature ".$sRequestAmount.' '
						.$sRequestCurrency." user_id:".$iIdUser);

						//todo: parameters and testing
						$aTransactionAccount=$this->GetTransactionAccount($sRequestMerchantId,$sRequestCurrency);

						$iInsertedId=Finance::Deposit($iIdUser,$dAmount
						,Language::GetMessage('liqpay auto payment').': '.$sRequestAmount.' '.
						$sRequestCurrency
						,'','',
						$sRequestCurrency.' '.$sRequestAmount.' '
						.Language::GetMessage(Base::GetConstant('payment:liqpay_service','liqpay_service'))
						,361,$aTransactionAccount['id_user_account_log_type'],$aTransactionAccount['id']);

						//Invoice Account log add
						if ($iInsertedId) {
							InvoiceAccountLog::Add($iIdUser,$iInsertedId,'user_account_log',$dAmount);
						}
					}
					else {
						$this->Log("liqpay", "Bad signature ".Base::$aRequest['signature']." != ".$sCheckSignature."");
					}
				}
			}
		}
	}
	//-----------------------------------------------------------------------------------------------
	//	$sOperationXml=$this->GetLiqpayOperationXml();
	//	$sSignature=$this->GetLiqpaySignature();
	public function GetLiqpayOperationXml()
	{
		return "<request>
      <version>1.2</version>
      <result_url>http://".SERVER_NAME."/?action=payment_liqpay_result</result_url>
      <server_url>http://".SERVER_NAME."/?action=payment_liqpay_success</server_url>
      <merchant_id>".Base::GetConstant('payment:liqpay_merchant_id','i9477382803')."</merchant_id>
      <order_id>".Auth::$aUser['id'].'_'.uniqid()."</order_id>
      <amount>".Base::$aRequest['amount']."</amount>
      <currency>".Base::$aRequest['currency']."</currency>
      <description>".Base::GetConstant('payment:liqpay_description','Liqpay description')
		.' '.Auth::$aUser['login'].':'.Auth::$aUser['id']."</description>
      <default_phone></default_phone>
      <pay_way></pay_way>
		</request>";
	}
	//-----------------------------------------------------------------------------------------------
	function ParseTag($rs, $tag)
	{
		$rs = str_replace("\n", "", str_replace("\r", "", $rs));
		$tags = '<'.$tag.'>';
		$tage = '</'.$tag;
		$start = strpos($rs, $tags)+strlen($tags);
		$end = strpos($rs, $tage);
		return substr($rs, $start, ($end-$start));
	}
	//-----------------------------------------------------------------------------------------------
	public function GetTransactionAccount($sAccountId,$sCurrencyCode='')
	{
		if ($sCurrencyCode) $sWhere.=" and c.code='".$sCurrencyCode."'";

		$aAccount=Db::GetRow(Base::GetSql('Account',array(
		'where'=>" and a.account_id='".$sAccountId."' ".$sWhere,
		)));

		return $aAccount;
	}
	//-----------------------------------------------------------------------------------------------
	public function WebtopayResult()
	{
		$this->WebtopayPayment();
		//Base::$sText.=Language::GetText("Webtopay Result");
		die("Ok");
	}
	//-----------------------------------------------------------------------------------------------
	public function WebtopaySuccess()
	{
		Base::$sText.=Language::GetText("Webtopay Success");
		if(Base::GetConstant('payment:webtopay_simple','0')==1) Base::Redirect("./?action=cart_payment_end&data[id_payment_type]=4");
	}
	//-----------------------------------------------------------------------------------------------
	public function WebtopayFail()
	{
		Base::$sText.=Language::GetText("Webtopay Fail");
	}
	//-----------------------------------------------------------------------------------------------
	private function WebtopayPayment()
	{
		require_once(SERVER_PATH.'/lib/payment/WebToPay.php');

		$aResponse = WebToPay::checkResponse($_GET, array(
		'projectid'     => Base::GetConstant('payment:webtopay_projectid','3801'),
		'sign_password' => Base::GetConstant('payment:webtopay_sign_password','8d436f9ddac32c8266cf110061432e0f'),
		));

		$this->Log("Webtopay", print_r($aResponse,true));

		if ($aResponse['status']=='1'){
			$dAmount=Currency::BasePrice($aResponse['amount']/100, $aResponse['currency']);
			$aTransactionAccount=$this->GetTransactionAccount('webtopay',$aResponse['currency']);

			Finance::Deposit($aResponse['orderid'],$dAmount
			,Language::GetMessage('webtopay auto payment').': '.($aResponse['amount']/100).' '.
			$aResponse['currency']
			,'','',
			$aResponse['currency'].' '.($aResponse['amount']/100).' '
			.Language::GetMessage(Base::GetConstant('payment:webtopay_service','webtopay_service'))
			,361,$aTransactionAccount['id_user_account_log_type'],$aTransactionAccount['id']);
		}
	}
	//-----------------------------------------------------------------------------------------------
	public function QiwiResult()
	{
		$this->QiwiPayment();
		Base::$sText.=Language::GetText("Qiwi Result");
	}
	//-----------------------------------------------------------------------------------------------
	private function QiwiPayment()
	{
		require_once( SERVER_PATH.'/lib/payment/Qiwi.php');
		$qiwiConfig = array(
		'shopID' => Base::GetConstant('payment:qiwi_id','8409'),
		'password' => Base::GetConstant('payment:qiwi_password','mstarproject_117'),
		'lifetime' => Base::GetConstant('payment:qiwi_lifetime','QIWI_BILL_LIFETIME_UNLIMITED'),
		'txn-prefix' => Base::GetConstant('payment:qiwi_txn_prefix','LINE-PART-'),
		'encrypt' => FALSE,
		'url' => Base::GetConstant('payment:qiwi_url','https://ishop.qiwi.ru/xml'),
		'create-agt' => QIWI_BILL_CREATE_CLIENT,
		'alarm-sms' => '0',
		'alarm-call' => '0',
		'log' => FALSE
		);
		$q = QIWI::getInstance($qiwiConfig);

		$aBill=Db::GetAssoc("select pq.*, pq.id_cart_package as id from payment_qiwi as pq");
		$aBillId=array_keys($aBill);
		if (!$aBillId) return;
		foreach($q->billStatus($aBillId,FALSE) as $sKey=>$aValue)
		{
			$iIdCartPackage=str_replace(Base::GetConstant('payment:qiwi_txn_prefix','LINE-PART-'),'',$sKey);

			switch (''.$aValue['status'])
			{
				//not payed
				case 50:
					break;

					//payed
				case 60:
					// ------------------------------------------------------
					$aTransactionAccount=$this->GetTransactionAccount(Base::GetConstant('payment:qiwi_id','8409'));
					$sDescription=Language::GetMessage('Qiwi auto payment').': '.''.$aValue['amount'].' '.$this->aQiwiCurrency[RUR];

					$oBuh = new Buh();
					$oBuh->EntrySingle(array('id_buh_section'=>1,'buh_section_id'=>$iIdCartPackage)
					, $aTransactionAccount['id_buh'], 361, $aValue['amount'], $sDescription
					, $aTransactionAccount['id'], 0, 0,	$aBill[$iIdCartPackage]['id_user'], 0, 0, $aTransactionAccount['id_currency'],'',"");
					// ------------------------------------------------------
					Db::Execute("delete from payment_qiwi where id_cart_package=".$iIdCartPackage);
					Cart::PayCartPackage($iIdCartPackage,$aValue['amount']);
					break;

					//canceled
				case 150:
					Db::Execute("delete from payment_qiwi where id_cart_package=".$iIdCartPackage);
					break;

					//timeout
				case 161:
					Db::Execute("delete from payment_qiwi where id_cart_package=".$iIdCartPackage);
					break;

			}

		}



	}
	//-----------------------------------------------------------------------------------------------
	public function MonexyResult()
	{
		$this->MonexyPayment();
		Base::$sText.=Language::GetText("Monexy Result");
	}
	//-----------------------------------------------------------------------------------------------
	public function MonexySuccess()
	{
		Base::$sText.=Language::GetText("Monexy Success");
	}
	//-----------------------------------------------------------------------------------------------
	public function MonexyFail()
	{
		Base::$sText.=Language::GetText("Monexy Fail");
	}
	//-----------------------------------------------------------------------------------------------
	private function MonexyPayment()
	{
		$this->Log('monexy', "Result:\n\n".print_r(Base::$aRequest, true));
		$sSignHash=md5(
		Base::$aRequest['trans_id'].';'.
		Base::$aRequest['MonexyMerchantOrderId'].';'.
		Base::$aRequest['MonexyMerchantID'].';'.
		Base::$aRequest['trans_ex_sum'].';'.
		Base::$aRequest['trans_ex_currency'].';'.
		Base::$aRequest['trans_date'].';'.
		Base::GetConstant('payment:monexy_secretkey','part100088749master'));

		if ($sSignHash==Base::$aRequest['ServerHASH'])
		{
			$dAmount=Currency::BasePrice(Base::$aRequest['trans_sum'], Base::$aRequest['trans_currency']);
			$aTransactionAccount=$this->GetTransactionAccount(Base::$aRequest['MonexyMerchantID']);

			if (Base::GetConstant('payment:finance_module','finance')=='finance')
			{
				$iInsertedId=Finance::Deposit(Base::$aRequest['MonexyMerchantOrderId']
				,$dAmount
				,Language::GetMessage('Monexy auto payment').': '.Base::$aRequest['trans_sum'].' '.
				Base::$aRequest['trans_currency']
				,'','',
				Base::$aRequest['trans_currency'].' '
				.Base::$aRequest['trans_sum'].' '
				.Language::GetMessage(Base::GetConstant('payment:monexy_service','monexy_service')),
				361,$aTransactionAccount['id_user_account_log_type'],$aTransactionAccount['id']);

				//Invoice Account log add
				if ($iInsertedId) {
					InvoiceAccountLog::Add(Base::$aRequest['MonexyMerchantOrderId'],$iInsertedId,'user_account_log',$dAmount);
				}
				// ------------------------------------------------------
			}
			else
			{
				// ------------------------------------------------------
				$sDescription=Language::GetMessage('Monexy auto payment').': '.Base::$aRequest['trans_sum'].' '.
				Base::$aRequest['trans_currency'];

				$oBuh = new Buh();
				$oBuh->EntrySingle(array(), $aTransactionAccount['id_buh'], 361, $dAmount, $sDescription
				, $aTransactionAccount['id'], 0, 0,	Base::$aRequest['MonexyMerchantOrderId'], 0, 0, $aTransactionAccount['id_currency'],'',"");
				// ------------------------------------------------------
			}

		}
		else
		{
			$this->Log('monexy', "hash error");
		}
	}
	//-----------------------------------------------------------------------------------------------
	public function UnitellerResult()
	{
		$this->UnitellerPayment();
		Base::$sText.=Language::GetText("Uniteller Result");
	}
	//-----------------------------------------------------------------------------------------------
	public function UnitellerSuccess()
	{
		Base::$sText.=Language::GetText("Uniteller Success");
	}
	//-----------------------------------------------------------------------------------------------
	public function UnitellerFail()
	{
		Base::$sText.=Language::GetText("Uniteller Fail");
	}
	//-----------------------------------------------------------------------------------------------
	private function UnitellerPayment()
	{
		$iOrderId=Base::$aRequest['Order_ID'];
		$sStatus=Base::$aRequest['Status'];
		$this->Log('uniteller', "Result:\n\n".print_r(Base::$aRequest, true));
		$sSign=strtoupper(md5($iOrderId
		.$sStatus
		.Base::GetConstant('payment:uniteller_passwd','34Y2sO1l27RE2nmasckZ7HqDT5ra12NUSTMjjqdE3FUlULcMuhKOGFbJSajL8MieGtOQcjjeLl2sNqDm'
		)));

		if (Base::$aRequest['Signature']==$sSign)
		{
			switch ($sStatus)
			{
				case 'canseled':
					Db::Autoexecute('payment_uniteller',array('status'=>'canceled'),'UPDATE',"id_cart_package='".$iOrderId."'");
					break;

				case 'paid':
					break;

				case 'authorized':
					Db::Autoexecute('payment_uniteller',array('status'=>'authorized'),'UPDATE',"id_cart_package='".$iOrderId."'");
					$aPayment=Db::GetRow("select * from payment_uniteller where id_cart_package='".$iOrderId."'");
					if (!$aPayment) break;

					$dAmount=Currency::BasePrice($aPayment['amount'], $aPayment['currency']);
					$aTransactionAccount=$this->GetTransactionAccount(Base::GetConstant('payment:uniteller_shop_id','8181359319-186'));
					if (Base::GetConstant('payment:finance_module','finance')=='finance')
					{
						$iInsertedId=Finance::Deposit($aPayment['id_user']
						,$dAmount
						,Language::GetMessage('Uniteller auto payment').': '.$aPayment['amount'].' '.
						$aPayment['currency']
						,'','',
						$aPayment['currency'].' '
						.$aPayment['amount'].' '
						.Language::GetMessage(Base::GetConstant('payment:uniteller_service','uniteller_service')),
						361,$aTransactionAccount['id_user_account_log_type'],$aTransactionAccount['id']);
						//Invoice Account log add
						//						if ($iInsertedId) {
						//							InvoiceAccountLog::Add($aPayment['id_user'],$iInsertedId,'user_account_log',$dAmount);
						//						}
						// ------------------------------------------------------
					}
					else
					{
						// ------------------------------------------------------
						$sDescription=Language::GetMessage('Uniteller auto payment').': '.$aPayment['amount'].' '.
						$aPayment['currency'];

						$oBuh = new Buh();
						$oBuh->EntrySingle(array(), $aTransactionAccount['id_buh'], 361, $dAmount, $sDescription
						, $aTransactionAccount['id'], 0, 0,	$aPayment['id_user'], 0, 0, $aTransactionAccount['id_currency'],'',"");
						// ------------------------------------------------------
					}
					break;
			}
		}
		else
		{
			$this->Log('uniteller', "sign error");
		}

	}
	//-----------------------------------------------------------------------------------------------

}

?>