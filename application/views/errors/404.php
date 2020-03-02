<!DOCTYPE html>
<html>
    <head>
        <?php $this->load->view("template/header", ["title" => "Senschema | Página não encontrada"]); ?>
    </head>
    <body>
        <!--Conteúdo da página-->
        <div class="container mt-lg-5" style="padding: 7%">
            <div class="text-center">
                <img src="<?= base_url("assets") ?>/img/lost.png" style="max-width: 25%" />
                <div class="form-group">
                    <h3>Ops, parece que você está perdido aqui, amigo!</h3>
                </div>
                <p>Clique no botão abaixo para retornar a homepage do sistema</p>
            </div>
            <div class="text-center mt-60">
                <a class="btn btn-info form-control form-group" href="<?= base_url(); ?>">
                    <i class="fa fa-home"></i>&nbsp;&nbsp;Homepage
                </a>
            </div>
        </div>
    </body>
    <!-- Importar scripts no fim de pagina -->
    <?php $this->load->view("template/footer"); ?>
</html>