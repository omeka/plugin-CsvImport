<?php
/**
 * CsvImport_ColumnMap_UpdateIdentifier class
 *
 * @package CsvImport
 */
class CsvImport_ColumnMap_UpdateIdentifier extends CsvImport_ColumnMap
{
    /**
     * @param string $columnName
     */
    public function __construct($columnName)
    {
        parent::__construct($columnName);
        $this->_type = CsvImport_ColumnMap::TYPE_UPDATE_IDENTIFIER;
    }

    /**
     * Map a row to the update identifier of a record.
     *
     * @param array $row The row to map
     * @param array $result
     * @return string Identifier to select to update a record.
     */
    public function map($row, $result)
    {
        $result = $row[$this->_columnName];
        return $result;
    }
}
