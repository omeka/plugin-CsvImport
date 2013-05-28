<?php
/**
 * CsvImport_ColumnMap_FileUrl class
 * Differs from CsvImport_ColumnMap_File because it should be a unique name and
 * it can't be empty.
 *
 * @package CsvImport
 */
class CsvImport_ColumnMap_FileUrl extends CsvImport_ColumnMap
{
    /**
     * @param string $columnName
     */
    public function __construct($columnName)
    {
        parent::__construct($columnName);
        $this->_type = CsvImport_ColumnMap::TYPE_FILE_URL;
    }

    /**
     * Map a row to an array that can be parsed by insert_item() or
     * insert_files_for_item().
     *
     * @param array $row The row to map
     * @param array $result
     * @return array The result
     */
    public function map($row, $result)
    {
        $fileUrl = $row[$this->_columnName];
        if ($fileUrl) {
            $result = $fileUrl;
        } else {
            $result = null;
        }
        return $result;
    }
}
