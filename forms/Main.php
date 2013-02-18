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
        $this->_fileDelimiter = CsvImport_ColumnMap_File::getDefaultFileDelimiter();
        $this->_tagDelimiter = CsvImport_ColumnMap_Tag::getDefaultTagDelimiter();
        $this->_elementDelimiter = CsvImport_ColumnMap_Element::getDefaultElementDelimiter();
        
        $this->setAttrib('id', 'csvimport');
        $this->setMethod('post');

        $this->_addFileElement();
        $values = get_db()->getTable('ItemType')->findPairsForSelectForm();
        $values = array('' => __('Select Item Type')) + $values;
        
        $this->addElement('checkbox', 'omeka_csv_export', array(
            'label' => __('Use an export from Omeka CSV Report'), 
            'description'=> __('Selecting this will override the options below.'))
        );
        
        $this->addElement('checkbox', 'automap_columns_names_to_elements', array(
            'label' => __('Automap Column Names to Elements'), 
            'description'=> __('Automatically maps columns to elements based on their column names. The column name must be in the form: <br/> {ElementSetName}:{ElementName}'),
            'value' => true)
        );

        $this->addElement('select', 'item_type_id', array(
            'label' => __('Select Item Type'),
            'multiOptions' => $values,
        ));
        $values = get_db()->getTable('Collection')->findPairsForSelectForm();
        $values = array('' => __('Select Collection')) + $values;

        $this->addElement('select', 'collection_id', array(
            'label' => __('Select Collection'),
            'multiOptions' => $values,
        ));
        $this->addElement('checkbox', 'items_are_public', array(
            'label' => __('Make All Items Public?'),
        ));
        $this->addElement('checkbox', 'items_are_featured', array(
            'label' => __('Feature All Items?'),
        ));

        $this->_addColumnDelimiterElement();
        $this->_addTagDelimiterElement();
        $this->_addFileDelimiterElement();
        $this->_addElementDelimiterElement();
        
        $this->applyOmekaStyles();
        $this->setAutoApplyOmekaStyles(false);
        
        $submit = $this->createElement('submit', 
                                       'submit', 
                                       array('label' => __('Next'),
                                             'class' => 'submit submit-medium'));
            
        
        $submit->setDecorators(array('ViewHelper',
                                      array('HtmlTag', 
                                            array('tag' => 'div', 
                                                  'class' => 'csvimportnext'))));
                                            
        $this->addElement($submit);
    }

    /**
     * Return the human readable word for a delimiter
     *
     * @param string $delimiter The delimiter
     * @return string The human readable word for the delimiter
     */
    protected function _getHumanDelimiterText($delimiter)
    {
        $delimiterText = $delimiter;
        switch ($delimiter) {
            case ',':
                $delimiterText = __('comma');
                break;
            case ';':
                $delimiterText = __('semi-colon');
                break;
            case '':
                $delimiterText = __('empty');
                break;
        }
        return $delimiterText;
    }

    /**
     * Add the column delimiter element to the form
     */
    protected function _addColumnDelimiterElement()
    {
        $delimiter = $this->_columnDelimiter;
        $humanDelimiterText = $this->_getHumanDelimiterText($delimiter);
        $this->addElement('text', 'column_delimiter', array(
            'label' => __('Choose Column Delimiter'),
            'description' => __('A single character that will be used to '
                . 'separate columns in the file (%s by default).'
                . ' Note that spaces, tabs, and other whitespace are not accepted.', $humanDelimiterText),
            'value' => $delimiter,
            'required' => true,
            'size' => '1',
            'validators' => array(
                array('validator' => 'NotEmpty',
                      'breakChainOnFailure' => true,
                      'options' => array('messages' => array(
                            Zend_Validate_NotEmpty::IS_EMPTY =>
                                __('Column delimiter cannot be whitespace and must be one character long.'),
                      )),
                ),
                array('validator' => 'StringLength', 'options' => array(
                    'min' => 1,
                    'max' => 1,
                    'messages' => array(
                        Zend_Validate_StringLength::TOO_SHORT =>
                            __('Column delimiter cannot be whitespace and must be one character long.'),
                        Zend_Validate_StringLength::TOO_LONG =>
                            __('Column delimiter cannot be whitespace and must be one character long.'),
                    ),
                )),
            ),
        ));
    }

    /**
     * Add the file delimiter element to the form
     */
    protected function _addFileDelimiterElement()
    {        
        $delimiter = $this->_fileDelimiter;
        $humanDelimiterText = $this->_getHumanDelimiterText($delimiter);
        $this->addElement('text', 'file_delimiter', array(
            'label' => __('Choose File Delimiter'),
            'description' => __('A single character that will be used to '
                . 'separate file paths or URLs within a cell (%s by default).'
                . ' If the delimiter is empty, then the whole text will be used as the file path or URL. Note that spaces, tabs, and other whitespace are not accepted.', $humanDelimiterText),
            'value' => $delimiter,
            'required' => false,
            'size' => '1',
            'validators' => array(
                
                array('validator' => 'NotEmpty',
                      'breakChainOnFailure' => true,
                      'options' => array('type' => 'space', 'messages' => array(
                            Zend_Validate_NotEmpty::IS_EMPTY =>
                                __('File delimiter cannot be whitespace, and must be empty or one character long.'),
                      )),
                ),
                
                array('validator' => 'StringLength', 'options' => array(
                    'min' => 0,
                    'max' => 1,
                    'messages' => array(
                        Zend_Validate_StringLength::TOO_SHORT =>
                            __('File delimiter cannot be whitespace, and must be empty or one character long.'),
                        Zend_Validate_StringLength::TOO_LONG =>
                            __('File delimiter cannot be whitespace, and must be empty or one character long.'),
                    ),
                )),
            ),
        ));
    }

    /**
     * Add the tag delimiter element to the form
     */    
    protected function _addTagDelimiterElement()
    {
        $delimiter = $this->_tagDelimiter;
        $humanDelimiterText = $this->_getHumanDelimiterText($delimiter);
        $this->addElement('text', 'tag_delimiter', array(
            'label' => __('Choose Tag Delimiter'),
            'description' => __('A single character that will be used to '
                . 'separate tags within a cell (%s by default).'
                . ' Note that spaces, tabs, and other whitespace are not accepted.', $humanDelimiterText),
            'value' => $delimiter,
            'required' => true,
            'size' => '1',
            'validators' => array(
                array('validator' => 'NotEmpty',
                      'breakChainOnFailure' => true,
                      'options' => array('messages' => array(
                            Zend_Validate_NotEmpty::IS_EMPTY =>
                                __('Tag delimiter cannot be whitespace and must be one character long.'),
                      )),
                ),
                array('validator' => 'StringLength', 'options' => array(
                    'min' => 1,
                    'max' => 1,
                    'messages' => array(
                        Zend_Validate_StringLength::TOO_SHORT =>
                            __('Tag delimiter cannot be whitespace and must be one character long.'),
                        Zend_Validate_StringLength::TOO_LONG =>
                            __('Tag delimiter cannot be whitespace and must be one character long.'),
                    ),
                )),
            ),
        ));
    }

    /**
     * Add the element delimiter element to the form
     */
    protected function _addElementDelimiterElement()
    {
        $delimiter = $this->_elementDelimiter;
        $humanDelimiterText = $this->_getHumanDelimiterText($delimiter);
        $this->addElement('text', 'element_delimiter', array(
            'label' => __('Choose Element Delimiter'),
            'description' => __('A single character that will be used to '
                . 'separate metadata elements within a cell (%s by default).'
                . ' If the delimiter is empty, then the whole text will be used as the element text. Note that spaces, tabs, and other whitespace are not accepted.', $humanDelimiterText),
            'value' => $delimiter,
            'required' => false,
            'size' => '1',
            'validators' => array(
                
                array('validator' => 'NotEmpty',
                      'breakChainOnFailure' => true,
                      'options' => array('type' => 'space', 'messages' => array(
                            Zend_Validate_NotEmpty::IS_EMPTY =>
                                __('Element delimiter cannot be whitespace, and must be empty or one character long.'),
                      )),
                ),
                
                array('validator' => 'StringLength', 'options' => array(
                    'min' => 0,
                    'max' => 1,
                    'messages' => array(
                        Zend_Validate_StringLength::TOO_SHORT =>
                            __('Element delimiter cannot be whitespace, and must be empty or one character long.'),
                        Zend_Validate_StringLength::TOO_LONG =>
                            __('Element delimiter cannot be whitespace, and must be empty or one character long.'),
                    ),
                )),
            ),
        ));
    }

    /**
     * Add the file element to the form
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
            'label' => __('Upload CSV File'),
            'required' => true,
            'validators' => $fileValidators,
            'destination' => $this->_fileDestinationDir,
            'description' => __("Maximum file size is %s.", $size->toString())
        ));
        $this->csv_file->addFilter($filter);
    }

    /**
     * Validate the form post
     */
    public function isValid($post)
    {
        // Too much POST data, return with an error.
        if (empty($post) && (int)$_SERVER['CONTENT_LENGTH'] > 0) {
            $maxSize = $this->getMaxFileSize()->toString();
            $this->csv_file->addError(
                __('The file you have uploaded exceeds the maximum post size '
                . 'allowed by the server. Please upload a file smaller '
                . 'than %s.', $maxSize));
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
     * If this is not set in the plugin configuration,
     * defaults to the smaller of 'upload_max_filesize' and 'post_max_size'
     * settings in php.
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

        // If the plugin max file size setting is lower, choose it as the strict max size
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
        
        if ($maxSize === false || 
            $maxSize === null || 
            $maxSize->compare($strictMaxSize) > 0) {
            $maxSize = $strictMaxSize;
        }
        
        $this->_maxFileSize = $maxSize;
    }

    /**
     * Return the max file size
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
     * Return the binary size measure
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
