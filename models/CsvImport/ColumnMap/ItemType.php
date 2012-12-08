<?php
/**
 * CsvImport_ColumnMap_ItemType class
 *
 * @copyright Copyright 2007-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 * @package CsvImport
 */
class CsvImport_ColumnMap_ItemType extends CsvImport_ColumnMap 
{
    /**
     * @param string $columnName
     */    
    public function __construct($columnName)
    {
        parent::__construct($columnName);
        $this->_type = CsvImport_ColumnMap::TYPE_ITEM_TYPE;
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
        $result = $row[$this->_columnName];
        return $result;
    }
}