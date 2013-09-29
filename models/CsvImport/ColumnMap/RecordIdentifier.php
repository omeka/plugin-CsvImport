<?php
/**
 * CsvImport_ColumnMap_RecordIdentifier class
 *
 * @package CsvImport
 */
class CsvImport_ColumnMap_RecordIdentifier extends CsvImport_ColumnMap
{
    /**
     * @param string $columnName
     */
    public function __construct($columnName)
    {
        parent::__construct($columnName);
        $this->_type = CsvImport_ColumnMap::TYPE_RECORD_IDENTIFIER;
    }

    /**
     * Map a row to the identifier of a record.
     *
     * @param array $row The row to map
     * @param array $result
     * @return string Identifier of the record.
     */
    public function map($row, $result)
    {
        $result = $row[$this->_columnName];
        return $result;
    }
}
