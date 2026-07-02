<?php
/**
 * CsvImport_ColumnMap_Tag class
 *
 * @copyright Copyright 2007-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 * @package CsvImport
 */
class CsvImport_ColumnMap_Cat extends CsvImport_ColumnMap
{

    const CAT_DELIMITER_OPTION_NAME = 'csv_import_tag_delimiter';
    const DEFAULT_CAT_DELIMITER = ',';
    private $_catDelimiter;

    /**
     * @param string $columnName
     * @param string $tagDelimiter
     */
    public function __construct($columnName, $tagDelimiter = null)
    {
        parent::__construct($columnName);
        $this->_type = CsvImport_ColumnMap::TYPE_CAT;
        if ($tagDelimiter !== null) {
            $this->_catDelimiter = $tagDelimiter;
        } else {
            $this->_catDelimiter = self::getDefaultCatDelimiter();
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
        if ($this->_catDelimiter == '') {
            $rawCats = array($row[$this->_columnName]);
        } else {
            $rawCats = explode($this->_catDelimiter, $row[$this->_columnName]);
        }

	$trimmed = array_map('trim', $rawCats);
	$cleaned = array_diff($trimmed, array(''));
        $cats = array_merge($result, $cleaned);

        return $cats;
    }

    /**
     * Return the tag delimiter.
     *
     * @return string The tag delimiter
     */
    public function getCatDelimiter()
    {
        return $this->_catDelimiter;
    }

    /**
     * Returns the default tag delimiter.
     * Uses the default tag delimiter specified in the options table if
     * available.
     *
     * @return string The default tag delimiter
     */
    static public function getDefaultCatDelimiter()
    {
        if (!($delimiter = get_option(self::CAT_DELIMITER_OPTION_NAME))) {
            $delimiter = self::DEFAULT_CAT_DELIMITER;
        }
        return $delimiter;
    }
}
