<?php
/**
 * CsvImport_ColumnMap_Set class
 *
 * @copyright Copyright 2007-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 * @package CsvImport
 */
class CsvImport_ColumnMap_Set
{
    private $_maps = array();
    
    public function __construct(array $maps)
    {
        $this->_maps = $maps;
    }

    public function add(CsvImport_ColumnMap $map)
    {
        $this->_maps[] = $map;
    }

    public function map(array $row)
    {
        $allResults = array(
            CsvImport_ColumnMap::TARGET_TYPE_FILE => array(),
            CsvImport_ColumnMap::TARGET_TYPE_ELEMENT => array(),
            CsvImport_ColumnMap::TARGET_TYPE_TAG => array(),
            CsvImport_ColumnMap::METADATA_COLLECTION => null,
            CsvImport_ColumnMap::METADATA_FEATURED => null,
            CsvImport_ColumnMap::METADATA_ITEM_TYPE => null,
            CsvImport_ColumnMap::METADATA_PUBLIC => null
            
        );
        foreach ($this->_maps as $map) {
            $subset = $allResults[$map->getType()];
            $allResults[$map->getType()] = $map->map($row, $subset);
        }

        return $allResults;
    }
}
