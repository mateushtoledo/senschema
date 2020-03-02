<!DOCTYPE html>
<html lang="zxx" class="no-js">
    <head>
        <?php $this->load->view("template/header", ["title" => "Senschema | Escolher endpoint da API"]); ?>
    </head>
    <body>
        <section>
            <div class="mt-lg-5 container">
                <div class="text-center">
                    <i class="fa fa-bug fa-4x"></i>
                    <h2>Selecione o endpoint</h2>
                    <p>Será gerado o Json schema que valida o corpo da requisição da API selecionada.</p>
                </div>
                <hr />
                <div>
                    <?php foreach ($endpoints as $endpoint): ?>
                        <a href="<?= base_url("endpoint-schema?file=$file&endpoint=$endpoint->id"); ?>" class="endpoint-link">
                            <div class="alert alert-dark bg-light">
                                <span class="badge badge-<?= strtolower($endpoint->method); ?>">
                                    <?= strtoupper($endpoint->method); ?>
                                </span>&nbsp;&nbsp;&nbsp;
                                <?= $endpoint->path; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                    <?php if(empty($endpoints)): ?>
                    <div class="text-center text-danger mt-lg-4 mb-lg-5">
                        <span>O Swagger informado não possui nenhum endpoint com corpo na requisição!</span>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="text-center">
                    <a class="btn btn-info" href="<?= base_url(); ?>">
                        <i class="fa fa-undo"></i>&nbsp;Voltar para a homepage
                    </a>
                </div>
            </div>
        </section>
        <!-- End footer Area -->
        <?php $this->load->view("template/footer"); ?>
    </body>
</html>