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
                
                // do the import
                $csvImportFileImport = new CsvImport_Import($csvImportFile, $csvImportItemType['id'], $collectionId, $itemsArePublic, $itemsAreFeatured, $colNumsToElementIdsMap);
                $csvImportFileImport->doImport();
                
                //redirect to column mapping page
                $this->redirect->goto('status');
                
            }  
        }   
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

        $view->csvImportFileImport = $csvImportSession->csvImportFileImport; 
        
    }
}