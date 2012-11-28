<?php
/**
 * CsvImport_ColumnMap_Element class
 *
 * @copyright Copyright 2007-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 * @package CsvImport
 */
 
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
