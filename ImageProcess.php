<?
/**
 * @author Mikhail Starovoyt
 *
 */

class ImageProcess
{

	public $aDisallowedExtension=array('.php');
	public $aAllowedExtension=array('.gif','.jpg','.jpeg','.png');

	//	public static function WhiteRectangle($sFilename,$aCoordinate,$bRewriteSource=true,$sType='gif')
	//	{
	//		$im = imagecreatefromgif ($sFilename);
	//		if (!$im) return false;
	//
	//		$white = imagecolorallocate ($im, 255, 255, 255);
	//		imagefilledrectangle ($im, $aCoordinate[0],$aCoordinate[1],$aCoordinate[2],$aCoordinate[3], $white);
	//
	//		//$sOutFilename=str_replace('.gif','_.gif',$sFilename);
	//		$sOutFilename=$sFilename;
	//		if ($bRewriteSource) imagegif($im,$sOutFilename);
	//		else return imagegif($im);
	//	}

	//-----------------------------------------------------------------------------------------------
	/**
	 * Uploads and resize images.
	 *
	 * @param  $sFieldName
	 * @param  $iMaxUploaded
	 * @param  $sTargetPath
	 * @param  $iIdCustom - ID of object of images for name creation
	 * @param  $iBigWidth
	 * @param  $iSmallWidth
	 * @return array of uploaded and resized images
	 */
	public function GetUploadedImage($sFieldName,$iMaxUploaded=3,$sTargetPath='/imgbank/Image/'
	,$iIdCustom='',$iBigWidth=600,$iSmallWidth=80,$bLeaveOriginal=false)
	{
		for ($i=1;$i<=$iMaxUploaded;$i++)
		{
			if (is_uploaded_file($_FILES[$sFieldName]['tmp_name'][$i]) &&
			(strpos(' '.$_FILES[$sFieldName]['type'][$i],'image') || strpos(' '.$_FILES[$sFieldName]['type'][$i],'pdf')))
			{
				if (stripos(' '.$_FILES[$sFieldName]['type'][$i],'jpeg')) $sUploadedExtension=".jpg";
				elseif (stripos(' '.$_FILES[$sFieldName]['type'][$i],'jpg')) $sUploadedExtension=".jpg";
				elseif (stripos(' '.$_FILES[$sFieldName]['type'][$i],'gif')) $sUploadedExtension=".gif";
				elseif (stripos(' '.$_FILES[$sFieldName]['type'][$i],'png')) $sUploadedExtension=".png";
				elseif (stripos(' '.$_FILES[$sFieldName]['type'][$i],'pdf')) $sUploadedExtension=".pdf";
				elseif (stripos(' '.$_FILES[$sFieldName]['type'][$i],'bmp')) $sUploadedExtension=".bmp";
				else $sError.="$i: only jpg and gif\n";

				if (in_array($sUploadedExtension,array('.pdf','.bmp'))) {
					$bLeaveOriginal=true;
					$bLeaveSmallOriginal=true;
				}

				$sUnique=uniqid();
				$sImageName='image'.$iIdCustom.'_'.$i.'_'.$sUnique.$sUploadedExtension;
				$sImageNameSmall='image'.$iIdCustom.'_'.$i.'_'.$sUnique.'_small'.$sUploadedExtension;

				if (!$sError) {
					$sPath=$sTargetPath.date('Y').'/'.date('m').'/';
					if (!file_exists(SERVER_PATH.$sPath)) {
						if (!file_exists(SERVER_PATH.$sTargetPath)) mkdir(SERVER_PATH.$sTargetPath);
						if (!file_exists(SERVER_PATH.$sTargetPath.date('Y'))) mkdir(SERVER_PATH.$sTargetPath.date('Y'));
						mkdir(SERVER_PATH.$sPath);
					}
					if ($bLeaveOriginal) {
						copy($_FILES[$sFieldName]['tmp_name'][$i],SERVER_PATH.$sPath.$sImageName);
					}
					else {
						$this->ImageResize($_FILES[$sFieldName]['tmp_name'][$i], SERVER_PATH.$sPath.$sImageName,$iBigWidth
						,$sUploadedExtension);
					}
					if ($bLeaveSmallOriginal){
						copy($_FILES[$sFieldName]['tmp_name'][$i],SERVER_PATH.$sPath.$sImageNameSmall);
					}
					else {
						$this->ImageResize($_FILES[$sFieldName]['tmp_name'][$i], SERVER_PATH.$sPath.$sImageNameSmall,$iSmallWidth
						,$sUploadedExtension);
					}
					$aImage[$i]['name']=$sPath.$sImageName;
					$aImage[$i]['name_small']=$sPath.$sImageNameSmall;
				}
			}
		}
		return $aImage;
	}
	//-----------------------------------------------------------------------------------------------
	function ImageResize($sSource, $sDestination, $iNewWidth, $sExtension)
	{
		if (Base::GetConstant('use_gdlib_resize',1)) {
			$aSize=GetImageSize("$sSource");
			$iNewHeight=round($iNewWidth*$aSize[1]/$aSize[0]);

			if ($sExtension==".jpg") $sSourceImage = imagecreatefromjpeg($sSource);
			if ($sExtension==".gif") $sSourceImage = imagecreatefromgif($sSource);
			if ($sExtension==".png") $sSourceImage = imagecreatefrompng($sSource);
			$sDestinationImage = imagecreatetruecolor($iNewWidth,$iNewHeight);
			imagecopyresized($sDestinationImage,$sSourceImage,0,0,0,0,$iNewWidth,$iNewHeight
			,imagesx($sSourceImage),imagesy($sSourceImage));
			if ($sDestination!='') {
				if ($sExtension==".jpg") imagejpeg($sDestinationImage, $sDestination);
				if ($sExtension==".gif") imagegif($sDestinationImage, $sDestination);
				if ($sExtension==".png") imagepng($sDestinationImage, $sDestination);
			} else {
				if ($sExtension==".jpg") imagejpeg($sDestinationImage);
				if ($sExtension==".gif") imagegif($sDestinationImage);
				if ($sExtension==".png") imagepng($sDestinationImage);
			}
		}
		else {
			$oImage = new Imagick($sSource);

			$iOldWidth=$oImage->getImageWidth();
			$iOldHeight=$oImage->getImageHeight();
			$iNewHeight=round($iNewWidth*$iOldHeight/$iOldWidth);
			$oImage->resizeImage($iNewWidth, $iNewHeight, imagick::FILTER_LANCZOS,0.9);

			$oImage->writeImage($sDestination);
		}
		return true;
	}
	//-----------------------------------------------------------------------------------------------
	public function CreateSubnail($sSource,$sTargetPath='/imgbank/Image/',$iIdCustom='',$iBigWidth=600
	,$iMiddleWidth=300,$iSmallWidth=100)
	{
		if (stripos(' '.$sSource,'jpeg') || stripos(' '.$sSource,'jpg')) $sUploadedExtension=".jpg";
		elseif (stripos(' '.$sSource,'gif')) $sUploadedExtension=".gif";
		else return " only jpg and gif\n";

		$sUnique=uniqid();
		$sImageNameBig='image'.$iIdCustom.'_'.$sUnique.'_big'.$sUploadedExtension;
		$sImageNameMiddle='image'.$iIdCustom.'_'.$sUnique.'_middle'.$sUploadedExtension;
		$sImageNameSmall='image'.$iIdCustom.'_'.$sUnique.'_small'.$sUploadedExtension;

		$sPath=$sTargetPath.date('Y').'/'.date('m').'/';
		if (!file_exists(SERVER_PATH.$sPath)) {
			if (!file_exists(SERVER_PATH.$sTargetPath)) mkdir(SERVER_PATH.$sTargetPath);
			if (!file_exists(SERVER_PATH.$sTargetPath.date('Y'))) mkdir(SERVER_PATH.$sTargetPath.date('Y'));
			mkdir(SERVER_PATH.$sPath);
		}
		if ($iBigWidth) {
			$this->ImageResize($sSource, SERVER_PATH.$sPath.$sImageNameBig,$iBigWidth,$sUploadedExtension);
			$aImage['name_big']=$sPath.$sImageNameBig;
		}
		if ($iMiddleWidth) {
			$this->ImageResize($sSource, SERVER_PATH.$sPath.$sImageNameMiddle,$iMiddleWidth,$sUploadedExtension);
			$aImage['name_middle']=$sPath.$sImageNameMiddle;
		}
		if ($iSmallWidth) {
			$this->ImageResize($sSource, SERVER_PATH.$sPath.$sImageNameSmall,$iSmallWidth,$sUploadedExtension);
			$aImage['name_small']=$sPath.$sImageNameSmall;
		}
		return $aImage;
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Make an array /some/path/file.jpeg to
	 * array (
	 * 'begin'=>'/some/path/file',
	 * 'end'=>'.jpeg',
	 * )
	 */
	function ParseExtension($sPath)
	{
		$iDotPosition=strrpos($sPath,'.');
		return array(
		'begin'=>substr($sPath,0,$iDotPosition),
		'end'=>substr($sPath,$iDotPosition),
		);
	}
	//-----------------------------------------------------------------------------------------------


}

?>