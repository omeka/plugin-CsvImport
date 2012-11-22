<?php
/**
 * CsvImport_ColumnMap_Collection class
 *
 * @copyright Copyright 2007-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 * @version    $Id:$
 * @package CsvImport
 * @author CHNM
 */
class CsvImport_ColumnMap_Collection extends CsvImport_ColumnMap {
    
    public function __construct($columnName)
    {
        parent::__construct($columnName);
        $this->_targetType = CsvImport_ColumnMap::METADATA_COLLECTION;
    }

    public function map($row, $result)
    {
        $name = $row[$this->_columnName];
        $result = $this->getCollectionId($name);
        return $result;
    }
    
    public function getCollectionId($name)
    {
        //CollectionTable doesn't have a findBy name filter 1.5-dev
        
        $db = get_db();
        $collectionTable = $db->getTable('Collection');
        $select = $collectionTable->getSelect();
        $select->where('`name` = ?', $name);
        $collection = $collectionTable->fetchObject($select);
        if(! $collection) {
            _log("Collection not found. Collections must be created with identical names prior to import", Zend_Log::NOTICE);
            return false;
        }
        return $collection->id;
    }
}