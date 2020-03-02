<?php

/**
 * Model to remove documentation and irrelevant data from swagger entity.
 */
class SwaggerCleaner extends CI_Model {
    
    public function __construct(){
        parent::__construct();
    }
    
    /**
     * Removes the swagger documentation and irrelevant data.
     * 
     * @param stdClass $swagger Swagger as a object.
     * 
     * @return stdClass Swagger object without irrelevant data.
     */
    public function cleanSwagger($swagger) {
        if (isset($swagger->info)) {
            unset($swagger->info);
        }
        if (isset($swagger->servers)) {
            unset($swagger->servers);
        }
        //unset($swagger->components);
        return $swagger;
    }
    
    /**
     * Remove the list of endpoints from swagger.
     * 
     * @param stdClass $swagger Swagger as a object.
     */
    public function deleteEndpoints($swagger) {
        unset($swagger->endpoints);
    }
}