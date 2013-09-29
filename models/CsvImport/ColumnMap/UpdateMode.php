<?php
/**
 * CsvImport_ColumnMap_UpdateMode class
 *
 * @package CsvImport
 */
class CsvImport_ColumnMap_UpdateMode extends CsvImport_ColumnMap
{
    /**
     * @param string $columnName
     */
    public function __construct($columnName)
    {
        parent::__construct($columnName);
        $this->_type = CsvImport_ColumnMap::TYPE_UPDATE_MODE;
    }

    /**
     * Map a row to the update mode for a record.
     *
     * @param array $row The row to map
     * @param array $result
     * @return string Update mode for a record.
     */
    public function map($row, $result)
    {
        $result = $row[$this->_columnName];
        return $result;
    }
}
