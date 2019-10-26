"use strict";

var optionsModalActive = false;
var moved = false;
var activeChat;
var activeMessage;
var activeUsername;
var timer;

var colors = {
	'Azul': '#99F9FF',
	'Verde': '#9ADB05',
	'Rojo': '#FF415B',
	'Morado': '#58235E',
	'Naranja': '#F38200',
	'Amarillo': '#FFE600'
};

var avatars = {
	'Rockera': 'F',
	'Tablista': 'F',
	'Rapero': 'M',
	'Guapo': 'M',
	'Bandido': 'M',
	'Encapuchado': 'M',
	'Rapear': 'M',
	'Inconformista': 'M',
	'Coqueta': 'F',
	'Punk': 'M',
	'Metalero': 'M',
	'Rudo': 'M',
	'Señor': 'M',
	'Nerd': 'M',
	'Hombre': 'M',
	'Cresta': 'M',
	'Emo': 'M',
	'Fabulosa': 'F',
	'Mago': 'M',
	'Jefe': 'M',
	'Sensei': 'M',
	'Rubia': 'F',
	'Dulce': 'F',
	'Belleza': 'F',
	'Músico': 'M',
	'Rap': 'M',
	'Artista': 'M',
	'Fuerte': 'M',
	'Punkie': 'M',
	'Vaquera': 'F',
	'Modelo': 'F',
	'Independiente': 'F',
	'Extraña': 'F',
	'Hippie': 'M',
	'Chica Emo': 'F',
	'Jugadora': 'F',
	'Sencilla': 'F',
	'Geek': 'F',
	'Deportiva': 'F',
	'Moderna': 'F',
	'Surfista': 'M',
	'Señorita': 'F',
	'Rock': 'F',
	'Genia': 'F',
	'Gótica': 'F',
	'Sencillo': 'M',
	'Hawaiano': 'M',
	'Ganadero': 'M',
	'Gótico': 'M'
};

$(function () {
	$('.tabs').tabs();
	if (typeof messages != "undefined") {
		resizeChat();
		$(window).resize(function () {
			return resizeChat();
		});
		if (messages.length > 0) $('.chat').scrollTop($('.bubble:last-of-type').offset().top);

		$('#message').focus();
		activeChat = id;
		activeUsername = username;
		$('.bubble').on("touchstart", function (event) {
			runTimer();
			activeMessage = event.currentTarget.id;
		}).on("touchmove", function (event) {
			clearTimeout(timer);
			moved = true;
		}).on("touchend", function (event) {
			clearTimeout(timer);
		});
		$('.bubble').on("mousedown", function (event) {
			runTimer();
			activeMessage = event.currentTarget.id;
		}).on("mouseup", function (event) {
			clearTimeout(timer);
		});
	}

	$('.modal').modal();
	$('.openchat').on("touchstart", function (event) {
		activeChat = event.currentTarget.id;
		activeUsername = event.currentTarget.getAttribute('username');
	})
	$('.openchat').on("mousedown", function (event) {
		activeChat = event.currentTarget.id;
		activeUsername = event.currentTarget.getAttribute('username');
	})

	$(document).ready(function () {
		$('.fixed-action-btn').floatingActionButton({
			'direction': 'left',
			'hoverEnabled': false,

		});
	});
});

function resizeChat() {
	$('.chat').height($(window).height() - $('#messageField').outerHeight(true) - 20);
}

function openChat() {
	if (!optionsModalActive && !moved) {
		apretaste.send({
			'command': 'CHAT',
			'data': {
				'userId': activeChat
			}
		});
	}
	optionsModalActive = false;
	moved = false;
	clearTimeout(timer);
}

function getAvatar(avatar, serviceImgPath, size) {
	var index = Object.keys(avatars).indexOf(avatar);
	var fullsize = size * 7;
	var x = index % 7 * size;
	var y = Math.floor(index / 7) * size;
	return "background-image: url(" + serviceImgPath + "/avatars.png);" + "background-size: " + fullsize + "px " + fullsize + "px;" + "background-position: -" + x + "px -" + y + "px;";
}

function viewProfile() {
	apretaste.send({
		'command': 'PERFIL',
		'data': {
			'username': activeChat
		}
	});
}

function writeModalOpen() {
	optionsModalActive = false;
	M.Modal.getInstance($('#optionsModal')).close();
	M.Modal.getInstance($('#writeMessageModal')).open();
}

function deleteModalOpen() {
	if (typeof messages == "undefined") {
		$('#deleteModal p').html('¿Eliminar chat con @' + activeUsername + '?');
	}
	M.Modal.getInstance($('#deleteModal')).open();
}

function resendModalOpen() {
	optionsModalActive = false;
	M.Modal.getInstance($('#optionsModal')).close();
	M.Modal.getInstance($('#resendMessageModal')).open();
}

function resendMessage() {
	var username = $('#usernameToResend').val().trim();
	var message = $('#' + activeMessage + ' msg').html();

	if (username.length > 2) {
		apretaste.send({
			'command': 'CHAT ESCRIBIR',
			'data': {
				'id': username,
				'message': message
			},
			'redirect': false,
			'callback': {
				'name': 'resendMessageCallback',
				'data': username
			}
		});
	} else {
		showToast("Ingrese un username valido");
	}
}

function searchProfile() {
	var username = $('#usernameToSearch').val().trim();

	if (username.length > 2) {
		apretaste.send({
			'command': 'CHAT BUSCAR',
			'data': {
				'username': username
			}
		});
	} else {
		showToast("Ingrese un username valido");
	}
}

function deleteChat() {
	apretaste.send({
		'command': 'CHAT BORRAR',
		'data': {
			'id': activeChat,
			'type': 'chat'
		},
		'redirect': false,
		'callback': {
			'name': 'deleteChatCallback',
			'data': activeChat
		}
	});
}

function deleteMessage() {
	apretaste.send({
		'command': 'CHAT BORRAR',
		'data': {
			'id': activeMessage,
			'type': 'message'
		},
		'redirect': false,
		'callback': {
			'name': 'deleteMessageCallback',
			'data': activeMessage
		}
	});
}

function deleteChatCallback(chatId) {
	$('#' + chatId).remove();
	showToast('Chat eliminado');
}

function deleteMessageCallback(messageId) {
	$('#' + messageId).remove();
	showToast('Mensaje eliminado');
}

function resendMessageCallback(username) {
	$('#usernameToResend').val('');
	showToast("Mensaje reenviado a @" + username);
}

function runTimer() {
	timer = setTimeout(function () {
		optionsModalActive = true;
		M.Modal.getInstance($('#optionsModal')).open();
	}, 800);
}

function sendMessage() {
	var message = $('#message').val().trim();

	if (message.length > 0) {
		apretaste.send({
			'command': "CHAT ESCRIBIR",
			'data': {
				'id': activeChat,
				'message': message
			},
			'redirect': false,
			'callback': {
				'name': 'sendMessageCallback',
				'data': message
			}
		});
	} else {
		showToast("Mensaje vacio");
	}
}

function sendMessageCallback(message) {
	if (typeof messages != "undefined") {
		if (messages.length == 0) {
			// Jquery Bug, fixed in 1.9, insertBefore or After deletes the element and inserts nothing
			// $('#messageField').insertBefore("<div class=\"chat\"></div>");
			$('#nochats').remove();
			$('#chat-row').append("<div class=\"chat\"></div>");
		}

		$('.chat').append("<div class=\"bubble me\" id=\"last\">" + message + "<br>" + "<small>" + new Date().toLocaleString('es-ES') + "</small>" + "</div>");
	} else {
		if (message.length > 70) message = message.substr(0, 70) + '...';
		$('#' + activeChat + ' msg').html(message);
	}

	$('#message').val('');
}

function messageLengthValidate() {
	var message = $('#message').val().trim();

	if (message.length <= 500) {
		$('.helper-text').html('Restante: ' + (500 - message.length));
	} else {
		$('.helper-text').html('Limite excedido');
	}
}

function showToast(text) {
	M.toast({
		html: text
	});
}

function _typeof(obj) {
	if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") {
		_typeof = function _typeof(obj) {
			return typeof obj;
		};
	} else {
		_typeof = function _typeof(obj) {
			return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj;
		};
	}
	return _typeof(obj);
}

if (!Object.keys) {
	Object.keys = function () {
		'use strict';

		var hasOwnProperty = Object.prototype.hasOwnProperty,
			hasDontEnumBug = !{
				toString: null
			}.propertyIsEnumerable('toString'),
			dontEnums = ['toString', 'toLocaleString', 'valueOf', 'hasOwnProperty', 'isPrototypeOf', 'propertyIsEnumerable', 'constructor'],
			dontEnumsLength = dontEnums.length;
		return function (obj) {
			if (_typeof(obj) !== 'object' && (typeof obj !== 'function' || obj === null)) {
				throw new TypeError('Object.keys called on non-object');
			}

			var result = [],
				prop,
				i;

			for (prop in obj) {
				if (hasOwnProperty.call(obj, prop)) {
					result.push(prop);
				}
			}

			if (hasDontEnumBug) {
				for (i = 0; i < dontEnumsLength; i++) {
					if (hasOwnProperty.call(obj, dontEnums[i])) {
						result.push(dontEnums[i]);
					}
				}
			}

			return result;
		};
	}();
}

