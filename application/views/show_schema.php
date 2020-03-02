<!DOCTYPE html>
<html lang="zxx" class="no-js">
    <head>
        <?php $this->load->view("template/header", ["title" => "Senschema | Copie o JSON Schema gerado"]); ?>
    </head>
    <body>
        <section>
            <div class="mt-lg-5 container">
                <div class="text-center">
                    <i class="fa fa-bug fa-4x"></i>
                    <h2>JSON schema da requisição</h2>
                    <p>Copie e cole o código JSON gerado no interceptor JSON Schema validation.</p>
                </div>
                <hr />
                <textarea class="schema-content form-control" id="notify-here" readonly="true"><?= $schema; ?></textarea>
                <div class="text-center" style="margin-bottom: 40px">
                    <a class="btn btn-info" href="<?= base_url("select-endpoint?file=$file"); ?>">
                        <i class="fa fa-undo"></i>&nbsp;Voltar para os endpoints
                    </a>
                    <button class="btn btn-success" onclick="copyToClipboard();">
                        <i class="fa fa-copy"></i>&nbsp;Copiar Json Schema
                    </button>
                    <a class="btn btn-info" href="<?= base_url(); ?>">
                        <i class="fa fa-undo"></i>&nbsp;Voltar para a homepage
                    </a>
                </div>
            </div>
        </section>
        <!-- End footer Area -->
        <?php $this->load->view("template/footer"); ?>
        <script type="text/javascript">
            function copyToClipboard() {
                // Copy the json schema to clipboard
                $("#notify-here").prop("readonly", false);
                var schema = document.getElementsByClassName("schema-content")[0];
                schema.select();
                document.execCommand("copy");
                $("#notify-here").prop("readonly", true);

                // Notify user
                $("#notify-here").notify(
                    "JSON Schema copiado! Cole no interceptor JSON schema validator.",
                    {position:"top center", className: "success"}
                );
            }
        </script>
    </body>
</html>