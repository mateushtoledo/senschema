var courseId = "";
var courseTitle = "";
var courseStatus = "";

/**
 * Adiciona um campo para pré requisito no formulário.
 */
function addPreRequirement() {
    let locale = $("#create-pre-requirements");
    
    let html = "<div class=\"mb-10\">";
    html += "<div class=\"input-group\"><input type=\"text\" name=\"requirement[]\" required placeholder=\"Informe um pré requisito\" class=\"common-input form-control\" />";
    html += "<button type='button' onclick=\"removeField(this);\" class=\"btn primary input-group-append\"><i class='fa fa-trash'></i></button></div>";
    html += "</div>";
    
    $(locale).append(html);
}

/**
 * Remove a DIV que contém o campo referente.
 * 
 * @param {type} field Campo que deve ser removido.
 */
function removeField(field) {
    $(field).parent().remove();
}

/**
 * Abre as opções de gerenciamento do curso.
 * 
 * @param {string} id Id do curso.
 * @param {string} title Título do curso.
 * @param {int} status Ativo (1) ou bloqueado (0).
 */
function manageCourse(id, title, status) {
    // Salvar nas variáveis globais
    courseId = id;
    courseTitle = title;
    courseStatus = status;
    
    // Setar dados na tela de gerenciamento
    $("#course-title").text('Opções para o curso "' + courseTitle + '"');
    
    // Exibir ícone de liberar ou bloquear curso
    if (courseStatus === "ACTIVE") {
        $("#action-unlock-course").addClass("hidden-content");
        $("#action-lock-course").removeClass("hidden-content");
    } else {
        $("#action-unlock-course").removeClass("hidden-content");
        $("#action-lock-course").addClass("hidden-content");
    }
    
    // Ir para tela de gerenciamento do curso
    changeContent("manage-course");
}

/**
 * Redireciona para a página de gerenciamento das aulas do curso.
 */
function manageLeassons() {
    window.location.assign("gerenciar-aulas?course=" + courseId);
}

/**
 * Leva a página de edição dos dados básicos do curso.
 */
function editCourseData() {
    window.location.assign("editar-curso?course=" + courseId);
}

/**
 * Leva ao controlador para apagar o curso.
 */
function deleteCourse() {
    window.location.assign("remover-curso?course=" + courseId);
}

/**
 * Leva ao controlador para alterar o status do curso
 */
function changeStatus() {
    window.location.assign("alterar-status-curso?course=" + courseId);
}