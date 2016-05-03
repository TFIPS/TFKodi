<?
class TFKodi extends IPSModule {
	public function Create()
	{
		parent::Create();		
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();
		
		$this->CreateCatsVars();
		$this->CreateRXScript();
		$this->CreateUpdateScript();
		$this->CheckSocketRegVar();
		
    }

	public function Test()
	{
		//print_r(IPS_GetInstance($this->InstanceID));
		
	//$text = '{"jsonrpc": "2.0", "method": "Player.GetActivePlayers", "id": 1}';
		//$text = '{"id": 1, "jsonrpc": "2.0", "result": [ { "playerid": 1, "type": "video" } ]}';
		// $text = '{"jsonrpc": "2.0", "method": "Player.PlayPause", "params": { "playerid": 1 }, "id": 1}';
		//$text = '{"jsonrpc": "2.0", "method": "Player.GetItem", "params": { "properties": ["All"], "playerid": 1 }, "id": "VideoGetItem"}';
		//$text 		= '{"jsonrpc":"2.0","method":"Input.Up","id":1}';
		//$text 	= '{"jsonrpc":"2.0","method":"Input.Down","id":1}';
		//$volumeUp = '{"jsonrpc":"2.0","method":"Input.VolumeUp","id":1}';
		//$text = '{ "jsonrpc": "2.0", "method": "Player.GetItem", "params": { "properties": ["title"], "playerid":1 }, "id": 1 }';
		//$test = CSCK_SendText($id,$text);
		//$text = '{"jsonrpc": "2.0", "method": "Player.GetActivePlayers", "id": 1}';
		//$text = '{"jsonrpc":"2.0","method":"Player.GetProperties","params":{"playerid":1,"properties":["percentage"]},"id":"1"} }';
		//$channelUp 		= '{"jsonrpc":"2.0","method":"Input.Up","id":1}';
		//$test = CSCK_SendText(16791,$channelUp);
		//IPS_LogMessage('Kodi', $channelUp);
		//$text = '{"jsonrpc": "2.0", "method": "Player.GetActivePlayers", "id": 1}';
		//$test = $this->Send($text);
		//print_r(GetValue(45128));
		
		//IPS_LogMessage('KodiJSON', $test);
		//$this->GetChannelInfo();
		//$this->GetDuration();
		$this->SetActuatorsByCatIdent("TFKodi_onPlay");
	}
	
	public function IncomingData($data) { //Wird ausgeführt, wenn Daten empfangen werden
		$data = unserialize($data);	
		//print_r($data);
		$this->UpdateChannelInfo($data);
		$this->UpdateDuration($data);
		$this->UpdateState($data);
	}
	
	
	public function GetChannelInfo(){ //Anfrage Kanal und Title
		$channelInfoJson = '{"jsonrpc": "2.0", "method": "Player.GetItem", "params": { "properties": ["title"], "playerid":1 }, "id": 1}';
		$this->Send($channelInfoJson);
	}
	public function UpdateChannelInfo($data){
		if(isset($data["result"]["item"]["label"]) && isset($data["result"]["item"]["title"])){
			$parent = IPS_GetParent(IPS_GetParent($_IPS['SELF']));
			
			$channel = $data["result"]["item"]["label"];
			$title = $data["result"]["item"]["title"];
			
			SetValue(@IPS_GetObjectIDByIdent("TFKodi_channel", $parent), $channel);
			SetValue(@IPS_GetObjectIDByIdent("TFKodi_title", $parent), $title);
		}
	}
	
	public function GetDuration(){
		$durationJson = '{"jsonrpc":"2.0","method":"Player.GetProperties","params":{"playerid":1,"properties":["percentage"]},"id":"1"}}';
		$this->Send($durationJson);
	}
	public function UpdateDuration($data){
		if(isset($data["result"]["percentage"])){
			$parent = IPS_GetParent(IPS_GetParent($_IPS['SELF']));
			
			$percentage = round($data["result"]["percentage"]);
			
			SetValue(@IPS_GetObjectIDByIdent("TFKodi_duration", $parent), $percentage);
		}
	}
	
	public function UpdateState($data){
		//Play
		if(isset($data["method"]) && $data["method"] == "Player.OnPlay"){
			$scriptsCatID 	= @IPS_GetObjectIDByIdent("TFKodi_scripts", $this->InstanceID);
			$updaterID		= @IPS_GetScriptIDByName("TFKodi_Updater", $scriptsCatID);
			IPS_SetScriptTimer($updaterID,30);
			
			$this->SetActuatorsByCatIdent("TFKodi_onPlay");
			SetValue($this->GetIDForIdent("TFKodi_state"), 0);
			IPS_Sleep(4);
			$this->GetChannelInfo();
			$this->GetDuration();
			
		} else if(isset($data["method"]) && $data["method"] == "Player.OnStop"){
			$scriptsCatID 	= @IPS_GetObjectIDByIdent("TFKodi_scripts", $this->InstanceID);
			$updaterID		= @IPS_GetScriptIDByName("TFKodi_Updater", $scriptsCatID);
			IPS_SetScriptTimer($updaterID,0);
			
			$this->SetActuatorsByCatIdent("TFKodi_onStop");
			
			SetValue($this->GetIDForIdent("TFKodi_state"), 1);
			SetValue($this->GetIDForIdent("TFKodi_channel"), "");
			SetValue($this->GetIDForIdent("TFKodi_title"), "");
			SetValue($this->GetIDForIdent("TFKodi_duration"), 0);
			
		} else if(isset($data["method"]) && $data["method"] == "Player.OnPause"){
			$scriptsCatID 	= @IPS_GetObjectIDByIdent("TFKodi_scripts", $this->InstanceID);
			$updaterID		= @IPS_GetScriptIDByName("TFKodi_Updater", $scriptsCatID);
			IPS_SetScriptTimer($updaterID,0);
			
			$this->SetActuatorsByCatIdent("TFKodi_onPause");
			
			SetValue($this->GetIDForIdent("TFKodi_state"), 2);
			
		} else if(isset($data["method"]) && $data["method"] == "GUI.OnScreensaverActivated"){
			$scriptsCatID 	= @IPS_GetObjectIDByIdent("TFKodi_scripts", $this->InstanceID);
			$updaterID		= @IPS_GetScriptIDByName("TFKodi_Updater", $scriptsCatID);
			IPS_SetScriptTimer($updaterID,0);
			
			$this->SetActuatorsByCatIdent("TFKodi_screensaverActivated");
			SetValue($this->GetIDForIdent("TFKodi_state"), 3);
		}
	}
	
	
	public function SetActuatorsByCatIdent($ident){
		foreach(IPS_GetChildrenIDs($this->GetIDForIdent($ident)) as $actuatorLinkID) {
		//Prüfe auf Links
			if(IPS_LinkExists($actuatorLinkID)) {
				if(strpos(IPS_GetName($actuatorLinkID), "!") === false)
				{
					$reverse = false;
				} else {
					$reverse = true;
				}
				//Holt ID der Variable
				$actuatorVariableID = IPS_GetLink($actuatorLinkID)['TargetID'];
				
				if (IPS_VariableExists($actuatorVariableID)) {
					$o = IPS_GetObject($actuatorVariableID);
					$v = IPS_GetVariable($actuatorVariableID);

					$actionID = $this->GetProfileAction($v);
					
					if($reverse) {
						$value = false;
					} else {
						$value = true;
					}

					if(IPS_InstanceExists($actionID)) {
						IPS_RequestAction($actionID, $o['ObjectIdent'], $value);
					} else if(IPS_ScriptExists($actionID)) {
						IPS_RunScriptWaitEx($actionID, Array("VARIABLE" => $actuatorVariableID, "VALUE" => $value));
					} 
				}
			}
		}
	}
	
	public function RequestAction($ident, $value) {
		/*
		switch($ident) {
			case "TFKodi_on": 
				$this->SetOn();
			break;
			case "TFKodi_off": 
				$this->SetOff();
			break;
			case "TFKodi_channelUp": 
				$this->SendKey("Up");
			break;
			case "TFKodi_channelDown": 
				$this->SendKey("Down");
			break;
			case "TFKodi_volume": 
				$this->SetVolume($value);
			break;
			case "TFKodi_mute": 
				$this->SetMute();
			break;
			case "TFKodi_record": 
				$this->SetRecord();
			break;
			case "TFKodi_playPause": 
				$this->SetPlayPause();
			break;
			case "TFKodi_stopp": 
				$this->SetStopp();
			break;
			default:
				throw new Exception("Invalid Ident");
		}
		*/
 
	}
	
	public function Send($sendJson){
		$jsonRpcSocketID = IPS_GetInstance($this->InstanceID)["ConnectionID"];
		$kodiSend 		 = CSCK_SendText($jsonRpcSocketID, $sendJson);
		if($kodiSend) {
			return true;
		} else {
			return false;
		}
	}	
	/*
	public function SetOn(){
		$onJson = '{"jsonrpc":"2.0","method":"Addons.ExecuteAddon","params":{"addonid":"script.json-cec","params":{"command":"activate"}},"id":1}';
		CSCK_SendText(12741, $onJson);
	}
	
	public function SetOff(){
		$offJson = '{"jsonrpc":"2.0","method":"Addons.ExecuteAddon","params":{"addonid":"script.json-cec","params":{"command":"standby"}},"id":1}';
		CSCK_SendText(12741, $offJson);
	}
	
	public function SendKey($key){
		$keyJson = '{"jsonrpc":"2.0","method":"Input.'.$key.'","id":1}';
		CSCK_SendText(12741, $keyJson);
	}
	
	public function SetVolume($value){
		$volumeJson = '{"jsonrpc": "2.0", "method": "Application.SetVolume", "params": { "volume": '.$value.'}, "id": 1}';
		CSCK_SendText(12741, $volumeJson);
	}
	
	public function SetMute(){
		$muteJson = '{"jsonrpc": "2.0", "method": "Application.SetMute", "params": {"mute": "toggle"}, "id": "1"}';
		CSCK_SendText(12741, $muteJson);
	}
	
	public function SetRecord(){
		$recordJson = '{"jsonrpc": "2.0", "method": "PVR.Record", "params": {"record": "toggle", "channel": "current"}, "id": "1"}';
		CSCK_SendText(12741, $recordJson);
	}
	
	public function SetPlayPause(){
		$playPauseJson = '{"jsonrpc": "2.0", "method": "Player.PlayPause", "params": { "playerid": 1 }, "id": 1}';
		CSCK_SendText(12741, $playPauseJson);
	}
	
	public function SetStopp(){
		$stoppJson = '{"jsonrpc": "2.0", "method": "Player.Stop", "params": { "playerid": 1 }, "id": 1}';
		CSCK_SendText(12741, $stoppJson);
	}
	
	// Update Funktionen (Prüft Rückgabewerte)
	public function UpdateVolumeVar($data){
		if(isset($data["params"]["data"]["volume"])){
			$volume 	= $data["params"]["data"]["volume"];
			SetValue($this->GetIDForIdent("TFKodi_volume"), $volume);			
		}
	}
	
	public function UpdatePlayerItemVars($data){
		if(isset($data["result"]["item"]["label"]) && isset($data["result"]["item"]["title"])){
			$parent = IPS_GetParent(IPS_GetParent($_IPS['SELF']));
			
			$channel = $data["result"]["item"]["label"];
			$title = $data["result"]["item"]["title"];
			
			SetValue(@IPS_GetObjectIDByIdent("TFKodi_channel", $parent), $channel);
			SetValue(@IPS_GetObjectIDByIdent("TFKodi_title", $parent), $title);
		}
	}
	*/
	
	private function GetProfileAction($variable) 
	{
		if($variable['VariableCustomAction'] != ""){
			return $variable['VariableCustomAction'];
		} else {
			return $variable['VariableAction'];
		}
	}
	
	private function CreateCategoryByIdent($id, $ident, $name)
	{
		 $cid = @IPS_GetObjectIDByIdent($ident, $id);
		 if($cid === false)
		 {
			 $cid = IPS_CreateCategory();
			 IPS_SetParent($cid, $id);
			 IPS_SetName($cid, $name);
			 IPS_SetIdent($cid, $ident);
		 }
		 return $cid;
	}
		
	private function CreateVariableByIdent($id, $ident, $name, $type, $profile = "")
	{
		 $vid = @IPS_GetObjectIDByIdent($ident, $id);
		 if($vid === false)
		 {
			 $vid = IPS_CreateVariable($type);
			 IPS_SetParent($vid, $id);
			 IPS_SetName($vid, $name);
			 IPS_SetIdent($vid, $ident);
			 if($profile != "")
			 {
				IPS_SetVariableCustomProfile($vid, $profile);
			 }
		 }
		 return $vid;
	}
	
	private function CreateCatsVars(){ 
		$scriptsCatID = $this->CreateCategoryByIdent($this->InstanceID, "TFKodi_scripts", "Scripte"); //Kategorie Scripte
		IPS_SetHidden($scriptsCatID, true);
		IPS_SetPosition($scriptsCatID,0);
		
		$onPlayCatID = $this->CreateCategoryByIdent($this->InstanceID, "TFKodi_onPlay", "Play"); //Kategorie Scripte
		IPS_SetHidden($onPlayCatID, true);
		IPS_SetPosition($onPlayCatID,1);
		
		$onPauseCatID = $this->CreateCategoryByIdent($this->InstanceID, "TFKodi_onPause", "Pause"); //Kategorie Scripte
		IPS_SetHidden($onPauseCatID, true);
		IPS_SetPosition($onPauseCatID,2);
		
		$onStopCatID = $this->CreateCategoryByIdent($this->InstanceID, "TFKodi_onStop", "Stop"); //Kategorie Scripte
		IPS_SetHidden($onStopCatID, true);
		IPS_SetPosition($onStopCatID,3);
		
		$screensaverActivatedCatID 	= $this->CreateCategoryByIdent($this->InstanceID, "TFKodi_screensaverActivated", "Screensaver"); //Kategorie Scripte
		IPS_SetHidden($screensaverActivatedCatID, true);
		IPS_SetPosition($screensaverActivatedCatID,4);
		
		$channelID = $this->CreateVariableByIdent($this->InstanceID, "TFKodi_channel", "Kanal", 3, "");
		IPS_SetPosition($channelID,5);
		
		$titleID = $this->CreateVariableByIdent($this->InstanceID, "TFKodi_title", "Titel", 3, "");
		IPS_SetPosition($titleID,6);
		
		$durationID = $this->CreateVariableByIdent($this->InstanceID, "TFKodi_duration", "Fortschritt", 1, "~Valve");
		$this->EnableAction("TFKodi_duration");
		IPS_SetPosition($durationID,7);
		
		if(!IPS_VariableProfileExists("TFKodi_State")){											//Wenn Profil EMA_State nicht existiert
			IPS_CreateVariableProfile("TFKodi_State", 1);												//Legt ein neues Profil für EMA_Status an
			IPS_SetVariableProfileAssociation("TFKodi_State", 0, "Play", "Script", 0xFFFFFF);		//Status Unscharf in grün (ZZZ)	
			IPS_SetVariableProfileAssociation("TFKodi_State", 1, "Stop", "Eyes", 0xFFFFFF);			//Status Scharf in rot (Augen)
			IPS_SetVariableProfileAssociation("TFKodi_State", 2, "Pause", "Close", 0xFFFFFF);			//Status Scharf in rot (Augen)
			IPS_SetVariableProfileAssociation("TFKodi_State", 3, "Screensaver", "Hourglass", 0xFFFFFF);			//Status Scharf in rot (Augen)
			IPS_SetVariableProfileValues("TFKodi_State", 0, 3, 1);
		}
		
		$stateID = $this->CreateVariableByIdent($this->InstanceID, "TFKodi_state", "Status", 1, "TFKodi_State");
		IPS_SetPosition($stateID,8);
		
		/* 
		$this->CreateVariableByIdent($this->InstanceID, "TFKodi_on", "Einschalten", 0, "~Switch");
		$this->EnableAction("TFKodi_on");
		$this->CreateVariableByIdent($this->InstanceID, "TFKodi_off", "Ausschalten", 0, "~Switch");
		$this->EnableAction("TFKodi_off");
		$this->CreateVariableByIdent($this->InstanceID, "TFKodi_channelUp", "Kanal hoch", 0, "~Switch");
		$this->EnableAction("TFKodi_channelUp");
		$this->CreateVariableByIdent($this->InstanceID, "TFKodi_channelDown", "Kanal runter", 0, "~Switch");
		$this->EnableAction("TFKodi_channelDown");
		$this->CreateVariableByIdent($this->InstanceID, "TFKodi_volume", "Lautstärke", 1, "~Valve");
		$this->EnableAction("TFKodi_volume");
		
		$this->CreateVariableByIdent($this->InstanceID, "TFKodi_mute", "Stumm", 0, "~Switch");
		$this->EnableAction("TFKodi_mute");
		
		$this->CreateVariableByIdent($this->InstanceID, "TFKodi_record", "Aufnahme", 0, "~Switch");
		$this->EnableAction("TFKodi_record");
		
		$this->CreateVariableByIdent($this->InstanceID, "TFKodi_playPause", "Pause", 0, "~Switch");
		$this->EnableAction("TFKodi_playPause");
		
		$this->CreateVariableByIdent($this->InstanceID, "TFKodi_stopp", "Stopp", 0, "~Switch");
		$this->EnableAction("TFKodi_stopp");
		*/
	}
	
	private function CreateRXScript(){
		// Receiver-Script erstellen
		$scriptsCatID = @IPS_GetObjectIDByIdent("TFKodi_scripts", $this->InstanceID);
		
		$script  = '<?'."\n";
		$script .= '	if ($_IPS[\'SENDER\'] == \'RegisterVariable\'){'."\n";
		$script .= '		$jsonData = RegVar_GetBuffer($_IPS[\'INSTANCE\']);'."\n";
		$script .= '		$jsonData = $_IPS[\'VALUE\'];'."\n";
		$script .= '		if($jsonData){'."\n";
		$script .= '			$data = json_decode($jsonData,true);'."\n";
		$script .= '			//print_r($data);'."\n";
		$script .= '			if($data){'."\n";
		$script .= '				TFKodi_IncomingData('.$this->InstanceID.', serialize($data));'."\n";
		$script .= '			}'."\n";
		$script .= '		}'."\n";
		$script .= '	}'."\n";
		$script .= '	RegVar_SetBuffer($_IPS[\'INSTANCE\'], $jsonData);'."\n";
		$script .= '	unset($data);'."\n";
		$script .= '	unset($jsonData);'."\n";
		$script .= '?>';
		
		if(!@IPS_GetScriptIDByName("TFKodi_Receiver", $scriptsCatID)){
			$scriptID = IPS_CreateScript(0);
			IPS_SetName($scriptID, "TFKodi_Receiver");
			IPS_SetScriptContent($scriptID, $script);
			IPS_SetParent($scriptID, $scriptsCatID);
		}
	}
	
	private function CreateUpdateScript(){
		// Receiver-Script erstellen
		$scriptsCatID = @IPS_GetObjectIDByIdent("TFKodi_scripts", $this->InstanceID);
		
		$script  = '<?'."\n";
		$script .= '	TFKodi_GetChannelInfo('.$this->InstanceID.');'."\n";
		$script .= '	TFKodi_GetDuration('.$this->InstanceID.');'."\n";
		$script .= '?>';
		
		if(!@IPS_GetScriptIDByName("TFKodi_Updater", $scriptsCatID)){
			$scriptID = IPS_CreateScript(0);
			IPS_SetName($scriptID, "TFKodi_Updater");
			IPS_SetScriptContent($scriptID, $script);
			//IPS_SetScriptTimer($scriptID, 10);
			IPS_SetParent($scriptID, $scriptsCatID);
		}
	}
	
	private function CheckSocketRegVar(){
		// Prüfen / Erstellen und Verbinden des "TFKodi JSON-RPC-Socket"
		$instance = IPS_GetInstance($this->InstanceID);
		$rpcSocketModuleID = '{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}'; //Socket ID
		if($instance['ConnectionID'] == 0) { //Keine Socket Verbindung in der Instanz hinterlegt
			$moduleIDs = IPS_GetInstanceListByModuleID($rpcSocketModuleID);
			foreach($moduleIDs as $moduleID) {
				$name = IPS_GetName($moduleID);
				if($name == "TFKodi JSON-RPC-Socket") {
					$jsonRpcSocket = IPS_GetInstance($moduleID);
					$jsonRpcSocketID = $jsonRpcSocket["InstanceID"];
					IPS_ConnectInstance($this->InstanceID, $moduleID);
				}		
			}
			if(!isset($jsonRpcSocketID)) {
				$jsonRpcSocketID = IPS_CreateInstance($rpcSocketModuleID);
				IPS_SetName($jsonRpcSocketID, "TFKodi JSON-RPC-Socket");
				IPS_SetProperty($jsonRpcSocketID, "Open", false);
				IPS_SetProperty($jsonRpcSocketID, "Host", "127.0.0.1");
				IPS_SetProperty($jsonRpcSocketID, "Port", "9090");
				IPS_ApplyChanges($jsonRpcSocketID); 
				IPS_ConnectInstance($this->InstanceID, $jsonRpcSocketID);
			}
		}
		
		// Prüfen / Erstellen und Verbinden der "RegisterVariable"
		$scriptsCatID 	= @IPS_GetObjectIDByIdent("TFKodi_scripts", $this->InstanceID);
		$rxScriptID 	= @IPS_GetScriptIDByName("TFKodi_Receiver", $scriptsCatID);
		
		$registerVariableModuleID = "{F3855B3C-7CD6-47CA-97AB-E66D346C037F}";
		$moduleIDs = IPS_GetInstanceListByModuleID($registerVariableModuleID);
		foreach($moduleIDs as $moduleID) {
			$name = IPS_GetName($moduleID);
			if($name == "TFKodi RegisterVariable") {
				$registerVariable = IPS_GetInstance($moduleID);
				$registerVariableID = $registerVariable["InstanceID"];
				if($registerVariable['ConnectionID'] == 0) {
					IPS_ConnectInstance($registerVariableID, $jsonRpcSocketID);
					IPS_SetProperty($registerVariableID, "RXObjectID", $rxScriptID);
					IPS_SetHidden($registerVariableID, true); //Objekt verstecken
					IPS_ApplyChanges($registerVariableID);
				}				
			}
		}
		if(!isset($registerVariableID)) {
			$scriptsCatID = @IPS_GetObjectIDByIdent("TFKodi_scripts", $this->InstanceID);
			$newRegisterVariableID = IPS_CreateInstance("{F3855B3C-7CD6-47CA-97AB-E66D346C037F}");	
			IPS_SetName($newRegisterVariableID,"TFKodi RegisterVariable");
			IPS_ConnectInstance($newRegisterVariableID, $jsonRpcSocketID);
			IPS_SetProperty($newRegisterVariableID, "RXObjectID", $rxScriptID);
			IPS_SetHidden($newRegisterVariableID, true); //Objekt verstecken
			IPS_ApplyChanges($newRegisterVariableID);
			IPS_SetParent($newRegisterVariableID, $scriptsCatID); //verschieben
		}
	}
}
?>
