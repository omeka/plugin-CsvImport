<?php
/**
 * CsvImport_ColumnMap_MixElement class
 *
 * A merge of the classes CsvImport_ColumnMap_ExportedElement and
 * CsvImport_ColumnMap_Element.
 *
 * @package CsvImport
 */
class CsvImport_ColumnMap_MixElement extends CsvImport_ColumnMap
{
    const DEFAULT_COLUMN_NAME_DELIMITER = ':';
    const ELEMENT_DELIMITER_OPTION_NAME = 'csv_import_element_delimiter';
    const DEFAULT_ELEMENT_DELIMITER = "\r";

    private $_columnNameDelimiter;
    private $_elementDelimiter;
    private $_elementId;
    private $_isHtml;

    /**
     * @param string $columnName
     * @param string $elementDelimiter
     */
    public function __construct($columnName, $elementDelimiter = null)
    {
        parent::__construct($columnName);
        $this->_type = CsvImport_ColumnMap::TYPE_ELEMENT;
        $this->_columnNameDelimiter = self::DEFAULT_COLUMN_NAME_DELIMITER;

        $this->_elementDelimiter = ($elementDelimiter !== null)
            ? $elementDelimiter
            : self::getDefaultElementDelimiter();

        $element = $this->_getElementFromColumnName();

        $this->_elementId = !empty($element)
            ? $element->id
            : null;

        $this->_isHtml = true;
    }

    /**
     * Map a row to an array that can be parsed by insert_item() or
     * insert_files_for_item().
     *
     * @param array $row The row to map
     * @param array $result
     * @return array The result
     */
    public function map($row, $result)
    {
        if ($this->_isHtml) {
            $filter = new Omeka_Filter_HtmlPurifier();
            $text = $filter->filter($row[$this->_columnName]);
        } else {
            $text = $row[$this->_columnName];
        }

        $texts = ($this->_elementDelimiter == '')
            ? array($text)
            : explode($this->_elementDelimiter, $text);

        if ($this->_elementId) {
            foreach ($texts as $text) {
                $result[] = array(
                    'element_id' => $this->_elementId,
                    'html' => $this->_isHtml ? 1 : 0,
                    'text' => $text,
                );
            }
        }

        return $result;
    }

    /**
     * Return the element from the column name.
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
     * Sets the mapping options.
     *
     * @param array $options
     */
    public function setOptions($options)
    {
        if (isset($options['columnNameDelimiter'])) {
            $this->_columnNameDelimiter = $options['columnNameDelimiter'];
        }
        if (isset($options['elementDelimiter'])) {
            $this->_elementDelimiter = $options['elementDelimiter'];
        }
        if (isset($options['elementId'])) {
            $this->_elementId = $options['elementId'];
        }
        if (isset($options['isHtml'])) {
            $this->_isHtml = (boolean) $options['isHtml'];
        }
    }

    /**
     * Return the column name delimiter.
     *
     * @return string The column name delimiter
     */
    public function getColumnNameDelimiter()
    {
        return $this->_columnNameDelimiter;
    }

    /**
     * Return the element delimiter.
     *
     * @return string The element delimiter
     */
    public function getElementDelimiter()
    {
        return $this->_elementDelimiter;
    }

    /**
     * Return the element id.
     *
     * @return int The element id
     */
    public function getElementId()
    {
        return $this->_elementId;
    }

    /**
     * Return whether the element texts are HTML.
     *
     * @return bool Whether the element texts are HTML
     */
    public function isHtml()
    {
        return $this->_isHtml;
    }

    /**
     * Returns the default element delimiter.
     * Uses the default element delimiter specified in the options table if
     * available.
     *
     * @return string The default element delimiter
     */
    static public function getDefaultElementDelimiter()
    {
        if (!($delimiter = get_option(self::ELEMENT_DELIMITER_OPTION_NAME))) {
            $delimiter = self::DEFAULT_ELEMENT_DELIMITER;
        }
        return $delimiter;
    }
}
