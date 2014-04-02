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
