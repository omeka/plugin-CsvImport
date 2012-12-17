<?php
/**
 * CsvImport_ColumnMap_ExportedElement class - works with csv files exported from another Omeka
 * installation using CSV Report.  Differs from CsvImport_ColumnMap_Element in the structure of 
 * the result coming from map(). Also assumes all elements are HTML, and that they're already 
 * purified, which is only slightly more naughty that the usual import, which sets isHTML at 
 * the Element level, while in practice it is set on the ElementText (i.e., Item) level.
 *
 * @copyright Copyright 2007-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 * @package CsvImport
 */
class CsvImport_ColumnMap_ExportedElement extends CsvImport_ColumnMap 
{
    const DEFAULT_COLUMN_NAME_DELIMITER = ':';
    const DEFAULT_ELEMENT_DELIMITER = '^^';

    private $_columnNameDelimiter;
    private $_elementDelimiter;    
    private $_elementId;
    private $_isHtml;

    /**
     * @param string $columnName
     */
    public function __construct($columnName)
    {
        parent::__construct($columnName);
        $this->_type = CsvImport_ColumnMap::TYPE_ELEMENT;
        $this->_columnNameDelimiter = self::DEFAULT_COLUMN_NAME_DELIMITER;
        $this->_elementDelimiter = self::DEFAULT_ELEMENT_DELIMITER;
        $this->_isHtml = true;
        
        $element = $this->_getElementFromColumnName();
        if ($element) {
            $this->_elementId = $element->id;
        } else {
            $this->_elementId = null;
        }
    }

    /**
     * Map a row to an array that can be parsed by
     * insert_item() or insert_files_for_item().
     *
     * @param array $row The row to map
     * @param array $result
     * @return array The result
     */
    public function map($row, $result)
    {
        $filter = new Omeka_Filter_HtmlPurifier();
        $text = $filter->filter($row[$this->_columnName]);
        if ($this->_elementDelimiter == '') {
            $texts = array($text);
        } else {
            $texts = explode($this->_elementDelimiter, $text);    
        }

        if ($this->_elementId) {
            foreach($texts as $text) {
                $result[] = array('element_id' => $this->_elementId, 
                                  'html' => $this->_isHtml ? 1 : 0, 
                                  'text' => $text);
            }
        }

        return $result;
    }

    /**
     * Return the element from the column name
     *
     * @return Element|null The element from the column name
     */
    protected function _getElementFromColumnName()
    {
        $element = null;
        // $columnNameParts is an array like array('Element Set Name', 'Element Name')
        if (strlen($this->_columnNameDelimiter) > 0) {
            if ($columnNameParts = explode($this->_columnNameDelimiter, $this->_columnName)) {
                if (count($columnNameParts) == 2) {
                    $elementSetName = $columnNameParts[0];
                    $elementName = $columnNameParts[1];
                    $element = get_db()->getTable('Element')
                                       ->findByElementSetNameAndElementName($elementSetName, $elementName);
                }
            }
        }
        return $element;
    }

    /**
     * Sets the mapping options
     *
     * @param array $options
     */
    public function setOptions($options)
    {
        $this->_columnNameDelimiter = $options['columnNameDelimiter'];
        $this->_elementDelimiter = $options['elementDelimiter'];
        $this->_elementId = $options['elementId'];
        $this->_isHtml = $options['isHtml'];
    }
    
    /**
     * Return the element delimiter
     *
     * @return string The element delimiter
     */
    public function getElementDelimiter()
    {
        return $this->_elementDelimiter;
    }
    
    /**
     * Return the column name delimiter
     *
     * @return string The column name delimiter
     */
    public function getColumnNameDelimiter()
    {
        return $this->_columnNameDelimiter;
    }
    
    /**
     * Return the element id
     *
     * @return int The element id
     */
    public function getElementId()
    {
        return $this->_elementId;
    }
    
    /**
     * Return whether the element texts are HTML 
     *
     * @return bool Whether the element texts are HTML
     */
    public function isHtml()
    {
        return $this->_isHtml;
    }
}
