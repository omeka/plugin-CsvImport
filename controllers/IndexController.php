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
        require_once CSV_IMPORT_DIRECTORY . '/forms/Main.php';
        $form = new CsvImport_Form_Main();
        $this->view->form = $form;

        if (!$this->getRequest()->isPost()) {
            return;
        }

        if (!$form->isValid($this->getRequest()->getPost())) {
            return;
        }

        $file = new CsvImport_File($form->getValue('file_name'));
        
        $maxRowsToValidate = 2;
        if (!$file->isValid($maxRowsToValidate)) {                    
            return $this->flashError('Your file is incorrectly formatted. '
                . 'Please select a valid CSV file.');
        }

        $this->session->filename = $form->getValue('file_name');                    
        $this->session->itemTypeId = $form->getValue('item_type_id');
        $this->session->itemsArePublic = 
            $form->getValue('items_are_public');
        $this->session->itemsAreFeatured = 
            $form->getValue('items_are_featured');
        $this->session->collectionId = $form->getValue('collection_id');
        $this->session->stopImportIfFileDownloadError = 
            $form->getValue('stop_import_if_file_download_error');
        $this->_helper->redirector->goto('map-columns');   
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
        
        if (!$this->_sessionIsValid()) {
            return $this->_helper->redirector->goto('index');
        }
        
        $file = new CsvImport_File($this->session->filename);
                
        $this->view->file = $file;
        $this->view->itemTypeId = $this->session->itemTypeId;
        $this->view->fileImport = null;        
                
        if (!$this->getRequest()->isPost()) {
            return;
        }
            
        $columnMaps = array();
        $colCount = $file->getColumnCount();
        for($colIndex = 0; $colIndex < $colCount; $colIndex++) {
            
            if ($_POST[CSV_IMPORT_COLUMN_MAP_TAG_CHECKBOX_PREFIX . $colIndex] == 
                '1') {
                $columnMap = new CsvImport_ColumnMap($colIndex, 
                    CsvImport_ColumnMap::TARGET_TYPE_TAG);
                $columnMaps[] = $columnMap;
            }
            
            if ($_POST[CSV_IMPORT_COLUMN_MAP_FILE_CHECKBOX_PREFIX . $colIndex] 
                == '1') {
                $columnMap = new CsvImport_ColumnMap($colIndex, 
                    CsvImport_ColumnMap::TARGET_TYPE_FILE);
                $columnMaps[] = $columnMap;
            }
                            
            $rawElementIds = explode(',', 
                $_POST[CSV_IMPORT_COLUMN_MAP_ELEMENTS_HIDDEN_INPUT_PREFIX 
                . $colIndex]);
            foreach($rawElementIds as $rawElementId) {
                $elementId = trim($rawElementId);
                if ($elementId) {
                    $columnMap = new CsvImport_ColumnMap($colIndex, 
                        CsvImport_ColumnMap::TARGET_TYPE_ELEMENT);
                    $columnMap->addElementId($elementId);
                    $columnMap->setDataIsHtml( 
                        (boolean)$_POST[CSV_IMPORT_COLUMN_MAP_HTML_CHECKBOX_PREFIX 
                        . $colIndex]);
                    $columnMaps[] = $columnMap;                        
                }
            }
        }           
        
        if (count($columnMaps) == 0) {
            return $this->flashError('Please map at least one column to an '
                . 'element, file, or tag.');
        }
        
        $csvImport = new CsvImport_Import();
        $csvImport->initialize($file->getFileName(), 
                               $this->session->itemTypeId, 
                               $this->session->collectionId, 
                               $this->session->itemsArePublic, 
                               $this->session->itemsAreFeatured, 
                               $this->session->stopImportIfFileDownloadError, 
                               $columnMaps);
        $csvImport->status = CsvImport_Import::STATUS_IN_PROGRESS_IMPORT;
        $csvImport->save();
        
        $user = current_user();
        $args = array();
        $args['import_id'] = $csvImport->id;
        ProcessDispatcher::startProcess('CsvImport_ImportProcess', $user, $args);

        $this->session->unsetAll();
        $this->flashSuccess('Successfully started the import. Reload this page '
            . 'for status updates.');
        $this->_helper->redirector->goto('status');
    }
    
    public function undoImportAction()
    {
        $cit = $this->getTable('CsvImport_Import');
        $importId = $this->_getParam("id");
        $csvImport = $cit->find($importId);
        if ($csvImport) {
            
            $csvImport->status = CsvImport_Import::STATUS_IN_PROGRESS_UNDO_IMPORT;
            $csvImport->save();

            $user = current_user();
            $args = array();
            $args['import_id'] = $importId;
            ProcessDispatcher::startProcess('CsvImport_UndoImportProcess', 
                $user, $args);
        }
        $this->flashSuccess('Successfully started to undo the import. Reload '
            . 'this page for status updates.');
        $this->_helper->redirector->goto('status');
    }
    
    public function clearHistoryAction()
    {
        $cit = $this->getTable('CsvImport_Import');
        $importId = $this->_getParam("id");
        $csvImport = $cit->find($importId);
        if ($csvImport) {
            if ($csvImport->status == 
                CsvImport_Import::STATUS_COMPLETED_UNDO_IMPORT || 
                $csvImport->status == 
                CsvImport_Import::STATUS_IMPORT_ERROR_INVALID_CSV_FILE) {
                $csvImport->delete();
                $this->flashSuccess("Successfully cleared the history of the '
                    . 'import.");
            }
        }
        $this->_helper->redirector->goto('status');
    }
    
    public function statusAction() 
    {
        $this->view->csvImports = $this->getTable('CsvImport_Import')
                                       ->findAll();
    }
    
    private function _hasValidPHPCliPath()
    {
        try {
            $p = ProcessDispatcher::getPHPCliPath();
        } catch (Exception $e) {
            $this->flashError("Your PHP-CLI path setting is invalid.\n"  
                . "Please change the setting in " . CONFIG_DIR 
                . "/config.ini\nIf you do not know how to do this, please check "
                . "with your system or server administrator.");
            return false;
        }
        return true;
    }

    private function _sessionIsValid()
    {
        $requiredKeys = array('itemsArePublic', 'itemsAreFeatured', 
            'stopImportIfFileDownloadError', 'collectionId', 'itemTypeId');

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
