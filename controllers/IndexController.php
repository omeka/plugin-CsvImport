<?php

/**
 * @version $Id$
 * @copyright Center for History and New Media, 2008
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @package CsvImport
 */

/**
 * The CvsImport index controller class.
 *
 * @package CsvImport
 * @author CHNM
 * @copyright Center for History and New Media, 2008
 */

class CsvImport_IndexController extends Omeka_Controller_Action
{
    public function indexAction() 
    {
        // get the session and view
        $csvImportSession = new Zend_Session_Namespace('CsvImport');
        $view = $this->view;
        
        // check the form submit button
        $view->err = '';
        if (isset($_POST['csv_import_submit'])) {

            //make sure the user selected a file
            if (empty($_POST['csv_import_file_name'])) {
                $view->err = 'Please select a file to import.';
            } else {
                
                // make sure the file is correctly formatted
                $csvImportFile = new CsvImport_File($_POST['csv_import_file_name']);
                
                
                if (!$csvImportFile->isPreValid()) {                    
                    $view->err = "Your file is incorrectly formatted.  Please select a valid CSV file.";
                } else {                    
                    // save csv file and item type to the session
                    $csvImportSession->csvImportFile = $csvImportFile;                    
                    $csvImportSession->csvImportItemTypeId = empty($_POST['csv_import_item_type_id']) ? 0 : $_POST['csv_import_item_type_id'];
                    $csvImportSession->csvImportItemsArePublic = ($_POST['csv_import_items_are_public'] == '1');
                    $csvImportSession->csvImportItemsAreFeatured = ($_POST['csv_import_items_are_featured'] == '1');
                    $csvImportSession->csvImportCollectionId = $_POST['csv_import_collection_id'];
                    $csvImportSession->csvImportStopImportIfFileDownloadError = $_POST['csv_import_stop_import_if_file_download_error'];
                    //redirect to column mapping page
                    $this->redirect->goto('map-columns');   
                }                
            }
        }
        
    }
    
    public function mapColumnsAction()
    {
        // get the session and view
        $csvImportSession = new Zend_Session_Namespace('CsvImport');
        $itemsArePublic = $csvImportSession->csvImportItemsArePublic;
        $itemsAreFeatured = $csvImportSession->csvImportItemsAreFeatured;
        $collectionId = $csvImportSession->csvImportCollectionId;
        $stopImportIfFileDownloadError = $csvImportSession->csvImportStopImportIfFileDownloadError;
        
        $view = $this->view;

        // get the csv file to import
        $csvImportFile = $csvImportSession->csvImportFile;
                
        // pass the csv file and item type to the view
        $view->err = '';
        $view->csvImportFile = $csvImportFile;
        $view->csvImportItemTypeId = $csvImportSession->csvImportItemTypeId;
        $view->csvImportFileImport = null;        
                
        // process submitted column mappings
        if (isset($_POST['csv_import_submit'])) {
            
            // create the column maps
            $columnMaps = array();
            $colCount = $csvImportFile->getColumnCount();
            for($colIndex = 0; $colIndex < $colCount; $colIndex++) {
                
                // if applicable, add mapping to tags
                if ($_POST[CSV_IMPORT_COLUMN_MAP_TAG_CHECKBOX_PREFIX . $colIndex] == '1') {
                    $columnMap = new CsvImport_ColumnMap($colIndex, CsvImport_ColumnMap::TARGET_TYPE_TAG);
                    $columnMaps[] = $columnMap;
                }
                
                // if applicable, add mapping to file
                if ($_POST[CSV_IMPORT_COLUMN_MAP_FILE_CHECKBOX_PREFIX . $colIndex] == '1') {
                    $columnMap = new CsvImport_ColumnMap($colIndex, CsvImport_ColumnMap::TARGET_TYPE_FILE);
                    $columnMaps[] = $columnMap;
                }
                                
                // if applicable, add mapping to elements
                $rawElementIds = explode(',', $_POST[CSV_IMPORT_COLUMN_MAP_ELEMENTS_HIDDEN_INPUT_PREFIX . $colIndex]);
                foreach($rawElementIds as $rawElementId) {
                    $elementId = trim($rawElementId);
                    if ($elementId) {
                        $columnMap = new CsvImport_ColumnMap($colIndex, CsvImport_ColumnMap::TARGET_TYPE_ELEMENT);
                        $columnMap->addElementId($elementId);
                        $columnMaps[] = $columnMap;                        
                    }
                }
            }           
            
            // make sure the user maps at least one column
            if (count($columnMaps) == 0) {
                $view->err = 'Please map at least one column to an element, file, or tag.';
            }
            
            // if there are no errors with the column mappings, then run the import and goto the status page
            if (empty($view->err)) {
                
                // do the import in the background
                $csvImport = new CsvImport_Import();
                $csvImport->initialize($csvImportFile->getFileName(), $csvImportSession->csvImportItemTypeId, $collectionId, $itemsArePublic, $itemsAreFeatured, $stopImportIfFileDownloadError, $columnMaps);
                $this->_backgroundImport($csvImport);
                
                //redirect to column mapping page
                $this->flashSuccess("Successfully started import. Reload this page for status updates.");
                $this->redirect->goto('status');
                
            }  
        }   
    }
    
    public function undoImportAction()
    {
        $db = get_db();
        $cit = $db->getTable('CsvImport_Import');
        $importId = $this->_getParam("id");
        $csvImport = $cit->find($importId);
        if ($csvImport) {
            // undo the import in the background
            $this->_backgroundUndoImport($csvImport);
        }
        $this->flashSuccess("Successfully started undo import. Reload this page for status updates.");
        $this->redirect->goto('status');
    }
    
    public function clearHistoryAction()
    {
        $db = get_db();
        $cit = $db->getTable('CsvImport_Import');
        $importId = $this->_getParam("id");
        $csvImport = $cit->find($importId);
        if ($csvImport) {
            if ($csvImport->status == CsvImport_Import::STATUS_COMPLETED_UNDO_IMPORT || 
                $csvImport->status == CsvImport_Import::STATUS_IMPORT_ERROR_INVALID_CSV_FILE) {
                // delete the import object
                $csvImport->delete();
                $this->flashSuccess("Successfully cleared the history of the import.");
            }
        }
        $this->redirect->goto('status');
    }
    
    public function statusAction() 
    {
        // get the session and view
        $csvImportSession = new Zend_Session_Namespace('CsvImport');
        $view = $this->view;
        
        //get the imports
        $view->csvImports =  CsvImport_Import::getImports();
    }
    
    // import items in the background from csv file
    private function _backgroundImport($csvImport)
    {
        $this->_backgroundImportOrUndoImport($csvImport, true);
    }
    
    // undo the import in the background by deleting the imported items
    private function _backgroundUndoImport($csvImport)
    {
        $this->_backgroundImportOrUndoImport($csvImport, false);
    }
    
    // since import and undo import share similar code, this function can do either
    private function _backgroundImportOrUndoImport($csvImport, $isImport) 
    {   
         // make sure the import is saved in the database has a start status
         if ($isImport) {
             $csvImport->status = CsvImport_Import::STATUS_IN_PROGRESS_IMPORT;
         } else {
             $csvImport->status = CsvImport_Import::STATUS_IN_PROGRESS_UNDO_IMPORT;
         }       
        $csvImport->save();

        // Create shell command
        if ($isImport) {
            $scriptFileName = 'background-import.php';
        } else {
            $scriptFileName = 'background-undo-import.php';
        }
        $phpCommandPath = get_option('csv_import_php_path');
        $scriptFilePath = CSV_IMPORT_BACKGROUND_SCRIPTS_DIRECTORY . DIRECTORY_SEPARATOR . $scriptFileName;
        $importId = escapeshellarg($csvImport->id);
        $user = Omeka_Context::getInstance()->getCurrentUser();
        $userId = escapeshellarg($user->id);

        // Set the command and run the script in the background.
        $shellCmd = "$phpCommandPath $scriptFilePath -i $importId -u $userId";

        // execute a background script that does the import or undoes the import
        $this->_background($shellCmd);
    }

    // execute a shell command in the background
    private function _background($shellCmd)
    {
        //echo $shellCmd; exit;
        exec("nice $shellCmd > /dev/null 2>&1 &"); 
    }
}