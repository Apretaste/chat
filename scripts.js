"use strict";

var optionsModalActive = false;
var moved = false;
var activeChat;
var activeMessage;
var activeUsername;
var timer;

var colors = {
	'azul': '#99F9FF',
	'verde': '#9ADB05',
	'rojo': '#FF415B',
	'morado': '#58235E',
	'naranja': '#F38200',
	'amarillo': '#FFE600'
};

var avatars = {
	apretin: {caption: "Apretín", gender: 'M'},
	apretina: {caption: "Apretina", gender: 'F'},
	artista: {caption: "Artista", gender: 'M'},
	bandido: {caption: "Bandido", gender: 'M'},
	belleza: {caption: "Belleza", gender: 'F'},
	chica: {caption: "Chica", gender: 'F'},
	coqueta: {caption: "Coqueta", gender: 'F'},
	cresta: {caption: "Cresta", gender: 'M'},
	deportiva: {caption: "Deportiva", gender: 'F'},
	dulce: {caption: "Dulce", gender: 'F'},
	emo: {caption: "Emo", gender: 'M'},
	encapuchado: {caption: "Encapuchado", gender: 'M'},
	extranna: {caption: "Extraña", gender: 'F'},
	fabulosa: {caption: "Fabulosa", gender: 'F'},
	fuerte: {caption: "Fuerte", gender: 'M'},
	ganadero: {caption: "Ganadero", gender: 'M'},
	geek: {caption: "Geek", gender: 'F'},
	genia: {caption: "Genia", gender: 'F'},
	gotica: {caption: "Gótica", gender: 'F'},
	gotico: {caption: "Gótico", gender: 'M'},
	guapo: {caption: "Guapo", gender: 'M'},
	hawaiano: {caption: "Hawaiano", gender: 'M'},
	hippie: {caption: "Hippie", gender: 'M'},
	hombre: {caption: "Hombre", gender: 'M'},
	inconformista: {caption: "Inconformista", gender: 'M'},
	independiente: {caption: "Independiente", gender: 'F'},
	jefe: {caption: "Jefe", gender: 'M'},
	jugadora: {caption: "Jugadora", gender: 'F'},
	mago: {caption: "Mago", gender: 'M'},
	metalero: {caption: "Metalero", gender: 'M'},
	modelo: {caption: "Modelo", gender: 'F'},
	moderna: {caption: "Moderna", gender: 'F'},
	musico: {caption: "Músico", gender: 'M'},
	nerd: {caption: "Nerd", gender: 'M'},
	punk: {caption: "Punk", gender: 'M'},
	punkie: {caption: "Punkie", gender: 'M'},
	rap: {caption: "Rap", gender: 'M'},
	rapear: {caption: "Rapear", gender: 'M'},
	rapero: {caption: "Rapero", gender: 'M'},
	rock: {caption: "Rock", gender: 'M'},
	rockera: {caption: "Rockera", gender: 'F'},
	rubia: {caption: "Rubia", gender: 'F'},
	rudo: {caption: "Rudo", gender: 'M'},
	sencilla: {caption: "Sencilla", gender: 'F'},
	sencillo: {caption: "Sencillo", gender: 'M'},
	sennor: {caption: "Señor", gender: 'M'},
	sennorita: {caption: "Señorita", gender: 'F'},
	sensei: {caption: "Sensei", gender: 'M'},
	surfista: {caption: "Surfista", gender: 'M'},
	tablista: {caption: "Tablista", gender: 'F'},
	vaquera: {caption: "Vaquera", gender: 'F'}
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

function getAvatar(avatar, serviceImgPath) {
	return "background-image: url(" + serviceImgPath + "/" + avatar + ".png);";
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

