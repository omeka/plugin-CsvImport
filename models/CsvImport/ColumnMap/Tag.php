<?php
class CsvImport_ColumnMap_Tag extends CsvImport_ColumnMap
{
    const DEFAULT_DELIMITER = ',';

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

    private function _getDelimiter()
    {
        // New in 1.4.
        if (!($delimiter = get_option('tag_delimiter'))) {
            $delimiter = self::DEFAULT_DELIMITER;
        }
        return $delimiter;
    }
}
