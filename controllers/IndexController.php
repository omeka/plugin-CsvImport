<?php

/**
 * @version $Id$
 * @copyright Center for History and New Media, 2008-2011
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @package CsvImport
 */

/**
 * The CvsImport index controller class.
 *
 * @package CsvImport
 * @author CHNM
 * @copyright Center for History and New Media, 2008-2011
 */
class CsvImport_IndexController extends Omeka_Controller_Action
{

    public function init()
    {
        $this->session = new Zend_Session_Namespace('CsvImport');
    }

    public function preDispatch()
    {
        if (($this->getRequest()->getActionName() != 'error')
            && !$this->_hasValidPHPCliPath()
        ) {
            $this->_helper->redirector->goto('error');    
        }
    }

    public function indexAction() 
    {
        // get the session and view
        $view = $this->view;
        
        // check the form submit button
        if (isset($_POST['csv_import_submit'])) {

            //make sure the user selected a file
            if (trim($_POST['csv_import_file_name']) == '') {
                
                $this->flashError('Please select a file to import.');                
            
            } else {
                    
                // make sure the file is correctly formatted
                $file = new CsvImport_File($_POST['csv_import_file_name']);
                
                $maxRowsToValidate = 2;
                if (!$file->isValid($maxRowsToValidate)) {                    
                    $this->flashError('Your file is incorrectly formatted.  Please select a valid CSV file.');
                } else {                    
                    // save csv file and item type to the session
                    $this->session->file = $file;                    
                    $this->session->itemTypeId = empty($_POST['csv_import_item_type_id']) ? 0 : $_POST['csv_import_item_type_id'];
                    $this->session->itemsArePublic = ($_POST['csv_import_items_are_public'] == '1');
                    $this->session->itemsAreFeatured = ($_POST['csv_import_items_are_featured'] == '1');
                    $this->session->collectionId = $_POST['csv_import_collection_id'];
                    $this->session->stopImportIfFileDownloadError = $_POST['csv_import_stop_import_if_file_download_error'];
                    //redirect to column mapping page
                    $this->_helper->redirector->goto('map-columns');   
                }                
            }
        }
    }
    
    public function errorAction()
    {
        if ($this->_hasValidPHPCliPath()) {
            $this->_helper->redirector->goto('index');    
        }
    }
    
    public function mapColumnsAction()
    {
        $hasError = false;
        
        $view = $this->view;

        if (!$this->_sessionIsValid()) {
            return $this->_helper->redirector->goto('index');
        }
        
        // get the session variables
        $itemsArePublic = $this->session->itemsArePublic;
        $itemsAreFeatured = $this->session->itemsAreFeatured;
        $collectionId = $this->session->collectionId;
        $stopImportIfFileDownloadError = $this->session->stopImportIfFileDownloadError;
        
        // get the csv file to import
        $file = $this->session->file;
                
        // pass the csv file and item type to the view
        $view->file = $file;
        $view->itemTypeId = $this->session->itemTypeId;
        $view->fileImport = null;        
                
        // process submitted column mappings
        if (isset($_POST['csv_import_submit'])) {
            
            // create the column maps
            $columnMaps = array();
            $colCount = $file->getColumnCount();
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
                        $columnMap->setDataIsHtml((boolean)$_POST[CSV_IMPORT_COLUMN_MAP_HTML_CHECKBOX_PREFIX . $colIndex]);
                        $columnMaps[] = $columnMap;                        
                    }
                }
            }           
            
            // make sure the user maps have at least one column
            if (count($columnMaps) == 0) {
                $this->flashError('Please map at least one column to an element, file, or tag.');
                $hasError = true;
            }
            
            // if there are no errors with the column mappings, then run the import and goto the status page
            if (!$hasError) {
                
                // do the import in the background
                $csvImport = new CsvImport_Import();
                $csvImport->initialize($file->getFileName(), $this->session->itemTypeId, $collectionId, $itemsArePublic, $itemsAreFeatured, $stopImportIfFileDownloadError, $columnMaps);
                $csvImport->status = CsvImport_Import::STATUS_IN_PROGRESS_IMPORT;
                $csvImport->save();
                
                // dispatch the background process to import the items
                $user = current_user();
                $args = array();
                $args['import_id'] = $csvImport->id;
                ProcessDispatcher::startProcess('CsvImport_ImportProcess', $user, $args);
                
                //_helper->redirector to column mapping page
                $this->flashSuccess("Successfully started the import. Reload this page for status updates.");
                $this->_helper->redirector->goto('status');
                
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
            
            // change the status of the import
            $csvImport->status = CsvImport_Import::STATUS_IN_PROGRESS_UNDO_IMPORT;
            $csvImport->save();

            // // dispatch the background process to undo the import
            $user = current_user();
            $args = array();
            $args['import_id'] = $importId;
            ProcessDispatcher::startProcess('CsvImport_UndoImportProcess', $user, $args);
        }
        $this->flashSuccess("Successfully started to undo the import. Reload this page for status updates.");
        $this->_helper->redirector->goto('status');
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
        $this->_helper->redirector->goto('status');
    }
    
    public function statusAction() 
    {
        //get the imports
        $this->view->csvImports =  CsvImport_Import::getImports();
    }
    
    private function _hasValidPHPCliPath()
    {
        try {
            $p = ProcessDispatcher::getPHPCliPath();
        } catch (Exception $e) {
            $this->flashError('Your PHP-CLI path setting is invalid.'.  "\n"  . 'Please change the setting in ' . CONFIG_DIR . DIRECTORY_SEPARATOR . 'config.ini' . "\n" . 'If you do not know how to do this, please check with your system or server administrator.');
            return false;
        }
        return true;
    }

    private function _sessionIsValid()
    {
        $requiredKeys = array('itemsArePublic', 'itemsAreFeatured', 'stopImportIfFileDownloadError', 'collectionId', 'itemTypeId');

        foreach ($requiredKeys as $key) {
            if (!isset($this->session->$key) 
                || !is_numeric($this->session->$key)
            ) {
                return false;
            }
        }
        return true;
    }
}
