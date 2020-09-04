<?

/**
 * @author Mikhail Starovoyt
 * @version 4.5.2
 *
 * Class for inclding such static resources as js, css files to html header, html body and other places
 */

class Resource
{
	private static $oInstance = null;
	public $sPrefix = 'resource';

	private $aLocation = array();

	/**
	 * For each location will be seperate archive
	 */
	private $aHeaderResource = array();

	private $aResourceVersion = array();

	//-----------------------------------------------------------------------------------------------
	public static function Get(){
		if (!self::$oInstance) {
			self::$oInstance = new self();
		}
		return self::$oInstance;
	}
	//-----------------------------------------------------------------------------------------------
	public function __construct(){
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Adds file to header location for default
	 * Sample usage Resource::Get->Add('/css/main.css',6,'header')
	 *
	 * @param string $sFilePath
	 * @param number $iFileVersion - if number is grater than already included (duplicate), this grater number will be included
	 * @param string $sLocation
	 */
	public function Add($sFilePath,$iFileVersion=0,$sLocation='header',$aData=array())
	{
		if(Base::GetConstant('optimize_resource:location',0)) {
			if(substr(strtolower($sFilePath),-4)=='.css') {
				$sLocation=Base::GetConstant('optimize_css:place','header');
			} elseif(substr(strtolower($sFilePath),-3)=='.js') {
				$sPlace=Base::GetConstant('optimize_js:place','footer');
				$sLocation=(Auth::$aUser ? 'header' : $sPlace);
			}
		}
		
		$sFilePath=strtolower($sFilePath);
		$sLocation=strtolower($sLocation);
		$iFileVersion=(is_int($iFileVersion) ? $iFileVersion : 0);

		switch ($sLocation) {
			case 'footer':
				if (!$this->aFooterResource || !in_array($sFilePath,array_keys($this->aFooterResource))) {
					$this->aFooterResource[$sFilePath]=array(
					'file_path'=>$sFilePath,
					'data'=>$aData,
					'mtime'=>@filemtime(SERVER_PATH.$sFilePath),
					);
					$this->aResourceVersion[$sFilePath]=$iFileVersion;

					if (!in_array($sLocation,$this->aLocation)) $this->aLocation[]=$sLocation;
				}
				elseif ($this->aResourceVersion[$sFilePath]<$iFileVersion) {
					$this->aResourceVersion[$sFilePath]=$iFileVersion;
				}
				break;

			case 'header':
			default:
				if (!in_array($sFilePath,array_keys($this->aHeaderResource))) {
					$this->aHeaderResource[$sFilePath]=array(
					'file_path'=>$sFilePath,
					'data'=>$aData,
					'mtime'=>@filemtime(SERVER_PATH.$sFilePath),
					);
					$this->aResourceVersion[$sFilePath]=$iFileVersion;

					if (!in_array($sLocation,$this->aLocation)) $this->aLocation[]=$sLocation;
				}
				elseif ($this->aResourceVersion[$sFilePath]<$iFileVersion) {
					$this->aResourceVersion[$sFilePath]=$iFileVersion;
				}
				break;
		}
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Puts everything added to output template to fill it into html
	 * Called by Base::Process() method
	 */
	public function FillTemplate()
	{
//		print_r($this);
//		exit;
		
		if(Base::GetConstant('optimize_resource:combined',0)) {
			if(!defined("OPTIMIZE_CSS") && !defined("OPTIMIZE_JS")) {
				define(OPTIMIZE_CSS, true);
				define(OPTIMIZE_JS, true);
			}
		}
		$bSmartPath=Base::GetConstant('optimize_resource:smart_path',0);
		
		if ($this->aLocation) {
			foreach ($this->aLocation as $sValue) {
				$sTemplateName='s'.ucwords($sValue).'Resource';
				$sPropertyName='a'.ucwords($sValue).'Resource';
				
				if (OPTIMIZE_CSS === true) {
					$css_combined['list'] = array();
					$css_combined_path='/css/combined/';
					if(!is_dir(SERVER_PATH.$css_combined_path)) mkdir(SERVER_PATH.$css_combined_path, 0775);
				}
				if (OPTIMIZE_JS === true) {
					$js_combined['list'] = array();
					$js_combined_path='/js/combined/';
					if(!is_dir(SERVER_PATH.$js_combined_path)) mkdir(SERVER_PATH.$js_combined_path, 0775);
				}

				foreach ($this->$sPropertyName as $sKey2=>$aValue2) {
					$sFilePath=$aValue2['file_path'];
					$iFileVersion=$this->aResourceVersion[$sFilePath];

					$sResourceType='css';
					if (substr($sFilePath,-2)=='js') $sResourceType='js';

					if ($sResourceType == 'css' && count($aValue2['data'])==0 && OPTIMIZE_CSS === true) {
						$css_combined['list'][] = $aValue2['file_path'];
					} elseif ($sResourceType == 'js' && count($aValue2['data'])==0 && OPTIMIZE_JS === true) {
						$js_combined['list'][] = $aValue2['file_path'];
					} else {
						$sFilePathVersioned=$sFilePath.($iFileVersion ? '?'.$iFileVersion : '');
						Base::$tpl->assign('sFilePathVersioned',$sFilePathVersioned);
						Base::$tpl->assign('aData',$aValue2['data']);
	
						if($sResourceType =='js') {
						    if(Base::GetConstant('optimize_js:async', 0)){
							Base::$aData['template'][$sTemplateName].=
							Base::$tpl->fetch($this->sPrefix.'/type_js_async.tpl');
						    }else{
							Base::$aData['template'][$sTemplateName].=
							Base::$tpl->fetch('addon/'.$this->sPrefix.'/type_js.tpl');
						    }
						}
						else {
						    Base::$aData['template'][$sTemplateName].=
						    Base::$tpl->fetch('addon/'.$this->sPrefix.'/type_'.$sResourceType.'.tpl');
						}
					}
				}
				if (OPTIMIZE_CSS === true && count($css_combined['list'])>0) {
					$sSignature = "/* ".implode(', ', $css_combined['list'])." */\n";
					$css_combined['name'] = '/css/combined/'.$sValue.'.'. md5($sSignature) .'.css';
					$css_combined['mtime'] = @filemtime(SERVER_PATH.$css_combined['name']);
					$css_combined['valid'] = (boolean)$css_combined['mtime'];
					foreach($css_combined['list'] as $aFile) {
						if(@filemtime(SERVER_PATH.$aFile) >= $css_combined['mtime']) { // если объединенный файл старее чем его компонент то его надо перегенерировать
							$css_combined['valid'] = false;
							break;
						}
					}
					//проверяем сигнатуру файла чтоб убедиться что объеденены все нужные файлы в правильном порядке
					if ($css_combined['valid']) {
						$f = @fopen(SERVER_PATH.$css_combined['name'], 'r');
						$sCombinedSignature = fgets($f);
						fclose($f);
						if($sSignature != $sCombinedSignature) $css_combined['valid'] = false;
					}
					if (!$css_combined['valid']) { // файл устарел, надо перегенерировать
						$f = @fopen(SERVER_PATH.$css_combined['name'], 'w');
						if(!$f) die("Can't create combined CSS file ".SERVER_PATH.$css_combined['name']);
						fwrite($f, $sSignature);
						foreach($css_combined['list'] as $aFile) {
							$sFileContents = file_get_contents(SERVER_PATH.$aFile);
							$sFileContents = preg_replace("#\s+|/\*.*?\*/#s", " ", $sFileContents);
							$sFileContents = preg_replace("#[\r\n\t]#s", "", $sFileContents);
							if($bSmartPath) {
								$aPathPart=explode('/',substr($aFile, 1, strrpos($aFile,'/')-1));
								$aFind=array();
								$aReplace=array();
								while(!empty($aPathPart)) {
									array_pop($aPathPart);
									$aFind[]=str_repeat('../',count($aPathPart)+1);
									$aReplace[]='/'.(!empty($aPathPart) ? implode('/',$aPathPart).'/' : '');
								}
								$aReplace=array_reverse($aReplace);
								$sFileContents=str_replace($aFind,$aReplace,$sFileContents);
								preg_match_all("|@import\s+url\s*\(.+?\);|s", $sFileContents, $aImport);
								if(!empty($aImport[0])) foreach($aImport[0] as $sImport) {
									$aImportResource[]=$sImport;
								}
							}
							fwrite($f, $sFileContents);
						}
						fclose($f);
						if(!empty($aImportResource)) {
							$sFileData=implode("",$aImportResource).file_get_contents(SERVER_PATH.$css_combined['name']);
							file_put_contents(SERVER_PATH.$css_combined['name'], $sFileData);
						}
						$css_combined['mtime'] = @filemtime(SERVER_PATH.$css_combined['name']);
					}
					Base::$tpl->assign('sFilePathVersioned',"{$css_combined['name']}?{$css_combined['mtime']}");
					Base::$tpl->assign('aData',array());
					if(!Base::GetConstant('optimize_css:max', 0)) {
							Base::$aData['template'][$sTemplateName].=
							Base::$tpl->fetch('addon/'.$this->sPrefix.'/type_css.tpl');
					} else {
						$bGzip=(stripos(mb_strtolower($_SERVER['HTTP_ACCEPT_ENCODING']),'gzip')!==false ? true : false);
						if($bGzip) {
							if(!file_exists(SERVER_PATH.'/css/combined/.htaccess')) {
								$htaccess='Options All -Indexes'.PHP_EOL;
								$htaccess.='AddType text/css cssgz'.PHP_EOL;
								$htaccess.='AddType text/javascript jsgz'.PHP_EOL;
								$htaccess.='AddEncoding x-gzip .cssgz .jsgz'.PHP_EOL;
								$htaccess.='# for all files in min directory'.PHP_EOL;
								$htaccess.='FileETag None'.PHP_EOL;
								$htaccess.='# Cache for a week, attempt to always use local copy'.PHP_EOL; 
								$htaccess.='<IfModule mod_expires.c>'.PHP_EOL;
								$htaccess.='  ExpiresActive On'.PHP_EOL;
								$htaccess.='  ExpiresDefault A604800'.PHP_EOL;
								$htaccess.='</IfModule>'.PHP_EOL;
								$htaccess.='<IfModule mod_headers.c>'.PHP_EOL;
								$htaccess.='  Header unset ETag'.PHP_EOL;
								$htaccess.='  Header set Cache-Control "max-age=604800, public"'.PHP_EOL;
								$htaccess.='</IfModule>'.PHP_EOL;
								file_put_contents(SERVER_PATH.'/css/combined/.htaccess',$htaccess);
							}
							if(!file_exists(SERVER_PATH.$css_combined['name'].'gz') || !$css_combined['valid']) {
								$content_css=file_get_contents(SERVER_PATH.$css_combined['name']).' ';
								if(method_exists('CSSMin','minify')) 
									$content_css=CSSMin::minify($content_css);
								file_put_contents(SERVER_PATH.$css_combined['name'].'gz',gzencode($content_css,9));	
							}
							Base::$aData['template'][$sTemplateName."Preload"].='<link rel="preload" href="'.$css_combined['name'].'gz?'.$css_combined['mtime'].'" as="style" type="text/css" >';
							Base::$aData['template'][$sTemplateName].='<link href="'.$css_combined['name'].'gz?'.$css_combined['mtime'].'" rel="stylesheet" type="text/css" />';
						} else {
							Base::$aData['template'][$sTemplateName].=Base::$tpl->fetch('addon/'.$this->sPrefix.'/type_css.tpl');
						}
					}
				}
				if (OPTIMIZE_JS === true && count($js_combined['list'])>0) {
					$sSignature = "/* ".implode(', ', $js_combined['list'])." */\n";
					$js_combined['name'] = '/js/combined/'.$sValue.'.'. md5($sSignature) .'.js';
					$js_combined['mtime'] = @filemtime(SERVER_PATH.$js_combined['name']);
					$js_combined['valid'] = (boolean)$js_combined['mtime'];
					foreach($js_combined['list'] as $aFile) {
						if(@filemtime(SERVER_PATH.$aFile) >= $js_combined['mtime']) { // если объединенный файл старее чем его компонент то его надо перегенерировать
							$js_combined['valid'] = false;
							break;
						}
					}
					//проверяем сигнатуру файла чтоб убедиться что объеденены все нужные файлы в правильном порядке
					if ($js_combined['valid']) {
						$f = @fopen(SERVER_PATH.$js_combined['name'], 'r');
						$sCombinedSignature = fgets($f);
						fclose($f);
						if($sSignature != $sCombinedSignature) $js_combined['valid'] = false;
					}

					if (!$js_combined['valid']) { // файл устарел, надо перегенерировать
						$f = @fopen(SERVER_PATH.$js_combined['name'], 'w');
						if(!$f) die("Can't create combined JS file ".SERVER_PATH.$js_combined['name']);
						fwrite($f, $sSignature);
						foreach($js_combined['list'] as $aFile) {
							$sFileContents = file_get_contents(SERVER_PATH.$aFile);
							fwrite($f, $sFileContents."\n");
						}
						fclose($f);
						$js_combined['mtime'] = @filemtime(SERVER_PATH.$js_combined['name']);
					}
					Base::$tpl->assign('sFilePathVersioned',"{$js_combined['name']}?{$js_combined['mtime']}");
					Base::$tpl->assign('aData',array());
					if(!Base::GetConstant('optimize_js:max', 0)) {
						if(Base::GetConstant('optimize_js:async', 0)){
							Base::$aData['template'][$sTemplateName].=
							Base::$tpl->fetch($this->sPrefix.'/type_js_async.tpl');
						}else{
							Base::$aData['template'][$sTemplateName].=
							Base::$tpl->fetch('addon/'.$this->sPrefix.'/type_js.tpl');
						}
					} else {
						$bGzip=(stripos(mb_strtolower($_SERVER['HTTP_ACCEPT_ENCODING']),'gzip')!==false ? true : false);
						if($bGzip) {
							if(!file_exists(SERVER_PATH.'/js/combined/.htaccess')) {
								$htaccess='Options All -Indexes'.PHP_EOL;
								$htaccess.='AddType text/css cssgz'.PHP_EOL;
								$htaccess.='AddType text/javascript jsgz'.PHP_EOL;
								$htaccess.='AddEncoding x-gzip .cssgz .jsgz'.PHP_EOL;
								$htaccess.='# for all files in min directory'.PHP_EOL;
								$htaccess.='FileETag None'.PHP_EOL;
								$htaccess.='# Cache for a week, attempt to always use local copy'.PHP_EOL; 
								$htaccess.='<IfModule mod_expires.c>'.PHP_EOL;
								$htaccess.='  ExpiresActive On'.PHP_EOL;
								$htaccess.='  ExpiresDefault A604800'.PHP_EOL;
								$htaccess.='</IfModule>'.PHP_EOL;
								$htaccess.='<IfModule mod_headers.c>'.PHP_EOL;
								$htaccess.='  Header unset ETag'.PHP_EOL;
								$htaccess.='  Header set Cache-Control "max-age=604800, public"'.PHP_EOL;
								$htaccess.='</IfModule>'.PHP_EOL;
								file_put_contents(SERVER_PATH.'/js/combined/.htaccess',$htaccess);
							}
							if(!file_exists(SERVER_PATH.$js_combined['name'].'gz') || !$js_combined['valid']) {
								$content_js=file_get_contents(SERVER_PATH.$js_combined['name']).' ';
								if(method_exists('JSMin','minify')) 
									$content_js=JSMin::minify($content_js);
								file_put_contents(SERVER_PATH.$js_combined['name'].'gz',gzencode($content_js,9));	
							}
							Base::$aData['template'][$sTemplateName."Preload"].='<link rel="preload" href="'.$js_combined['name'].'gz?'.$js_combined['mtime'].'" as="script" type="text/javascript" >';
							Base::$aData['template'][$sTemplateName].='<script src="'.$js_combined['name'].'gz?'.$js_combined['mtime'].'"></script>';
						} else {
							if(Base::GetConstant('optimize_js:async', 0))
								Base::$aData['template'][$sTemplateName].=Base::$tpl->fetch($this->sPrefix.'/type_js_async.tpl');
							else
								Base::$aData['template'][$sTemplateName].=Base::$tpl->fetch('addon/'.$this->sPrefix.'/type_js.tpl');
						}
					}
				}
			}
		}
	}
	//-----------------------------------------------------------------------------------------------

}

?>