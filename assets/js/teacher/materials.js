/*
 * FUNCIONAMENTO DO MAP:
 * 
 *  1. Cada material tem sua chave.
 *  2. Se o valor associado à chave estiver como 'sending': Sem resposta do controlador, ainda.
 *  3. Se o valor associado à chave estiver como 'error': Ocorreu um erro no envio.
 *  4. Se o valor associado à chave estiver como 'deleted': O material foi removido com sucesso.
 *  5. Se o valor associado à chave estiver como qualquer outro valor: É a mensagem de erro do controlador.
 */
var materialsControl = new Map();
var listToSave = new Array();
var listToUpdate = new Array();

/**
 * Retorna a classe CSS do alerta, de acordo com o status do envio do material.
 * 
 * @param {String} status Status do envio do material.
 * 
 * @returns {String} Classe CSS do alerta, de acordo com seu status.
 */
function getClass(status) {
    // Sucesso: Mensagem em verde
    if (status === "success" || status === "rmv-success") {
        return "alert-success";
    }
    
    // Enviando: Mensagem em Amarelo
    if (status === "sending") {
        return "alert-warning";
    }
    
    // Erro: Mensagem em vermelho
    return "alert-danger";
}

/**
 * Adiciona um campo para pré requisito no formulário.
 */
function addLink() {
    let locale = $("#links-area");
    
    let html = "<div class='col-sm-6 mb-20'><div class=\"input-group\">";
    html += "<input type=\"text\" name=\"links[]\" placeholder=\"Informe um link\" class=\"common-input form-control class-link\" />";
    html += "<button type='button' title='Remover esse campo' onclick=\"$(this).parent().parent().remove();\" class=\"input-group-append\"><i class='fa fa-trash'></i></button></div>";
    html += "</div></div>";
    
    $(locale).append(html);
}

/**
 * Aguarda 3 segundos e atualiza o status dos materiais.
 */
function tryFinish() {
    window.setTimeout(function () {
        finish();
    }, 3000);
}

/**
 * Após realizar todas as etapas dos materiais, executa essa função.
 */
function finish() {
    let html = "";
    let finishedAll = true;
    
    materialsControl.forEach(function (status, material) {
        // Classe CSS do alerta e mensagem
        let cssClass = getClass(status);
        let message = "";
        
        // Montar mensagem de acordo com o material
        switch(material) {
            case "teoric-video":
                if (status === "success") {
                    message = "A vídeo aula teórica foi definida com sucesso!";
                } else if (status === "error") {
                    message = "Falha em definir vídeo aula teórica!";
                } else if (status === "sending") {
                    message = "Definindo vídeo aula teórica....";
                    finishedAll = false;
                } else if (status === "rmv-success") {
                    message = "A vídeo aula teórica foi removida com sucesso";
                } else if (status === "rmv-error") {
                    message = "Falha na remoção da vídeo aula teórica";
                } else {
                    message = "Erro ao definir Vídeo aula teórica: " + status;
                }
                break;
            case "pratic-video":
                if (status === "success") {
                    message = "A vídeo aula prática foi definida com sucesso!";
                } else if (status === "error") {
                    message = "Falha em definir vídeo aula prática!";
                } else if (status === "rmv-success") {
                    message = "A vídeo aula prática foi removida com sucesso";
                } else if (status === "rmv-error") {
                    message = "Falha na remoção da vídeo aula prática";
                } else if (status === "sending") {
                    message = "Definindo vídeo aula prática....";
                    finishedAll = false;
                } else {
                    message = "Erro ao definir Vídeo aula prática: " + status;
                }
                break;
            case "resum":
                if (status === "success") {
                    message = "O resumo foi definido com sucesso!";
                } else if (status === "error") {
                    message = "Falha em definir o resumo da aula!";
                } else if (status === "rmv-success") {
                    message = "O resumo foi removido com sucesso";
                } else if (status === "rmv-error") {
                    message = "Falha na remoção do resumo";
                } else if (status === "sending") {
                    message = "Definindo o resumo da aula....";
                    finishedAll = false;
                } else {
                    message = "Erro ao definir resumo: " + status;
                }
                break;
            case "slide":
                if (status === "success") {
                    message = "Os slides foram definidos com sucesso!";
                } else if (status === "error") {
                    message = "Falha em definir slides da aula!";
                } else if (status === "rmv-success") {
                    message = "As transparências foram removidas com sucesso";
                } else if (status === "rmv-error") {
                    message = "Falha na remoção das transparências";
                } else if (status === "sending") {
                    message = "Definindo os slides da aula....";
                    finishedAll = false;
                } else {
                    message = "Erro ao definir transparências: " + status;
                }
                break;
            case "links":
                if (status === "success") {
                    message = "A lista de links foi definida com sucesso!";
                } else if (status === "error") {
                    message = "Falha em definir lista de links da aula!";
                } else if (status === "rmv-success") {
                    message = "Os links foram removidos com sucesso";
                } else if (status === "rmv-error") {
                    message = "Falha na remoção dos links para materiais externos";
                } else if (status === "sending") {
                    message = "Definindo a lista de links da aula....";
                    finishedAll = false;
                } else {
                    message = "Erro ao definir Links: " + status;
                }
                break;
            case "exercises":
                if (status === "success") {
                    message = "A lista de exercícios foi definida com sucesso!";
                } else if (status === "error") {
                    message = "Falha em definir lista de exercícios da aula!";
                } else if (status === "rmv-success") {
                    message = "Os exercícios foram removidos com sucesso";
                } else if (status === "rmv-error") {
                    message = "Falha na remoção dos exercícios";
                } else if (status === "sending") {
                    message = "Definindo a lista de exercícios da aula....";
                    finishedAll = false;
                } else {
                    message = "Erro ao definir Lista de exercícios: " + status;
                }
                break;
            case "attachment":
                if (status === "success") {
                    message = "O anexo da aula foi definido com sucesso!";
                } else if (status === "error") {
                    message = "Falha em definir anexo da aula!";
                } else if (status === "rmv-success") {
                    message = "O anexo foi removido com sucesso";
                } else if (status === "rmv-error") {
                    message = "Falha na remoção do anexo da aula";
                } else if (status === "sending") {
                    message = "Definindo o anexo da aula....";
                    finishedAll = false;
                } else {
                    message = "Erro ao definir Anexo: " + status;
                }
                break;
            case "annotations":
                if (status === "success") {
                    message = "As Anotações da aula foram definidas com sucesso!";
                } else if (status === "error") {
                    message = "Falha em definir anotações da aula!";
                } else if (status === "rmv-success") {
                    message = "As anotações foram removidas com sucesso";
                } else if (status === "rmv-error") {
                    message = "Falha na remoção das anotações da aula";
                } else if (status === "sending") {
                    message = "Definindo o anotações da aula....";
                    finishedAll = false;
                } else {
                    message = "Erro ao definir Anotações: " + status;
                }
                break;
        }
        
        // Montar HTML pra esse material
        html += "<div class='alert " + cssClass + "'>" + message + "</div>";
    });
    
    // Finalizar HTML da tela de fim do cadastro
    let title = "<div class='mb-30'><h4>" + (finishedAll ? "Todos os materiais foram processados" : "Aguarde, ainda existem materiais sendo enviados") + "</h4><hr/></div>";
    html = title + html;
    $("#finish-content").html(html);
    changeContent("finish-div");
    
    // Se terminou tudo, mostrar botão de concluir
    if (finishedAll) {
        $("#finish-btn").removeClass("hidden-content");
    } else {
        // Senão, esperar 3 segundos e atualizar página, até que tudo esteja concluído
        tryFinish();
    }
}

/**
 * Vai para o próximo material.
 */
function routeNext() {
    // Exibir modal para o usuárioa aguardar
    $("#route-modal").modal("show");
    
    // Travar a tela para não dar pau no Map
    window.setTimeout(function() {
        if (listToSave.length > 0) {
            // Se tiver para salvar, salvar
            changeContent(listToSave.shift());
        } else if (listToUpdate.length > 0) {
            // Se tiver para atualizar, atualizar
            changeContent(listToUpdate.shift());
        } else {
            // Não tem, finalizar
            finish();
        }
        
        // Ocultar modal
        $("#route-modal").modal("hide");
    }, 2000);
}

/**
 * Envia os dados para o controlador da aplicação, via AJAX.
 * 
 * @param {type} data Dados que devem ser enviados para o controlador.
 * @param {string} controlName Nome que identifica o material enviado no Mapa de controle.
 */
function saveWithAjax(data, controlName) {
    // Enviar para o controlador
    $.ajax({
        url : "gerenciar-materiais",
        type: "POST",
        data: data,
        success: function(response) {
            if (response.status) {
                materialsControl.set(controlName, "success");
            } else {
                materialsControl.set(controlName, response.message);
            }
        },
        error: function (response) {
            materialsControl.set(controlName, "error");
            console.error(response);
        }
    });
}

/**
 * Salvar lista de links ao submeter formulário
 */
$("#form-links").on("submit", function () {
    let save = true;
    
    // Checar link a link, se foi preenchido
    $(".class-link").each(function () {
        let link = $(this).val();
        if (link === null || typeof link === "undefined" || link === "") {
            $(this).notify("Informe um link para prosseguir", {className: "error", position: "top left"});
            save = false;
        }
    });
    
    // Posso salvar?
    if (save) {
        // Serializar formulário
        let data = $("#form-links").serialize();
        
        // Salvar no banco
        saveWithAjax(data, "links");
        // Ir para o próximo material
        routeNext();
        $.notify("A lista de links está sendo salva. Prossiga.", {className: 'info', position: 'bottom right'});
    }
    
    return false;
});

// Salvar anotaçoes
$("#editor-btn").on("click", function () {
    // Checar se as anotaçoes nao estao vazias
    let ann = editor.getData();
    if (ann == null || ann == "") {
        return;
    }
    
    // Salvar no banco
    let data = {};
    data.annotations = ann;
    data.material = "annotations";
    data.course = $("#course-id").val();
    data.class = $("#class-id").val();
    saveWithAjax(data, "annotations");
    
    // Ir para o próximo material
    routeNext();
    $.notify("As anotações estão sendo salvas. Prossiga", {className: 'info', position: 'bottom right'});
});

/**
 * Obtém os dados necessários e salva o vídeo teórico da aula.
 * 
 * @param {string} type Vídeo teórico ou prático (pratic-video ou teoric-video)?
 */
function saveVideo(type) {
    let classId = $("#class-id").val();
    let courseId = $("#course-id").val();
    let videoLink = $("#input-" + type).val();
    
    // Checar se o vídeo foi preenchido
    if (videoLink === null || videoLink.trim() === "") {
        $.notify("Informe o link da vídeo aula no youtube!", {className: 'error', position: 'bottom right'});
        return;
    }
    
    // Checar se é um link do YouTube
    if (videoLink.search("youtube.com") === -1) {
        $.notify("São aceitos somente vídeos do Youtube!", {className: 'error', position: 'bottom right'});
        $("#input-" + type).val("");
        return;
    }
    
    // Checar se o link possui um Hash de vídeo (v=19i19a1u181)
    if (videoLink.search("v=") === -1) {
        $.notify("URL de video inválida!", {className: 'error', position: 'bottom right'});
        $("#input-" + type).val("");
        return;
    }
    
    // Informar que a operação está sendo realizada e mudar a tela
    let msg = type === "teoric-video" ? "O link da vídeo aula teórica está sendo salva. Prossiga" : "O link da vídeo aula prática está sendo salva. Prossiga";
    $.notify(msg, {className: 'info', position: 'bottom right'});
    
    // Salvar conteúdo
    let data = {class: classId, course: courseId, link: videoLink, material: type};
    saveWithAjax(data, type);
    
    // Ir para próximo material
    routeNext();
}

/**
 * Checa se o usuário quer adicionar, apagar ou remover algum material.
 */
function computeActions() {
    let valid = false;
    let listToRemove = new Array();
    
    // ver se existem materiais para cadastrar
    $(".to-add").each(function () {
        if ($(this).prop("checked") === true) {
            valid = true;
            listToSave.push($(this).attr("name"));
            materialsControl.set($(this).attr("name"), "sending");
        }
    });
    
    // ver se existem materiais para cadastrar
    $(".to-update").each(function () {
        if ($(this).prop("checked") === true) {
            valid = true;
            listToUpdate.push($(this).attr("name"));
            materialsControl.set($(this).attr("name"), "sending");
        }
    });
    
    // ver se existem materiais para remover
    $(".to-delete").each(function () {
        if ($(this).prop("checked") === true) {
            valid = true;
            listToRemove.push($(this).attr("name"));
            materialsControl.set($(this).attr("name"), "sending");
        }
    });
    
    // Remover os materiais, se necessário
    if (listToRemove.length > 0) {
        let courseId = $("#course-id").val();
        let classId = $("#class-id").val();
        
        $.ajax({
            url: "gerenciar-materiais",
            type: "POST",
            data: {
                course: courseId,
                class: classId,
                targets: listToRemove,
                material: "rmv-multiple"
            },
            success: function(response) {
                let status = response.status ? "rmv-success" : "rmv-error";
                listToRemove.forEach(function (item) {
                    materialsControl.set(item, status);
                });
            },
            error: function(response) {
                console.error(response);
                $.notify("Falha na remoção dos materiais!", {className: 'error', position: 'bottom right'});
            }
        });
    }
    
    // Se o formulário é inválido, mensagem de erro.
    if (!valid) {
        $.notify("Selecione algum material!", {className: 'error', position: 'bottom right'});
        return;
    }
    
    // Ir para os demais materiais
    routeNext();
}

/**
 * Checa se o arquivo da aula se enquadra no tamanho e extensões permitidas.
 * 
 * @param {string} material Tipo de material (resum, attachment...)
 * @param {file} file Arquivo enviado na entrada de arquivos.
 * @param {string} inputId Id da entrada de arquivos.
 * 
 * @returns {Boolean} O arquivo é válido?
 */
function checkFile(material, file, inputId) {
    let input = $("#" + inputId);
    
    // Checar tamanho do arquivo
    if (file.size/1000000 > 11) {
        $(input).notify("O arquivo excede 10 MB!", {classname: "error", position: "top left"});
        $(input).val('');
        return false;
    }
    
    // Obter extensão do arquivo
    let filename = file.name;
    let extension = filename.substr((filename.lastIndexOf('.') + 1)).toLowerCase();
    
    // Resumo e exercícios: .pdf, .doc ou .docx
    if (material === "resum" || material === "exercises") {
        if (!(extension === "pdf" || extension === "doc" || extension === "docx")) {
            $(input).notify("Tipo de arquivo inválido!", {classname: "error", position: "top left"});
            $(input).val('');
            return false;
        }
    } else if (material === "presentation") {
        // Slide: PDF, PPT ou PPTX
        if (!(extension === "pdf" || extension === "ppt" || extension === "pptx")) {
            $(input).notify("Tipo de arquivo inválido!", {classname: "error", position: "top left"});
            $(input).val('');
            return false;
        }
    } /*else if (material === "attachment") {
        // Anexo: ZIP ou RAR
        if (!(extension === "zip" || extension === "rar")) {
            $(input).notify("Tipo de arquivo inválido!", {classname: "error", position: "top left"});
            $(input).val('');
            return false;
        }
    }*/
    
    // Se chegou aqui, OK
    return true;
}

/**
 * Envia um material com AJAX.
 * 
 * @param {string} material Material que deve ser enviado (teoric-video, resum ...)
 * @param {file} file Arquivo que representa o material.
 */
function sendMaterial(material, file) {
    // Criar formulário tipo FormData() e adicionar dados informados pelo usuário
    var form_data = new FormData();
    form_data.append('course', $("#course-id").val());
    form_data.append('class', $("#class-id").val());
    form_data.append('material', material);
    form_data.append('file', file);
    form_data.append('MAX_FILE_SIZE', 11000000);

    // Enviar dados para página que salva a documentação
    $.ajax({
        type: "POST",
        url: "gerenciar-materiais",
        data: form_data,
        cache: false,
        contentType: false,
        processData: false,
        success: function(response) {
            if (response.status) {
                materialsControl.set(material, "success");
            } else {
                materialsControl.set(material, response.message);
            }
        },
        error: function (response) {
            materialsControl.set(material, "error");
            console.error(response);
        }
    });
}

/**
 * Tenta salvar um arquivo da aula.
 * 
 * @param {string} material Qual material deve ser salvo.
 * @param {string} inputFileId ID do campo de entrada de arquivos.
 */
function saveMaterialFile(material, inputFileId) {
    let file = document.getElementById(inputFileId).files[0];
    
    // Checar se o arquivo foi adicionado
    if (file === null || typeof file === 'undefined' || file === "") {
        $("#" + inputFileId).notify("Selecione o arquivo!", {classname: "error", position: "top left"});
    } else if (checkFile(material, file, inputFileId)) {
        sendMaterial(material, file);
        $.notify("O material está sendo salvo. Prossiga.", {className: 'info', position: 'bottom right'});
        routeNext();
    }
}

var editor;
ClassicEditor
    .create(document.querySelector('#editor'), {
        toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote'],
        heading: {
            options: [
                {model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph'},
                {model: 'heading1', view: 'h1', title: 'Heading 1', class: 'ck-heading_heading1'},
                {model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2'}
            ]
        }
    })
    .then( newEditor => {
        editor = newEditor;
    } )
    .catch(error => {
        console.error(error);
    });