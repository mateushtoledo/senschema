<?php

/**
 * Model that convert Swagger to Jsonschema.
 */
class SchemaGenerator extends CI_Model {

    private $refLocals = ['$ref', 'not', 'oneOf', 'allOf', 'anyOf', 'not'];

    public function __construct() {
        parent::__construct();
        $this->load->model("SwaggerExtractor", "extractor");
    }

    /**
     * Convert a swagger model to Json Schema model.
     * 
     * @param stdClass $modelSwagger Swagger model definition.
     * 
     * @return stdClass Json Schema model.
     */
    public function getModelSchema($modelSwagger) {
        $ref = '$ref';
        $modelSchema = new stdClass();
        $requiredProperties = [];

        if (isset($modelSwagger->required)) {
            $requiredProperties = $modelSwagger->required;
        }

        if (isset($modelSwagger->$ref)) {
            $modelSchema->$ref = $modelSwagger->$ref;
        }

        if (isset($modelSwagger->type)) {
            $modelSchema->type = $modelSwagger->type;
        }

        if (isset($modelSwagger->properties)) {
            $modelSchema->properties = $this->createSchemaProperties($modelSwagger->properties, $requiredProperties);
        }

        if (!empty($requiredProperties)) {
            $modelSchema->required = $modelSwagger->required;
        }

        return $modelSchema;
    }

    /**
     * Returns the final Json Schema code.
     * 
     * @param array(string) $requestBodyModels Models that can be send at the request body.
     * @param array(stdClass) $finalModels List of models involved at the current request body (json schema compatible).
     * 
     * @return stdClass Json schema as a object.
     */
    public function getJsonSchema($requestBodyModels, $finalModels) {
        $scm = '$schema';
        $schemaSkeleton = new stdClass();

        // Define schema
        $schemaSkeleton->$scm = "http://json-schema.org/draft-07/schema#";

        // Create model definitions
        if (count($finalModels) > 0) {
            $schemaSkeleton->definitions = new stdClass();
            foreach ($finalModels as $name => $definition) {
                $modelName = $this->getModelName($name);
                $schemaSkeleton->definitions->$modelName = $definition;
            }
        }

        // Define json schema request body
        $jsonSchema = $this->addPrincipalReference($schemaSkeleton, $requestBodyModels);

        // Replace swagger references to json schema references
        return $this->replaceSwaggerReferences($jsonSchema);
    }
    
    /**
     * Add the reference(s) to the principal schema object(s).
     * 
     * @param stdClass $schemaObj JSON schema object.
     * @param stdClass $models Models extracted from swagger.
     * 
     * @return stdClass Schema with reference to the request body.
     */
    private function addPrincipalReference($schemaObj, $models) {
        $ref = '$ref';
        
        // Define json schema request body
        if ($models->refType === "single") {
            if ($models->body != null) {
                $schema = (array) $schemaObj;
                $requestBody = (array) $models->body;
                $schemaObj = (object) (array_merge($schema, $requestBody));
            } else {
                $schemaObj->$ref = "#/definitions/" . $this->getModelName($models->references[0]);
            }
        } else {
            $refType = $models->refType;
            $schemaObj->$refType = [];
            foreach ($models->references as $reference) {
                $modelRef = new stdClass();
                $refShortcut = "#/definitions/" . $this->getModelName($reference);
                $modelRef->$ref = $refShortcut;
                $schemaObj->$refType[] = $modelRef;
            }
        }
        
        return $schemaObj;
    }

    /**
     * Extract the model name from the model reference.
     * 
     * @param string $modelPath Path to the model (model reference).
     * 
     * @return string Name of the refered model.
     */
    public function getModelName($modelPath) {
        // Explode path
        $path = explode("/", $modelPath);
        // Get the last segment (modelName)
        return $path[sizeof($path) - 1];
    }

    /**
     * Creates a array item schema definition.
     * 
     * @param stdClass $arrayItems Definition of array items.
     * 
     * @return stdClass Array items definition (compatible with json schema).
     */
    private function getArrayDefinitionSchema($arrayItems) {
        $ref = '$ref';
        $arrayItemSchema = new stdClass();

        // This item has a type definition?
        if (isset($arrayItems->type)) {
            $arrayItemSchema->type = $arrayItems->type;
            if ($arrayItemSchema->type != "array") {
                /* if (isset($arrayItems->properties)) {
                  $arrayItemSchema->properties = $this->createSchemaProperties($arrayItems->properties, isset($arrayItems->required) ? $arrayItems->required : []);
                  } */
                return $this->getModelSchema($arrayItems);
            } else {
                $arrayItemSchema->items = $this->getArrayDefinitionSchema($arrayItems->items);
            }
        }

        // This item has a reference to another model?
        if (isset($arrayItems->$ref)) {
            $arrayItemSchema->$ref = "#/definitions/" . $this->getModelName($arrayItems->$ref);
        } else {
            // This item accepts multiple item types?
            $refs = $this->extractor->proccessReferences($arrayItems);
            if (isset($refs->type)) {
                $type = $refs->type;
                $arrayItemSchema->$type = $refs->refs;
            }
        }

        return $arrayItemSchema;
    }

    /**
     * Create a json schema to property, based at the property type.
     * 
     * @param stdClass $propertyDefinition Property definition (at swagger).
     * @param boolean $isRequired This property is required?
     * 
     * @return stdClass Property definition.
     */
    private function getPropertySchema($propertyDefinition, $isRequired) {
        switch ($propertyDefinition->type) {
            case "string":
                return $this->convertToSchemaString($propertyDefinition, $isRequired);
            case "number":
                return $this->convertToSchemaNumber($propertyDefinition);
            case "integer":
                return $this->convertToSchemaInteger($propertyDefinition);
            case "boolean":
                return $this->getBooleanSchema();
            case "array":
                return $this->convertToSchemaArray($propertyDefinition);
            case "object":
                return $this->getModelSchema($propertyDefinition);
        }
    }

    /**
     * Create the schema of all properties of a object.
     * 
     * @param stdClass $modelProperties $(object)->properties content.
     * @param array $requiredProperties List of object required properties.
     * 
     * @return stdClass All properties of object, in json schema notation.
     */
    private function createSchemaProperties($modelProperties, $requiredProperties) {
        $ref = '$ref';
        $properties = new stdClass();
        foreach ($modelProperties as $property => $definition) {
            // This property is a reference to another model?
            if ($property === $ref) {
                $properties->$ref = "#/definitions/" . $this->getModelName($definition);
            } else if (isset($definition->$ref)) {
                $prop = new stdClass();
                $prop->$ref = "#/definitions/" . $this->getModelName($definition->$ref);
                $properties->$property = $prop;
            }
            // This property has a defined type?
            if (isset($definition->type)) {
                $properties->$property = $this->getPropertySchema($definition, array_search($property, $requiredProperties) !== false);
            }
        }
        return $properties;
    }

    /**
     * Converts a swagger string to a json schema string.
     * 
     * @param stdClass $definition String property definition (at swagger).
     * @param boolean $isRequired This property is required?
     * 
     * @return stdClass String property definition at json schema.
     */
    private function convertToSchemaString($definition, $isRequired) {
        $stringDefinition = new stdClass();
        $stringDefinition->type = "string";

        // Validate min length?
        if (isset($definition->minLength)) {
            $stringDefinition->minLength = $definition->minLength;
        }
        // Validate max length?
        if (isset($definition->maxLength)) {
            $stringDefinition->maxLength = $definition->maxLength;
        }
        // Validate with regex?
        if (isset($definition->pattern)) {
            $stringDefinition->pattern = $definition->pattern;
        } elseif (isset($definition->format)) {
            switch ($definition->format) {
                case "date":
                    $stringDefinition->pattern = $this->getRegexPattern($isRequired ? "REQUIRED_DATE" : "OPTIONAL_DATE");
                    break;
                case "date-time":
                    $stringDefinition->pattern = $this->getRegexPattern($isRequired ? "REQUIRED_DATETIME" : "OPTIONAL_DATETIME");
                    break;
                case "email":
                    $stringDefinition->pattern = $this->getRegexPattern($isRequired ? "REQUIRED_EMAIL" : "OPTIONAL_EMAIL");
                    break;
                case "uri":
                    $stringDefinition->pattern = $this->getRegexPattern($isRequired ? "REQUIRED_URI" : "OPTIONAL_URI");
                    break;
                case "ipv4":
                    $stringDefinition->pattern = $this->getRegexPattern($isRequired ? "REQUIRED_IPV4" : "OPTIONAL_IPV4");
                    break;
                case "ipv6":
                    $stringDefinition->pattern = $this->getRegexPattern($isRequired ? "REQUIRED_IPV6" : "OPTIONAL_IPV6");
                    break;
            }
        } elseif ($isRequired) {
            $stringDefinition->pattern = $this->getRegexPattern("REQUIRED_STRING");
        }

        // This attribute can be null?
        if (isset($definition->nullable) && $definition->nullable) {
            $stringDefinition->type = ["null", "string"];
        }

        // Add enums and other generic keywords (if it exists)
        return $this->addGenericKeywords($stringDefinition, $definition);
    }

    /**
     * Converts a swagger number to a json schema number.
     * 
     * @param stdClass $definition Swagger number definition.
     * 
     * @return stdClass Json schema number definition.
     */
    private function convertToSchemaNumber($definition) {
        $numberDefinition = new stdClass();
        $numberDefinition->type = "number";
        return $this->complementNumericProperties($numberDefinition, $definition);
    }

    /**
     * Converts a swagger integer to a json schema integer/number.
     * 
     * @param stdClass $definition Swagger integer definition.
     * 
     * @return Json schema integer/number definition.
     */
    private function convertToSchemaInteger($definition) {
        $integerDefinition = new stdClass();
        $integerDefinition->type = "integer";
        return $this->complementNumericProperties($integerDefinition, $definition);
    }

    /**
     * Creates a swagger boolean definition.
     * 
     * @return stdClass Swagger boolean definition.
     */
    private function getBooleanSchema() {
        $booleanDefinition = new stdClass();
        $booleanDefinition->type = "boolean";
        return $booleanDefinition;
    }

    /**
     * Creates a swagger array definition.
     * 
     * @param stdClass $definition Swagger array definition.
     * 
     * @return stdClass Swagger array definition.
     */
    private function convertToSchemaArray($definition) {
        $arrayDefinition = new stdClass();
        $arrayDefinition->type = "array";

        // Define array items
        if (isset($definition->items)) {
            $arrayDefinition->items = $this->getArrayDefinitionSchema($definition->items);
        }
        return $arrayDefinition;
    }

    /**
     * Increases the details of numeric properties (numbers and integers), extracting additional data from swagger definition.
     * 
     * @param stdClass $numericAttribute Attribute definition, at json schema.
     * @param stdClass $attributeDefinition Attribute definition, at swagger.
     * 
     * @return stdClass Numeric attribute, increased with important validation data.
     */
    private function complementNumericProperties($numericAttribute, $attributeDefinition) {
        // This numeric attribute has a minimum value?
        if (isset($attributeDefinition->minimum)) {
            $numericAttribute->minimum = $attributeDefinition->minimum;
            // This numeric attribute has a exclusive minimum definition?
            if (isset($attributeDefinition->exclusiveMinimum)) {
                $numericAttribute->exclusiveMinimum = $attributeDefinition->exclusiveMinimum;
            }
        }
        // This numeric attribute has a maximum value?
        if (isset($attributeDefinition->maximum)) {
            $numericAttribute->maximum = $attributeDefinition->maximum;
            // This numeric attribute has a exclusive maximum definition?
            if (isset($attributeDefinition->exclusiveMaximum)) {
                $numericAttribute->exclusiveMinimum = $attributeDefinition->exclusiveMaximum;
            }
        }
        // This numeric attribute has a multipleOf definition?
        if (isset($attributeDefinition->multipleOf)) {
            $numericAttribute->multipleOf = $attributeDefinition->multipleOf;
        }
        // Add enums (if it exists)
        return $this->addGenericKeywords($numericAttribute, $attributeDefinition);
    }

    /**
     * Add the generic keywords to the json schema properties.
     * 
     * @param stdClass $swaggerAttribute Attribute definition, in swagger.
     * @param stdClass $jsonSchemaAttribute Attribute definition, in json schema.
     * 
     * @return stdClass The json schema attribute, with data of swagger generic keywords.
     */
    private function addGenericKeywords($swaggerAttribute, $jsonSchemaAttribute) {
        if (isset($jsonSchemaAttribute->enum)) {
            if (isset($swaggerAttribute->pattern)) {
                unset($swaggerAttribute->pattern);
            }
            $swaggerAttribute->enum = $jsonSchemaAttribute->enum;
        }
        return $swaggerAttribute;
    }

    /**
     * Change swagger references to json schema references.
     * 
     * @param stdClass $jsonSchema Json schema as a object.
     * 
     * @return stdClass Json schema with all references fixed.
     */
    private function replaceSwaggerReferences($jsonSchema) {
        // Try to convert to array
        $isObject = is_object($jsonSchema);
        if ($isObject) {
            $jsonSchema = (array) $jsonSchema;
        }

        // If it's a array, loop
        if (is_array($jsonSchema)) {
            foreach ($jsonSchema as $key => $value) {
                if (is_string($key) && in_array($key, $this->refLocals)) {
                    if (is_array($value)) {
                        $fixxedRefs = [];
                        foreach ($value as $reference) {
                            $fixxedRefs[] = $this->fixJsonSchemaReference($reference, true);
                        }
                        $jsonSchema[$key] = $fixxedRefs;
                    } else {
                        $jsonSchema[$key] = $this->fixJsonSchemaReference($value);
                    }
                } elseif (is_object($value) || is_array($value)) {
                    $jsonSchema[$key] = $this->replaceSwaggerReferences($value);
                }
            }
        }

        return $isObject ? (object) $jsonSchema : $jsonSchema;
    }

    /**
     * Check if the received reference is a JSON schema reference. If it isn't, fix it.
     * 
     * @param string|stdClass $refItem Reference to model.
     * 
     * @return string Reference at json schema pattern.
     */
    private function fixJsonSchemaReference($refItem, $returnObject = false) {
        $ref = '$ref';

        if (is_string($refItem)) {
            if (strpos($refItem, "#/definitions/") === FALSE) {
                $refItem = "#/definitions/" . $this->getModelName($refItem);
            }
        } elseif (is_object($refItem) && isset($refItem->$ref)) {
            $reference = $refItem->$ref;
            if (strpos($reference, "#/definitions/") === FALSE) {
                $refItem->$ref = "#/definitions/" . $this->getModelName($reference);
            }
            return $refItem;
        }

        if ($returnObject) {
            $object = new stdClass();
            $object->$ref = $refItem;
            return $object;
        }

        return $refItem;
    }

    /**
     * Creates a regex to validate some relevant string formats of swagger ($def->format).
     * 
     * @param string $pattern Keyword to get the correct pattern.
     * 
     * @return string Regex to validate the property.
     */
    private function getRegexPattern($pattern) {
        // Regex patterns
        $regex = array(
            "REQUIRED_STRING" => "^(.+)$",
            "REQUIRED_DATE" => "^([0-9]{4})(-)(((0)[1-9])|((1)[0-2]))(-)(([1-2][0-9]|[0][1-9])|(3)[0-1])$",
            "OPTIONAL_DATE" => "(^$)|(^([0-9]{4})(-)(((0)[1-9])|((1)[0-2]))(-)(([1-2][0-9]|[0][1-9])|(3)[0-1])$)",
            "REQUIRED_EMAIL" => "^([A-z0-9._%+-]+@[A-z0-9.-]+\\\\.[A-z]{2,4})$",
            "OPTIONAL_EMAIL" => "(^$)|(^[A-z0-9._%+-]+@[A-z0-9.-]+\\\\.[A-z]{2,4}$)",
            "REQUIRED_DATETIME" => "^([0-9]+)-(0[1-9]|1[012])-(0[1-9]|[12][0-9]|3[01])[Tt]([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9]|60)(\\.[0-9]+)?(([Zz])|([\\+|\\-]([01][0-9]|2[0-3]):[0-5][0-9]))$",
            "OPTIONAL_DATETIME" => "(^$)|(^([0-9]+)-(0[1-9]|1[012])-(0[1-9]|[12][0-9]|3[01])[Tt]([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9]|60)(\\.[0-9]+)?(([Zz])|([\\+|\\-]([01][0-9]|2[0-3]):[0-5][0-9]))$)",
            "REQUIRED_URI" => "^([a-z0-9+.-]+):(?://(?:((?:[a-z0-9-._~!$&'()*+,;=:]|%[0-9A-F]{2})*)@)?((?:[a-z0-9-._~!$&'()*+,;=]|%[0-9A-F]{2})*)(?::([0-9]*))?(/(?:[a-z0-9-._~!$&'()*+,;=:@/]|%[0-9A-F]{2})*)?|(/?(?:[a-z0-9-._~!$&'()*+,;=:@]|%[0-9A-F]{2})+(?:[a-z0-9-._~!$&'()*+,;=:@/]|%[0-9A-F]{2})*)?)(?:\\?((?:[a-z0-9-._~!$&'()*+,;=:/?@]|%[0-9A-F]{2})*))?(?:#((?:[a-z0-9-._~!$&'()*+,;=:/?@]|%[0-9A-F]{2})*))?$/i",
            "OPTIONAL_URI" => "(^$)|(^([a-z0-9+.-]+):(?://(?:((?:[a-z0-9-._~!$&'()*+,;=:]|%[0-9A-F]{2})*)@)?((?:[a-z0-9-._~!$&'()*+,;=]|%[0-9A-F]{2})*)(?::([0-9]*))?(/(?:[a-z0-9-._~!$&'()*+,;=:@/]|%[0-9A-F]{2})*)?|(/?(?:[a-z0-9-._~!$&'()*+,;=:@]|%[0-9A-F]{2})+(?:[a-z0-9-._~!$&'()*+,;=:@/]|%[0-9A-F]{2})*)?)(?:\\?((?:[a-z0-9-._~!$&'()*+,;=:/?@]|%[0-9A-F]{2})*))?(?:#((?:[a-z0-9-._~!$&'()*+,;=:/?@]|%[0-9A-F]{2})*))?$/i)",
            "REQUIRED_IPV4" => "^(?:(?:^|\\.)(?:2(?:5[0-5]|[0-4][0-9])|1?[0-9]?[0-9])){4}$",
            "OPTIONAL_IPV4" => "(^$)|(^(?:(?:^|\\.)(?:2(?:5[0-5]|[0-4][0-9])|1?[0-9]?[0-9])){4}$)",
            "REQUIRED_IPV6" => "^(([0-9a-fA-F]{1,4}:){7,7}[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,7}:|([0-9a-fA-F]{1,4}:){1,6}:[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,5}(:[0-9a-fA-F]{1,4}){1,2}|([0-9a-fA-F]{1,4}:){1,4}(:[0-9a-fA-F]{1,4}){1,3}|([0-9a-fA-F]{1,4}:){1,3}(:[0-9a-fA-F]{1,4}){1,4}|([0-9a-fA-F]{1,4}:){1,2}(:[0-9a-fA-F]{1,4}){1,5}|[0-9a-fA-F]{1,4}:((:[0-9a-fA-F]{1,4}){1,6})|:((:[0-9a-fA-F]{1,4}){1,7}|:)|fe80:(:[0-9a-fA-F]{0,4}){0,4}%[0-9a-zA-Z]{1,}|::(ffff(:0{1,4}){0,1}:){0,1}((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])|([0-9a-fA-F]{1,4}:){1,4}:((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9]))$",
            "OPTIONAL_IPV6" => "(^$)|(^(([0-9a-fA-F]{1,4}:){7,7}[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,7}:|([0-9a-fA-F]{1,4}:){1,6}:[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,5}(:[0-9a-fA-F]{1,4}){1,2}|([0-9a-fA-F]{1,4}:){1,4}(:[0-9a-fA-F]{1,4}){1,3}|([0-9a-fA-F]{1,4}:){1,3}(:[0-9a-fA-F]{1,4}){1,4}|([0-9a-fA-F]{1,4}:){1,2}(:[0-9a-fA-F]{1,4}){1,5}|[0-9a-fA-F]{1,4}:((:[0-9a-fA-F]{1,4}){1,6})|:((:[0-9a-fA-F]{1,4}){1,7}|:)|fe80:(:[0-9a-fA-F]{0,4}){0,4}%[0-9a-zA-Z]{1,}|::(ffff(:0{1,4}){0,1}:){0,1}((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])|([0-9a-fA-F]{1,4}:){1,4}:((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9]))$)"
        );

        // Return regex
        return isset($regex[$pattern]) ? $regex[$pattern] : "^(.+)$";
    }

}
