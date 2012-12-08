<?php
/**
 * CsvImport_ColumnMap_Featured class
 *
 * @copyright Copyright 2007-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 * @package CsvImport
 */
class CsvImport_ColumnMap_Featured extends CsvImport_ColumnMap 
{
    /**
     * @param string $columnName
     */
    public function __construct($columnName)
    {
        parent::__construct($columnName);
        $this->_type = CsvImport_ColumnMap::TYPE_FEATURED;
    }

    /**
     * Map a row to whether the row corresponding to an item is featured or not
     *
     * @param array $row The row to map
     * @param array $result
     * @return bool Whether the row corresponding to an item is featured or not
     */
    public function map($row, $result)
    {
        $filter = new Omeka_Filter_Boolean;
        $flag = strtolower(trim($row[$this->_columnName]));
        if ($flag == 'no') {
            return 0;
        } else if ($flag == 'yes') {
            return 1;
        } else {
            return $filter->filter($flag);
        }
    }
}