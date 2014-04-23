<?php
/*
 * @package AJAX_Chat
 * @author Sebastian Tschan
 * @copyright (c) Sebastian Tschan
 * @license Modified MIT License
 * @link https://blueimp.net/ajax/
 */

class CustomAJAXChat extends AJAXChat {

	function __construct($handle_request = true)
	{
	   	if(!$handle_request)
	   	{
	   		// Initialize configuration settings:
			$this->initConfig();

			// Initialize the DataBase connection:
			$this->initDataBaseConnection();

			// Initialize request variables:
			$this->initRequestVars();
			
			// Initialize the chat session:
			$this->initSession();
	   	}
	   	else
	   	{
	   		parent::__construct();
	   	}


	}

	// Returns an associative array containing userName, userID and userRole
	// Returns null if login is invalid
	function getValidLoginUserData() {
		$customUsers = $this->getCustomUsers();
		
		if($this->getRequestVar('password')) {
			// Check if we have a valid registered user:

			$userName = $this->getRequestVar('userName');
			$userName = $this->convertEncoding($userName, $this->getConfig('contentEncoding'), $this->getConfig('sourceEncoding'));

			$password = $this->getRequestVar('password');
			$password = $this->convertEncoding($password, $this->getConfig('contentEncoding'), $this->getConfig('sourceEncoding'));

			foreach($customUsers as $key=>$value) {
				if(($value['userName'] == $userName) && ($value['password'] == $password)) {
					$userData = array();
					$userData['userID'] = $key;
					$userData['userName'] = $this->trimUserName($value['userName']);
					$userData['userRole'] = $value['userRole'];
					return $userData;
				}
			}
			
			return null;
		} else {
				$userName = $this->getRequestVar('userName');
				$userName = $this->convertEncoding($userName, $this->getConfig('contentEncoding'), $this->getConfig('sourceEncoding'));

				$onlineUsersData = $this->getOnlineUsersData();
				//print_r($onlineUsersData);
				//die();
				if($userName == "admin") return null;
				$id = 1;
				foreach($onlineUsersData as $onlineUser)
				{
					if($userName == $onlineUser["userName"]) return null;
					$id++;
				}
					
				
				$userData = array();
				$userData['userID'] = $id;
				$userData['userName'] = $this->trimUserName($userName);
				$userData['userRole'] = AJAX_CHAT_USER;
				$userData['channels'] = array_values($this->getAllChannels());
				//print_r($userData);
				//die();
				return $userData;
		}

	
	}

	// Returns an associative array containing userName, userID and userRole
	// Returns null if login is invalid
	function getValidLoginUserDataOld() {
		
		$customUsers = $this->getCustomUsers();
		
		if($this->getRequestVar('password')) {
			// Check if we have a valid registered user:

			$userName = $this->getRequestVar('userName');
			$userName = $this->convertEncoding($userName, $this->getConfig('contentEncoding'), $this->getConfig('sourceEncoding'));

			$password = $this->getRequestVar('password');
			$password = $this->convertEncoding($password, $this->getConfig('contentEncoding'), $this->getConfig('sourceEncoding'));

			foreach($customUsers as $key=>$value) {
				if(($value['userName'] == $userName) && ($value['password'] == $password)) {
					$userData = array();
					$userData['userID'] = $key;
					$userData['userName'] = $this->trimUserName($value['userName']);
					$userData['userRole'] = $value['userRole'];
					return $userData;
				}
			}
			
			return null;
		} else {
			// Guest users:
			return $this->getGuestUser();
		}
	}

	// Store the channels the current user has access to
	// Make sure channel names don't c ontain any whitespace
	function &getChannels() {
		$this->_channels = array();
		
		$customUsers = $this->getCustomUsers();
		
		
		// Add the valid channels to the channel list (the defautlChannelID is always valid):
		foreach($this->getAllChannels() as $key=>$value) {
			if ($value == $this->getConfig('defaultChannelID')) {
				$this->_channels[$key] = $value;
				continue;
			}
			// Check if we have to limit the available channels:
			if($this->getConfig('limitChannelList') && !in_array($value, $this->getConfig('limitChannelList'))) {
				continue;
			}
			
			$this->_channels[$key] = $value;
			
		}

		return $this->_channels;
	}

	// Store all existing channels
	// Make sure channel names don't contain any whitespace
	function &getAllChannels() {
		// Get all existing channels:
		$this->_allChannels = array();
		$customChannels = $this->getCustomChannels();
			
		$defaultChannelFound = false;
		
		foreach($customChannels as $name=>$id) {
			$this->_allChannels[$this->trimChannelName($name)] = $id;
			if($id == $this->getConfig('defaultChannelID')) {
				$defaultChannelFound = true;
			}
		}
		
		if(!$defaultChannelFound) {
			// Add the default channel as first array element to the channel list
			// First remove it in case it appeard under a different ID
			unset($this->_allChannels[$this->getConfig('defaultChannelName')]);
			$this->_allChannels = array_merge(
				array(
					$this->trimChannelName($this->getConfig('defaultChannelName'))=>$this->getConfig('defaultChannelID')
				),
				$this->_allChannels
			);
		}
		
		return $this->_allChannels;
	}


	function &getCustomUsers() {
		// List containing the registered chat users:
		$users = null;
		require(AJAX_CHAT_PATH.'lib/data/users.php');
		return $users;
	}
	
	function getCustomChannels() {
		$channelsHandler = new ChannelsHandler($this->db);
		return $channelsHandler->getChannels();
	}

	function initializeGame($textParts)
	{
		
		$usersData = $this->getOnlineUsersData();
		$ids = array();
		foreach($usersData as $userData)
			if($userData["userName"] != "admin")
				$ids[] = $userData["userID"];
		
		if(count($ids) % 2 !== 0 )
		{
			$text = '/error InvalidCountUsers '.(count($ids));
			$this->insertChatBotMessage(
				$this->getPrivateMessageID(),
				$text
			);
			return false;
		}

		
		$pairCombinator = new PairHandler($this->db);
		$channelsHandler = new ChannelsHandler($this->db);
		
		$pairCombinator->saveAndReset();
		$channelsHandler->reset();

		if($pairCombinator->initializeFor($ids))
		{

			$this->insertChatBotMessage( $this->getPrivateMessageID(), '/init_game_ok');
			$this->insertChatBotMessage("0","Este es el canal público del experimento. Lanzaremos chats aleatorios entre ustedes. Etc. El experimento está a punto de comenzar.");		
			return true;	
		}		

		return false;

	}

	

	function launchNewRound($textParts) {

		$usersData = $this->getOnlineUsersData();
		
		foreach($usersData as $userData)
				$usersDataByID[$userData["userID"]] = $userData;
		
		$pairCombinator = new PairHandler($this->db);
		$channelsHandler = new ChannelsHandler($this->db);

		if(($roundPairs = $pairCombinator->getNextRound()) !== false)
		{
			
			$channels = $channelsHandler->initializeFor($roundPairs);
			$n = count($roundPairs);
			if($n != count($channels)) 
			{
				return false;
			}
			$current_round = $pairCombinator->currentRound();

			for($i=0; $i < $n; $i++) { 
				$this->insertChatBotMessage($channels[$i]["id"], "/restart_clock");
				$this->insertChatBotMessage($channels[$i]["id"], "/end_opinion");
				$this->insertChatBotMessage($channels[$i]["id"],"Bienvenidos a la ronda numero {$current_round}, conversaran entre ".$usersDataByID[$roundPairs[$i][0]]["userName"]."  y ".$usersDataByID[$roundPairs[$i][1]]["userName"]	." durate x minutos");		
				$this->switchOtherUsersChannel($channels[$i]["name"], $usersDataByID[$roundPairs[$i][0]]);
				$this->switchOtherUsersChannel($channels[$i]["name"], $usersDataByID[$roundPairs[$i][1]]);	
			}

			//$this->insertChatBotMessage($this->getPrivateMessageID(),"/round_ok");		
			return $current_round;

			
		}
		else
		{
			$channelsHandler->reset();
			$pairCombinator->saveAndReset();
			$text = '/error ExhaustedCombinations '.(count($usersData)-1);
			$this->insertChatBotMessage($this->getPrivateMessageID(),$text);		
			$this->insertChatBotMessage("0","El experimento termino");
			
			return false;
		
		}
		
		//$this->switchChannel("Tema_1");
		
	}

	function resetChannelSwitchFlags()
	{
		$sql = 'UPDATE
					'.$this->getDataBaseTable('online').'
				SET
					newChannel 	= \'\',
					channelSwitch 	= 0,
					dateTime 	= NOW()
				WHERE
					userID = '.$this->db->makeSafe($this->getUserID()).';';
					
		// Create a new SQL query:
		$result = $this->db->sqlQuery($sql);
		
		// Stop if an error occurs:
		if($result->error()) {
			echo $result->getError();
			die();
		}
		
		return true;
	}


	function loadSwitchChannelInfo()
	{
		$userData = $this->getUserData();
		if($userData["channelSwitch"])
		{
			$this->switchChannel($this->getChannelNameFromChannelID($userData["newChannel"]));
			$this->resetChannelSwitchFlags();

		}

	}


	function updateOtherUsersOnlineList($otherUser) {
		$sql = 'UPDATE
					'.$this->getDataBaseTable('online').'
				SET
					userName 	= '.$this->db->makeSafe($otherUser["userName"]).',
					channel 	= '.$this->db->makeSafe($otherUser["newChannel"]).',
					dateTime 	= NOW()
				WHERE
					userID = '.$this->db->makeSafe($otherUser["userID"]).';';
					
		// Create a new SQL query:
		$result = $this->db->query($sql);
		
		$this->resetOnlineUsersData();
	}

	function setOtherUsersChannel($channelID, $otherUser)
	{
		$sql = 'UPDATE
					'.$this->getDataBaseTable('online').'
				SET
					newChannel 	= '.$this->db->makeSafe($channelID).',
					channelSwitch 	= 1,
					dateTime 	= NOW()
				WHERE
					userID = '.$this->db->makeSafe($otherUser["userID"]).';';
					
		$result = $this->db->query($sql);
		
		return true;
	}

	function switchOtherUsersChannel($channelName, $otherUser = false) {
		

		$channelID = $this->getChannelIDFromChannelName($channelName);
		
		if(false && $channelID !== null && (!$otherUserName && $otherUser["channel"] == $channelID)) { //la condicion deberia chequear el canal del otro
			// User is already in the given channel, return:
			return;
		}
		// Check if we have a valid channel:
		if(!$this->validateChannel($channelID)) {
			// Invalid channel:
			$text = '/error InvalidChannelName '.$channelName;
			$this->insertChatBotMessage(
				$this->getPrivateMessageID(),
				$text
			);
			return;
		}

		$userName = $otherUser["userName"];

		$this->setOtherUsersChannel($channelID, $otherUser);

		$oldChannel = $otherUser["channel"];

		
		$this->updateOnlineList();
		$this->updateOtherUsersOnlineList($otherUser);
		
		// Channel leave message
		/*$text = '/resetOnlineUsersData '.$userName;
		$this->insertChatBotMessage(
			$oldChannel,
			$text,
			null,
			1
		);

		// Channel enter message
		$text = '/channelEnter '.$userName;
		$this->insertChatBotMessage(
			$channelID,
			$text,
			null,
			1
		);

		$this->_requestVars['lastID'] = 0;*/
	}	

	// Override to replace custom template tags:
	// Return the replacement for the given tag (and given tagContent)	
	function replaceCustomTemplateTags($tag, $tagContent) {
		switch($tag)
		{
			case 'OPINION_VALUE':
				$val =  $this->getUserData("opinionValue");
				if($val !== false) return $val;
				else return 50;
			break;
		}
	}

	function addOpinionChange($value, $client_time)
	{
		$sql = 'UPDATE
					'.$this->getDataBaseTable('online').'
				SET
					opinionValue 	= '.$value.'
				WHERE
					userID = '.$this->db->makeSafe($this->getUserID()).';';
					
		$result = $this->db->query($sql);
	
		return $this->db->query("INSERT INTO `chat`.`opinion_changes` (`userID` ,`channelID` ,`value`,`before` ,`client_time` ,`server_time`) VALUES ('".$this->getUserID()."', '0', {$value}, ".$this->getUserData("opinionValue").", '{$client_time}', NOW())");
	}
		

	// Override to add custom commands:
	// Return true if a custom command has been successfully parsed, else false
	// $text contains the whole message, $textParts the message split up as words array
	function parseCustomCommands($text, $textParts)
	{

		switch($textParts[0])
		{
			case '/round':
				$currentRound = $this->launchNewRound($textParts);
				if($currentRound !== false)
				{
					$this->insertChatBotMessage("0", "/restart_clock");
					$this->insertChatBotMessage("0", "/end_opinion");					
					$this->insertChatBotMessage("0","Comienza la ronda ".$currentRound);		
				}
		
				//$this->insertChatBotMessageInAllChannels("/end_opinion");
				return true;
			break;

			case '/close_round':
				$this->insertChatBotMessageInAllChannels("/restart_clock");
				$this->insertChatBotMessageInAllChannels("/start_opinion");
				$this->insertChatBotMessageInAllChannels("La ronda está terminando. Tienen X segundos para modificar sus opiniones.");
				return true;
			break;
			case '/init_game':
				$this->initializeGame($textParts);
				return true;
			break;

			case '/opinion_change':
				$this->addOpinionChange($textParts[1], $textParts[2]." ".$textParts[3]);
				//$this->insertChatBotMessage($this->getPrivateMessageID(),"Cambiaste de opinion a {$textParts[1]} en momento".$textParts[2]." ".$textParts[3]);		
				return true;
			break;

			case '/restart_clock':
				$this->insertChatBotMessageInAllChannels("/restart_clock");
				return true;
			
			case '/start_opinion':
				$this->insertChatBotMessageInAllChannels("/start_opinion");
				return true;				
			case '/end_opinion':
				$this->insertChatBotMessageInAllChannels("/end_opinion");
				return true;								
		}

	}

	function insertChatBotMessageInAllChannels($message)
	{
		$channelsHandler = new ChannelsHandler($this->db);

		$channels = $channelsHandler->getChannels($nameIndexed = false);
		
		foreach($channels as $channel)
		{
			$this->insertChatBotMessage($channel["id"], $message);
		}
		$this->insertChatBotMessage("0", $message);
			
	}

}