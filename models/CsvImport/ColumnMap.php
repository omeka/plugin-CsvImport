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
    const TARGET_TYPE_ELEMENT = 'Element';
    const TARGET_TYPE_TAG = 'Tag';
    const TARGET_TYPE_FILE = 'File';
    const METADATA_COLLECTION = 'Collection';
    const METADATA_PUBLIC = 'Public';
    const METADATA_FEATURED = 'Featured';
    const METADATA_ITEM_TYPE = 'ItemType';

    protected $_columnName;
    protected $_targetType;

    /**
     * @param string $columnName
     */
    public function __construct($columnName)
    {
        $this->_columnName = $columnName;
    }

    public function getType()
    {
        return $this->_targetType;
    }

    /**
     * Use the column mapping to convert a CSV row into a value that can be
     * parsed by insert_item() or insert_files_for_item().
     */
    abstract public function map($row, $result);
}
