<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Application controller.
 */
class Senschema extends Base_Controller {

    // Construtor
    public function __construct() {
        parent::__construct();
        $this->load->model("SwaggerCleaner", "cleaner");
        $this->load->model("SwaggerUploader", "uploader");
        $this->load->model("SwaggerExtractor", "extractor");
        $this->load->model("ReferenceFinder", "finder");
        $this->load->model("SchemaGenerator", "schema");
    }

    /**
     * Show the system homepage.
     * 
     * STEP 1.
     * 
     * GET base_url()
     */
    public function index() {
        $this->deleteOldSwaggers();
        $this->load->view("home", $this->makeErrorMessage([]));
    }

    public function notFound() {
        $this->load->view("errors/404");
    }

    /**
     * Extract the endpoints with request body from json schema.
     * 
     * STEP 2.
     * 
     * POST base_url()/select-endpoint
     */
    public function extractEndpoints() {
        try {
            // Extract data from swagger
            $swaggerEntity = $this->uploader->readSwagger();
            $cleanEntity = $this->cleaner->cleanSwagger($swaggerEntity);
            try {
                $importantEndpoints = $this->extractor->extractEndpointsWithRequestBody($cleanEntity);
            } catch (Exception $ex) {
                redirect(base_url() . "?error=invalid_json&errorDescription=" . $ex->getMessage());
            }

            // Create custom entity
            $fileData = new stdClass();
            $fileData->swagger = $swaggerEntity;

            // Build a clean endpoint list
            $endpoints = [];
            $endpointId = 1;
            foreach ($importantEndpoints as $endpoint) {
                $endpoint->id = $endpointId;
                $endpoints[] = $endpoint;
                $endpointId++;
            }

            // Persist custom entity
            $fileData->endpoints = $endpoints;
            $filename = $this->persistJson($fileData);

            // Show endpoints to user
            $this->load->view("select_endpoint", array(
                "file" => urlencode($filename),
                "endpoints" => $endpoints
            ));
        } catch (Exception $ex) {
            redirect(base_url() . "?error=invalid_json&errorDescription=" . $ex->getMessage());
        }
    }

    /**
     * Show the endpoints extracted at step 2.
     * 
     * STEP 2.
     * 
     * GET base_url()/select-endpoint
     */
    public function showEndpoints() {
        // Validate json file
        $jsonFile = $this->input->get("file");
        if ($jsonFile == null || trim($jsonFile) == "" || !$this->fileExists($jsonFile)) {
            redirect(base_url() . "?error=invalid_filename");
        }

        // Define page content structure
        $pageData = [];
        $pageData["file"] = urlencode($jsonFile);
        $pageData["endpoints"] = null;

        // Read file to get endpoints
        try {
            $fileContent = file_get_contents($this->getFilePath($jsonFile));
            $jsonContent = json_decode($fileContent);
            $pageData["endpoints"] = $jsonContent->endpoints;
        } catch (Exception $ex) {
            redirect(base_url() . "?error=invalid_json&errorDescription=" . $ex->getMessage());
        }

        // Show endpoints to user
        $this->load->view("select_endpoint", $pageData);
    }

    /**
     * Create the json schema from the selected endpoint.
     * 
     * STEP 3.
     * 
     * GET base_url()/json-schema
     */
    public function createJsonSchema() {
        // Get filename and check if the file exists
        $filename = $this->input->get("file");
        if ($filename == null || trim($filename) == "" || !$this->fileExists($filename)) {
            redirect(base_url() . "?error=invalid_filename");
        }

        // Get endpoint id and validate
        $endpointId = $this->input->get("endpoint");
        if ($endpointId == null || $endpointId < 1) {
            redirect(base_url() . "?error=invalid_endpoint");
        }

        // Read json file
        try {
            // Read json file
            $jsonContent = file_get_contents($this->getFilePath($filename));
            $swaggerEntity = json_decode($jsonContent);

            // Create json schema and load page
            $jsonSchema = $this->createSchemaToEndpoint($swaggerEntity, $endpointId);
            $this->load->view("show_schema", array(
                "file" => $filename,
                "schema" => json_encode($jsonSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            ));
        } catch (Exception $ex) {
            redirect(base_url() . "?error=invalid_filename&errorDescription=" . $ex->getMessage());
        }
    }

    /**
     * Create the json schema to the selected endpoint.
     * 
     * @param stdClass $swaggerEntity Swagger as a object.
     * @param integer $endpointId Endpoint id.
     * 
     * @return stdClass JSON schema as a object.
     */
    private function createSchemaToEndpoint($swaggerEntity, $endpointId) {
        // Find the selected endpoint
        $targetEndpoint = null;
        foreach ($swaggerEntity->endpoints as $endpoint) {
            if ($endpoint->id == $endpointId) {
                $targetEndpoint = $endpoint;
                break;
            }
        }

        // Validate endpoint
        if ($targetEndpoint == null) {
            redirect(base_url() . "?error=invalid_endpoint");
        }
        $this->cleaner->deleteEndpoints($swaggerEntity);

        // Filter important data
        $requestBody = $this->getRequestBody($swaggerEntity->swagger, $targetEndpoint);
        $requestBodyModels = $this->extractor->getRequestBodyModels($requestBody);
        $usedModels = $this->extractor->extractModels($swaggerEntity->swagger, $requestBody);

        // Load model dependencies, recursive
        $allModels = $this->recursiveModelExtract($swaggerEntity->swagger, $usedModels, $usedModels);

        // Convert important models to json schema
        $modelSchemas = [];
        foreach ($allModels as $modelName => $swaggerModel) {
            $modelSchemas[$modelName] = $this->schema->getModelSchema($swaggerModel);
        }

        // Build the final json schema model(s)
        return $this->schema->getJsonSchema($requestBodyModels, $modelSchemas);
    }

    /**
     * Get the request body.
     *
     * @param stdClass $swaggerEntity Swagger as a object.
     * @param stdClass $cleanEndpoint Target endpoint data.
     * 
     * @return Target endpoint request body.
     */
    private function getRequestBody($swaggerEntity, $cleanEndpoint) {
        $rb = $swaggerEntity;
        foreach ($cleanEndpoint->requestBody as $bodySegment) {
            $rb = $rb->$bodySegment;
        }
        return $rb;
    }

    /**
     * Add the error message (if exists) to the page data.
     * 
     * @param array $pageData Page data (key value array).
     * 
     * @return array Page data with a error.
     */
    private function makeErrorMessage($pageData) {
        $error = $this->input->get("error");
        if ($error != null && trim($error) != "") {
            switch ($error) {
                case "invalid_filename":
                    $pageData["errorMessage"] = "O arquivo da sua API é inválido!";
                    break;
                case "invalid_json":
                    $pageData["errorMessage"] = "Por favor, verifique o arquivo enviado. São aceitos apenas arquivos .json, contendo um swagger v3!";
                    break;
                case "invalid_endpoint":
                    $pageData["errorMessage"] = "O endpoint da API não foi encontrado!";
                    break;
            }
        }
        return $pageData;
    }

    /**
     * Load submodels (not known), recursively.
     * 
     * @param stdClass $swagger Swagger.
     * @param array $knownModels Loaded models.
     * @param array $searchIn Search references in these models, and append it to the knownModels list.
     * 
     * @return array All necessary models.
     */
    private function recursiveModelExtract($swagger, $knownModels, $searchIn) {
        // Stop criterion: All references loaded
        $newRefs = $this->finder->findReferences($searchIn);
        if (empty($newRefs)) {
            return $knownModels;
        }

        // Recursive criterion: More models to load
        $newModels = [];
        foreach ($newRefs as $ref) {
            if (!isset($knownModels[$ref])) {
                $newModel = $this->extractor->extractModel($ref, $swagger);
                $newModels[$ref] = $newModel;
                $knownModels[$ref] = $newModel;
            }
        }
        return $this->recursiveModelExtract($swagger, $knownModels, $newModels);
    }

    /**
     * Delete old swagger files (created by system).
     */
    private function deleteOldSwaggers() {
        // Important informations
        $path = $this->config->item("temp_file_storage");
        $files = glob($path . '*.{json}', GLOB_BRACE);
        $pathLen = strlen($path);
        $today = date("Y-m-d");
        
        // Delete all files created before today
        foreach ($files as $filePath) {
            $filename = substr($filePath, $pathLen);
            $fileCreatedAt = substr($filename, 0, 10);
            if ($fileCreatedAt < $today) {
                unlink($filePath);
            }
        }
    }
}
