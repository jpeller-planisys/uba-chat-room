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
	// Make sure channel names don't contain any whitespace
	function &getChannels() {
		if($this->_channels === null) {
			$this->_channels = array();
			
			$customUsers = $this->getCustomUsers();
			
			// Get the channels, the user has access to:
			if($this->getUserRole() == AJAX_CHAT_GUEST) {
				$validChannels = $customUsers[0]['channels'];
			} else {
				//$validChannels = $customUsers[$this->getUserID()]['channels'];
				$validChannels = array(0,1,2);
			}
			
			// Add the valid channels to the channel list (the defaultChannelID is always valid):
			foreach($this->getAllChannels() as $key=>$value) {
				if ($value == $this->getConfig('defaultChannelID')) {
					$this->_channels[$key] = $value;
					continue;
				}
				// Check if we have to limit the available channels:
				if($this->getConfig('limitChannelList') && !in_array($value, $this->getConfig('limitChannelList'))) {
					continue;
				}
				if(in_array($value, $validChannels)) {
					$this->_channels[$key] = $value;
				}
			}
		}
		return $this->_channels;
	}

	// Store all existing channels
	// Make sure channel names don't contain any whitespace
	function &getAllChannels() {
		if($this->_allChannels === null) {
			// Get all existing channels:
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
		// List containing the custom channels:
		$channels = null;
		require(AJAX_CHAT_PATH.'lib/data/channels.php');
		// Channel array structure should be:
		// ChannelName => ChannelID
		return array_flip($channels);
	}

	function initializeGame($textParts)
	{
		$usersData = $this->getOnlineUsersData();
		$ids = array();
		foreach($usersData as $userData)
			if($userData["userName"] != "admin")
				$ids[] = $userData["userID"];
		
		$pairCombinator = new PairHandler($this->db);
		if($res = $pairCombinator->initializeFor($ids))
		{
			$text = '/init_game_ok';
			$this->insertChatBotMessage(
				$this->getPrivateMessageID(),
				$text
			);
			return true;
		} 
		else
		{
			return false;	
		} 

		if(count($usersData) % 2 != 1 )
		{
			$text = '/error InvalidCountUsers '.(count($usersData)-1);
			$this->insertChatBotMessage(
				$this->getPrivateMessageID(),
				$text
			);
		}

	}

	

	function insertParsedMessageRound($textParts) {

		$text = '/round';
		$usersData = $this->getOnlineUsersData();
		
		foreach($usersData as $userData)
				$usersDataByID[$userData["userID"]] = $userData;
		
		$pairCombinator = new PairHandler($this->db);
		if(($round = $pairCombinator->getNextRound()) !== false)
		{
			foreach($round as $pair)
			{
				//$this->createChannel();
				$this->switchOtherUsersChannel("Tema_1", $usersDataByID[$pair[0]]);
				$this->switchOtherUsersChannel("Tema_1", $usersDataByID[$pair[1]]);	
			}	
		}
		else
		{
			$text = '/error ExhaustedCombinations '.(count($usersData)-1);
				$this->insertChatBotMessage(
				$this->getPrivateMessageID(),
				$text
				);		
		
		
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
		$result = $this->db->sqlQuery($sql);
		
		// Stop if an error occurs:
		if($result->error()) {
			echo $result->getError();
			die();
		}
		
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
					
		// Create a new SQL query:
		$result = $this->db->sqlQuery($sql);
		
		// Stop if an error occurs:
		if($result->error()) {
			echo $result->getError();
			die();
		}
		
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
		/*$text = '/channelLeave '.$userName;
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

	// Override to add custom commands:
	// Return true if a custom command has been successfully parsed, else false
	// $text contains the whole message, $textParts the message split up as words array
	function parseCustomCommands($text, $textParts)
	{

		switch($textParts[0])
		{
			case '/round':
				$this->insertParsedMessageRound($textParts);
			break;

			case '/init_game':
				$this->initializeGame($textParts);
				return true;
			break;
				
		}

	}

}