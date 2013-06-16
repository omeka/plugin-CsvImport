<?php
/**
 * CsvImport_ImportedItem class - represents an imported item for a specific csv
 * import event
 *
 * @copyright Copyright 2007-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 * @package CsvImport
 */
class CsvImport_ImportedItem extends Omeka_Record_AbstractRecord
{
    public $item_id;
    public $source_item_id;
    public $import_id;

    /**
     * Returns the item id for the imported item.
     *
     * @return int The item id.
     */
    public function getItemId()
    {
        return $this->item_id;
    }

    /**
     * Returns the source item id for the imported item.
     *
     * @return int The source item id.
     */
    public function getSourceItemId()
    {
        return $this->source_item_id;
    }

    /**
     * Returns the import id for the imported item.
     *
     * @return int The import id.
     */
    public function getImportId()
    {
        return $this->import_id;
    }
}
