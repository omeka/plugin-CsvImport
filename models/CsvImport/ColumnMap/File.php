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
        $result[] = trim($row[$this->_columnName]);
        return $result;
    }

    private function _getFileUrls($row, $maps)
    {
        $urls = array();
        foreach ($maps as $index => $isMapped) {
            if ($isMapped) {
                $url = trim($row[$index]);
                if ($url) {
                    $urls[] = $url;
                }
            }
        }
        return $urls;
    }

}
