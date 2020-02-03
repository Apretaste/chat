$(document).ready(function() {
	// initialize components
	$('.tabs').tabs();
	$('.modal').modal();
	$('select').formSelect();

	// scroll to the bottom if in the chat page
	var isAtChatPage = $('#chat-row').length == 1;
	if(isAtChatPage) scrollToEndOfPage();
});

function chat(id) {
	apretaste.send({
		'command': 'CHAT',
		'data': {'userId': id}
	});
}

function profile(username) {
	apretaste.send({
		'command': 'PERFIL',
		'data': {'username': username}
	});
}

function deleteModalOpen(id, username) {
	// add to id to the modal so the modal knows what to delete
	$('#deleteModal').attr('data-value', id);

	// change the modal's message
	$('#deleteModalUsername').html(username);

	// open the modal
	M.Modal.getInstance($('#deleteModal')).open();
}

function deleteChat() {
	// get id from the modal
	var id = $('#deleteModal').attr('data-value');

	// delete the chat
	apretaste.send({
		'command': 'CHAT BORRAR',
		'data': {'id': id, 'type': 'chat'},
		'redirect': false,
		'callback': {'name':'deleteChatCallback', 'data':id}
	});
}

function searchUsers() {
	// get values from the form
	var username = $('#username').val();
	var province = $('#province').val();
	var gender = $('#gender').val();
	var min_age = $('#min_age').val() * 1;
	var max_age = $('#max_age').val() * 1;
	var religion = $('#religion').val();

	// check the age range is valid
	if(min_age < 0 || max_age > 120 || min_age > max_age) {
		M.toast({html: 'Error en la edad'});
		return false;
	}

	// send request to the backend
	apretaste.send({
		'command': "CHAT USERS",
		'data': {
			'username': username,
			'province': province,
			'gender': gender,
			'min_age': min_age,
			'max_age': max_age,
			'religion': religion
		}
	});
}

function deleteChatCallback(chatId) {
	$('#' + chatId).remove();
	M.toast({html: 'Chat eliminado'});
}

function sendMessage(toId) {
	// get the message to send
	var message = $('#message').val().trim();

	// do now allow short or empty messages
	if (message.length <= 3) {
		M.toast({html: "Mínimo 3 letras"});
		return false;
	}

	// send the message
	apretaste.send({
		'command': "CHAT ESCRIBIR",
		'data': {'id':toId, 'message':message},
		'redirect': false,
		'callback': {'name':'sendMessageCallback', 'data':message}
	});
}

function sendMessageCallback(message) {
	// remove the no chats message
	$('#nochats').remove();

	// add a new chat bubble
	$('.chat').append('<div class="bubble me" id="last">' + message + '<br><small>' + new Date().toLocaleString('es-ES') + '</small></div>');

	// clean the text box
	$('#message').val('');

	// scroll to the end of the page
	scrollToEndOfPage();
}

function scrollToEndOfPage() {
	$("html, body").animate({
		scrollTop: $(document).height()
	}, 1000);
}

function short(username) {
	if (username.length > 9) {
		return username.substring(0, 6) + '...';
	}
	return username;
}