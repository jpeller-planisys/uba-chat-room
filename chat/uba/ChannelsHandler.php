<?php
/*
 * @package AJAX_Chat
 * @author Sebastian Tschan
 * @copyright (c) Sebastian Tschan
 * @license Modified MIT License
 * @link https://blueimp.net/ajax/
 */

// List containing the custom channels:
class ChannelsHandler
{
	function __construct()
	{

	}

	static function getChannels()
	{

		$channels = array();

		// Sample channel list:
		$channels[0] = 'Publico';
		$channels[1] = 'Tema_1';
		$channels[2] = 'Tema_2';

		// Channel array structure should be:
		// ChannelName => ChannelID
		return array_flip($channels);
	}

	static function createChannels($ids)
	{

	}

	static function emptyChannels()
	{

	}

}


?>