<?php
/**
 * CsvImport_ColumnMap_Tag class
 *
 * @copyright Copyright 2007-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 * @package CsvImport
 */
class CsvImport_ColumnMap_Tag extends CsvImport_ColumnMap
{
    const TAG_DELIMITER_OPTION_NAME = 'csv_import_tag_delimiter';
    const DEFAULT_TAG_DELIMITER = ',';

    private $_tagDelimiter;

    /**
     * @param string $columnName
     * @param string $tagDelimiter
     */
    public function __construct($columnName, $tagDelimiter = null)
    {
        parent::__construct($columnName);
        $this->_type = CsvImport_ColumnMap::TYPE_TAG;
        if ($tagDelimiter !== null) {
            $this->_tagDelimiter = $tagDelimiter;
        } else {
            $this->_tagDelimiter = self::getDefaultTagDelimiter();
        }
    }

    /**
     * Map a row to an array of tags.
     *
     * @param array $row The row to map
     * @param array $result
     * @return array The array of tags
     */
    public function map($row, $result)
    {
        if ($this->_tagDelimiter == '') {
            $rawTags = array($row[$this->_columnName]);
        } else {
            $rawTags = explode($this->_tagDelimiter, $row[$this->_columnName]);
        }
        $trimmed = array_map('trim', $rawTags);
        $cleaned = array_diff($trimmed, array(''));
        $tags = array_merge($result, $cleaned);
        return $tags;
    }

    /**
     * Return the tag delimiter.
     *
     * @return string The tag delimiter
     */
    public function getTagDelimiter()
    {
        return $this->_tagDelimiter;
    }

    /**
     * Returns the default tag delimiter.
     * Uses the default tag delimiter specified in the options table if
     * available.
     *
     * @return string The default tag delimiter
     */
    static public function getDefaultTagDelimiter()
    {
        if (!($delimiter = get_option(self::TAG_DELIMITER_OPTION_NAME))) {
            $delimiter = self::DEFAULT_TAG_DELIMITER;
        }
        return $delimiter;
    }
}
