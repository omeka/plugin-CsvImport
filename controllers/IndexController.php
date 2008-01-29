<?php
class CsvImport_IndexController extends Omeka_Controller_Action {
    
    public function indexAction() {
        $this->render('csvimport/index.php');
    }
    
    public function formAction() {
        
        try {
            // Set the CSV files.
            $files = array();
            $path = new DirectoryIterator(CSVIMPORT_CSVFILES_DIRECTORY);
            foreach ($path as $file) {
                if (!$file->isDot() && !$file->isDir() && $this->_hasCsvFilenameExtension($file)) {
                    $files[] = $file->getFilename();
                }
            }
            
            if (empty($files)) {
                throw new Omeka_Validator_Exception("There are no valid files in the dropbox.");
            }
        
            // Set the types.
            $types = $this->_getTypes();
            
            if (empty($types)) {
                throw new Omeka_Validator_Exception("There are no item types in Omeka.");
            }
            
            $this->render('csvimport/form.php', compact('files', 'types'));
        
        } catch (Omeka_Validator_Exception $e) {
            $this->flashValidationErrors($e);
            $this->render('csvimport/index.php');
        }
    }
    
    public function mappingAction() {
        
        try {
            // Perform validation.
            $this->_validateForm();
            
            // Set the CSV file -- posted by the formAction() form.
            $file = $_POST['file'];
            
            // Set the type ID -- posted by the formAction() form.
            $typeId = $_POST['typeId'];
            
            // Set the type name.
            $typeName = $this->getTable('Type')->find($typeId)->name;
            
            // Set the CSV headers. 
            require_once CSVIMPORT_MODELS_DIRECTORY . DIRECTORY_SEPARATOR . 'Csv.php';
            $filePath = CSVIMPORT_CSVFILES_DIRECTORY . DIRECTORY_SEPARATOR . $file;
            $csv = new Csv($filePath);
            $csv->setHeaders();
            $csvHeaders = $csv->getHeaders();
            
            // Set the CSV rows to validate the CSV. (Not required.)
            // Commented out indefinitely due to memory size issues: "Fatal error: Allowed memory size of xxx bytes exhausted"
            // This prevents us from using the most accurate method for validating a CSV file.
            /*
            $csv->setRows();
            $csvRows = $csv->getRows();
            if (!$csv->isValidCsv()) {
                throw new Omeka_Validator_Exception("The CSV file appears to be invalid.");
            }
            */
            
            // Set the type fields.
            $fields = $this->_getFields($typeId);
            
            $this->render('csvimport/mapping.php', compact('file', 'typeId', 'typeName', 'csvHeaders', 'fields'));
        
        } catch (Omeka_Validator_Exception $e) {
            $this->flashValidationErrors($e);
            $this->render('csvimport/index.php');
        }
    }
    
    public function importAction() {
        // Perform validation.
        $this->_validateForm();
        
        // Set the CSV file -- posted by the mappingAction() form.
        $file = $_POST['file'];
        
        // Set the type ID -- posted by the mappingAction() form.
        $typeId = $_POST['typeId'];
        
        // the "fields" array -- posted from the mappingAction() form -- follows this format:
        // the keys correspond to the CSV header number (starting with 0, left to right). The 
        // values correspond to 1) the textual name of a default (Dublin Core) field; or 2) 
        // the numerical ID of a metafield.
        $fields = $_POST['fields'];
        
        // Set the CSV rows and import the data.
        // There are irreconcilable memory size issues here. A alternate method of importing the 
        // data will have to be determined. The obvious solution is to increase the memory limit 
        // using ini_set(), but this may not be advisable in an installed software product such 
        // as Omeka. One suggestion is to kick off an import script in the background. However, 
        // some web hosts do not allow web scripts to initiate background scripts. Logging the 
        // progress of the import is another solution, whereby a script can resume the import 
        // if the script times out. However, since this is a memory limit problem, it may be a 
        // moot solution.
        
        // Below is the background import script solution:
        
        // Remove empty values from the fields array. This is done to clean up unneccessary shell 
        // command content, but is not required.
        $fields = array_filter($fields, array($this, '_unsetEmptyValue'));
        
        // Set the shell command arguments.
        $importFilePath = CSVIMPORT_DIRECTORY . DIRECTORY_SEPARATOR . 'import.php';
        $fileArg = escapeshellarg($file);
        $typeIdArg = escapeshellarg($typeId);
        $fieldsArg = escapeshellarg(serialize($fields)); // Serialize the array so it can be passed by argument.
        
        // Set the shell command.
        $shellCmd = "php $importFilePath ".CSVIMPORT_FILE_ARG." $fileArg ".CSVIMPORT_TYPEID_ARG." $typeIdArg ".CSVIMPORT_FIELDS_ARG." $fieldsArg";
        echo $shellCmd;
        
        // Fork the import process to the background.
        $this->_fork($shellCmd);
        
        $this->render('csvimport/import.php');
    }
    
    private function _getTypes() {
        // Set the types.
        $types = $this->getTable('Type')->findAll();
        
        return $types;
    }
    
    private function _getFields($typeId) {
        // Set the type's metafields. (There must be an easier way to do this.)
        $db = get_db();
        $sql = "SELECT m.* FROM {$db->Metafield} m 
        INNER JOIN {$db->TypesMetafields} tm 
        ON tm.metafield_id = m.id 
        WHERE tm.type_id = ? 
        GROUP BY m.id";
        $metafields = $db->query($sql, array($typeId))->fetchAll();
        
        // Set the default (Dublin Core) fields.
        $defaultFields = array();
        $defaultFieldList = array(
            'contributor', 'creator', 'date', 'description', 
            'format', 'language', 'publisher', 'relation', 
            'rights', 'source', 'subject', 'title', 
            // The following three Dublin Core elements are not explicitly included in the `items` table, 
            // and therefore should not be included here. Moreover, several default fields presently in the 
            // `items` table should not be there, including `spatial_coverage`, `additional_creator`, 
            // `rights_holder`, `provenance`, `citation`, `temporal_coverage_start`, and `temporal_coverage_end`. 
            // Those elements are not included here in anticipation of a new schema that includes only the 
            // "core 15" metadata elements in the `items` table.
            //'identifier', 'type', 'coverage'
        );
        foreach ($defaultFieldList as $defaultField) {
            $defaultFields[] = array('name'=>$defaultField);
        }
        
        // Merge the default fields and the metafields.
        $fields = array_merge($defaultFields, $metafields);
        
        return $fields;
    }
    
    private function _fileExists($filePath) {
        return file_exists($filePath) ? true : false;
    }
    
    private function _typeExistsById($typeId) {
        return $this->getTable('Type')->find($typeId) ? true : false;
    }
    
    private function _hasCsvFilenameExtension($file) {
        return strrchr($file, '.') == '.csv' ? true : false;
    }
    
    private function _validateForm() {
        // Perform validation.
        if (!isset($_POST['file'])) throw new Omeka_Validator_Exception("File is not set.");
        if (!isset($_POST['typeId'])) throw new Omeka_Validator_Exception("Type is not set.");
        if (empty($_POST['file'])) throw new Omeka_Validator_Exception("Invalid file.");
        if (empty($_POST['typeId'])) throw new Omeka_Validator_Exception("Invalid type.");
        if (!$this->_fileExists(CSVIMPORT_CSVFILES_DIRECTORY . DIRECTORY_SEPARATOR . $_POST['file'])) throw new Omeka_Validator_Exception("The file \"{$_POST['file']}\" does not exist.");
        if (!$this->_hasCsvFilenameExtension($_POST['file'])) throw new Omeka_Validator_Exception("The file \"{$_POST['file']}\" appears not to be a CSV file. The filename should end with \".csv\".");
        if (!$this->_typeExistsById($_POST['typeId'])) throw new Omeka_Validator_Exception("The type ID \"{$_POST['typeId']}\" does not exist.");
    }
    
    // Launch a low-priority background process, returning control to the foreground.
    // See: http://www.php.net/manual/en/ref.exec.php#70135
    private function _fork($shellCmd) {
        // Comment this out until the import script is operational.
        //exec("nice $shellCmd > /dev/null 2>&1 &");
    }
    
    // Used as a callback method to remove empty values from an array, using array_filter().
    private function _unsetEmptyValue($value) {
        if (!empty($value)) return $value;
    }
}