<?php
/**
 * CsvImport_ColumnMap_ItemType class
 *
 * @copyright Copyright 2007-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 * @version    $Id:$
 * @package CsvImport
 * @author CHNM
 */
 
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