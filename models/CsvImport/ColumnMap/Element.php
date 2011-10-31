<?php
class CsvImport_ColumnMap_Element extends CsvImport_ColumnMap
{
    private $_elementId;
    private $_isHtml;

    public function __construct($columnName)
    {
        parent::__construct($columnName);
        $this->_targetType = CsvImport_ColumnMap::TARGET_TYPE_ELEMENT;
    }

    public function map($row, $result)
    {
        if ($this->_isHtml && class_exists('Omeka_Filter_HtmlPurifier')) {
            $filter = new Omeka_Filter_HtmlPurifier();
            $text = $filter->filter($row[$this->_columnName]);
        } else {
            $text = $row[$this->_columnName];
        }
        $result[] = array(
            'element_id' => $this->_elementId,
            'html' => $this->_isHtml ? 1 : 0,
            'text' => $text,
        );
        return $result;
    }

    public function setOptions($options)
    {
        $this->_elementId = $options['elementId'];
        $this->_isHtml = (boolean)$options['isHtml'];
    }
}
