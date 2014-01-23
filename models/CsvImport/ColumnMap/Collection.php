<?php
/**
 * CsvImport_ColumnMap_Collection class
 *
 * @copyright Copyright 2007-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 * @package CsvImport
 */
class CsvImport_ColumnMap_Collection extends CsvImport_ColumnMap 
{    
    /**
     * @param string $columnName
     */
    public function __construct($columnName)
    {
        parent::__construct($columnName);
        $this->_type = CsvImport_ColumnMap::TYPE_COLLECTION;
    }

    /**
     * Map a row to an array that can be parsed by
     * insert_item() or insert_files_for_item().
     *
     * @param array $row The row to map
     * @param array $result
     * @return array The result
     */
    public function map($row, $result)
    {
        $result = null;
        $collectionTitle = $row[$this->_columnName];
        if ($collectionTitle != '') {
            $collection = $this->_getCollectionByTitle($collectionTitle);
            if ($collection) {
                $result = $collection->id;
            }
        }
        return $result;
    }
    
    /**
     * Return a collection by its title
     *
     * @param string $name The collection name
     * @return Collection The collection
     */    
    protected function _getCollectionByTitle($name)
    {
        $db = get_db();
        
        $elementTable = $db->getTable('Element');
        $element = $elementTable->findByElementSetNameAndElementName('Dublin Core', 'Title');
        
        $collectionTable = $db->getTable('Collection');
        $select = $collectionTable->getSelect();
        $select->joinInner(array('s' => $db->ElementText), 
                           's.record_id = collections.id', array());
        $select->where("s.record_type = 'Collection'");
        $select->where("s.element_id = ?", $element->id);
        $select->where("s.text = ?", $name);
                
        $collection = $collectionTable->fetchObject($select);
        if (!$collection) {
            _log("Collection not found. Collections must be created with identical names prior to import", Zend_Log::NOTICE);
            return false;
        }
        return $collection;
    }
}
