var optionsModalActive = false;
var moved = false;
var activeChat;
var activeMessage;
var activeUsername;
var timer;

$(() => {
    if (typeof messages != "undefined") {
        $('#message').focus();
        activeChat = id;
        activeUsername = username;

        $('.bubble')
            .on("touchstart", event => { runTimer(); activeMessage = event.currentTarget.id; })
            .on("touchmove", event => { clearTimeout(timer); moved = true; })
            .on("touchend", event => { clearTimeout(timer); });

        $('.bubble')
            .on("mousedown", event => { runTimer(); activeMessage = event.currentTarget.id; })
            .on("mouseup", event => {clearTimeout(timer);});
    }
    
    $('.modal').modal();
    $('.openchat')
        .on("touchstart", event => { runTimer(); activeChat = event.currentTarget.id; activeUsername = event.currentTarget.getAttribute('username'); })
        .on("touchmove", event => { clearTimeout(timer); moved = true; })
        .on("touchend", event => { openChat() });

    $('.openchat')
        .on("mousedown", event => { runTimer(); activeChat = event.currentTarget.id; activeUsername = event.currentTarget.getAttribute('username'); })
        .on("mouseup", event => { openChat() });
});

function openChat() {
    if (!optionsModalActive && !moved) apretaste.send({ 'command': 'CHAT', 'data': { 'userId': activeChat } });
    optionsModalActive = false;
    moved = false;
    clearTimeout(timer);
}

function viewProfile() {
    apretaste.send({ 'command': 'PERFIL', 'data': { 'username': activeChat } });
}

function writeModalOpen() {
    optionsModalActive = false;
    M.Modal.getInstance($('#optionsModal')).close();
    M.Modal.getInstance($('#writeMessageModal')).open();
}

function deleteModalOpen() {
    optionsModalActive = false;
    M.Modal.getInstance($('#optionsModal')).close();
    if(typeof messages == "undefined") $('#deleteModal p').html('¿Esta seguro de eliminar su chat con @'+ activeUsername +'?');
    M.Modal.getInstance($('#deleteModal')).open();
}

function resendModalOpen() {
    optionsModalActive = false;
    M.Modal.getInstance($('#optionsModal')).close();
    M.Modal.getInstance($('#resendMessageModal')).open();
}

function resendMessage(){
    var username = $('#usernameToResend').val();
    var message = $('#'+activeMessage+' msg').html()
    if(username.length > 2){
        apretaste.send({
            'command':'CHAT ESCRIBIR',
            'data':{'id': username, 'message': message},
            'redirect': false,
            'callback': {'name':'resendMessageCallback','data':username}
        })
    }
    else showToast("Ingrese un username valido")
    
}

function deleteChat(){
    apretaste.send({
        'command': 'CHAT BORRAR',
        'data':{'id':activeChat, 'type': 'chat'},
        'redirect': false,
        'callback':{'name':'deleteChatCallback','data':activeChat}
    })
}

function deleteMessage(){
    apretaste.send({
        'command': 'CHAT BORRAR',
        'data':{'id':activeMessage, 'type': 'message'},
        'redirect': false,
        'callback':{'name':'deleteMessageCallback','data':activeMessage}
    })
}

function deleteChatCallback(chatId){
    $('#'+chatId).remove();
    showToast('Chat eliminado');
}

function deleteMessageCallback(messageId){
    $('#'+messageId).remove();
    showToast('Mensaje eliminado');
}

function resendMessageCallback(username){
    $('#usernameToResend').val('');
    showToast("Mensaje reenviado a @"+username);
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
            'data': { 'id': activeChat, 'message': message },
            'redirect': true,
            'callback': { 'name': 'sendMessageCallback', 'data': message }
        });
    }
    else showToast("Mensaje vacio");
}

function sendMessageCallback(message) {
    if (typeof messages != "undefined") {
        if (messages.length == 0) {
            $('#nochats').remove();
            $('#messageField').insertBefore("<div class=\"chat\"></div>");
        }

        $('.chat').append(
            "<div class=\"bubble me\" id=\"last\">" +
            "<small>" +
            "    <b>@" + myusername + "</b> - " + new Date(Date.now()).toLocaleString() +
            "</small>" +
            "<br>" +
            message +
            "</div>"
        );
    }
    else{
        if(message.length > 70) message = message.substr(0, 70)+'...';
        $('#'+activeChat+' msg').html(message)
    }
    $('#message').val('')
}

function messageLengthValidate() {
    var message = $('#message').val().trim();
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
