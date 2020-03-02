/**
 * Altera a parte do conteúdo exibido nas divs classe zencontent.
 * 
 * @param {string} target Qual div de conteúdo quero ver target="identificador-do-alvo".
 */
function changeContent(target) {
    // Para cada div de conteúdo
    $(".zen-content").each(function () {
        // Exibir se for a desejada
        if ($(this).attr("target") === target) {
            $(this).removeClass("hidden-content");
        } else {
            // Ocultar se for outra
            $(this).addClass("hidden-content");
        }
    });
}

/**
 * Exibe os detalhes dos formulários de contato.
 * 
 * @param {string} id ID do registro.
 * @param {string} name Nome do remetente.
 * @param {string} email E-mail do remetente.
 * @param {string} subject Assunto do contato.
 * @param {string} message Mensagem recebida.
 */
function showContact(id, name, email, subject, message) {
    // Setar dados na página de detalhes
    $("#from").html(name);
    $("#email").html(email);
    $("#subject").html(subject);
    $("#message").html(message);

    // Setar Id do contato, caso queira ignorar ou responder ele
    $("#ignore-target").val(id);
    $("#response-target").val(id);
    $("#response-email").val(email);

    // Exibir detalhes
    changeContent("details");
}

/**
 * Cria um Alert com a mensagem que deve ser dada ao usuario.
 * 
 * @param {string} msgClass Classe CSS da mensagem.
 * @param {string} msgBody Conteudo da mensagem.
 */
function addAlertMessage(msgClass, msgBody) {
    targetDiv = $("#alert-message");
    if (targetDiv !== null && typeof targetDiv !== "undefined") {
        msgHtml = '<div class="alert alert-' + msgClass + ' text-center alert-dismissible show" role="alert">';
        
        // icone e mensagem
        msgHtml += msgClass === "danger" ? "<i class='fa fa-ban'></i>" : (msgClass === "success" ? "<i class='fa fa-check'></i>" : "<i class='fa fa-exclamation-triangle'></i>");
        msgHtml += "&nbsp;" + msgBody;
        
        // Botao de fechar
        msgHtml += '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                '<span aria-hidden="true">&times;</span>' +
                '</button>';
        
        
        msgHtml += '</div>';
        
        $(targetDiv).append(msgHtml);
    }
}