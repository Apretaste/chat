$(document).ready(function () {
	// initialize components
	$('.tabs').tabs();
	$('.modal').modal();
	$('select').formSelect();
	$('.materialboxed').materialbox();

	// scroll to the bottom if in the chat page
	var isAtChatPage = $('#chat-row').length == 1;
	if (isAtChatPage) scrollToEndOfPage();
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

var messagePicture = null;

function sendFile(base64File) {
	if (base64File.length > 2584000) {
		showToast("Imagen demasiado pesada");
		$('input:file').val(null);
		return false;
	}

	messagePicture = base64File;
	var messagePictureSrc = "data:image/jpg;base64," + base64File;

	if ($('#messagePictureBox').length == 0) {
		$('#messageBox').append('<div id="messagePictureBox">' +
			'<img id="messagePicture" class="responsive-img"/>' +
			'<i class="material-icons red-text" onclick="removePicture()">cancel</i>' +
			'</div>');
	}

	$('#messagePicture').attr('src', messagePictureSrc);
}

function removePicture() {
	// clean the img if exists
	messagePicture = null;
	$('input:file').val(null);
	$('#messagePictureBox').remove();
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
		'callback': {'name': 'deleteChatCallback', 'data': id}
	});
}

function searchUsers() {
	// get values from the form
	var username = $('#username').val().replace('@', '');
	var province = $('#province').val();
	var gender = $('#gender').val();
	var min_age = $('#min_age').val() * 1;
	var max_age = $('#max_age').val() * 1;
	var religion = $('#religion').val();

	// check the age range is valid
	if (min_age < 0 || max_age > 120 || min_age > max_age) {
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
	if (message.length <= 3 && messagePicture == null) {
		M.toast({html: "Mínimo 3 letras"});
		return false;
	}

	// send the message
	apretaste.send({
		'command': "CHAT ESCRIBIR",
		'data': {'id': toId, 'message': message, 'image': messagePicture},
		'redirect': false,
		'callback': {'name': 'sendMessageCallback', 'data': message}
	});
}

function sendMessageCallback(message) {
	// remove the no chats message
	$('#nochats').remove();

	// add a new chat bubble
	var messageContent = '<div class="bubble me" id="last">' + message + '<br>';

	if (messagePicture != null) {
		messageContent += '<img src="data:image/jpg;base64,' + messagePicture + '" class="responsive-img materialboxed"/><br>';
	}

	messageContent += '<small>' + new Date().toLocaleString('es-ES') + '</small></div>';

	$('.chat').append(messageContent);

	// clean the text box
	$('#message').val('');

	// clean the img if exists
	messagePicture = null;
	$('input:file').val(null);
	$('#messagePictureBox').remove();

	// scroll to the end of the page
	$('.materialboxed').materialbox();
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