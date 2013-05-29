<?php
/**
 * CsvImport_Form_Main class - represents the form on csv-import/index/index.
 *
 * @copyright Copyright 2007-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 * @package CsvImport
 */

class CsvImport_Form_Main extends Omeka_Form
{
    private $_columnDelimiter;
    private $_fileDelimiter;
    private $_tagDelimiter;
    private $_elementDelimiter;
    private $_fileDestinationDir;
    private $_maxFileSize;

    /**
     * Initialize the form.
     */
    public function init()
    {
        parent::init();

        $this->_columnDelimiter = CsvImport_RowIterator::getDefaultColumnDelimiter();
        $this->_elementDelimiter = CsvImport_ColumnMap_Element::getDefaultElementDelimiter();
        $this->_tagDelimiter = CsvImport_ColumnMap_Tag::getDefaultTagDelimiter();
        $this->_fileDelimiter = CsvImport_ColumnMap_File::getDefaultFileDelimiter();

        $this->setAttrib('id', 'csvimport');
        $this->setMethod('post');

        $this->_addFileElement();

        $this->addElement('radio', 'format', array(
            // 'label' => __('Import type'),
            'description'=> __('Choose the type of record (the format of your file) you want to import.'),
            'multiOptions' => array(
                'Csv Report' => __('Export from Omeka CSV Report'),
                'Item' => __('Items'),
                'File' => __('Files metadata'),
            ),
            'required' => TRUE,
        ));

        $values = get_db()->getTable('ItemType')->findPairsForSelectForm();
        $values = array('' => __('Select item type')) + $values;
        $this->addElement('select', 'item_type_id', array(
            'label' => __('Select item type'),
            'multiOptions' => $values,
        ));

        $values = get_db()->getTable('Collection')->findPairsForSelectForm();
        $values = array('' => __('Select collection')) + $values;
        $this->addElement('select', 'collection_id', array(
            'label' => __('Select collection'),
            'multiOptions' => $values,
        ));

        $this->addElement('checkbox', 'items_are_public', array(
            'label' => __('Make all items public?'),
        ));

        $this->addElement('checkbox', 'items_are_featured', array(
            'label' => __('Feature all items?'),
        ));

        $this->addElement('checkbox', 'elements_are_html', array(
            'label' => __('All imported elements are html?'),
            'description' => 'This checkbox allows to set default format of all imported elements as raw text or html.',
            'value' => get_option('csv_import_html_elements'),
        ));

        $this->addElement('checkbox', 'automap_columns', array(
            'label' => __('Automap column names to elements'),
            'description'=> __('Automatically maps columns to elements based on their column names.')
                . ' ' . __('The column name must be in the form: {ElementSetName}:{ElementName}'),
            'value' => get_option('csv_import_automap_columns'),
        ));

        $this->_addColumnDelimiterElement();
        $this->_addElementDelimiterElement();
        $this->_addTagDelimiterElement();
        $this->_addFileDelimiterElement();

        $this->applyOmekaStyles();
        $this->setAutoApplyOmekaStyles(false);

        $submit = $this->createElement(
            'submit', 'submit',
            array('label' => __('Next'),
                'class' => 'submit submit-medium'));


        $submit->setDecorators(
            array('ViewHelper',
                array('HtmlTag',
                    array('tag' => 'div',
                        'class' => 'csvimportnext'))));

        $this->addElement($submit);
    }

    /**
     * Add the file element to the form.
     */
    protected function _addFileElement()
    {
        $size = $this->getMaxFileSize();
        $byteSize = clone $this->getMaxFileSize();
        $byteSize->setType(Zend_Measure_Binary::BYTE);

        $fileValidators = array(
            new Zend_Validate_File_Size(array(
                'max' => $byteSize->getValue())),
            new Zend_Validate_File_Count(1),
        );
        if ($this->_requiredExtensions) {
            $fileValidators[] =
                new Omeka_Validate_File_Extension($this->_requiredExtensions);
        }
        if ($this->_requiredMimeTypes) {
            $fileValidators[] =
                new Omeka_Validate_File_MimeType($this->_requiredMimeTypes);
        }
        // Random filename in the temporary directory.
        // Prevents race condition.
        $filter = new Zend_Filter_File_Rename($this->_fileDestinationDir
                    . '/' . md5(mt_rand() + microtime(true)));
        $this->addElement('file', 'csv_file', array(
            'label' => __('Upload CSV file'),
            'required' => true,
            'validators' => $fileValidators,
            'destination' => $this->_fileDestinationDir,
            'description' => __("Maximum file size is %s.", $size->toString())
        ));
        $this->csv_file->addFilter($filter);
    }

    /**
     * Return the human readable word for a delimiter if any, or the delimiter.
     *
     * @param string $delimiter The delimiter
     * @return string The human readable word for the delimiter if any, or the
     * delimiter itself.
     */
    protected function _getHumanDelimiterText($delimiter)
    {
        $delimitersList = CsvImport_IndexController::getDelimitersList();

        return in_array($delimiter, $delimitersList) ?
            array_search($delimiter, $delimitersList) :
            $delimiter;
    }

    /**
     * Return the list of standard delimiters for drop-down menu.
     *
     * @return array The list of standard delimiters
     */
    protected function _getDelimitersMenu()
    {
        $delimitersListKeys = array_keys(CsvImport_IndexController::getDelimitersList());
        $values = array_combine($delimitersListKeys, $delimitersListKeys);
        $values['custom'] = 'custom';
        return $values;
    }

    /**
     * Add the column delimiter element to the form.
     */
    protected function _addColumnDelimiterElement()
    {
        $delimiter = $this->_columnDelimiter;
        $humanDelimiterText = $this->_getHumanDelimiterText($delimiter);

        $delimitersList = CsvImport_IndexController::getDelimitersList();
        $delimiterCurrent = in_array($delimiter, $delimitersList) ?
            array_search($delimiter, $delimitersList) :
            'custom';

        // Two elements are needed to select the delimiter.
        // First, a list for special characters.
        $values = $this->_getDelimitersMenu();
        unset($values['empty']);
        unset($values['double space']);
        $this->addElement('select', 'column_delimiter_name', array(
            'label' => __('Choose column delimiter'),
            'description'=> __('A single character that will be used to separate columns in the file (the previously used "%s" by default).', $humanDelimiterText)
                . '<br />' . __("If you want a specific one, choose 'Custom' in the drop-down list and fill the text field with a single character."),
            'multiOptions' => $values,
            'value' => $delimiterCurrent,
        ));

        // Second, a field to let user chooses a custom delimiter.
        // TODO Autoset according to previous element or display the element only if the column delimiter is "custom".
        $this->addElement('text', 'column_delimiter', array(
            'value' => $delimiter,
            'required' => false,
            'size' => '1',
            'validators' => array(
                // A second check is done in method isValid() with minimum of 1.
                array('validator' => 'StringLength', 'options' => array(
                    'min' => 0,
                    'max' => 1,
                    'messages' => array(
                        Zend_Validate_StringLength::TOO_LONG =>
                            __('Column delimiter must be one character long.'),
                    ),
                )),
            ),
        ));
    }

    /**
     * Add the file delimiter element to the form.
     */
    protected function _addFileDelimiterElement()
    {
        $delimiter = $this->_fileDelimiter;
        $humanDelimiterText = $this->_getHumanDelimiterText($delimiter);

        $delimitersList = CsvImport_IndexController::getDelimitersList();
        $delimiterCurrent = in_array($delimiter, $delimitersList) ?
            array_search($delimiter, $delimitersList) :
            'custom';

        // Two elements are needed to select the delimiter.
        // First, a list for special characters.
        $values = $this->_getDelimitersMenu();
        $this->addElement('select', 'file_delimiter_name', array(
            'label' => __('Choose file delimiter'),
            'description' => __('This delimiter will be used to separate file paths or URLs within a cell (the previously used "%s" by default).', $humanDelimiterText)
                . '<br />' . __('If the delimiter is empty, then the whole text will be used as the file path or URL.')
                . '<br />' . __("If you want a specific one, choose 'Custom' in the drop-down list and fill the text field.")
                . ' ' . __('To use more than one character is allowed.'),
            'multiOptions' => $values,
            'value' => $delimiterCurrent,
            'required' => false,
        ));
        // Second, a field to let user chooses a custom delimiter.
        // TODO Autoset according to previous element or display and check the element only if the file delimiter is "custom".
        $this->addElement('text', 'file_delimiter', array(
            'value' => $delimiter,
            'required' => false,
            'size' => '40',
        ));
    }

    /**
     * Add the tag delimiter element to the form.
     */
    protected function _addTagDelimiterElement()
    {
        $delimiter = $this->_tagDelimiter;
        $humanDelimiterText = $this->_getHumanDelimiterText($delimiter);

        $delimitersList = CsvImport_IndexController::getDelimitersList();
        $delimiterCurrent = in_array($delimiter, $delimitersList) ?
            array_search($delimiter, $delimitersList) :
            'custom';

        // Two elements are needed to select the delimiter.
        // First, a list for special characters.
        $values = $this->_getDelimitersMenu();
        $this->addElement('select', 'tag_delimiter_name', array(
            'label' => __('Choose tag delimiter'),
            'description' => __('This delimiter will be used to separate tags within a cell (the previously used "%s" by default).', $humanDelimiterText)
                . '<br />' . __('If the delimiter is empty, then the whole text will be used.')
                . '<br />' . __("If you want a specific one, choose 'Custom' in the drop-down list and fill the text field.")
                . ' ' . __('To use more than one character is allowed.'),
            'multiOptions' => $values,
            'value' => $delimiterCurrent,
            'required' => false,
        ));
        // Second, a field to let user chooses a custom delimiter.
        // TODO Autoset according to previous element or display and check the element only if the tag delimiter is "custom".
        $this->addElement('text', 'tag_delimiter', array(
            'value' => $delimiter,
            'required' => false,
            'size' => '40',
        ));
    }

    /**
     * Add the element delimiter element to the form.
     */
    protected function _addElementDelimiterElement()
    {
        $delimiter = $this->_elementDelimiter;
        $humanDelimiterText = $this->_getHumanDelimiterText($delimiter);

        $delimitersList = CsvImport_IndexController::getDelimitersList();
        $delimiterCurrent = in_array($delimiter, $delimitersList) ?
            array_search($delimiter, $delimitersList) :
            'custom';

        // Two elements are needed to select the delimiter.
        // First, a list for special characters.
        $values = $this->_getDelimitersMenu();
        $this->addElement('select', 'element_delimiter_name', array(
            'label' => __('Choose element delimiter'),
            'description' => __('This delimiter will be used to separate metadata elements within a cell (the previously used "%s" by default).', $humanDelimiterText)
                . '<br />' . __('If the delimiter is empty, then the whole text will be used.')
                . '<br />' . __("If you want a specific one, choose 'Custom' in the drop-down list and fill the text field.")
                . ' ' . __('To use more than one character is allowed.'),
            'multiOptions' => $values,
            'value' => $delimiterCurrent,
            'required' => false,
        ));
        // Second, a field to let user chooses a custom delimiter.
        // TODO Autoset according to previous element or display and check the element only if the file delimiter is "custom".
        $this->addElement('text', 'element_delimiter', array(
            'value' => $delimiter,
            'required' => false,
            'size' => '40',
        ));
    }

    /**
     * Validate the form post.
     */
    public function isValid($post)
    {
        $isValid = true;
        // Too much POST data, return with an error.
        if (empty($post) && (int)$_SERVER['CONTENT_LENGTH'] > 0) {
            $maxSize = $this->getMaxFileSize()->toString();
            $this->csv_file->addError(
                __('The file you have uploaded exceeds the maximum post size '
                . 'allowed by the server. Please upload a file smaller '
                . 'than %s.', $maxSize));
            $isValid = false;
        }

        // Check custom delimiters.
        if ('column_delimiter_name' == 'custom') {
            if (strlen($column_delimiter) != 1) {
                $this->column_delimiter->addError(
                    __('The custom delimiter you choose cannot be whitespace and must be one character long.'));
                $isValid = false;
            }
        }

        if (!$isValid) {
            return false;
        }

        return parent::isValid($post);
    }

    /**
     * Set the column delimiter for the form.
     *
     * @param string $delimiter The column delimiter
     */
    public function setColumnDelimiter($delimiter)
    {
        $this->_columnDelimiter = $delimiter;
    }

    /**
     * Set the file delimiter for the form.
     *
     * @param string $delimiter The file delimiter
     */
    public function setFileDelimiter($delimiter)
    {
        $this->_fileDelimiter = $delimiter;
    }

    /**
     * Set the tag delimiter for the form.
     *
     * @param string $delimiter The tag delimiter
     */
    public function setTagDelimiter($delimiter)
    {
        $this->_tagDelimiter = $delimiter;
    }

    /**
     * Set the element delimiter for the form.
     *
     * @param string $delimiter The element delimiter
     */
    public function setElementDelimiter($delimiter)
    {
        $this->_elementDelimiter = $delimiter;
    }

    /**
     * Set the file destination for the form.
     *
     * @param string $dest The file destination
     */
    public function setFileDestination($dest)
    {
        $this->_fileDestinationDir = $dest;
    }

    /**
     * Set the maximum size for an uploaded CSV file.
     *
     * If this is not set in the plugin configuration, defaults to the smaller
     * of 'upload_max_filesize' and 'post_max_size' settings in php.
     *
     * If this is set but it exceeds the aforementioned php setting, the size
     * will be reduced to that lower setting.
     *
     * @param string|null $size The maximum file size
     */
    public function setMaxFileSize($size = null)
    {
        $postMaxSize = $this->_getBinarySize(ini_get('post_max_size'));
        $fileMaxSize = $this->_getBinarySize(ini_get('upload_max_filesize'));

        // Start with the max size as the lower of the two php ini settings.
        $strictMaxSize = $postMaxSize->compare($fileMaxSize) > 0
            ? $fileMaxSize
            : $postMaxSize;

        // If the plugin max file size setting is lower, choose it as the strict
        // max size.
        $pluginMaxSizeRaw = trim(get_option(CsvImportPlugin::MEMORY_LIMIT_OPTION_NAME));
        if ($pluginMaxSizeRaw != '') {
            if ($pluginMaxSize = $this->_getBinarySize($pluginMaxSizeRaw)) {
                $strictMaxSize = $strictMaxSize->compare($pluginMaxSize) > 0
                    ? $pluginMaxSize
                    : $strictMaxSize;
            }
        }

        if ($size === null) {
            $maxSize = $this->_maxFileSize;
        } else {
            $maxSize = $this->_getBinarySize($size);
        }

        if ($maxSize === false
            || $maxSize === null
            || $maxSize->compare($strictMaxSize) > 0) {
            $maxSize = $strictMaxSize;
        }

        $this->_maxFileSize = $maxSize;
    }

    /**
     * Return the max file size.
     *
     * @return string The max file size
     */
    public function getMaxFileSize()
    {
        if (!$this->_maxFileSize) {
            $this->setMaxFileSize();
        }
        return $this->_maxFileSize;
    }

    /**
     * Return the binary size measure.
     *
     * @return Zend_Measure_Binary The binary size
     */
    protected function _getBinarySize($size)
    {
        if (!preg_match('/(\d+)([KMG]?)/i', $size, $matches)) {
            return false;
        }

        $sizeType = Zend_Measure_Binary::BYTE;

        $sizeTypes = array(
            'K' => Zend_Measure_Binary::KILOBYTE,
            'M' => Zend_Measure_Binary::MEGABYTE,
            'G' => Zend_Measure_Binary::GIGABYTE,
        );

        if (count($matches) == 3 && array_key_exists($matches[2], $sizeTypes)) {
            $sizeType = $sizeTypes[$matches[2]];
        }

        return new Zend_Measure_Binary($matches[1], $sizeType);
    }
}
