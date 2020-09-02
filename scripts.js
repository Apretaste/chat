$(document).ready(function () {
	// initialize components
	$('.tabs').tabs();
	$('.modal').modal();
	$('select').formSelect();
	$('.materialboxed').materialbox();

	// scroll to the bottom if in the chat page
	resizeChat();
	scrollToEndOfPage();
});

function resizeChat() {
	if ($('.row').length == 2) {
		$('.chat').height($(window).height() - $($('.row')[0]).outerHeight(true) - $('#messageField').outerHeight(true) - $('.collection.profile').outerHeight(true) - 20);
	} else $('.chat').height($(window).height() - $('#messageField').outerHeight(true) - $('.collection.profile').outerHeight(true) - 10);
}

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
		showToast("La imagen que escogió pesa mucho. Una solución rápida es tomar una captura de pantalla de la imagen, para disminuir el peso sin perder calidad.");
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

function sendMessage() {
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
		'data': {'id': id, 'message': message, 'image': messagePicture},
		'redirect': false,
		'callback': {'name': 'sendMessageCallback', 'data': message}
	});
}

function sendMessageCallback(message) {
	if (messages.length == 0) {
		// Jquery Bug, fixed in 1.9, insertBefore or After deletes the element and inserts nothing
		// $('#messageField').insertBefore("<div class=\"chat\"></div>");
		$('#nochats').remove();
		$('#chat-row > .col').append("<ul class=\"chat\"></ul>");
	}

	var pictureContent = "";
	if (messagePicture != null) {
		pictureContent += '<img src="data:image/jpg;base64,' + messagePicture + '" class="responsive-img materialboxed"/>';
	}

	var newMessage =
		"<li class=\"right\" id=\"last\">\n" +
		"     <div class=\"person-avatar message-avatar circle\"\n" +
		"      face=\"" + myAvatar + "\" color=\"" + myColor + "\" size=\"30\"></div>\n" +
		"     <div class=\"head\">\n" +
		"         <a href=\"#!\" class=\"" + myGender + "\">" + myUsername + "</a>\n" +
		"         <span class=\"date\">" + new Date().toLocaleString('es-ES') + "</span>\n" +
		"     </div>\n" +
		"     <span class=\"text\">" + pictureContent + message + "</span>\n" +
		"</li>"

	$('.chat').append(newMessage);

	$('#message').val('');

	// clean the img if exists
	messagePicture = null;
	$('input:file').val(null);
	$('#messagePictureBox').remove();

	$('.materialboxed').materialbox();

	$('.person-avatar').each(function (i, item) {
		setElementAsAvatar(item)
	});

	// scroll to the end of the page
	scrollToEndOfPage();
}

function scrollToEndOfPage() {
	console.log("to the end!");
	$(".chat").animate({
		scrollTop: $(".chat").height() + 1000
	}, 1000);
}

function short(username) {
	if (username.length > 9) {
		return username.substring(0, 6) + '...';
	}
	return username;
}

$(function () {
	// initialize components
	$('.tabs').tabs();
	$('.modal').modal();
});

var currentUser = null;

function openSearchModal() {
	M.Modal.getInstance($('#searchModal')).open();
}

function deleteModalOpen(id, username) {
	currentUser = id;
	setCurrentUsername(username);
	M.Modal.getInstance($('#deleteModal')).open();
}

function rejectModalOpen(id, username) {
	currentUser = id;
	setCurrentUsername(username);
	M.Modal.getInstance($('#rejectModal')).open();
}

function cancelRequestModalOpen(id, username) {
	currentUser = id;
	setCurrentUsername(username);
	M.Modal.getInstance($('#cancelRequestModal')).open();
}

function blockModalOpen(id, username) {
	currentUser = id;
	setCurrentUsername(username);
	M.Modal.getInstance($('#blockModal')).open();
}

function addFriendModalOpen(id, username) {
	currentUser = id;
	setCurrentUsername(username);
	M.Modal.getInstance($('#addFriendModal')).open();
}

function acceptModalOpen(id, username) {
	currentUser = id;
	setCurrentUsername(username);
	M.Modal.getInstance($('#acceptFriendModal')).open();
}

function searchUser() {
	var username = $('#search').val();
	if (username.length < 4) {
		showToast('Minimo 4 caracteres');
		return;
	} else if (username.length > 16) {
		showToast('Maximo 16 caracteres');
		return;
	}

	apretaste.send({command: 'amigos buscar', data: {username: username}});
}

function addFriend(message) {
	apretaste.send({
		command: 'amigos agregar',
		data: {id: currentUser},
		redirect: false,
		callback: {
			name: 'addFriendCallback',
			data: {id: currentUser, message: message}
		}
	});
}

function addFriendCallback(data) {
	showToast(data.message);
	$('#' + data.id).remove();
}

function deleteFriend() {
	apretaste.send({
		command: 'amigos eliminar',
		data: {id: currentUser},
		redirect: false,
		callback: {
			name: 'showToast',
			data: 'Amigo eliminado'
		}
	});
}

function rejectFriend(message) {
	apretaste.send({
		command: 'amigos rechazar',
		data: {id: currentUser},
		redirect: false,
		callback: {
			name: 'showToast',
			data: message
		}
	});
}

function blockUser() {
	apretaste.send({
		command: 'amigos bloquear',
		data: {id: currentUser},
		redirect: false,
		callback: {
			name: 'showToast',
			data: 'Usuario bloqueado'
		}
	});
}

function openProfile(id) {
	apretaste.send({command: 'perfil', data: {id: id}});
}

function showToast(text) {
	M.toast({html: text});
}

function setCurrentUsername(username) {
	$('.username').html('@' + username);
}