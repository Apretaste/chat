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

	// open conversation
	$(".open-conversation").click(function(){
		apretaste.send({
			command:'CHAT',
			data: {
				id: $(this).attr('data-value')
			}
		});
	});

	// open profile
	$(".open-profile").click(function(e){
		e.stopPropagation();
		openProfile($(this).attr('data-username'));
	});

	// delete conversation
	$(".delete-conversation").click(function(e){
		e.stopPropagation();
		deleteModalOpen($(this).attr('data-id'), $(this).attr('data-username'));
	});
});

function resizeChat() {
	if ($('.row').length == 2) {
		$('.chat').height($(window).height() - $($('.row')[0]).outerHeight(true) - $('#messageField').outerHeight(true) - $('.collection.profile').outerHeight(true) - 20);
	} else $('.chat').height($(window).height() - $('#messageField').outerHeight(true) - $('.collection.profile').outerHeight(true) - 10);
}

function openChat(id) {
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

function onVoiceRecorded(voicePath) {
	var basename = voicePath.split(/[\\/]/).pop()

	// send the message with the file
	apretaste.send({
		command: "CHAT ESCRIBIR",
		data: {id: id, voiceName: basename},
		redirect: false,
		files: [voicePath],
		callback: {'name': 'sendMessageCallback'},
		async: true
	});

	appendVoiceMessage(voicePath)
}

function appendVoiceMessage(voicePath) {
	var newMessage =
		"<li class=\"right\" id=\"last\">" +
		"    <div class=\"person-avatar message-avatar circle\"" +
		"        face=\"" + myAvatar + "\" color=\"" + myColor + "\" size=\"30\"></div>" +
		"    <div class=\"head\">" +
		"        <a href=\"#!\" class=\"" + myGender + "\">@" + myUsername + "</a>" +
		"        <span class=\"date\">" + moment().format('DD/MM/Y hh:mm a') + "</span>" +
		"    </div>" +
		"    <span class=\"text\">" +
		"        <audio id=\"audio\" src=\"" + voicePath + "\"" +
		"            preload=\"auto\" controls>" +
		"        </audio>" +
		"    </span>" +
		"    <br>" +
		"    <a class=\"small red-text deleteButton delete-message\" onclick=\"deleteMessage('last')\">" +
		"       <i class=\"fa fa-trash-alt\"></i>" +
		"    </a>" +
		"</li>"


	$('.chat').append(newMessage);

	$('.materialboxed').materialbox();

	$('.person-avatar').each(function (i, item) {
		setElementAsAvatar(item)
	});

	// scroll to the end of the page
	scrollToEndOfPage()
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

	// open the modal
	M.Modal.getInstance(modal).open();

	// add to id to the modal so the modal knows what to delete
	modal.attr('data-value', id);

	// change the modal's message
	$('#deleteModalUsername').html(username);
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

function deleteChatCallback(chatId) {
	apretaste.send({command: 'chat', useCache: false});

	$('#' + chatId).remove();
	M.toast({html: 'Chat eliminado'});
}

function deleteMessage(messageId) {
	// delete the chat
	apretaste.send({
		'command': 'CHAT BORRAR',
		'data': {'id': messageId, 'type': 'message', 'userToId': id},
		'redirect': false,
		'callback': {'name': 'deleteMessageCallback', 'data': messageId}
	});
}

function deleteMessageCallback(id) {
	$('#' + id).remove();
	M.toast({html: 'Mensaje eliminado'});
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

		const options = {
			align: 'right', message: message,
			avatar: myAvatar, color: myColor,
			gender: myGender, username: myUsername,
			imgData: imgSource
		}

		appendMessage(options);

		// send the message with the file
		apretaste.send({
			command: "CHAT ESCRIBIR",
			data: {id: id, message: message, imageName: basename},
			redirect: false,
			files: [messagePicturePath],
			callback: {'name': 'sendMessageCallback'},
			async: true
		});

		return;
	}

	const options = {
		align: 'right', message: message,
		avatar: myAvatar, color: myColor,
		gender: myGender, username: myUsername,
		imgData: imgSource
	}

	appendMessage(options);

	// send the message
	apretaste.send({
		command: "CHAT ESCRIBIR",
		data: {id: id, message: message, image: messagePicture},
		redirect: false,
		callback: {name: 'sendMessageCallback'},
		async: true
	});

	clearMsgBox();
}

function sendMessageCallback(data, images) {
	// clean the img if exists
	messagePicture = null;
	messagePicturePath = null;
	$('input:file').val(null);
	$('#messagePictureBox').remove();

	$('.materialboxed').materialbox();


	var lastMessage = $('#last');
	lastMessage.attr('id', data.id)
	lastMessage.find('.deleteButton').click(
		function () {
			deleteMessage(data.id);
		}
	)

	// scroll to the end of the page
	scrollToEndOfPage();
}

function clearMsgBox() {
	var msgBox = $('#message');

	msgBox.val('');
	msgBox.height(0);
	M.textareaAutoResize(msgBox);
	msgBox.trigger('autoresize');
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

		const options = {
			align: 'left', message: data.message,
			avatar: avatar, color: avatarColor,
			gender: gender, username: username,
			imgData: null
		}

		appendMessage(options);

		return true;
	} else return false;
}

function handleImageMessage(imgPath) {
	if (imgPath == null) return;

	const options = {
		align: 'left', message: currentHandlerData.message,
		avatar: avatar, color: avatarColor,
		gender: gender, username: username,
		imgData: imgPath
	}

	appendMessage(options)
}

function appendMessage(options) {
	var {align, message, avatar, color, gender, username, imgData} = options;

	if (messages.length == 0) {
		// Jquery Bug, fixed in 1.9, insertBefore or After deletes the element and inserts nothing
		// $('#messageField').insertBefore("<div class=\"chat\"></div>");
		$('#nochats').remove();
		$('#chat-row > .col').append("<ul class=\"chat\"></ul>");

		// TODO msg object
		messages.push(1);
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

		if (message != '') {
			pictureContent += '<br>';
		}
	}

	avatar = 'face="' + avatar + '"';
	if (username === myUsername && iAmInfluencer) {
		var serviceImgPath = $('serviceImgPath').attr('data');
		avatar += ' creator_image="' + serviceImgPath + myUsername + '.png" state="gold"'
	}

	var newMessage =
		"<li class=\"" + align + "\" id=\"last\">" +
		"    <div class=\"person-avatar message-avatar circle\"" +
		avatar + " color=\"" + color + "\" size=\"30\"></div>" +
		"    <div class=\"head\">" +
		"        <a href=\"#!\" class=\"" + gender + "\">@" + username + "</a>" +
		"        <span class=\"date\">" + moment().format('DD/MM/Y hh:mm a') + "</span>" +
		"    </div>" +
		"    <span class=\"text\">" + pictureContent + message + "</span>" +
		"    <br>" +
		"    <i class=\"material-icons small red-text deleteButton\" onclick=\"deleteMessage('last')\">" +
		"        delete" +
		"    </i>" +
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

$(function () {
	// initialize components
	$('.tabs').tabs();
	$('.modal').modal();
});

function showToast(text) {
	M.toast({html: text});
}

// filter by service category
function filtrar(category) {
	// select category
	selectedCategory = category;

	// highlight the category
	$('.filter').addClass('hidden');
	$('#' + category).find('.filter').removeClass('hidden');

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

	$('.collection-item.avatar').show().each(function (i, e) {
		// get the caption
		var caption = cleanUpSpecialChars($(e).attr('data-value').toLowerCase());

		// hide if caption does not match
		if (caption.indexOf(text) < 0) {
			$(e).hide();
		}
	})
}

// clean special chars
function cleanUpSpecialChars(str) {
	return str
		.replace(/Á/g, "A").replace(/a/g, "a")
		.replace(/É/g, "E").replace(/é/g, "e")
		.replace(/Í/g, "I").replace(/í/g, "i")
		.replace(/Ó/g, "O").replace(/ó/g, "o")
		.replace(/Ú/g, "U").replace(/ú/g, "u")
		.replace(/Ñ/g, "N").replace(/ñ/g, "n")
		.replace(/[^a-z0-9]/gi, ''); // final clean up
}


function openProfile(username) {
	apretaste.send({command: 'perfil', data: {username: username}});
}

/*
function openProfile(username) {
	apretaste.send({
		'command': 'PERFIL',
		'data': {'username': '@' + username}
	});
}*/


// open search input
function openSearch() {
	$('.filter').removeClass('hide');
	$('#buscar').focus();
}

function closeSearch() {
	$('.filter').addClass('hide');
}