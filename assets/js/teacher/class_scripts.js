var classId = null;
var classTitle = null;

/**
 * Seta os dados da aula para edição e troca o conteúdo da view.
 * 
 * @param {string} cId ID da aula.
 * @param {string} cTitle Título da aula.
 */
function manageClass(cId, cTitle) {
    // Salvar nas variáveis
    classId = cId;
    classTitle = cTitle;
    
    // Setar nome da aula em alguns títulos
    $("#del-class-title").text("Remover aula \"" + classTitle + "\"");
    $("#upd-class-title").text("Gerenciar aula \"" + classTitle + "\"");
    
    // Setar nome da turma no formulário
    $("#upd-class-name").val(cTitle);
    
    // Setar ID da aula nos formulários de remoção e atualização
    $("#upd-class-id").val(classId);
    $("#del-class-id").val(classId);
    
    // Trocar conteúdo visível da tela
    changeContent("manage-class");
}