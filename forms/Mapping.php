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

    public function setColumnNames($columnNames)
    {
        $this->_columnNames = $columnNames;
    }

    public function setColumnExamples($columnExamples)
    {
        $this->_columnExamples = $columnExamples;
    }

    public function setItemTypeId($itemTypeId)
    {
        $this->_itemTypeId = $itemTypeId;
    }

    public function setElementDelimiter($elementDelimiter)
    {
        $this->_elementDelimiter = $elementDelimiter;
    }

    public function setFileDelimiter($fileDelimiter)
    {
        $this->_fileDelimiter = $fileDelimiter;
    }

    public function setTagDelimiter($tagDelimiter)
    {
        $this->_tagDelimiter = $tagDelimiter;
    }

    public function getMappings()
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

    private function _isTagMapped($index)
    {
        return $this->getSubForm("row$index")->tags->isChecked();
    }

    private function _isFileMapped($index)
    {
        return $this->getSubForm("row$index")->file->isChecked();
    }

    private function _getMappedElementId($index)
    {
        return $this->_getRowValue($index, 'element');
    }

    private function _getRowValue($row, $name)
    {
        return $this->getSubForm("row$row")->$name->getValue();
    }

    private function _setSubFormDecorators($subForm)
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
     * @param int $index
     * @param string $columnName
     * @return CsvImport_ColumnMap|array|null A ColumnMap or an array of ColumnMaps
     */
    private function _getColumnMap($index, $columnName)
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
