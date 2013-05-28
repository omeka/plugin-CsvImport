<?php
/**
 * CsvImport_ColumnMap_Format class
 *
 * @package CsvImport
 */
class CsvImport_ColumnMap_Format extends CsvImport_ColumnMap
{
    /**
     * @param string $columnName
     */
    public function __construct($columnName)
    {
        parent::__construct($columnName);
        $this->_type = CsvImport_ColumnMap::TYPE_FORMAT;
    }

    /**
     * Map a row to whether the row corresponding to an item or a file or all.
     *
     * @param array $row The row to map
     * @param array $result
     * @return string Whether the row corresponding to an item, a file or all.
     */
    public function map($row, $result)
    {
        $result = $row[$this->_columnName];
        return $result;
    }
}
