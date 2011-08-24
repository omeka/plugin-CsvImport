<?php

class CsvImport_ColumnMap_Collection extends CsvImport_ColumnMap {
    
    public function __construct($columnName)
    {
        parent::__construct($columnName);
        $this->_targetType = CsvImport_ColumnMap::METADATA_COLLECTION;
    }

    public function map($row, $result)
    {
        $name = $row[$this->_columnName];
        $result[] = $this->getCollectionId($name);
        return $result;
    }
    
    public function getCollectionId($name)
    {
        //@TODO
        $db = get_db();
        $collection = $db->getTable('Collection')->findBy(array('name' => $name));
        return $collection->id;
    }
}