<?

class ATranslateText extends Admin {

	//-----------------------------------------------------------------------------------------------
	function __construct() {
		$this->sTableName = 'translate_text';
		$this->sTablePrefix = 't';
		$this->sAction = 'translate_text';
		$this->sWinHead = Language::getDMessage('Text Translate');
		$this->sPath = Language::GetDMessage('>>Content >');
		$this->aCheckField = array('code');
		$this->aFCKEditors = array('content');
		if(file_exists(SERVER_PATH."/template/mpanel/dtree_new.tpl")) {
		    //use /template/mpanel/***
		} else {
		    $this->sAddonPath='addon/';
		}
		$this->sSqlPath='CoreTranslateText';
		$this->Admin();

		$this->sAdminRegulationsUrl = 'http://irbis.mstarproject.com';
		if (Base::$aGeneralConf['AdminRegulationsUrl'])
			$this->sAdminRegulationsUrl = Base::$aGeneralConf['AdminRegulationsUrl'];
	}
	//-----------------------------------------------------------------------------------------------
	public function Index() {
		$this->PreIndex ();

		require_once (SERVER_PATH . '/class/core/Table.php');
		$oTable = new Table ( );
		$oTable->aColumn = array ();
		$oTable->aColumn ['id'] = array ('sTitle' => 'Id', 'sOrder' => 't.id' );
		$oTable->aColumn ['code'] = array ('sTitle' => 'Code', 'sOrder' => 't.code' );
		$oTable->aColumn ['content'] = array ('sTitle' => 'Content', 'sOrder' => 't.content' );
		$this->initLocaleGlobal ();
		$oTable->aColumn ['language'] = array ('sTitle' => 'Lang' );
		$oTable->aColumn ['action'] = array ();
		$this->SetDefaultTable ( $oTable );
		Base::$sText .= $oTable->getTable ();

		$this->AfterIndex ();
		
		if (Base::$aRequest['aMessage'])
			Admin::Message(Base::$aRequest['aMessage']['type'],Base::$aRequest['aMessage']['message']);
	}
	//-----------------------------------------------------------------------------------------------
	public function SendIrbis() {
		if (Base::$aRequest['id']) {
			$aRow = Db::GetRow("Select * from translate_text where id=".Base::$aRequest['id']);
			if ($aRow) {
				if ($aRow['code'] == $aRow['content'])
					Admin::Message('MT_ERROR',Language::getMessage('SendIrbisError:key_not_fill'));
				else {
					$sUrl = $this->sAdminRegulationsUrl . '/pages/admin_regulations_insert_irbis/';
					$ch=curl_init();
					curl_setopt($ch,CURLOPT_URL,$sUrl);
					curl_setopt($ch,CURLOPT_FOLLOWLOCATION,1);
					curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
					curl_setopt($ch,CURLOPT_TIMEOUT,3);
					curl_setopt($ch,CURLOPT_POST,1);
					curl_setopt($ch,CURLOPT_POSTFIELDS,http_build_query(array(
							'type'=>'text',
							'data'=>base64_encode(json_encode($aRow)),
					)));
					$sContent=curl_exec($ch);
					$iErrCode=curl_errno($ch);
					if ($iErrCode == 0 && $sContent)
						Admin::Message('MT_NOTICE',$sContent);
					else 
						Admin::Message('MT_ERROR',Language::getMessage('SendIrbisError:code') .' '. $iErrCode);
				}
			}
			else
				Admin::Message('MT_ERROR',Language::getMessage('SendIrbisError:NotData'));
		}
		else
			Admin::Message('MT_ERROR',Language::getMessage('SendIrbisError:NotData'));
	}
	//-----------------------------------------------------------------------------------------------
	public function GetFromIrbis() {
		if (Base::$aRequest['id']) {
			$aRow = Db::GetRow("Select * from translate_text where id=".Base::$aRequest['id']);
			if ($aRow) {
				$sUrl = $this->sAdminRegulationsUrl . '/pages/admin_regulations_get_from_irbis/';
				$ch=curl_init();
				curl_setopt($ch,CURLOPT_URL,$sUrl);
				curl_setopt($ch,CURLOPT_FOLLOWLOCATION,1);
				curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
				curl_setopt($ch,CURLOPT_TIMEOUT,3);
				curl_setopt($ch,CURLOPT_POST,1);
				curl_setopt($ch,CURLOPT_POSTFIELDS,http_build_query(array(
				'type'=>'text',
				'data'=>base64_encode(json_encode($aRow)),
				)));
				$sContent=curl_exec($ch);
				$iErrCode=curl_errno($ch);
				if ($iErrCode == 0 && $sContent) {
					$aData = json_decode(base64_decode($sContent),true);
					if (!is_array($aData) || !$aData['content']) 
						Admin::Message('MT_ERROR',$sContent);
					else {
						Db::Execute("Update translate_text set
							content = '".mysql_real_escape_string($aData['content'])."',
							post_date = '".date("Y-m-d H:i:s")."'
							where id = '".$aRow['id']."'");

						$aMessage = array ('type' => 'MT_NOTICE', 'message' => Language::getMessage('GetFromIrbisOk:key_update'));
						$this->AdminRedirect ( $this->sAction, $aMessage);
					}
				}
				else
					Admin::Message('MT_ERROR',Language::getMessage('GetFromIrbisError:code') .' '. $iErrCode);
			}
			else 
				Admin::Message('MT_ERROR',Language::getMessage('GetFromIrbisError:not_set_data'));
		}
		else
			Admin::Message('MT_ERROR',Language::getMessage('GetFromIrbisError:not_set_data'));
	}
}
?>