<?php
/**
 * CsvImport_IndexController class - represents the Csv Import index controller
 *
 * @copyright Copyright 2008-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 * @package CsvImport
 */
class CsvImport_IndexController extends Omeka_Controller_AbstractActionController
{
    protected $_browseRecordsPerPage = 10;
    protected $_pluginConfig = array();

    /**
     * Initialize the controller.
     */
    public function init()
    {
        $this->session = new Zend_Session_Namespace('CsvImport');
        $this->_helper->db->setDefaultModelName('CsvImport_Import');
    }

    /**
     * Configure a new import.
     */
    public function indexAction()
    {
        $form = $this->_getMainForm();
        $this->view->form = $form;

        if (!$this->getRequest()->isPost()) {
            return;
        }

        if (!$form->isValid($this->getRequest()->getPost())) {
            $this->_helper->flashMessenger(__('Invalid form input. Please see errors below and try again.'), 'error');
            return;
        }

        if (!$form->csv_file->receive()) {
            $this->_helper->flashMessenger(__('Error uploading file. Please try again.'), 'error');
            return;
        }

        $filePath = $form->csv_file->getFileName();
        $columnDelimiter = $form->getValue('column_delimiter');

        $file = new CsvImport_File($filePath, $columnDelimiter);

        if (!$file->parse()) {
            $this->_helper->flashMessenger(__('Your file is incorrectly formatted.')
                . ' ' . $file->getErrorString(), 'error');
            return;
        }

        $this->session->setExpirationHops(2);
        $this->session->originalFilename = $_FILES['csv_file']['name'];
        $this->session->filePath = $filePath;

        $this->session->columnDelimiter = $columnDelimiter;
        $this->session->columnNames = $file->getColumnNames();
        $this->session->columnExamples = $file->getColumnExamples();

        $this->session->fileDelimiter = $form->getValue('file_delimiter');
        $this->session->tagDelimiter = $form->getValue('tag_delimiter');
        $this->session->elementDelimiter = $form->getValue('element_delimiter');
        $this->session->itemTypeId = $form->getValue('item_type_id');
        $this->session->itemsArePublic = $form->getValue('items_are_public');
        $this->session->itemsAreFeatured = $form->getValue('items_are_featured');
        $this->session->collectionId = $form->getValue('collection_id');

        $this->session->automapColumnNamesToElements = $form->getValue('automap_columns_names_to_elements');

        $this->session->ownerId = $this->getInvokeArg('bootstrap')->currentuser->id;

        // All is valid, so we save settings.
        set_option(CsvImport_RowIterator::COLUMN_DELIMITER_OPTION_NAME, $this->session->columnDelimiter);
        set_option(CsvImport_ColumnMap_Element::ELEMENT_DELIMITER_OPTION_NAME, $this->session->elementDelimiter);
        set_option(CsvImport_ColumnMap_Tag::TAG_DELIMITER_OPTION_NAME, $this->session->tagDelimiter);
        set_option(CsvImport_ColumnMap_File::FILE_DELIMITER_OPTION_NAME, $this->session->fileDelimiter);

        if ($form->getValue('omeka_csv_export')) {
            $this->_helper->redirector->goto('check-omeka-csv');
        }

        $this->_helper->redirector->goto('map-columns');
    }

    /**
     * Map the columns for an import.
     */
    public function mapColumnsAction()
    {
        if (!$this->_sessionIsValid()) {
            $this->_helper->flashMessenger(__('Import settings expired. Please try again.'), 'error');
            $this->_helper->redirector->goto('index');
            return;
        }

        require_once CSV_IMPORT_DIRECTORY . '/forms/Mapping.php';
        $form = new CsvImport_Form_Mapping(array(
            'itemTypeId' => $this->session->itemTypeId,
            'columnNames' => $this->session->columnNames,
            'columnExamples' => $this->session->columnExamples,
            'fileDelimiter' => $this->session->fileDelimiter,
            'tagDelimiter' => $this->session->tagDelimiter,
            'elementDelimiter' => $this->session->elementDelimiter,
            'automapColumnNamesToElements' => $this->session->automapColumnNamesToElements
        ));
        $this->view->form = $form;

        if (!$this->getRequest()->isPost()) {
            return;
        }
        if (!$form->isValid($this->getRequest()->getPost())) {
            $this->_helper->flashMessenger(__('Invalid form input. Please try again.'), 'error');
            return;
        }

        $columnMaps = $form->getColumnMaps();
        if (count($columnMaps) == 0) {
            $this->_helper->flashMessenger(__('Please map at least one column to an element, file, or tag.'), 'error');
            return;
        }

        $csvImport = new CsvImport_Import();
        foreach ($this->session->getIterator() as $key => $value) {
            $setMethod = 'set' . ucwords($key);
            if (method_exists($csvImport, $setMethod)) {
                $csvImport->$setMethod($value);
            }
        }
        $csvImport->setColumnMaps($columnMaps);
        if ($csvImport->queue()) {
            $this->_dispatchImportTask($csvImport, CsvImport_ImportTask::METHOD_START);
            $this->_helper->flashMessenger(__('Import started. Reload this page for status updates.'), 'success');
        } else {
            $this->_helper->flashMessenger(__('Import could not be started. Please check error logs for more details.'), 'error');
        }

        $this->session->unsetAll();
        $this->_helper->redirector->goto('browse');
    }

    /**
     * For import of Omeka.net CSV.
     * Check if all needed Elements are present.
     */
    public function checkOmekaCsvAction()
    {
        $elementTable = get_db()->getTable('Element');
        $skipColumns = array('itemType',
                             'collection',
                             'tags',
                             'public',
                             'featured',
                             'file');

        $skipColumnsWrapped = array();
        foreach($skipColumns as $skipColumn) {
            $skipColumnsWrapped[] = "'" . $skipColumn . "'";
        }
        $skipColumnsText = '( ' . implode(',  ', $skipColumnsWrapped) . ' )';

        if (empty($this->session->columnNames)) {
            $this->_helper->redirector->goto('index');
        }

        $hasError = false;
        foreach ($this->session->columnNames as $columnName){
            if (!in_array($columnName, $skipColumns)) {
                $data = explode(':', $columnName);
                if (count($data) != 2) {
                    $this->_helper->flashMessenger(__('Invalid column names. Column names must either be one of the following %s, or have the following format: {ElementSetName}:{ElementName}', $skipColumnsText), 'error');
                    $hasError = true;
                    break;
                }
            }
        }

        if (!$hasError) {
            foreach ($this->session->columnNames as $columnName){
                if (!in_array($columnName, $skipColumns)) {
                    $data = explode(':', $columnName);
                    //$data is like array('Element Set Name', 'Element Name');
                    $elementSetName = $data[0];
                    $elementName = $data[1];
                    $element = $elementTable->findByElementSetNameAndElementName($elementSetName, $elementName);
                    if (empty($element)) {
                        $this->_helper->flashMessenger(__('Element "%s" is not found in element set "%s"', array($elementName, $elementSetName)), 'error');
                         $hasError = true;
                    }
                }
            }
        }

        if (!$hasError) {
            $this->_helper->redirector->goto('omeka-csv');
        }
    }

    /**
     * Create and queue a new import from Omeka.net.
     */
    public function omekaCsvAction()
    {
        // specify the export format's file and tag delimiters
        // do not allow the user to specify it
        $fileDelimiter = ',';
        $tagDelimiter = ',';

        $headings = $this->session->columnNames;
        $columnMaps = array();
        foreach ($headings as $heading) {
            switch ($heading) {
                case 'collection':
                    $columnMaps[] = new CsvImport_ColumnMap_Collection($heading);
                    break;
                case 'itemType':
                    $columnMaps[] = new CsvImport_ColumnMap_ItemType($heading);
                    break;
                case 'file':
                    $columnMaps[] = new CsvImport_ColumnMap_File($heading, $fileDelimiter);
                    break;
                case 'tags':
                    $columnMaps[] = new CsvImport_ColumnMap_Tag($heading, $tagDelimiter);
                    break;
                case 'public':
                    $columnMaps[] = new CsvImport_ColumnMap_Public($heading);
                    break;
                case 'featured':
                    $columnMaps[] = new CsvImport_ColumnMap_Featured($heading);
                    break;
                default:
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
        if ($csvImport->queue()) {
            $this->_dispatchImportTask($csvImport, CsvImport_ImportTask::METHOD_START);
            $this->_helper->flashMessenger(__('Import started. Reload this page for status updates.'), 'success');
        } else {
            $this->_helper->flashMessenger(__('Import could not be started. Please check error logs for more details.'), 'error');
        }
        $this->session->unsetAll();
        $this->_helper->redirector->goto('browse');
    }

    /**
     * Browse the imports.
     */
    public function browseAction()
    {
        if (!$this->_getParam('sort_field')) {
            $this->_setParam('sort_field', 'added');
            $this->_setParam('sort_dir', 'd');
        }
        parent::browseAction();
    }

    /**
     * Undo the import.
     */
    public function undoImportAction()
    {
        $csvImport = $this->_helper->db->findById();
        if ($csvImport->queueUndo()) {
            $this->_dispatchImportTask($csvImport, CsvImport_ImportTask::METHOD_UNDO);
            $this->_helper->flashMessenger(__('Undo import started. Reload this page for status updates.'), 'success');
        } else {
            $this->_helper->flashMessenger(__('Undo import could not be started. Please check error logs for more details.'), 'error');
        }

        $this->_helper->redirector->goto('browse');
    }

    /**
     * Clear the import history.
     */
    public function clearHistoryAction()
    {
        $csvImport = $this->_helper->db->findById();
        $importedItemCount = $csvImport->getImportedItemCount();

        if ($csvImport->isUndone() ||
            $csvImport->isUndoImportError() ||
            $csvImport->isOtherError() ||
            ($csvImport->isImportError() && $importedItemCount == 0)) {
            $csvImport->delete();
            $this->_helper->flashMessenger(__('Cleared import from the history.'), 'success');
        } else {
            $this->_helper->flashMessenger(__('Cannot clear import history.'), 'error');
        }
        $this->_helper->redirector->goto('browse');
    }

    /**
     * Get the main Csv Import form.
     *
     * @return CsvImport_Form_Main
     */
    protected function _getMainForm()
    {
        require_once CSV_IMPORT_DIRECTORY . '/forms/Main.php';
        $csvConfig = $this->_getPluginConfig();
        $form = new CsvImport_Form_Main($csvConfig);
        return $form;
    }

    /**
      * Returns the plugin configuration
      *
      * @return array
      */
    protected function _getPluginConfig()
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

    /**
     * Returns whether the session is valid.
     *
     * @return boolean
     */
    protected function _sessionIsValid()
    {
        $requiredKeys = array('itemsArePublic',
                              'itemsAreFeatured',
                              'collectionId',
                              'itemTypeId',
                              'ownerId');
        foreach ($requiredKeys as $key) {
            if (!isset($this->session->$key)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Dispatch an import task.
     *
     * @param CsvImport_Import $csvImport The import object
     * @param string $method The method name to run in the CsvImport_Import object
     */
    protected function _dispatchImportTask($csvImport, $method = null)
    {
        if ($method === null) {
            $method = CsvImport_ImportTask::METHOD_START;
        }
        $csvConfig = $this->_getPluginConfig();

        $options = array(
            'importId' => $csvImport->id,
            'memoryLimit' => @$csvConfig['memoryLimit'],
            'batchSize' => @$csvConfig['batchSize'],
            'method' => $method,
        );

        $jobDispatcher = Zend_Registry::get('job_dispatcher');
        $jobDispatcher->setQueueName(CsvImport_ImportTask::QUEUE_NAME);
        try {
            $jobDispatcher->sendLongRunning('CsvImport_ImportTask',
                array(
                    'importId' => $csvImport->id,
                    'memoryLimit' => @$csvConfig['memoryLimit'],
                    'batchSize' => @$csvConfig['batchSize'],
                    'method' => $method,
                )
            );
        } catch (Exception $e) {
            $csvImport->setStatus(CsvImport_Import::OTHER_ERROR);
            throw $e;
        }
    }
}
