<?php
/**
 * CsvImport_ColumnMap class - represents a mapping
 * from a column in a csv file to an item element, file, or tag
 *
 * @copyright Copyright 2007-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 * @package CsvImport
 */
abstract class CsvImport_ColumnMap
{
    const TYPE_ELEMENT = 'Element';
    const TYPE_TAG = 'Tag';
    const TYPE_FILE = 'File';
    const TYPE_COLLECTION = 'Collection';
    const TYPE_PUBLIC = 'Public';
    const TYPE_FEATURED = 'Featured';
    const TYPE_ITEM_TYPE = 'ItemType';

    protected $_columnName;
    protected $_type;

    /**
     * @param string $columnName
     */
    public function __construct($columnName)
    {
        $this->_columnName = $columnName;
    }

    /**
     * Returns the type of column map
     *
     * @return string The type of column map
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * Use the column mapping to convert a CSV row into a value that can be
     * parsed by insert_item() or insert_files_for_item().
     *
     * @param array $row The row in the CSV file
     * @param array $result
     * @return array An array value that can be parsed 
     * by insert_item() or insert_files_for_item()
     */
    abstract public function map($row, $result);
}
