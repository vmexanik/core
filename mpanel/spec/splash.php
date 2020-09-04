<?

class ASplash extends Admin{

	//-----------------------------------------------------------------------------------------------
	public function Index() {
		if(file_exists(SERVER_PATH."/template/mpanel/dtree_new.tpl")) {
		    //use /template/mpanel/***
		} else {
		    $this->sAddonPath='addon/';
		}
		Base::$sText=Base::$tpl->fetch($this->sAddonPath.'mpanel/splash.tpl');
		Base::$tpl->assign('sWinHead','>>Welcome');
		Base::$tpl->assign('sPath','Welcome');

		if (Base::$oResponse) {
			Base::$oResponse->addAssign('sub_menu','innerHTML','');
			Base::$oResponse->addAssign('path','innerHTML','');
			Base::$oResponse->addAssign('win_head','innerHTML','');
			Base::$oResponse->addAssign('win_text','innerHTML',Base::$tpl->fetch($this->sAddonPath.'mpanel/splash.tpl'));
		}
	}
	//-----------------------------------------------------------------------------------------------
}
?>