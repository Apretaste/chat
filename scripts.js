$(document).ready(function () {
	// initialize components
	$('.tabs').tabs();
	$('.modal').modal();
	$('select').formSelect();
	$('.materialboxed').materialbox();

	// scroll to the bottom if in the chat page
	resizeChat();
	scrollToEndOfPage();
	$(window).resize(resizeChat);
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

function recordVoiceNote() {
	if (typeof apretaste.recordVoice != 'undefined') {
		apretaste.recordVoice('onVoiceRecorded')
	}
}

function onVoiceRecorded(path) {
	console.log(path);
}

var messagePicture = null;
var messagePicturePath = null;

function loadImage() {
	if (typeof apretaste.loadImage != 'undefined') {
		apretaste.loadImage('onImageLoaded')
	} else {
		loadFileToBase64();
	}
}

function onImageLoaded(path) {
	showLoadedImage(path);
	messagePicturePath = path;
}

function sendFile(base64File) {
	if (base64File.length > 2584000) {
		showToast("La imagen que escogió pesa mucho. Una solución rápida es tomar una captura de pantalla de la imagen, para disminuir el peso sin perder calidad.");
		$('input:file').val(null);
		return false;
	}

	messagePicture = base64File;
	var messagePictureSrc = "data:image/jpg;base64," + base64File;
	showLoadedImage(messagePictureSrc)
}

function showLoadedImage(imageSource) {
	if ($('#messagePictureBox').length === 0) {
		$('#messageBox').append(
			'<div id="messagePictureBox">' +
			'<img id="messagePicture" class="responsive-img"/>' +
			'<i class="material-icons red-text" onclick="removePicture()">cancel</i>' +
			'</div>'
		);
	}

	$('#messagePicture').attr('src', imageSource);
}

function removePicture() {
	// clean the img if exists
	messagePicture = null;
	messagePicturePath = null;
	$('input:file').val(null);
	$('#messagePictureBox').remove();
}

function deleteModalOpen(id, username) {
	var modal = $('#deleteModal')
	// add to id to the modal so the modal knows what to delete
	modal.attr('data-value', id);

	// change the modal's message
	$('#deleteModalUsername').html(username);

	// open the modal
	M.Modal.getInstance(modal).open();
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
	if (message.length === 0 && messagePicture == null && messagePicturePath == null) {
		return false;
	}

	var imgSource = messagePicture != null ? messagePicture : messagePicturePath;

	if (messagePicturePath != null) {
		var basename = messagePicturePath.split(/[\\/]/).pop()

		appendMessage(
			'right', message, myAvatar, myColor,
			myGender, myUsername, imgSource
		);

		// send the message with the file
		apretaste.send({
			'command': "CHAT ESCRIBIR",
			'data': {'id': id, 'message': message, 'imageName': basename},
			'redirect': false,
			'files': [messagePicturePath],
			'callback': {'name': 'sendMessageCallback', 'data': message}
		});

		return;
	}

	appendMessage(
		'right', message, myAvatar, myColor,
		myGender, myUsername, imgSource
	);

	// send the message
	apretaste.send({
		'command': "CHAT ESCRIBIR",
		'data': {'id': id, 'message': message, 'image': messagePicture},
		'redirect': false,
		'callback': {'name': 'sendMessageCallback', 'data': message}
	});
}

function sendMessageCallback(message) {

	var msgBox = $('#message');

	msgBox.val('');
	msgBox.height(0);
	M.textareaAutoResize($("#message"));
	msgBox.trigger('autoresize');


	// clean the img if exists
	messagePicture = null;
	messagePicturePath = null;
	$('input:file').val(null);
	$('#messagePictureBox').remove();

	$('.materialboxed').materialbox();

	// scroll to the end of the page
	scrollToEndOfPage();
}

var currentHandlerData;

function chatNewMessageHandler(data) {
	currentHandlerData = data;

	if (data.fromUser == id) {
		if (data.image !== '') {
			apretaste.apiHandler({
				name: 'chat/image',
				data: {'file': data.image},
				callback: 'handleImageMessage',
				isFile: true,
			});

			return true;
		}

		appendMessage(
			'left', data.message, avatar, avatarColor,
			gender, username, null
		)
		return true;
	} else return false;
}

function handleImageMessage(imgPath) {
	if (imgPath == null) return;
	appendMessage(
		'left', currentHandlerData.message, avatar, avatarColor,
		gender, username, imgPath
	)
}

function appendMessage(align, message, avatar, color, gender, username, imgData) {
	if (messages.length == 0) {
		// Jquery Bug, fixed in 1.9, insertBefore or After deletes the element and inserts nothing
		// $('#messageField').insertBefore("<div class=\"chat\"></div>");
		$('#nochats').remove();
		$('#chat-row > .col').append("<ul class=\"chat\"></ul>");
	}

	var pictureContent = "";
	if (imgData != null && imgData) {
		var src = imgData;
		var isFile = imgData.indexOf('file://') !== -1 || (imgData[0] === '/' && imgData.length < 200);

		if (!isFile) {
			src = 'data:image/jpg;base64,' + imgData;
		}

		if (typeof apretaste.showImage != 'undefined' && isFile) {
			pictureContent += '<img src="' + src + '" class="responsive-img" onclick="apretaste.showImage(\'' + src + '\')"/>';
		} else {
			pictureContent += '<img src="' + src + '" class="responsive-img materialboxed"/>';
		}


	}

	avatar = 'face="' + avatar + '"';
	if (username === myUsername && iAmInfluencer) {
		var serviceImgPath = $('serviceImgPath').attr('data');
		avatar += ' creator_image="' + serviceImgPath + myUsername + '.png" state="gold"'
	}

	var newMessage =
		"<li class=\"" + align + "\" id=\"last\">\n" +
		"     <div class=\"person-avatar message-avatar circle\"\n" +
		avatar + " color=\"" + color + "\" size=\"30\"></div>\n" +
		"     <div class=\"head\">\n" +
		"         <a href=\"#!\" class=\"" + gender + "\">@" + username + "</a>\n" +
		"         <span class=\"date\">" + moment().format('DD/MM/Y hh:mm a') + "</span>\n" +
		"     </div>\n" +
		"     <span class=\"text\">" + pictureContent + message + "</span>\n" +
		"</li>"

	$('.chat').append(newMessage);

	$('.materialboxed').materialbox();

	$('.person-avatar').each(function (i, item) {
		setElementAsAvatar(item)
	});

	// scroll to the end of the page
	scrollToEndOfPage();
}

function scrollToEndOfPage() {
	var chat = $(".chat");

	if (chat.length > 0) {
		chat.animate({
			scrollTop: chat[0].scrollHeight
		}, 1000);
	}
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


// filter by service category
function filtrar(category) {
	// select category
	selectedCategory = category;

	// highlight the category
	$('.filter').addClass('hidden');
	$('#'+category).find('.filter').removeClass('hidden');

	// scroll to the filters
	$('html, body').animate({scrollTop: $('#filters').offset().top}, 1000);

	// show msg only on the favorite category
	$('#empty-note').hide();

	$('.user-card').slideDown('fast');
	$('#buscar input').val('').focus();
	return false;

}

// search for a service on the list
function buscar() {
	// get text to search by
	var text = cleanUpSpecialChars($('#buscar').val().toLowerCase());

	$('.user-card-col').show().each(function(i, e) {
		// get the caption
		var caption = cleanUpSpecialChars($(e).attr('data-value').toLowerCase());

		// hide if caption does not match
		if(caption.indexOf(text) < 0) {
			$(e).hide();
		}
	})
}

// clean special chars
function cleanUpSpecialChars(str) {
	return str
		.replace(/Á/g,"A").replace(/a/g,"a")
		.replace(/É/g,"E").replace(/é/g,"e")
		.replace(/Í/g,"I").replace(/í/g,"i")
		.replace(/Ó/g,"O").replace(/ó/g,"o")
		.replace(/Ú/g,"U").replace(/ú/g,"u")
		.replace(/Ñ/g,"N").replace(/ñ/g,"n")
		.replace(/[^a-z0-9]/gi,''); // final clean up
}


function openProfile(username) {
	apretaste.send({
		'command': 'PERFIL',
		'data': {'username': '@' + username}
	});
}


// open search input
function openSearch() {
	$('.filter').removeClass('hide');
	$('#buscar').focus();
}

function closeSearch() {
	$('.filter').addClass('hide');
}