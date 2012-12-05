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
    const TAG_DELIMTER_OPTION_NAME = 'csv_import_tag_delimiter';
    const DEFAULT_TAG_DELIMITER = ',';

    public function __construct($columnName)
    {
        parent::__construct($columnName);
        $this->_targetType = CsvImport_ColumnMap::TARGET_TYPE_TAG;
    }

    public function map($row, $result)
    {
        $rawTags = explode($this->_getDelimiter(), $row[$this->_columnName]);
        $trimmed = array_map('trim', $rawTags);
        $cleaned = array_diff($trimmed, array(''));
        $tags = array_merge($result, $cleaned);
        return $tags;
    }

    protected function _getDelimiter()
    {
        if (!($delimiter = get_option(self::TAG_DELIMTER_OPTION_NAME))) {
            $delimiter = self::DEFAULT_TAG_DELIMITER;
        }
        return $delimiter;
    }
}
