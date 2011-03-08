<?php
/**
 * CsvImport_Import - represents a csv import event
 * 
 * @version $Id$ 
 * @package CsvImport
 * @author CHNM
 * @copyright Center for History and New Media, 2008-2011
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 **/
class CsvImport_Import extends Omeka_Record 
{ 

    const UNDO_IMPORT_LIMIT_PER_QUERY = 100;

    const STATUS_IN_PROGRESS_IMPORT = 'Import In Progress';
    const STATUS_COMPLETED_IMPORT = 'Completed Import';
    const STATUS_IN_PROGRESS_UNDO_IMPORT = 'Undo Import In Progress';
    const STATUS_COMPLETED_UNDO_IMPORT = 'Completed Undo Import';
    const STATUS_IMPORT_ERROR_INVALID_CSV_FILE = 
        'Import Error: Invalid CSV File';
    const STATUS_IMPORT_ERROR_INVALID_ITEM = 'Import Error: Invalid Item';
    const STATUS_IMPORT_ERROR_INVALID_FILE_DOWNLOAD = 
        'Import Error: Invalid File Download';

    public $csv_file_name;
    public $item_type_id;
    public $collection_id;
    public $added; 

    public $item_count; 
    public $is_public;
    public $is_featured;
    public $status;
    public $error_details;
    public $serialized_column_maps;

    public $stop_on_file_error;

    protected $_csvFile;

    /**
     * An array of columnMaps, where each columnMap maps a column index number 
     * (starting at 0) to an element, tag, and/or file.
     *
     * @var array 
     */
    protected $_columnMaps; 

    public function initialize($csvFileName, $itemTypeId, $collectionId, 
        $isPublic, $isFeatured, $stopOnError, $columnMaps) 
    {
        $this->setArray(array('csv_file_name' => $csvFileName, 
            'item_type_id' => $itemTypeId, 
            'collection_id' => $collectionId, 
            'is_public' => $isPublic, 
            'is_featured' => $isFeatured,
            'status' => '',
            'error_details' => '',
            'stop_on_file_error' => 
                $stopOnError,
            '_columnMaps' => $columnMaps)
        );

        $this->item_count = $this->getItemCount();                            
    }

    protected function beforeSave()
    {
        if (!$this->item_count) {
            $this->item_count = 0;
        }
        $this->serialized_column_maps = serialize($this->getColumnMaps());
    }

    /**
     * Imports the csv file.  This function can only be run once.
     * To import the same csv file, you will have to
     * create another instance of CsvImport_Import and run doImport
     * 
     * @return boolean true if the import is successful, else false
     */
    public function doImport() 
    { 
        $this->status = self::STATUS_IN_PROGRESS_IMPORT;
        $this->item_count = $this->getItemCount();
        $this->forceSave(); 

        $csvFile = $this->getCsvFile();

        if (!$csvFile->isValid()) {
            $this->status = self::STATUS_IMPORT_ERROR_INVALID_CSV_FILE;
            $this->forceSave();
            return false;
        }            

        $itemMetadata = array(
            'public'         => $this->is_public, 
            'featured'       => $this->is_featured, 
            'item_type_id'   => $this->item_type_id,
            'collection_id'  => $this->collection_id
        );

        $maps = $this->getMapsForImport();

        $rows = $csvFile->getRows();
        // ignore the first row because it is the header
        $i = 1;
        foreach($rows as $row) {
            $i++;

            try {
                $item = $this->addItemFromRow($row, $itemMetadata, $maps);
            } catch (Exception $e) {
                $this->status = self::STATUS_IMPORT_ERROR_INVALID_ITEM;
                $this->error_details = $e->getMessage();
            }
            release_object($item);

            if ($this->hasErrorStatus()) {
                $this->forceSave();
                return false;
            }
        }

        $this->status = self::STATUS_COMPLETED_IMPORT;
        $this->forceSave();
        return true;
    }


    // adds an item based on the row data
    // returns inserted Item
    private function addItemFromRow($row, $itemMetadata, $maps) 
    {
        $itemElementTexts = array();
        $tags = array();
        $fileUrls = array();
        $colIndex = -1;
        for($colIndex = 0; $colIndex < count($row); $colIndex++) {
            $columnValue = $row[$colIndex]['value'];
            
            if (isset($maps['elements'][$colIndex])) {
                foreach($maps['elements'][$colIndex] as $elementText) {
                    $elementText['text'] = $columnValue;
                    array_push($itemElementTexts, 
                        $elementText);
                }
            }

            if (isset($maps['tags'][$colIndex])) {
                $rawTags = explode(',', $columnValue);
                array_walk($rawTags, 'trim');
                $tags = array_merge($rawTags, $tags);
            }

            if (isset($maps['files'][$colIndex])) {
                $url = trim($columnValue);
                $fileUrls = array_merge(array($url), $fileUrls);
            }        
        }

        $item = insert_item(array_merge(array('tags' => $tags), $itemMetadata),
            $itemElementTexts);

        foreach($fileUrls as $url) {
            try {
                $file = insert_files_for_item($item, 
                    'Url', $url, 
                    array(
                        'ignore_invalid_files' => !$this->stop_on_file_error
                    )
                );
            } catch(Exception $e) {
                if (!($e instanceof Omeka_File_Ingest_InvalidException) || 
                    $this->stop_on_file_error) {
                    $this->status 
                        = self::STATUS_IMPORT_ERROR_INVALID_FILE_DOWNLOAD;
                    $this->error_details = $url . "\n" 
                        . $e->getMessage();
                    release_object($file);
                    break;
                }
            }
            release_object($file);
        }

        // Makes it easy to unimport the item later.
        $this->recordImportedItemId($item->id);
        return $item;
    }

    private function recordImportedItemId($itemId) 
    {
        $csvImportedItem = new CsvImport_ImportedItem();
        $csvImportedItem->setArray(array('import_id' => $this->id, 'item_id' => 
            $itemId));
        $csvImportedItem->forceSave();
    }

    public function getCsvFile() 
    {
        if (empty($this->_csvFile)) {
            $this->_csvFile = new CsvImport_File($this->csv_file_name);
        }
        return $this->_csvFile;
    }

    public function getColumnMaps() 
    {
        if(empty($this->_columnMaps)) {
            $this->_columnMaps = unserialize($this->serialized_column_maps);
        }

        return $this->_columnMaps;
    }

    public function undoImport() 
    {
        $this->status = self::STATUS_IN_PROGRESS_UNDO_IMPORT;
        $this->forceSave();

        $itemLimitPerQuery = self::UNDO_IMPORT_LIMIT_PER_QUERY;        
        $iit = $this->getTable('CsvImport_ImportedItem');
        $it = $this->getTable('Item');

        $sql = $iit->getSelect()->where('`import_id` 
            = ?')->limit($itemLimitPerQuery);
        $importedItems = $iit->fetchObjects($sql, array($this->id));

        while(count($importedItems) > 0) {
            foreach($importedItems as $importedItem) {
                $item = $it->find($importedItem->getItemId());
                if ($item) {
                    $item->delete();
                }
                $importedItem->delete();
            }
            $sql = $iit->getSelect()->where('`import_id` 
                = ?')->limit($itemLimitPerQuery);
            $importedItems = $iit->fetchObjects($sql, array($this->id));        
        } 

        $this->status = self::STATUS_COMPLETED_UNDO_IMPORT;
        $this->forceSave();
    }

    // returns true if the import has completed
    // else returns false
    public function isComplete() 
    {
        return (($this->status == self::STATUS_COMPLETED_IMPORT) || 
            ($this->status == self::STATUS_COMPLETED_UNDO_IMPORT));
    }

    public function getStatus() 
    {
        return $this->status;
    }

    public function hasErrorStatus()
    {
        return (($this->status == self::STATUS_IMPORT_ERROR_INVALID_CSV_FILE) ||
            ($this->status == self::STATUS_IMPORT_ERROR_INVALID_ITEM) || 
            ($this->status == self::STATUS_IMPORT_ERROR_INVALID_FILE_DOWNLOAD));
    }

    public function getErrorDetails()
    {
        return $this->error_details;
    }

    // returns the number of items currently imported.  if a user undoes an 
    // import, it decreases the count to show the number of items left to 
    // unimport
    public function getImportedItemCount()
    {
        $iit = $this->getTable('CsvImport_ImportedItem');
        $sql = $iit->getSelectForCount()->where('`import_id` = ?');
        $importedItemCount = $this->getDb()->fetchOne($sql, array($this->id));
        return $importedItemCount;
    }

    // returns the total number of items to import
    public function getItemCount() {
        if ($this->item_count == 0) {
            // One extra for the header row.
            $this->item_count = $this->getCsvFile()->getRowCount() - 1;
        }
        return $this->item_count;
    }

    public function getProgress()
    {
        $importedItemCount = $this->getImportedItemCount();
        $itemCount = $this->getItemCount();
        if ($itemCount != -1) {
            $progress = $importedItemCount . ' / ' . $itemCount;
        } else {
            $progress = 'NA';
        }
        return $progress;
    }

    public function getMapsForImport()
    {
        // create a map from the column index number to an array of element 
        // infos, where each element info contains the element set name, 
        // element name, and whether the element text is html or not 
        $maps = array(
            'elements' => array(),
            'files' => array(),
            'tags' => array(),
        );
        foreach($this->getColumnMaps() as $columnMap) {
            $index = $columnMap->getColumnIndex();

            $maps['tags'][$index] = $columnMap->mapsToTag();                    
            if ($columnMap->mapsToFile()) {
                $maps['files'][$index] = true;
            }
            $maps['elements'][$index] = $columnMap->getElementMetadata();
        }
        return $maps;
    }
}
