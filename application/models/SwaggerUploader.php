<?php

/**
 * Model to read the uploaded swagger (file.json).
 */
class SwaggerUploader extends CI_Model {
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Read the sent file via http POST.
     * 
     * @return stdClass Swagger as a object.
     */
    public function readSwagger() {
        $swaggerString = file_get_contents($_FILES['swagger']['tmp_name']);
        return json_decode($swaggerString);
    }
}
