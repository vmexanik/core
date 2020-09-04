<?
/**
 * @author Starovoit Oleksandr
 */

class File extends Base {

	//-----------------------------------------------------------------------------------------------
	/**
	 * Files from dir $sUrl (comparative)
	 *
	 * @param strung $sUrl
	 * @param boolen $bComparative
	 * @return array
	 */
	public function GetFromDir($sPathToDir, $bComparative=true)
	{
		if ($bComparative) $sPathToDir=SERVER_PATH."/".trim($sPathToDir,"/");
		$sPathToDir=rtrim($sPathToDir,"/");

		$oHandle=opendir($sPathToDir);
		if($oHandle===false) return false;
		while (false !== ($fls=readdir($oHandle))) {
			if (is_file($sPathToDir."/".$fls)) {
				$aPathInfo=pathinfo($fls);
				$iTime = filemtime($sPathToDir."/".$fls);
				$sDate = '';
				if ($iTime)
					$sDate = date("Y-m-d H:i:s",$iTime);
				$aFile[]=array('name'=>$fls, 'path'=>$sPathToDir."/".$fls, 'extension'=>$aPathInfo['extension'], 'filename'=>$aPathInfo['filename'],'date' => $sDate);
			}
		}

		if (is_array($aFile)) return $aFile;
		else return false;
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Remove file to dir
	 *
	 * @param array $aFile array('name'=>'FileName', 'path'=>'FilePath')
	 * @param string $sPathToDir
	 * @param boolen $bComparative
	 * @return boolen
	 */
	public function RemoveToDir($aFile, $sPathToDir, $bComparative=true, $bAddTime=true, $bDeleteBefore=true)
	{
		if ($bComparative) $sPathToDir=SERVER_PATH."/".trim($sPathToDir,"/");
		$sPathToDir=rtrim($sPathToDir,"/");

		$sPathToRemoveFile=$sPathToDir."/".$aFile['name'];
		if ($bAddTime) $sPathToRemoveFile.="_".time();

		if ($bDeleteBefore) {
			if (is_file($sPathToRemoveFile)) {
				unlink($sPathToRemoveFile);
			}
		}

		return rename($aFile['path'], $sPathToRemoveFile);

	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Write to file
	 *
	 * @param array $aFile array('name'=>'FileName', 'path'=>'FilePath')
	 * @param string $sContent
	 * @return boolen
	 */
	public function Write($aFile,$sContent,$sMode="w")
	{
		if (!$oHandle = fopen($aFile['path'], $sMode)) return false;
		if (fwrite($oHandle, $sContent) === FALSE) return false;
		fclose($oHandle);
		return true;
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Extract from archive
	 *
	 * @param string $sPathToFile
	 * @param string $sPathToExtract
	 * @return array array(0=>array(name=>...,path=>...),...)
	 */
	public function Extract($sPathToFile, $sPathToExtract)
	{
		$sPathToExtract=rtrim($sPathToExtract,"/")."/";
		$aFilePart = pathinfo($sPathToFile);
		//$sExt=strtolower(end(explode(".",$sPathToFile)));
		$sExt=$aFilePart['extension'];
		if ($sExt=="zip") {
			$aFile=array();
			$oZip = new ZipArchive;
			if ($oZip->open($sPathToFile) === TRUE) {
				for($i = 0; $i < $oZip->numFiles; $i++) {
					$aFile[$i]['name']=iconv("cp866","UTF-8",$oZip->getNameIndex($i));
					$aFile[$i]['path']=$sPathToExtract.$aFile[$i]['name'];
					$oZip->renameIndex($i,$aFilePart['basename'].$i);
				}
				$oZip->close();
				
				$oZip->open($sPathToFile);
				$oZip->extractTo($sPathToExtract);
				if ($aFile) foreach ($aFile as $sKey => $aValue) {
					rename($sPathToExtract.$aFilePart['basename'].$sKey, $aValue['path']);
				}
				$oZip->close();

				return $aFile;
			} else {
				return false;
			}
		} elseif ($sExt=="rar") {
			$aFile=array();
			exec("LANG=ru_RU.UTF8 rar vb ".$sPathToFile,$aTmp);
			if ($aTmp) {
				$sResult=exec("LANG=ru_RU.UTF8 rar e ".$sPathToFile." ".$sPathToExtract);
				if ($sResult=="All OK") {
					for($i = 0; $i < count($aTmp); $i++) {
						$aFile[$i]['name']=trim($aTmp[$i]);
						$aFile[$i]['path']=$sPathToExtract.$aFile[$i]['name'];
					}
				} else return false;
				return $aFile;

			} else {
				return false;
			}
		} elseif ($sExt=="7z") {
			$aFile=array();
			exec("LANG=ru_RU.UTF8 7za l ".$sPathToFile,$aTmp0);
			if($aTmp0)
			foreach ($aTmp0 as $sValue) {
				if($bSave){
					$aTmp[]=substr($sValue,53);
				}
				if(strpos($sValue,'----')===0) $bSave=!$bSave;
			}
			unset($aTmp[count($aTmp)-1]);
			if ($aTmp) {
				$sResult=exec("LANG=ru_RU.UTF8 7za e -o".$sPathToExtract." ".$sPathToFile);
				if (strpos($sResult,'Compressed')===0) {
					for($i = 0; $i < count($aTmp); $i++) {
						$aFile[$i]['name']=trim($aTmp[$i]);
						$aFile[$i]['path']=$sPathToExtract.$aFile[$i]['name'];
					}
				} else return false;
				return $aFile;

			} else {
				return false;
			}
		}
	}

	//-----------------------------------------------------------------------------------------------
	/**
	 * Extract from archive
	 * Check extansions for use in price and delete not need
	 * 
	 * @param string $sPathToFile
	 * @param string $sPathToExtract
	 * @return array array(0=>array(name=>...,path=>...),...) filename added user_id 
	 */
	public function ExtractForPrice($sPathToFile, $sPathToExtract)
	{
		$sPathToExtract=rtrim($sPathToExtract,"/")."/";
		$aFilePart = pathinfo($sPathToFile);
		//$sExt=strtolower(end(explode(".",$sPathToFile)));
		$sExt=strtolower($aFilePart['extension']);
		if ($sExt=="zip" || $sExt=="z ip") {
			$aFile=array();
			$oZip = new ZipArchive;
			if ($oZip->open($sPathToFile) === TRUE) {
				for($i = 0; $i < $oZip->numFiles; $i++) {
				    $sTmpName=ltrim($oZip->getNameIndex($i),"/");
				    $sEncoding=mb_detect_encoding($sTmpName);
				    if($sEncoding!="UTF-8"){
				        $aFile[$i]['name']=Auth::$aUser['id'].iconv($sEncoding,"UTF-8",$sTmpName);
				    } else {
				        $aFile[$i]['name']=Auth::$aUser['id'].$sTmpName;
				    }
					$aFile[$i]['path']=$sPathToExtract.$aFile[$i]['name'];
					$oZip->renameIndex($i,$aFilePart['basename'].$i);
				}
				$oZip->close();
				
				$oZip->open($sPathToFile);
				$oZip->extractTo($sPathToExtract);
				if ($aFile) foreach ($aFile as $sKey => $aValue) {
					rename($sPathToExtract.$aFilePart['basename'].$sKey, $aValue['path']);
				}
				$oZip->close();
			} else {
				return false;
			}
		} elseif ($sExt=="gz" || $sExt=="gzip") {
			$aFile=array();
			exec("LANG=ru_RU.UTF8 gzip -l -N \"".$sPathToFile."\"",$aTmp);
			unset($aTmp[0]);
			$sNameFile=trim(substr($aTmp[1],stripos($aTmp[1], $aFilePart['dirname'])));
			$sNameFile=ltrim($sNameFile,$aFilePart['dirname']);
			if (count($aTmp)==1) {
				$aPathIn=pathinfo($sPathToFile);
				exec("LANG=ru_RU.UTF8 gzip -d -N \"".$sPathToFile."\"");
				if(Auth::$aUser['id']){
				    rename($sPathToExtract.$sNameFile, $sPathToExtract.Auth::$aUser['id'].$sNameFile);
				    $sNameFile=Auth::$aUser['id'].$sNameFile;
				}
				$aFile[0]['name']=$sNameFile;
				$aFile[0]['path']=$aPathIn['dirname'].'/'.$sNameFile;
			} else {
				return false;
			}
		} elseif ($sExt=="rar") {
			$aFile=array();
			exec("LANG=ru_RU.UTF8 rar vb \"".$sPathToFile."\"",$aTmp);
			if ($aTmp) {
				$sResult=exec("LANG=ru_RU.UTF8 rar -y e \"".$sPathToFile."\" ".$sPathToExtract);
				if ($sResult=="All OK") {
					for($i = 0; $i < count($aTmp); $i++) {
						$a=explode("/",$aTmp[$i]);
						$sName = trim($a[count($a)-1]);
						$aFile[$i]['name']=Auth::$aUser['id'].$sName;
						$aFile[$i]['path']=$sPathToExtract.$aFile[$i]['name'];
						rename($sPathToExtract.$sName, $sPathToExtract.$aFile[$i]['name']);
						/* basename not work correct with curyllic symbol 
						$aFile[$i]['name']=Auth::$aUser['id'].trim(basename($aTmp[$i]));
						$aFile[$i]['path']=$sPathToExtract.$aFile[$i]['name'];
						rename($sPathToExtract.trim(basename($aTmp[$i])), $sPathToExtract.$aFile[$i]['name']);*/
					}
				} else return false;
			} else {
				return false;
			}
		}
		
		if (!$aFile || $aFile == array())
			return false;
		
		// check validate extensions
		$oPrice = new Price();
		$aFileSave = $aFile;
		foreach($aFileSave as $key => $aValue) {
			$aPathInfo = pathinfo($aValue['path']);
			if (!in_array(strtolower($aPathInfo['extension']),$oPrice->aValidateExtensions)) {
				@unlink($aFile[$key]['path']);
				unset($aFile[$key]);
			}
		}
		return array_values($aFile);
	}        
    //-----------------------------------------------------------------------------------------------
	/**
	 * Extract from archive first file
 	 *
	 * @param string $sPathToFile
	 * @param string $sPathToExtract
	 * @return array array(0=>array(name=>...,path=>...),...)
	 */
	public function ExtractFirstFile($sPathToFile, $sPathToExtract)
	{
		$sPathToExtract=rtrim($sPathToExtract,"/")."/";
		$aFilePart = pathinfo($sPathToFile);
		$sExt=$aFilePart['extension'];
		if ($sExt=="zip") {
			$aFile=array();
			$oZip = new ZipArchive;
			if ($oZip->open($sPathToFile) === TRUE) {
				$oZip->renameIndex(0, Auth::$aUser['id'] . $oZip->getNameIndex(0));
                $aFile['name']=iconv("cp866","UTF-8",$oZip->getNameIndex(0));
                $aFile['path']=$sPathToExtract.$aFile['name'];
                $oZip->extractTo($sPathToExtract, array($aFile['name']));
                $oZip->close();
				if (file_exists($aFile['path']) && filesize($aFile['path']) > 0) {
					@unlink($sPathToFile);
					if (file_exists($aFile['path']) && filesize($aFile['path']) > 0) {
						return $aFile;
					}
				}
			}
		} elseif ($sExt=="rar") {
			$aFile=array();
			@exec("LANG=ru_RU.UTF8 rar vb ".$sPathToFile,$aTmp);
			if ($aTmp[0]) {
				$sLocalPathUser = $sPathToExtract . Auth::$aUser['id'] . $aTmp[0];
				@unlink($sLocalPathUser);
				$sLocalPath = $sPathToExtract . $aTmp[0];
				@unlink($sLocalPath);
				@exec("LANG=ru_RU.UTF8 rar e ".$sPathToFile." \"".$aTmp[0]."\" ".$sPathToExtract, $sOutput, $iCode);
				if ($iCode==0 && file_exists($sLocalPath) && filesize($sLocalPath) > 0) {
					@unlink($sPathToFile);
					@rename($sLocalPath, $sLocalPathUser);
					if (file_exists($sLocalPathUser) && filesize($sLocalPathUser) > 0) {
                    	$aFile['name']=basename($sLocalPathUser);
                    	$aFile['path']=$sLocalPathUser;
                    	return $aFile;
					}
				}
			}
		}
		return false;
	}
	//-----------------------------------------------------------------------------------------------
	public function CheckFileUpload($aFile) {

		$aResult = array('iError' => 0);
		
		// If a file was uploaded, process it.
	  	if ($aFile["name"]) {
	
		    // Check for file upload errors and return FALSE if a
		    // lower level system error occurred.
		    switch ($aFile["error"]) {
		
		      // @see http://php.net/manual/en/features.file-upload.errors.php
		      case UPLOAD_ERR_OK:
		        break;
		
		      case UPLOAD_ERR_INI_SIZE:
		      case UPLOAD_ERR_FORM_SIZE:
		        $aResult['sMessage'] = Language::GetMessage('The file could not be uploaded, because it exceeds the maximum allowed size for uploads.');
		        break;
		
		      case UPLOAD_ERR_PARTIAL:
		      case UPLOAD_ERR_NO_FILE:
		        $aResult['sMessage'] = Language::GetMessage('The file could not be uploaded, because the upload did not complete.');
		        break;
		
		        // Unknown error
		      default:
		        $aResult['sMessage'] = Language::GetMessage('The file could not be uploaded. An unknown error has occurred.');
		        break;
		    }
	  	}
	  	if ($aResult['sMessage'])
	  		$aResult['iError'] = 1;
	  	
	  	if (!is_uploaded_file($aFile["tmp_name"]) && $aResult['iError'] == 0)
	  		$aResult = array(
	  			'iError' 	=> 1,
	  			'sMessage' 	=> Language::GetMessage('The file could not be uploaded. An unknown error has occurred.')
	  	);
	  	
	  	return $aResult;
	}
}
?>
