<?php
/**
 * CsvImport_Form_Mapping class - represents the form on csv-import/index/map-columns.
 *
 * @copyright Copyright 2007-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 * @package CsvImport
 */

class CsvImport_Form_Mapping extends Omeka_Form
{
    private $_itemTypeId;
    private $_columnNames = array();
    private $_columnExamples = array();
    private $_fileDelimiter;
    private $_tagDelimiter;
    private $_elementDelimiter;
    private $_automapColumnNamesToElements;

    /**
     * Initialize the form.
     */
    public function init()
    {
        parent::init();
        $this->setAttrib('id', 'csvimport-mapping');
        $this->setMethod('post'); 

        $elementsByElementSetName = $this->_getElementPairs($this->_itemTypeId);
        $elementsByElementSetName = array('' => 'Select Below') 
                                  + $elementsByElementSetName;
        foreach ($this->_columnNames as $index => $colName) {
            $rowSubForm = new Zend_Form_SubForm();
            $selectElement = $rowSubForm->createElement('select',
                'element',
                array(
                    'class' => 'map-element',
                    'multiOptions' => $elementsByElementSetName,
                    'multiple' => false // see ZF-8452
                )
            );
            $selectElement->setIsArray(true);
            if ($this->_automapColumnNamesToElements) {
                $selectElement->setValue($this->_getElementIdFromColumnName($colName));
            }
            
            $rowSubForm->addElement($selectElement);
            $rowSubForm->addElement('checkbox', 'html');
            $rowSubForm->addElement('checkbox', 'tags');
            $rowSubForm->addElement('checkbox', 'file');
            $this->_setSubFormDecorators($rowSubForm);
            $this->addSubForm($rowSubForm, "row$index");
        }

        $this->addElement('submit', 'submit',
            array('label' => __('Import CSV File'),
                  'class' => 'submit submit-medium'));
    }

    protected function _getElementIdFromColumnName($columnName, $columnNameDelimiter=':')
    {
        $element = $this->_getElementFromColumnName($columnName, $columnNameDelimiter);
        if ($element) {
            return $element->id;
        } else {
            return null;
        }
    }
    
    /**
     * Return the element from the column name
     *
     * @param string $columnName The name of the column
     * @param string $columnNameDelimiter The column name delimiter
     * @return Element|null The element from the column name
     */
    protected function _getElementFromColumnName($columnName, $columnNameDelimiter=':')
    {
        $element = null;
        // $columnNameParts is an array like array('Element Set Name', 'Element Name')
        if (strlen($columnNameDelimiter) > 0) {
            if ($columnNameParts = explode($columnNameDelimiter, $columnName)) {
                if (count($columnNameParts) == 2) {
                    $elementSetName = trim($columnNameParts[0]);
                    $elementName = trim($columnNameParts[1]);
                    $element = get_db()->getTable('Element')
                                       ->findByElementSetNameAndElementName($elementSetName, $elementName);
                }
            }
        }
        return $element;
    }


    /**
     * Load the default decorators.
     */
    public function loadDefaultDecorators()
    {
        $this->setDecorators(array(
            array('ViewScript', array(
                'viewScript' => 'index/map-columns-form.php',
                'itemTypeId' => $this->_itemTypeId,
                'form' => $this,
                'columnExamples' => $this->_columnExamples,
                'columnNames' => $this->_columnNames,
            )),
        ));
    }

    /**
     * Set the column names
     * 
     * @param array $columnNames The array of column names (which are strings)
     */
    public function setColumnNames($columnNames)
    {
        $this->_columnNames = $columnNames;
    }

    /**
     * Set the column examples
     * 
     * @param array $columnExamples The array of column examples (which are strings)
     */
    public function setColumnExamples($columnExamples)
    {
        $this->_columnExamples = $columnExamples;
    }

    /**
     * Set the column examples
     * 
     * @param int $itemTypeId The id of the item type
     */
    public function setItemTypeId($itemTypeId)
    {
        $this->_itemTypeId = $itemTypeId;
    }

    /**
     * Set the element delimiter
     * 
     * @param int $elementDelimiter The element delimiter
     */
    public function setElementDelimiter($elementDelimiter)
    {
        $this->_elementDelimiter = $elementDelimiter;
    }

    /**
     * Set the file delimiter
     * 
     * @param int $fileDelimiter The file delimiter
     */
    public function setFileDelimiter($fileDelimiter)
    {
        $this->_fileDelimiter = $fileDelimiter;
    }

    /**
     * Set the tag delimiter
     * 
     * @param int $tagDelimiter The tag delimiter
     */
    public function setTagDelimiter($tagDelimiter)
    {
        $this->_tagDelimiter = $tagDelimiter;
    }
    
    /**
     * Set whether or not to automap column names to elements
     * 
     * @param boolean $flag Whether or not to automap column names to elements
     */
    public function setAutomapColumnNamesToElements($flag)
    {
        $this->_automapColumnNamesToElements = (boolean)$flag;
    }

    /**
    * Returns array of column maps
    *
    * @return array The array of column maps   
    */
    public function getColumnMaps()
    {
        $columnMaps = array();
        foreach ($this->_columnNames as $key => $colName) {
            if ($map = $this->_getColumnMap($key, $colName)) {
                if (is_array($map)) {
                    $columnMaps = array_merge($columnMaps, $map);
                } else {
                    $columnMaps[] = $map;
                }
            }
        }
        return $columnMaps;
    }

    /**
    * Returns whether a subform row contains a tag mapping
    *
    * @param int $index The subform row index
    * @return bool Whether the subform row contains a tag mapping    
    */
    protected function _isTagMapped($index)
    {
        return $this->getSubForm("row$index")->tags->isChecked();
    }

    /**
    * Returns whether a subform row contains a file mapping
    *
    * @param int $index The subform row index
    * @return bool Whether a subform row contains a file mapping    
    */
    protected function _isFileMapped($index)
    {
        return $this->getSubForm("row$index")->file->isChecked();
    }

    /**
    * Returns the element id mapped to the subform row
    *
    * @param int $index The subform row index
    * @return mixed The element id mapped to the subform row   
    */
    protected function _getMappedElementId($index)
    {
        return $this->_getRowValue($index, 'element');
    }

    /**
    * Returns a row element value
    *
    * @param int $index The subform row index
    * @param string $elementName The element name in the row
    * @return mixed The row element value     
    */
    protected function _getRowValue($index, $elementName)
    {
        return $this->getSubForm("row$index")->$elementName->getValue();
    }

    /**
    * Adds decorators to a subform.
    *
    * @param Zend_Form_SubForm $subForm The subform  
    */
    protected function _setSubFormDecorators($subForm)
    {
        // Get rid of the fieldset tag that wraps subforms by default.
        $subForm->setDecorators(array(
            'FormElements',
        ));

        // Each subform is a row in the table.
        foreach ($subForm->getElements() as $el) {
            $el->setDecorators(array(
                array('decorator' => 'ViewHelper'),
                array('decorator' => 'HtmlTag',
                      'options' => array('tag' => 'td')),
            ));
        }
    }

    /**
     * Get the mappings from one column in the CSV file.
     *
     * Some columns can have multiple mappings; these are represented
     * as an array of maps.
     *
     * @param int $index The subform row index
     * @param string $columnName The name of the CSV file column
     * @return CsvImport_ColumnMap|array|null A ColumnMap or an array of ColumnMaps
     */
    protected function _getColumnMap($index, $columnName)
    {
        $columnMap = array();

        if ($this->_isTagMapped($index)) {
            $columnMap[] = new CsvImport_ColumnMap_Tag($columnName, $this->_tagDelimiter);
        }

        if ($this->_isFileMapped($index)) {
            $columnMap[] = new CsvImport_ColumnMap_File($columnName, $this->_fileDelimiter);
        }

        $elementIds = $this->_getMappedElementId($index);
        $isHtml = $this->_getRowValue($index, 'html');
        foreach($elementIds as $elementId) {
            // Make sure to skip empty mappings
            if (!$elementId) {
                continue;
            }
            
            $elementMap = new CsvImport_ColumnMap_Element($columnName, $this->_elementDelimiter);
            $elementMap->setOptions(array('elementId' => $elementId,
                                         'isHtml' => $isHtml));
            $columnMap[] = $elementMap;
        }

        return $columnMap;
    }
    
    /**
    * Returns element selection array for an item type or Dublin Core. 
    * This is used for selecting elements in form dropdowns
    *
    * @param int|null $itemTypeId The id of the item type.  
    * If null, then it only includes Dublin Core elements
    * @return array
    */
    protected function _getElementPairs($itemTypeId=null)
    {
        $params = $itemTypeId ? array('item_type_id' => $itemTypeId)
                              : array('exclude_item_type' => true);
        return get_db()->getTable('Element')->findPairsForSelectForm($params);
    }
}
