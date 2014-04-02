<?php
/*
 * @package AJAX_Chat
 * @author Sebastian Tschan
 * @copyright (c) Sebastian Tschan
 * @license Modified MIT License
 * @link https://blueimp.net/ajax/
 */

// Include custom libraries and initialization code here

require(AJAX_CHAT_PATH.'uba/PairHandler.php');
require(AJAX_CHAT_PATH.'uba/Log.php');
require(AJAX_CHAT_PATH.'uba/Combinatorics.php');
require(AJAX_CHAT_PATH.'uba/ChannelsHandler.php');

function pre($val, $msg = "")
{
	echo ($msg?"$msg:":"")."<pre>";
	print_r($val);
	echo "</pre>";
}


?>