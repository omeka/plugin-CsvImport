<?php

/**
 * @version $Id$
 * @copyright Center for History and New Media, 2008
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @package CvsImport
 */

/**
 * The CvsImport index controller class.
 *
 * @package SimplePages
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
                } else if (empty($_POST['csv_import_item_type_id'])) {
                    // make sure user has selected an item type
                    $view->err = 'Please select an item type to import.'; 
                } else {
                    // save csv file and item type to the session
                    $csvImportSession->csvImportFile = $csvImportFile;
                    $csvImportSession->csvImportItemTypeId = $_POST['csv_import_item_type_id'];
                    $csvImportSession->csvImportItemsArePublic = ($_POST['csv_import_items_are_public'] == '1');
                    $csvImportSession->csvImportItemsAreFeatured = ($_POST['csv_import_items_are_featured'] == '1');
                    $csvImportSession->csvImportCollectionId = $_POST['csv_import_collection_id'];
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
        
        $view = $this->view;

        // get the csv file to import
        $csvImportFile = $csvImportSession->csvImportFile;
                
        // get the item type to import
        $db = get_db();
        $itt = $db->getTable('ItemType'); // get ItemTypeTable
        $csvImportItemType = $itt->find($csvImportSession->csvImportItemTypeId);
        
        // pass the csv file and item type to the view
        $view->err = '';
        $view->csvImportFile = $csvImportFile;
        $view->csvImportItemTypeId = $csvImportItemType['id'];
        $view->csvImportFileImport = null;        
                
        // process submitted column mappings
        if (isset($_POST['csv_import_submit'])) {
            
            // map the column index numbers to the element ids
            $colNumsToElementIdsMap = array();
            $colCount = $csvImportFile->getColumnCount();
            for($i = 0; $i < $colCount; $i++) {
                $elementName =  CSV_IMPORT_SELECT_COLUMN_DROPDOWN_PREFIX . $i;
                $elementId = $_POST[$elementName];
                if (!empty($elementId)) {
                    $colNumsToElementIdsMap[$i . ''] = $elementId;
                }
            }           
            
            // make sure the user maps at least one column to an element
            if (count($colNumsToElementIdsMap) == 0) {
                $view->err = 'Please map at least one column to an element.';
            }
            
            // if there are no errors with the column mappings, then run the import and goto the status page
            if (empty($view->err)) {
                
                // do the import in the background
                $csvImport = new CsvImport_Import();
                $csvImport->initialize($csvImportFile->getFileName(), $csvImportItemType['id'], $collectionId, $itemsArePublic, $itemsAreFeatured, $colNumsToElementIdsMap);
                $this->_backgroundImport($csvImport);
                
                //redirect to column mapping page
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
        $this->redirect->goto('status');
    }
    
    public function clearAction()
    {
        $db = get_db();
        $it = $db->getTable('Item');
        $items = $it->findBy(array(), 500);
        foreach($items as $item) {
            $item->delete();
        }
        $this->redirect->goto('index');
    }
    
    public function statusAction() 
    {
        // get the session and view
        $csvImportSession = new Zend_Session_Namespace('CsvImport');
        $view = $this->view;
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
             $csvImport->status = CSV_IMPORT_STATUS_IN_PROGRESS_IMPORT;
         } else {
             $csvImport->status = CSV_IMPORT_STATUS_IN_PROGRESS_UNDO_IMPORT;
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
        exec("nice $shellCmd > /dev/null 2>&1 &"); 
    }
}