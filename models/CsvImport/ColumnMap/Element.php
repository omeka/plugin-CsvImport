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
    const ELEMENT_DELIMITER_OPTION_NAME = 'csv_import_element_delimiter';
    const DEFAULT_ELEMENT_DELIMITER = '';

    private $_isHtml;
    private $_elementId;
    private $_elementDelimiter;

    /**
     * @param string $columnName
     * @param string $elementDelimiter
     */
    public function __construct($columnName, $elementDelimiter = null)
    {
        parent::__construct($columnName);
        $this->_type = CsvImport_ColumnMap::TYPE_ELEMENT;
        if ($elementDelimiter !== null) {
            $this->_elementDelimiter = $elementDelimiter;
        } else {
            $this->_elementDelimiter = self::getDefaultElementDelimiter();
        }
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
        if ($this->_elementDelimiter == '') {
            $texts = array($text);
        } else {
            $texts = explode($this->_elementDelimiter, $text);
        }
        foreach($texts as $text) {
            $result[] = array(
                'element_id' => $this->_elementId,
                'html' => $this->_isHtml ? 1 : 0,
                'text' => $text,
            );
        }
        return $result;
    }

    /**
     * Sets the mapping options.
     *
     * @param array $options
     */
    public function setOptions($options)
    {
        $this->_elementId = $options['elementId'];
        $this->_isHtml = (boolean)$options['isHtml'];
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