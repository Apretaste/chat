var optionsModalActive = false;
var moved = false;
var activeChat;
var activeUsername;
var timer;

$(() => {
    if (typeof messages != "undefined") {
        $('#message').focus();
    }
    $('.modal').modal();
    $('.openchat')
        .on("touchstart", event => { runTimer(); activeChat = event.currentTarget.id; activeUsername = event.currentTarget.getAttribute('username') })
        .on("touchmove", event => { clearTimeout(timer); moved = true; })
        .on("touchend", event => { openChat() });

    $('.openchat')
        .on("mousedown", event => { runTimer(); activeChat = event.currentTarget.id })
        .on("mouseup", event => { openChat() });
});

function openChat() {
    if (!optionsModalActive && !moved) apretaste.send({ 'command': 'CHAT', 'data': { 'userId': activeChat } });
    optionsModalActive = false;
    moved = false;
    clearTimeout(timer);
}

function viewProfile() {
    apretaste.send({ 'command': 'PERFIL', 'data': { 'userId': activeChat } });
}

function writeModalOpen() {
    optionsModalActive = false;
    M.Modal.getInstance($('#optionsModal')).close();
    M.Modal.getInstance($('#writeMessageModal')).open();
}

function runTimer() {
    timer = setTimeout(function () {
        optionsModalActive = true;
        M.Modal.getInstance($('#optionsModal')).open();
    }, 800);
}

function sendMessage() {
    let message = $('#message').val().trim();
    if (message.length > 0) {
        apretaste.send({
            'command': "CHAT ESCRIBIR",
            'data': { 'id': activeChat, 'message': message },
            'redirect': false,
            'callback': { 'name': 'sendMessageCallback', 'data': message }
        })
    }
    else showToast("Mensaje vacio")

}

function sendMessageCallback() {
    console.log("callback!")
}

function messageLengthValidate() {
    let message = $('#message').val().trim();
    if (message.length <= 500) {
        $('.helper-text').html('Restante: ' + (500 - message.length));
    }
    else {
        $('.helper-text').html('Limite excedido');
    }
}

function showToast(text) {
    M.toast({ html: text });
}
