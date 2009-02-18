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
        $view = $this->view;
        
        $view->err = '';
        if (isset($_POST['csv_import_submit'])) {

            //make sure the user selected a file and item type
            if (empty($_POST['csv_import_file'])) {
                $view->err = 'Please select a file to import.';
            } else if (empty($_POST['csv_import_item_type'])) {
                $view->err = 'Please select an item type to import.';
            } else {

                // save file name and item type in csvimport_session
                $csvImportSession = new Zend_Session_Namespace('CsvImport');
                $csvImportSession->csvImportFile = $_POST['csv_import_file'];
                $csvImportSession->csvImportItemType = $_POST['csv_import_item_type'];
                
                //redirect to column mapping page
                $this->redirect->goto('map-columns');
                
            }
        }
        
    }
    
    public function statusAction() 
    {
        
    }
    
    public function mapColumnsAction()
    {

        $csvImportSession = new Zend_Session_Namespace('CsvImport');

        // get the csv file to import
        $csvImportFile = new CsvImport_File($csvImportSession->csvImportFile);
        
        // get the item type to import
        $db = get_db();
        $itt = $db->getTable('ItemType'); // get ItemTypeTable
        $csvImportItemType = $itt->find($csvImportSession->csvImportItemType);
        
        // store the csv file and item type in the view
        $view = $this->view;
        $view->err = '';
        $view->csvImportFile = $csvImportFile;
        $view->csvImportItemTypeId = $csvImportItemType['id'];
        
        // process submitted column mappings
        if (isset($_POST['csv_import_submit'])) {
            
            // make sure that no two columns map to the same item type element
            $elementIds = array();
            $colCount = $csvImportFile->getColumnCount();
            for($i = 0; $i < $colCount; $i++) {
                $elementName =  CSV_IMPORT_SELECT_COLUMN_DROPDOWN_PREFIX . $i;
                $elementId = $_POST[$elementName];
                
                // see if the element is already used
                if (!empty($elementId)) {
                    if (in_array($elementId, $elementIds)) {
                        // if it has been used, indicate the error
                        $view->err = 'Please map the columns to different item type elements.';
                        break;
                    } else {
                        // if not, add remember that it has been used
                        $elementIds[] = $elementId;
                    }
                }
            }
            
            // if there are no errors with the mappings, then run the import and goto the status page
            if (empty($view->err)) {
                // do the import
                $view->err = 'Success!';
                
                //redirect to column mapping page
                $this->redirect->goto('status');
            }  
        }   
    }
}