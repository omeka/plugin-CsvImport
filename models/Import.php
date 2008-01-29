<?php
require_once 'Csv.php';
class Import extends Csv {
    protected $argv;
        
    protected $file;
    
    protected $typeId;
    
    protected $fields;
    
    protected $db;
    
    public function __construct($argv) {        
        // Extract the individual arguments from $argv.
        $this->argv = $argv;
        $this->_extractFromArgv();
        
        // Set the CSV data using the parent class..
        parent::__construct($this->_getFilePath());
        $this->setHeaders();
        $this->setRows();
        
        // Set the database object.
        $this->db = get_db();
                
        // Validate the CSL.
        if (!$this->isValidCsv()) exit("The CSV file appears to be invalid.");
        
        // Do the import.
        $this->_import();
    }
    
    protected function _extractFromArgv() {
        $this->file = $this->_extractFile();
        $this->typeId = $this->_extractTypeId();
        $this->fields = $this->_extractFields();
    }
    
    protected function _extractFile() {
        $argv = $this->argv;
        
        if (!$key = array_search('--file', $argv)) {
            throw new Exception('no file was given');
        }        
        
        // Matches anthing that begins with a hyphen.
        if (preg_match('/^-/', $argv[$key + 1])) {
            throw new Exception('no file was given');
        }
        
        $file = $argv[$key + 1];
        
        return $file;
    }
    
    protected function _extractTypeId() {
        $argv = $this->argv;
        
        if (!$key = array_search('--typeid', $argv)) {
            throw new Exception('the "typeid" argument was not passed');
        }        
        
        // Matches anthing that begins with a hyphen.
        if (preg_match('/^-/', $argv[$key + 1])) {
            throw new Exception('the "typeid" value is unavailable');
        }
        
        $typeId = $argv[$key + 1];
        
        if (!is_numeric($typeId)) {
            throw new Exception('the "typeid" value is not numeric');
        }
        
        return $typeId;
    }
    
    protected function _extractFields() {
        $argv = $this->argv;
        
        if (!$key = array_search('--fields', $argv)) {
            throw new Exception('The "fields" argument was not passed.');
        }        
        
        // Matches anthing that begins with a hyphen.
        if (preg_match('/^-/', $argv[$key + 1])) {
            throw new Exception('The "fields" value is unavailable.');
        }
        
        if (!$fields = unserialize($argv[$key + 1])) {
            throw new Exception('The "fields" value cannot be unserialized.');
        }
        
        return $fields;
    }
    
    protected function _getFilePath() {
        return CSVIMPORT_CSVFILES_DIRECTORY . DIRECTORY_SEPARATOR . $this->file;
    }
    
    // This is where the magic happens.
    protected function _import() {
        
        // Iterate through the CSV rows.
        foreach ($this->rows as $rowKey => $row) {
            
            // dfui = default field, update item
            $dfui = array();
            // mfimt = metafield, insert metatext
            $mfimt = array();
            
            // Iterate through the CSV headers.
            foreach ($this->fields as $fieldKey => $field) {
                
                $value = $row[$fieldKey];
                
                // Remember that if $field is a numeric value it is a metafield; whereas if 
                // $field is a string value it is a default (Dublin Core) field. Note: "coverage," 
                // "identifier," and "type" are commented out until they are explicitly included in 
                // the `items` table.
                switch ($field) {
                    case 'contributor':
                        $dfui['contributor'] = $value;
                        break;
                    //case 'coverage':
                        //$dfui['coverage'] = $value;
                        //break;
                    case 'creator':
                        $dfui['creator'] = $value;
                        break;
                    case 'date':
                        $dfui['date'] = $value;
                        break;
                    case 'description':
                        $dfui['description'] = $value;
                        break;
                    case 'format':
                        $dfui['format'] = $value;
                        break;
                    //case 'identifier':
                        //$dfui['identifier'] = $value;
                        //break;
                    case 'language':
                        $dfui['language'] = $value;
                        break;
                    case 'publisher':
                        $dfui['publisher'] = $value;
                        break;
                    case 'relation':
                        $dfui['relation'] = $value;
                        break;
                    case 'rights':
                        $dfui['rights'] = $value;
                        break;
                    case 'source':
                        $dfui['source'] = $value;
                        break;
                    case 'subject':
                        $dfui['subject'] = $value;
                        break;
                    case 'title':
                        $dfui['title'] = $value;
                        break;
                    //case 'type':
                        //$dfui['type'] = $value;
                        //break;
                    default:
                        // If the field is numeric, process it as a metafield.
                        if (is_numeric($field)) {
                            $mfimt[$field] = $value;
                        // If the field is neither a default field or a metafield, there is a problem. Maybe just ignore?
                        } else {}
                        break;
                } // end switch, $field
            
            } // end foreach, $this->fields
            
            // Now perform the necessary database statements.
            
            // Insert a row into the items table with default fields.
            $dfui['type_id'] = $this->typeId;
            $this->db->insert('Item', $dfui);
            
            // Get the new item ID.
            $itemId = $this->db->lastInsertId();
            
            // Insert metatext and associate it to the item.
            if (count($mfimt)) {
                foreach ($mfimt as $mfId => $text) {
                    // $text must not be null.
                    if ($text === null) $text = '';
                    $this->db->insert('Metatext', array('item_id' => $itemId, 'metafield_id' => $mfId, 'text' => $text));
                }
            }
            
        } // end foreach, $this->rows
    }
}