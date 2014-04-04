/*
 * @package AJAX_Chat
 * @author Sebastian Tschan
 * @copyright (c) Sebastian Tschan
 * @license Modified MIT License
 * @link https://blueimp.net/ajax/
 */

// Overriding client side functionality:


// Example - Overriding the replaceCustomCommands method:
ajaxChat.replaceCustomCommands = function(text, textParts) {
	switch(textParts[0])
	{
		case '/init_game_ok':
			return this.replaceCommandInitGameOk(textParts);
		case '/round':
			return this.replaceCommandRound(textParts);
		case '/restart_clock':
			ajaxChat.restartChronometer(0);
			//return "restarteado!";
		case '/start_opinion':
			ajaxChat.startOpinion();
			return false;
		break;
		case '/end_opinion':
			ajaxChat.endOpinion();
			return false;
		break;


	}
	
	return text;
}


ajaxChat.replaceCommandRound = function(textParts) {
		//var rollText = this.lang['roll'].replace(/%s/, textParts[1]);
		//rollText = rollText.replace(/%s/, textParts[2]);
		//rollText = rollText.replace(/%s/, textParts[3]);
		return	'<span class="chatBotMessage">'
				+ "Comenzó un nuevo round de chats! Tienen 5 minutos para discutir"
				+ '</span>';		
}

ajaxChat.replaceCommandInitGameOk = function(textParts) {
		//var rollText = this.lang['roll'].replace(/%s/, textParts[1]);
		//rollText = rollText.replace(/%s/, textParts[2]);
		//rollText = rollText.replace(/%s/, textParts[3]);
		return	'<span class="chatBotMessage">'
				+ "Los rounds ya están calculados. Pueden comenzar las rondas!"
				+ '</span>';		
}

ajaxChat.getDatetime = function()
{
	return '2200-10-10 23:00:00';
}

ajaxChat.restartChronometer = function(i)
{
	clearTimeout(this.timeout);
	this.chronometer(i);
}

ajaxChat.chronometer = function (i)
{
	/*var today=new Date();
	var h=today.getHours();
	var m=today.getMinutes();
	var s=today.getSeconds();
	// add a zero in front of numbers<10
	m=this.checkTime(m);
	s=this.checkTime(s);
	*/
	document.getElementById('chronometer').innerHTML = i;

	this.timeout=setTimeout(function(){ajaxChat.chronometer(i+1)},1000);
}

ajaxChat.startOpinion = function ()
{
	$("#opinionBarContainer").css("display", "block");
}

ajaxChat.endOpinion = function ()
{
	$("#opinionBarContainer").css("display", "none");	
}

ajaxChat.checkTime = function (i)
{

	if (i<10)
	  {
	  i="0" + i;
	  }
	return i;
}


// Override to add custom initialization code
	// This method is called on page load
ajaxChat.customInitialize = function() {		
	this.chronometer(0);
}


ajaxChat.customOnNewMessage = function(dateObject, userID, userName, userRole, messageID, messageText, channelID, ip)
{
	console.log(channelID+" "+messageText);
	
	switch(messageText)
	{
		case '/end_opinion':
		case '/start_opinion':
		case '/restart_clock':
			var textParts = messageText.split(' ');	
			this.replaceCustomCommands(messageText, textParts);
		 	return false;
		break;

		default:
			return true;
		break;
	}
	
	return true;
}