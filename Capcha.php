<?
/**
 * @author Mikhail Starovoyt
 *
 */
class Capcha extends Base
{
	public $aMathematicOperator=array('+','-');
	
	//-----------------------------------------------------------------------------------------------
	public function __construct()
	{
		Repository::InitDatabase('capcha_hash');
	}
	//-----------------------------------------------------------------------------------------------
	public function GetMathematic($sTemplate='addon/capcha/mathematic.tpl')
	{
		require_once(SERVER_PATH.'/lib/Pear/RPN.php');
		for ($i=1; $i<=100; $i++) {
			while (true) {
				$oMathRpn = new Math_Rpn();
				$sMathematicFormula=$this->GetRandomOperand().$this->GetRandomOperator().$this->GetRandomOperand()
				.$this->GetRandomOperator().$this->GetRandomOperand();
				if($oMathRpn->calculate($sMathematicFormula,'deg',false)>0) break;
			}
			$sValidationHash=$this->GetValidationHash($sMathematicFormula);

			if (!Db::GetOne("select id from capcha_hash where hash='".$sValidationHash."'")) break;
		}

		$aCapcha=array(
		'mathematic_formula' => $sMathematicFormula,
		'validation_hash' => $sValidationHash,
		);
		Base::$tpl->assign('aCapcha',$aCapcha);

		return Base::$tpl->fetch($sTemplate);
	}
	//-----------------------------------------------------------------------------------------------
	public function CheckMathematic()
	{
		Capcha::ClearOldHash();

		if (!Base::$aRequest['capcha']['validation_hash'] || !Base::$aRequest['capcha']['mathematic_formula']
		|| !isset(Base::$aRequest['capcha']['result'])) return false;

		$sValidationHash=Capcha::GetValidationHash(Base::$aRequest['capcha']['mathematic_formula']);
		if (Base::$aRequest['capcha']['validation_hash']!=$sValidationHash)
		return false;

		if (Db::GetOne("select id from capcha_hash where hash='".Base::$aRequest['capcha']['validation_hash']."'")) return false;

		require_once(SERVER_PATH.'/lib/Pear/RPN.php');
		$oMathRpn = new Math_Rpn();
		$iCalculatedResult=$oMathRpn->calculate(Base::$aRequest['capcha']['mathematic_formula'],'deg',false);

		if (Base::$aRequest['capcha']['result']!=$iCalculatedResult) return false;

		Capcha::AddHash('mathematic',$sValidationHash);

		return true;
	}
	//-----------------------------------------------------------------------------------------------
	public function GetRandomOperator()
	{
		return $this->aMathematicOperator[rand(0,count($this->aMathematicOperator)-1)];
	}
	//-----------------------------------------------------------------------------------------------
	public function GetRandomOperand()
	{
		return rand(1,Base::GetConstant('capcha:max_random_operand',10));
	}
	//-----------------------------------------------------------------------------------------------
	public function GetValidationHash($sMathematicFormula)
	{
		return md5(base64_encode($sMathematicFormula.Base::GetConstant('global:project_name','PartMaster')));
	}
	//-----------------------------------------------------------------------------------------------
	public function ClearOldHash()
	{
		Db::Execute("delete from capcha_hash where post_date<DATE_SUB(CURDATE(), INTERVAL "
		.Base::GetConstant('capcha:old_hash_day',10)." DAY)");
	}
	//-----------------------------------------------------------------------------------------------
	public function AddHash($sType='mathematic',$sHash)
	{
		if (!$sHash) return;
		Db::AutoExecute('capcha_hash',array('type_'=>$sType,'hash'=>$sHash));
	}
	//-----------------------------------------------------------------------------------------------
	public function IsFreeFromCapcha(){
		if (!Base::GetConstant('capcha::free from capcha',1))
			return 0;
		
		$iAllowedFreeFromCapchaTimes=Base::GetConstant('capcha::free from capcha times',3);
		$iAllowedFreeFromCapchaPeriod=Base::GetConstant('capcha::free from capcha period',60);
		
		if ($_SESSION['capcha']['free_from_capcha']) {
			$aFreeFromCapcha=$_SESSION['capcha']['free_from_capcha'];
			$iArrayLength=sizeof($aFreeFromCapcha);
			if ($iArrayLength < $iAllowedFreeFromCapchaTimes) {
				$iFreeFromCapcha=1;
			} else {
				$iTimeStamp=$aFreeFromCapcha[$iArrayLength-$iAllowedFreeFromCapchaTimes];
				$iCurrentTimeStamp=time();
				if (($iCurrentTimeStamp-$iAllowedFreeFromCapchaPeriod)<$iTimeStamp)
					$iFreeFromCapcha=0;
					else
					$iFreeFromCapcha=1;
			}
		}else{
			$iFreeFromCapcha=1;
		}
		return $iFreeFromCapcha;
	}
	//-----------------------------------------------------------------------------------------------
	public function SaveLastCapcha(){
			if ($_SESSION['capcha']['free_from_capcha'])
				$aFreeFromCapcha=$_SESSION['capcha']['free_from_capcha'];
				else
				$aFreeFromCapcha=array();
			$aFreeFromCapcha[]=time();
			unset ($_SESSION['capcha']['free_from_capcha']);
			$_SESSION['capcha']['free_from_capcha']=$aFreeFromCapcha;
	}
	//-----------------------------------------------------------------------------------------------
	
	//-----------------------------------------------------------------------------------------------
	public function GetGraphics()
	{	
		require_once(SERVER_PATH.'/lib/captcha/captcha.function.php');

		$aCapcha=array(
				'sTypeCapcha' => 'graph',
				'sGraphCapcha' => "lib/captcha/captcha.image.php?nocache=".md5(time()),
		);
		Base::$tpl->assign('aCapcha',$aCapcha);
	
		return Base::$tpl->fetch('addon/capcha/graphics.tpl');
	}
	//-----------------------------------------------------------------------------------------------
	public function CheckGraph()
	{
		if (!Base::$aRequest['capcha']['result'] || !$_SESSION['magicword'] 
			|| $_SESSION['magicword'] != md5(Base::$aRequest['capcha']['result']))
			
		return false;
		
		return true;
	}
	//-------------------------------------------------------------------------------------------------
	public static function CheckGoogleCaptcha($capcha)
	{
        $url_to_google_api = "https://www.google.com/recaptcha/api/siteverify";
        $secret_key = Language::GetConstant('captcha:privat_key');
        
        $query = $url_to_google_api . '?secret=' . $secret_key . '&response=' . $capcha . '&remoteip=' . $_SERVER['REMOTE_ADDR'];
        $data = json_decode(file_get_contents($query));
        
        return $data->success;
	}
	//-------------------------------------------------------------------------------------------------
}
?>
