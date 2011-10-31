<?php

/*
 * ExportedElement works with csv files exported from another Omeka installation using CSV Report
 * Differs from Element in the structure of the result coming from map()
 *
 * Also assumes all elements are HTML, and that they're already purified, which is
 * only slightly more naughty that the usual import, which sets isHTML at the Element level,
 * while in practice it is set on the ElementText (i.e., Item) level.
 */

class CsvImport_ColumnMap_ExportedElement extends CsvImport_ColumnMap {

    private $_elementId;

    public function __construct($columnName)
    {
        parent::__construct($columnName);
        $this->_targetType = CsvImport_ColumnMap::TARGET_TYPE_ELEMENT;
    }

    public function map($row, $result)
    {
        //@TODO: see if we can handle multiple values
        $text = $row[$this->_columnName];
        $elementTextsData = explode('^^', $text);
        $data = explode(':', $this->_columnName);
        //$data is like array('Element Set Name', 'Element Name');
        //dig up the element_id
        $elementId = get_db()->getTable('Element')->findByElementSetNameAndElementName($data[0], $data[1])->id;
        $elementData = array($data[0] => array($data[1] => array() ) );
        foreach($elementTextsData as $text) {
            $result[] = array('element_id'=>$elementId, 'html' => 1, 'text'=>$text);
        }
        $result[] = $elementData;
        return $result;
    }

    public function setOptions($options)
    {
        $this->_elementId = $options['elementId'];
    }
}
