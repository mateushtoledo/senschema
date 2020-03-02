<?php

/**
 * Model that works with object references.
 */
class ReferenceFinder extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->model("SwaggerExtractor", "extractor");
        $this->load->model("SchemaGenerator", "generator");
    }

    /**
     * Navigate at extracted models to find references.
     * 
     * @param array() $models Models extracted from SwaggerExtractor.extractModels.
     * 
     * @return array(string) List of found models.
     */
    public function findReferences($models) {
        $ref = '$ref';
        $newRefs = [];
        foreach ($models as $refLink => $modelDefinition) {
            if (isset($modelDefinition->type)) {
                if ($modelDefinition->type == "object") {
                    $refs = $this->searchReferenceAtObject($modelDefinition);
                    if (!empty($refs)) {
                        $newRefs = array_merge($newRefs, $refs);
                    }
                } elseif ($modelDefinition->type == "array") {
                    $refs = $this->searchReferenceAtArray($modelDefinition);
                    if (!empty($refs)) {
                        $newRefs = array_merge($newRefs, $refs);
                    }
                }
            } elseif (isset($modelDefinition->properties)) {
                $refs = $this->searchReferenceAtObject($modelDefinition);
                if (!empty($refs)) {
                    $newRefs = array_merge($newRefs, $refs);
                }
            } elseif ($refLink === $ref) {
                $newRefs[] = $modelDefinition;
            }
        }

        return $newRefs;
    }

    public function buildReferenceQueue($models) {
        $models = rsort($models);
        foreach ($models as $shortcut => $definition) {
            
        }
    }

    /**
     * Navigate at swagger object field to find references.
     * 
     * @param stdClass $modelDefinition Model definition.
     * 
     * @return array(string) Found references.
     */
    private function searchReferenceAtObject($modelDefinition) {
        $refs = [];
        $ref = '$ref';
        if (isset($modelDefinition->properties)) {
            foreach ($modelDefinition->properties as $prop => $definition) {
                /* if ($prop === "DPS") {
                  echo '<pre>';
                  var_dump($definition);
                  echo '</pre>';die;
                  } */
                if (isset($definition->type)) {
                    if ($definition->type === "object") {
                        $refs = array_merge($refs, $this->searchReferenceAtObject($definition));
                    } elseif ($definition->type === "array") {
                        $refs = array_merge($refs, $this->searchReferenceAtArray($definition));
                    }
                } elseif (isset($definition->$ref)) {
                    $refs[] = $definition->$ref;
                }
            }
        } else {
            foreach ($modelDefinition as $key => $value) {
                if ($key === '$ref') {
                    $refs[] = $value;
                    continue;
                }
            }
        }
        return $refs;
    }

    /**
     * Navigate at array object to find references.
     * 
     * @param stdClass $modelDefinition Model definition.
     * 
     * @return array(string) Found references.
     */
    private function searchReferenceAtArray($modelDefinition) {
        $ref = '$ref';
        $refs = [];
        if (isset($modelDefinition->items)) {
            if (isset($modelDefinition->items->$ref)) {
                $refs[] = $modelDefinition->items->$ref;
            } elseif (isset($modelDefinition->items->properties)) {
                $refs = $this->searchReferenceAtObject($modelDefinition->items);
            } else {
                $refObject = $this->extractor->proccessReferences($modelDefinition->items);
                if (isset($refObject->refs) && !empty($refObject->refs)) {
                    $refs = array_merge($refs, $refObject->refs);
                }
            }
        }
        return $refs;
    }
}
