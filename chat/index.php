<?php
/*
 * @package AJAX_Chat
 * @author Sebastian Tschan
 * @copyright (c) Sebastian Tschan
 * @license Modified MIT License
 * @link https://blueimp.net/ajax/
 */

// Suppress errors.
error_reporting(E_ALL ^ E_NOTICE);




// Path to the chat directory:
define('AJAX_CHAT_PATH', dirname($_SERVER['SCRIPT_FILENAME']).'/');

// Include custom libraries and initialization code:
require(AJAX_CHAT_PATH.'lib/custom.php');

// Include Class libraries:
require(AJAX_CHAT_PATH.'lib/classes.php');



function pre($val, $msg = "")
{
	echo ($msg?"$msg:":"")."<pre>";
	print_r($val);
	echo "</pre>";
}
//echo "<pre>";



// Initialize the chat:
$ajaxChat = new CustomAJAXChat(false);

$pairCombinator = new PairHandler2($ajaxChat->db);


 
		$ids = array(1,2,3,4,5,6,7,8);
		$pairCombinator->initializeFor($ids);
		$res = true;
		$rounds = 0;
		while($res !== false)
		{	
			$res = $pairCombinator->getRound();	
			$rounds++;
		}
		echo "para ".count($ids)." tuve ".($rounds-1)." rounds<br>";




//$pairCombinator->reset();
echo "<pre>";






?>