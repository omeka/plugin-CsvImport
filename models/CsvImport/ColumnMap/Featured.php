<?php
class CsvImport_ColumnMap_Featured extends CsvImport_ColumnMap {
    
    public function __construct($columnName)
    {
        parent::__construct($columnName);
        $this->_targetType = CsvImport_ColumnMap::METADATA_FEATURED;
    }

    public function map($row, $result)
    {
        $result = $row[$this->_columnName];
        return $result;
    }
}