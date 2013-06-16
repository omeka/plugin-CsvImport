<?php
/**
 * CsvImport_ColumnMap_SourceItemId class
 *
 * @package CsvImport
 */
class CsvImport_ColumnMap_SourceItemId extends CsvImport_ColumnMap
{
    /**
     * @param string $columnName
     */
    public function __construct($columnName)
    {
        parent::__construct($columnName);
        $this->_type = CsvImport_ColumnMap::TYPE_SOURCE_ITEM_ID;
    }

    /**
     * Map a row to the source item id of a record.
     *
     * @param array $row The row to map
     * @param array $result
     * @return string Source item id of the record.
     */
    public function map($row, $result)
    {
        $result = $row[$this->_columnName];
        return $result;
    }
}
