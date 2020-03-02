<?php

/**
 * Model to extract data from swagger object.
 */
class SwaggerExtractor extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Extracts the endpoints with request body. Typically these endpoints are called with http post, put or patch.
     * 
     * @param stdClass $cleanSwagger Swagger without documentation same data.
     * 
     * @return stdClass With endpoints saved at path attribute and requestBody details at requestBody. 
     */
    public function extractEndpointsWithRequestBody($cleanSwagger) {
        $foundEndpoints = [];
        
        if (!isset($cleanSwagger->paths)) {
            throw new Exception("Invalid swagger file");
        }

        foreach ($cleanSwagger->paths as $path => $details) {
            // Add POST request?
            if (isset($details->post) && isset($details->post->requestBody) && $this->acceptJson($details->post->requestBody)) {
                $foundEndpoints[] = $this->createEndpointRoute($path, "post");
            }

            // Add PUT request?
            if (isset($details->put) && isset($details->put->requestBody) && $this->acceptJson($details->put->requestBody)) {
                $foundEndpoints[] = $this->createEndpointRoute($path, "put");
            }

            // Add PATCH request?
            if (isset($details->patch) && isset($details->patch->requestBody) && $this->acceptJson($details->patch->requestBody)) {
                $foundEndpoints[] = $this->createEndpointRoute($path, "patch");
            }
        }
        return $foundEndpoints;
    }

    /**
     * Returns a list of models send at the request body.
     * 
     * @param stdClass $requestBody Request body at swagger.
     * 
     * @return stdClass Definition of reference type (single, all of, any of) and reference list.
     */
    public function getRequestBodyModels($requestBody) {
        $expectedContentType = 'application/json';
        $ref = '$ref';
        $references = null;

        if (isset($requestBody->content) && isset($requestBody->content->$expectedContentType) && isset($requestBody->content->$expectedContentType->schema)) {
            $rbSchema = $requestBody->content->$expectedContentType->schema;
            
            $references = $this->proccessReferences($rbSchema);
            if (!isset($references->refs) && isset($rbSchema->$ref)) {
                $references->refs = [$rbSchema->$ref];
            } else if (isset($rbSchema->type) && $rbSchema->type === "object") {
                $references->body = $rbSchema;
            }
        }

        $requestBodyModels = new stdClass();
        $requestBodyModels->refType = isset($references->type) ? $references->type : "single";
        $requestBodyModels->references = isset($references->refs) ? $references->refs : [];
        $requestBodyModels->body = isset($references->body) ? $references->body : null;
        return $requestBodyModels;
    }

    /**
     * Returns a list of references to models.
     * 
     * @param array() $references List of possible model references.
     * 
     * @return array(string) List of model references.
     */
    private function getRefs($references) {
        $ref = '$ref';
        $refs = [];
        foreach ($references as $reference) {
            if (isset($reference->$ref)) {
                $refs[] = $reference->$ref;
            }
        }
        return $refs;
    }

    /**
     * Extracts the models sent at request body from swagger.
     * 
     * @param stdClass $fullSwagger Swagger as object.
     * @param stdClass $modelReference Endpoints found by SwaggerExtractor.extractEndpointsWithRequestBody.
     * 
     * @return array(stdClass) Found models.
     */
    public function extractModels($fullSwagger, $modelReference) {
        $models = [];

        $refs = $this->extractRecursive($modelReference->content);
        if (!empty($refs)) {
            foreach ($refs as $reference) {
                if (trim($reference) !== "") {
                    $models[$reference] = $this->extractModel($reference, $fullSwagger);
                }
            }
        }

        return $models;
    }

    /**
     * Extract model schema by the model reference.
     * 
     * @param stdClass $fullSwagger Swagger definition (full).
     * @param array(string) $references List of model references, at swagger.
     * 
     * @return array(stdClass) Refered models.
     */
    public function extractModelsByReferences($fullSwagger, $references) {
        $models = [];
        foreach ($references as $ref) {
            $models[] = $this->extractModel($ref, $fullSwagger);
        }
        return $models;
    }

    /**
     * Returns the model reference ("#/components/schemas/ModelName") as a array of strings (["components", "schemas", "ModelName"]).
     * 
     * @param string $modelReference Shortcut to the model.
     * 
     * @return array(string) Path segments to access the model.
     */
    private function explodeReference($modelReference) {
        $kaboom = explode("/", $modelReference);
        $reference = [];

        for ($i = 0; $i < sizeof($kaboom); $i++) {
            if ($kaboom[$i] != "" && $kaboom[$i] != "#") {
                $reference[] = $kaboom[$i];
            }
        }

        return $reference;
    }

    /**
     * Returns the model definition at swagger.
     * 
     * @param string $modelReference Shortcut to the model at swagger ("#/components/schemas/ModelName").
     * 
     * @param stdClass $fullSwagger Swagger as a object.
     * 
     * @return stdClass Model definition.
     */
    public function extractModel($modelReference, $fullSwagger) {
        $kaboom = $this->explodeReference($modelReference);
        $modelData = $fullSwagger;

        for ($i = 0; $i < sizeof($kaboom); $i++) {
            foreach ($modelData as $key => $value) {
                if ($key === $kaboom[$i]) {
                    $modelData = $value;
                    continue;
                }
            }
        }
        return $modelData;
    }

    
    public function proccessReferences($referencePool) {
        $response = new stdClass();
        
        if (isset($referencePool->oneOf)) {
            $response->type = "oneOf";
            $response->refs = $this->getRefs($referencePool->oneOf);
        } elseif (isset($referencePool->allOf)) {
            $response->type = "allOf";
            $response->refs = $this->getRefs($referencePool->allOf);
        } elseif (isset($referencePool->anyOf)) {
            $response->type = "anyOf";
            $response->refs = $this->getRefs($referencePool->anyOf);
        } elseif (isset($referencePool->not)) {
            $response->type = "not";
            $response->refs = $this->getRefs($referencePool->not);
        }
        
        return $response;
    }

    /**
     * Check if it's request body accept content-type application/json.
     * 
     * @param stdClass $requestBody Request body definition.
     * 
     * @return boolean Accept json content?
     */
    private function acceptJson($requestBody) {
        if (isset($requestBody->content)) {
            foreach ($requestBody->content as $contentType => $contentDetails) {
                if (strtolower($contentType) == "application/json") {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Create a object that defines the details of a endpoint.
     * 
     * @param string $path Request path.
     * @param string $httpMetod Request http method.
     * 
     * @return stdClass Endpoint details.
     */
    private function createEndpointRoute($path, $httpMetod) {
        $endpoint = new stdClass();
        $endpoint->path = $path;
        $endpoint->method = $httpMetod;
        $endpoint->requestBody = ["paths", $path, $httpMetod, "requestBody"];
        return $endpoint;
    }

    /**
     * Find model references, recursive.
     * 
     * @param stdClass Swagger data.
     * 
     * @return array(string) List of associated models.
     */
    private function extractRecursive($data) {
        $ref = '$ref';
        if (is_object($data)) {
            $data = (array) $data;
        }

        $refs = [];
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if ($key != "responses" && $key === $ref) {
                    $refs[] = $value;
                } else {
                    $refs = array_merge($refs, $this->extractRecursive($value));
                }
            }
        }
        return $refs;
    }

}
