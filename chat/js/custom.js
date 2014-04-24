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

		case '/open_chatbox':
			ajaxChat.toggleChatbox(true);
			return false;
		break;
		case '/close_chatbox':
			ajaxChat.toggleChatbox(false);
			return false;
		break;

		case '/close_experiment':
			if(this.userRole !== '2' && this.userRole !== '3')  ajaxChat.goToExitScreen();
			return false;
		break;



	}
	
	return text;
}

ajaxChat.goToExitScreen = function()
{
	
		window.location.replace("end.html");
}

ajaxChat.replaceCommandRound = function(textParts) {
		//var rollText = this.lang['roll'].replace(/%s/, textParts[1]);
		//rollText = rollText.replace(/%s/, textParts[2]);
		//rollText = rollText.replace(/%s/, textParts[3]);
		return	'<span class="chatBotMessage">'
				+ this.lang['roundStartMessage']+
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

ajaxChat.toggleChatbox = function (show)
{
	$("#inputFieldContainer").css("display", (show? "block": "none"));
	$("#submitButtonContainer").css("display", (show? "block": "none"));
	
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

	if (i<10) i="0" + i;
	return i;
}


// Override to add custom initialization code
	// This method is called on page load
ajaxChat.customInitialize = function() {		
	this.chronometer(0);
	this.setAudioVolume(0.0);

}


ajaxChat.customOnNewMessage = function(dateObject, userID, userName, userRole, messageID, messageText, channelID, ip)
{
	console.log(channelID+" "+messageText);
	
	switch(messageText)
	{
		case '/close_experiment':
		case '/close_chatbox':
		case '/open_chatbox':
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



ajaxChat.getUserNodeStringItems =  function(encodedUserName, userID, isInline) {
		var menu;
		if(encodedUserName !== this.encodedUserName) {
			menu = '';
			if(this.userRole === '2' || this.userRole === '3') { //admin y moderadores
				menu	+= '<li><a href="javascript:ajaxChat.sendMessageWrapperIfConfirm(\'/kick '
						+ encodedUserName
						+ ' \',\'Está seguro que desea desloguear a este usuario? Las rondas calculadas se corromperán.\');">'
						+ this.lang['userMenuKick']
						+ '</a></li>';
			}
		} 
		else 
		{
			menu 	= '';
			if(this.userRole === '2' || this.userRole === '3') { //admin y moderadores
				menu	+= '<li>---------------------</li>';
				menu	+= '<li>Inicialización</li>';
				menu	+= '<li><a href="javascript:ajaxChat.sendMessageWrapper(\'/init_game\');">1) Calcular rondas de chat</a></li>';
				menu	+= '<li><a href="javascript:ajaxChat.sendMessageWrapper(\'/ask_initial_opinion\');">2) Pedir opinion inicial</a></li>';
				menu	+= '<li>---------------------</li>';
				menu	+= '<li>Rondas</li>';
				menu	+= '<li><a href="javascript:ajaxChat.sendMessageWrapper(\'/round\');">3 a) Avanzar un paso</a></li>';
				menu	+= '<li><a href="javascript:ajaxChat.sendMessageWrapper(\'/close_round\');">3 b) Pedir opinion <br />(y avisar fin de ronda)</a></li>';
				menu	+= '<li>---------------------</li>';
				menu	+= '<li>Barra de opinión</li>';
				menu	+= '<li><a href="javascript:ajaxChat.sendMessageWrapper(\'/start_opinion\');">Habilitar</a></li>';
				menu	+= '<li><a href="javascript:ajaxChat.sendMessageWrapper(\'/end_opinion\');">Deshabilitar</a></li>';
				menu	+= '<li>---------------------</li>';
				menu	+= '<li>Chatbox</li>';
				menu	+= '<li><a href="javascript:ajaxChat.sendMessageWrapper(\'/open_chatbox\');">Habilitar</a></li>';
				menu	+= '<li><a href="javascript:ajaxChat.sendMessageWrapper(\'/close_chatbox\');">Deshabilitar</a></li>';
				menu	+= '<li>---------------------</li>';
				menu	+= '<li>Cierre</li>';
				menu	+= '<li><a href="javascript:ajaxChat.sendMessageWrapper(\'/restart_clock\');">Reiniciar clock</a></li>';
				menu	+= '<li><a href="javascript:ajaxChat.sendMessageWrapperIfConfirm(\'/close_experiment\', \'Este paso es irreversible. Está seguro que quiere redirigir a todos los usuarios a la pantalla de finalización?\');">Redirigir a pantalla de finalización</a></li>';
				menu	+= '<li><a href="javascript:ajaxChat.sendMessageWrapperIfConfirm(\'/empty_messages\', \'Vaciará todos los datos generados. Está seguro que desea continuar?\');">Borrar todo</a></li>';

				

			}
		}
		menu += this.getCustomUserMenuItems(encodedUserName, userID);
		return menu;
}

ajaxChat.sendMessageWrapperIfConfirm = function(message, confirmation_message)
{
	if(confirm(confirmation_message))
	{
		return this.sendMessageWrapper(message);
	}
}
