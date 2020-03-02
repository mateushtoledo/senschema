<!DOCTYPE html>
<html lang="zxx" class="no-js">
    <head>
        <?php $this->load->view("template/header"); ?>
    </head>
    <body>
        <section>
            <div class="container" style="margin-top: 100px">
                <div class="text-center">
                    <i class="fa fa-bug fa-4x"></i>
                    <h2>Swagger 3 para Json schema</h2>
                    <p>Serão exibidos os endpoints com corpo na requisição. A partir do corpo da requisição será criado o validador de esquema.</p>
                </div>
                <hr />
                <?php if (isset($errorMessage)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?= $errorMessage; ?>
                    </div>
                <?php endif; ?>
                <form action="select-endpoint" method="POST" enctype="multipart/form-data">
                    <div class="form-control form-group">
                        <label for="swagger" class="font-weight-bold">Selecione seu swagger, no formato .json:</label>
                        <input type="file" class="form-control form-control-file" name="swagger" id="swagger" required accept=".json" />
                    </div>
                    <div class="text-center form-group">
                        <button type="submit" class="btn btn-info">
                            <i class="fa fa-code"></i>&nbsp;Processar swagger
                        </button>
                    </div>
                </form>
            </div>
        </section>
        <!-- End footer Area -->
        <?php $this->load->view("template/footer"); ?>	
    </body>
</html>