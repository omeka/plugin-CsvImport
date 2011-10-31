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
    protected $_browseRecordsPerPage = 10;

    private $_pluginConfig = array();

    public function init()
    {
        $this->session = new Zend_Session_Namespace('CsvImport');
        if (version_compare(OMEKA_VERSION, '2.0-dev', '>=')) {
            $this->_helper->db->setDefaultModelName('CsvImport_Import');
        } else {
            $this->_modelClass = 'CsvImport_Import';
        }
    }

    public function preDispatch()
    {
        $this->view->navigation($this->_getNavigation());
    }

    public function indexAction()
    {
        $form = $this->_getMainForm();
        $this->view->form = $form;

        if (!$this->getRequest()->isPost()) {
            return;
        }

        if (!$form->isValid($this->getRequest()->getPost())) {
            return;
        }
        if (!$form->csv_file->receive()) {
            return $this->flashError("Error uploading file.  Please try again.");
        }

        $filePath = $form->csv_file->getFileName();
        $filename = $_FILES['csv_file']['name'];
        $delimiter = $form->getValue('column_delimiter');
        $file = new CsvImport_File($filePath, $delimiter);
        
        if (!$file->parse()) {
            return $this->flashError('Your file is incorrectly formatted. '
                . $file->getErrorString());
        }

        $this->session->originalFilename = $filename;
        $this->session->filePath = $filePath;
        $this->session->columnDelimiter = $delimiter;

        
        $this->session->itemTypeId = $form->getValue('item_type_id');
        $this->session->itemsArePublic =
            $form->getValue('items_are_public');
        $this->session->itemsAreFeatured =
            $form->getValue('items_are_featured');
        $this->session->collectionId = $form->getValue('collection_id');
        $this->session->columnNames = $file->getColumnNames();
        $this->session->columnExamples = $file->getColumnExamples();
        $this->session->ownerId = $this->getInvokeArg('bootstrap')->currentuser->id;

        if($form->getValue('omeka_csv_export')) {
            
            $this->_helper->redirector->goto('omeka-csv');
        }
        $this->_helper->redirector->goto('map-columns');
    }
    
    public function mapColumnsAction()
    {
        if (!$this->_sessionIsValid()) {
            return $this->_helper->redirector->goto('index');
        }

        require_once CSV_IMPORT_DIRECTORY . '/forms/Mapping.php';
        $form = new CsvImport_Form_Mapping(array(
            'itemTypeId' => $this->session->itemTypeId,
            'columnNames' => $this->session->columnNames,
            'columnExamples' => $this->session->columnExamples,
        ));
        $this->view->form = $form;
                
        if (!$this->getRequest()->isPost()) {
            return;
        }
        if (!$form->isValid($this->getRequest()->getPost())) {
            return;
        }

        $columnMaps = $form->getMappings();
        if (count($columnMaps) == 0) {
            return $this->flashError('Please map at least one column to an '
                . 'element, file, or tag.');
        }
        
        $csvImport = new CsvImport_Import();
        foreach ($this->session->getIterator() as $key => $value) {
            $setMethod = 'set' . ucwords($key);
            if (method_exists($csvImport, $setMethod)) {
                $csvImport->$setMethod($value);
            }
        }
        $csvImport->setColumnMaps($columnMaps);
        $csvImport->setStatus(CsvImport_Import::QUEUED);
        $csvImport->forceSave();

        $csvConfig = $this->_getPluginConfig();
        $jobDispatcher = Zend_Registry::get('job_dispatcher');
        $jobDispatcher->setQueueName('imports');
        $jobDispatcher->send('CsvImport_ImportTask',
            array(
                'importId' => $csvImport->id,
                'memoryLimit' => @$csvConfig['memoryLimit'],
                'batchSize' => @$csvConfig['batchSize'],
            )
        );

        $this->session->unsetAll();
        $this->flashSuccess('Successfully started the import. Reload this page '
            . 'for status updates.');
        $this->_helper->redirector->goto('browse');
    }
    
    public function omekaCsvAction()
    {
        $headings = $this->session->columnNames;
        $columnMaps = array();
        foreach($headings as $heading) {

            switch ($heading) {
                case 'collection':
                    $columnMaps[] = new CsvImport_ColumnMap_Collection($heading);
                    break;
                case 'itemType':
                    $columnMaps[] = new CsvImport_ColumnMap_ItemType($heading);
                    break;
                case 'file':
                    $columnMaps[] = new CsvImport_ColumnMap_File($heading);
                    break;
                case 'tags':
                    $columnMaps[] = new CsvImport_ColumnMap_Tag($heading);
                    break;
                case 'public':
                    $columnMaps[] = new CsvImport_ColumnMap_Public($heading);
                    break;
                case 'featured':
                    $columnMaps[] = new CsvImport_ColumnMap_Featured($heading);
                    break;
                default:
                    //@TODO: make sure this doesn't break regular import
                    $columnMaps[] = new CsvImport_ColumnMap_ExportedElement($heading);
                    break;
            }
        }
        $csvImport = new CsvImport_Import();
        
        //this is the clever way that mapColumns action sets the values passed along from indexAction
        //many will be irrelevant here, since CsvImport allows variable itemTypes and Collection
        
        //@TODO: check if variable itemTypes and Collections breaks undo. It probably should, actually
        foreach ($this->session->getIterator() as $key => $value) {
            $setMethod = 'set' . ucwords($key);
            if (method_exists($csvImport, $setMethod)) {
                $csvImport->$setMethod($value);
            }
        }
        $csvImport->setColumnMaps($columnMaps);
        $csvImport->setStatus(CsvImport_Import::QUEUED);
        $csvImport->forceSave();

        $csvConfig = $this->_getPluginConfig();
        $jobDispatcher = Zend_Registry::get('job_dispatcher');
        $jobDispatcher->setQueueName('imports');
        $jobDispatcher->send('CsvImport_ImportTask',
            array(
                'importId' => $csvImport->id,
                'memoryLimit' => @$csvConfig['memoryLimit'],
                'batchSize' => @$csvConfig['batchSize'],
            )
        );

        $this->session->unsetAll();
        $this->flashSuccess('Successfully started the import. Reload this page '
            . 'for status updates.');
        $this->_helper->redirector->goto('browse');
    }
    
    public function undoImportAction()
    {
        $csvImport = $this->findById();
        $csvImport->status = CsvImport_Import::QUEUED;
        $csvImport->forceSave();

        $jobDispatcher = Zend_Registry::get('job_dispatcher');
        $jobDispatcher->setQueueName('imports');
        $jobDispatcher->send('CsvImport_ImportTask',
            array('importId' => $csvImport->id, 'method' => 'undo'));
        $this->flashSuccess('Successfully started to undo the import. Reload '
            . 'this page for status updates.');
        $this->_helper->redirector->goto('browse');
    }
    
    public function clearHistoryAction()
    {
        $csvImport = $this->findById();
        if ($csvImport->status ==
            CsvImport_Import::COMPLETED_UNDO
        ) {
            $csvImport->delete();
            $this->flashSuccess("Successfully cleared the history "
                . " of the import.");
        }
        $this->_helper->redirector->goto('browse');
    }
    
    private function _getMainForm()
    {
        require_once CSV_IMPORT_DIRECTORY . '/forms/Main.php';
        $csvConfig = $this->_getPluginConfig();
        $form = new CsvImport_Form_Main($csvConfig);
        return $form;
    }

    private function _getNavigation()
    {
        return new Zend_Navigation(array(
            array(
                'label' => 'Import Items',
                'action' => 'index',
                'module' => 'csv-import',
            ),
            array(
                'label' => 'Status',
                'action' => 'browse',
                'module' => 'csv-import',
            ),
        ));
    }

    private function _getPluginConfig()
    {
        if (!$this->_pluginConfig) {
            $config = $this->getInvokeArg('bootstrap')->config->plugins;
            if ($config && isset($config->CsvImport)) {
                $this->_pluginConfig = $config->CsvImport->toArray();
            }
            if (!array_key_exists('fileDestination', $this->_pluginConfig)) {
                $this->_pluginConfig['fileDestination'] =
                    Zend_Registry::get('storage')->getTempDir();
            }
        }
        return $this->_pluginConfig;
    }
    
    private function _sessionIsValid()
    {
        $requiredKeys = array('itemsArePublic', 'itemsAreFeatured',
            'collectionId', 'itemTypeId', 'ownerId');

        foreach ($requiredKeys as $key) {
            if (!isset($this->session->$key)) {
                return false;
            }
        }
        return true;
    }
}
