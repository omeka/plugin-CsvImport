<?php
class CsvImport_ColumnMap_ItemType extends CsvImport_ColumnMap {
    
    public function __construct($columnName)
    {
        parent::__construct($columnName);
        $this->_targetType = CsvImport_ColumnMap::METADATA_ITEM_TYPE;
    }

    public function map($row, $result)
    {
        $result = $row[$this->_columnName];
        return $result;
    }
}