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
    private $_columnDelimiter = ',';
    private $_fileDestinationDir;
    private $_maxFileSize;

    public function init()
    {
        parent::init();
        $this->setAttrib('id', 'csvimport');
        $this->setMethod('post');

        $this->_addFileElement();
        $values = get_db()->getTable('ItemType')->findPairsForSelectForm();
        $values = array('' => __('Select Item Type')) + $values;
        
        $this->addElement('checkbox', 'omeka_csv_export', array(
            'label' => __('Use an export from Omeka CSV Report'), 
            'description'=> __('Selecting this will override the options below.'))
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

        switch ($this->_columnDelimiter) {
            case ',':
                $delimiterText = 'comma';
                break;
            case ';':
                $delimiterText = 'semi-colon';
                break;
            default:
                $delimiterText = $this->_columnDelimiter;
                break;
        }
        $this->addElement('text', 'column_delimiter', array(
            'label' => __('Choose Column Delimiter'),
            'description' => __('A single character that will be used to '
                . 'separate columns in the file (%s by default).'
                . ' Note that tabs and whitespace are not accepted.', $delimiterText),
            'value' => $this->_columnDelimiter,
            'required' => true,
            'size' => '1',
            'validators' => array(
                array('validator' => 'NotEmpty',
                      'breakChainOnFailure' => true,
                      'options' => array('messages' => array(
                            Zend_Validate_NotEmpty::IS_EMPTY =>
                                __('Column delimiter must be one character long.'),
                      )),
                ),
                array('validator' => 'StringLength', 'options' => array(
                    'min' => 1,
                    'max' => 1,
                    'messages' => array(
                        Zend_Validate_StringLength::TOO_SHORT =>
                            __('Column delimiter must be one character long.'),
                        Zend_Validate_StringLength::TOO_LONG =>
                            __('Column delimiter must be one character long.'),
                    ),
                )),
            ),
        ));
        $this->applyOmekaStyles();
        $this->setAutoApplyOmekaStyles(false);
        
        $submit = $this->createElement('submit', 
                                    'submit', 
                                    array('label' => __('Next'),
                                          'class' => 'submit submit-medium'));
            
        
        $submit->setDecorators(array('ViewHelper',
                                      array('HtmlTag', array('tag' => 'div', 'class' => 'csvimportnext'))));
                                            
        $this->addElement($submit);
    }

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

    private function _addFileElement()
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

    public function setColumnDelimiter($delimiter)
    {
        $this->_columnDelimiter = $delimiter;
    }

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
     */
    public function setMaxFileSize($size = null)
    {
        if (!$this->_maxFileSize) {
            $postMaxSize = $this->_getSizeMeasure(ini_get('post_max_size'));
            $fileMaxSize = $this->_getSizeMeasure(ini_get('upload_max_filesize'));

            // Start with the max size as the lower of the two php ini settings.
            $maxSize = $postMaxSize->compare($fileMaxSize) > 0
                     ? $fileMaxSize
                     : $postMaxSize;
        } else {
            $maxSize = $this->_maxFileSize;
        }

        if ($size) {
            $newSize = $this->_getSizeMeasure($size);
            if ($pluginIniSize->compare($maxSize) > 0) {
                $maxSize = $newSize;
            }
        }
        $this->_maxFileSize = $maxSize;
    }

    public function getMaxFileSize()
    {
        if (!$this->_maxFileSize) {
            $this->setMaxFileSize();
        }
        return $this->_maxFileSize;
    }

    private function _getSizeMeasure($size)
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
