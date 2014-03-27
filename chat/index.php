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


//echo "<pre>";

// Initialize the chat:
$ajaxChat = new CustomAJAXChat(false);

$pairCombinator = new PairHandler($ajaxChat->db);

$pairCombinator->reset();

$res = $pairCombinator->generateRoundPairs(array(1, 2, 3, 4, 5, 6));

die();
echo "<pre>";
print_r($res);
echo "algo";
//$res = $pairCombinator->generateRoundPairs(4);
//echo "<pre>";
//print_r($res);



?>