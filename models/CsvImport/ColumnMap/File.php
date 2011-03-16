<?php

class CsvImport_ColumnMap_File extends CsvImport_ColumnMap
{
    public function __construct($columnName)
    {
        parent::__construct($columnName);
        $this->_targetType = CsvImport_ColumnMap::TARGET_TYPE_FILE;
    }

    public function map($row, $result)
    {
        $url = trim($row[$this->_columnName]);
        if ($url) {
            $result[] = $url;
        }
        return $result;
    }
}
