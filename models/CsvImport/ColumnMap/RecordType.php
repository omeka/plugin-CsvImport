<?php
/**
 * CsvImport_ColumnMap_RecordType class
 *
 * @package CsvImport
 */
class CsvImport_ColumnMap_RecordType extends CsvImport_ColumnMap
{
    /**
     * @param string $columnName
     */
    public function __construct($columnName)
    {
        parent::__construct($columnName);
        $this->_type = CsvImport_ColumnMap::TYPE_RECORD_TYPE;
    }

    /**
     * Map a row to the type of a record.
     *
     * @param array $row The row to map
     * @param array $result
     * @return string Type of the record.
     */
    public function map($row, $result)
    {
        $result = $row[$this->_columnName];
        return $result;
    }
}
