<?php
/**
 * CsvImport_Import class - represents a csv import event
 *
 * @copyright Copyright 2007-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 * @package CsvImport
 */
class CsvImport_Import extends Omeka_Record_AbstractRecord
{
    const UNDO_IMPORT_LIMIT_PER_QUERY = 100;

    const QUEUED = 'queued';
    const IN_PROGRESS = 'in_progress';
    const COMPLETED = 'completed';
    const IN_PROGRESS_UNDO = 'undo_in_progress';
    const COMPLETED_UNDO = 'completed_undo';
    const ERROR = 'error';
    const STOPPED = 'stopped';
    const PAUSED = 'paused';

    public $original_filename;
    public $file_path;
    public $file_position = 0;
    public $item_type_id;
    public $collection_id;
    public $owner_id;
    public $added;

    public $delimiter; // the column delimiter
    public $is_public;
    public $is_featured;
    public $skipped_row_count = 0;
    public $skipped_item_count = 0;
    public $status;
    public $serialized_column_maps;

    private $_csvFile;
    private $_isOmekaExport;
    private $_importedCount = 0;

    /**
     * Batch importing is not enabled by default.
     */
    private $_batchSize = 0;

    /**
     * An array of columnMaps, where each columnMap maps a column index number
     * (starting at 0) to an element, tag, and/or file.
     *
     * @var array
     */
    private $_columnMaps;

    /**
     * Sets whether the imported items are public
     *
     * @param mixed $flag A boolean representation
     */
    public function setItemsArePublic($flag)
    {
        $booleanFilter = new Omeka_Filter_Boolean;
        $this->is_public = $booleanFilter->filter($flag);
    }

    /**
     * Sets whether the imported items are featured
     *
     * @param mixed $flag A boolean representation
     */
    public function setItemsAreFeatured($flag)
    {
        $booleanFilter = new Omeka_Filter_Boolean;
        $this->is_featured = $booleanFilter->filter($flag);
    }

    /**
     * Sets the collection id of the collection to which the imported items belong
     *
     * @param int $id The collection id
     */
    public function setCollectionId($id)
    {
        $this->collection_id = (int)$id;
    }

    /**
     * Sets the column delimiter in the imported CSV file
     *
     * @param string The column delimiter of the imported CSV file
     */
    public function setColumnDelimiter($delimiter)
    {
        $this->delimiter = $delimiter;
    }

    /**
     * Sets the file path of the imported CSV file
     *
     * @param string The file path of the imported CSV file
     */
    public function setFilePath($path)
    {
        $this->file_path = $path;
    }
    
    /**
     * Sets the original filename of the imported CSV file
     *
     * @param string The original filename of the imported CSV file
     */
    public function setOriginalFilename($filename)
    {
        $this->original_filename = $filename;
    }

    /**
     * Sets the item type id of the item type of every imported item
     *
     * @param int $id The item type id
     */
    public function setItemTypeId($id)
    {
        $this->item_type_id = (int)$id;
    }

    /**
     * Sets the status of the import 
     *
     * @param string The status of the import
     */
    public function setStatus($status)
    {
        $this->status = (string)$status;
    }

    /**
     * Sets the user id of the owner of the imported items
     *
     * @param int $id The user id of the owner of the imported items
     */
    public function setOwnerId($id)
    {
        $this->owner_id = (int)$id;
    }

    /**
     * Sets whether the import is an Omeka export
     *
     * @param mixed $flag A boolean representation
     */
    public function setIsOmekaExport($flag)
    {
        $this->_isOmekaExport = $flag;
    }

    /**
     * Sets the column maps for the import
     *
     * @param CsvImport_ColumnMap_Set|array $maps The set of column maps
     * @throws InvalidArgumentException
     */
    public function setColumnMaps($maps)
    {
        if ($maps instanceof CsvImport_ColumnMap_Set) {
            $mapSet = $maps;
        } else if (is_array($maps)) {
            $mapSet = new CsvImport_ColumnMap_Set($maps);
        } else {
            throw new InvalidArgumentException("Maps must be either an "
                . "array or an instance of CsvImport_ColumnMap_Set.");
        }
        $this->_columnMaps = $mapSet;
    }

    /**
     * Set the number of items to create before pausing the import.
     *
     * Used primarily for performance reasons, i.e. long-running imports may
     * time out or hog system resources in such a way that prevents other
     * imports from running.  When used in conjunction with Omeka_Job and
     * resume(), this can be used to spawn multiple sequential jobs for a given
     * import.
     * 
     * @param int $size
     */
    public function setBatchSize($size)
    {
        $this->_batchSize = (int)$size;
    }

    /**
     * Executes before the record is deleted.
     * @param array $args
     */
    protected function beforeSave($args)
    {
        $this->serialized_column_maps = serialize($this->getColumnMaps());
    }

    /**
     * Executes after the record is deleted.
     */
    protected function afterDelete()
    {
        if (file_exists($this->file_path)) {
            unlink($this->file_path);
        }
    }

    /**
     * Returns whether there is an error with the import
     *
     * @return boolean Whether there is an error with the import
     */
    public function isError()
    {
        return $this->status == self::ERROR;
    }

    /**
     * Returns whether the import is stopped
     *
     * @return boolean Whether the import is stopped
     */
    public function isStopped()
    {
        return $this->status == self::STOPPED;
    }

    /**
     * Returns whether the import is queued
     *
     * @return boolean Whether the import is queued
     */
    public function isQueued()
    {
        return $this->status == self::QUEUED;
    }

    /**
     * Returns whether the import is finished
     *
     * @return boolean Whether the import is finished
     */
    public function isFinished()
    {
        return $this->status == self::COMPLETED;
    }

    /**
     * Returns whether the import is undone
     *
     * @return boolean Whether the import is undone
     */
    public function isUndone()
    {
        return $this->status == self::COMPLETED_UNDO;
    }

    /**
     * Imports the CSV file.  This function can only be run once.
     * To import the same csv file, you will have to
     * create another instance of CsvImport_Import and run start
     * Sets import status to self::IN_PROGRESS
     *
     * @return boolean Whether the import was successful
     */
    public function start()
    {
        $this->status = self::IN_PROGRESS;
        $this->save();
        $this->_log("Started import.");        
        $this->_importLoop($this->file_position);
        return !$this->isError();
    }


    /**
     * Finishes the import.
     * Sets import status to self::COMPLETED
     *
     * @return boolean Whether the import was successfully finished 
     */
    public function finish()
    {
        if ($this->isFinished()) {
            $this->_log("Cannot finish an import that is already finished.");
            return false;
        }
        $this->status = self::COMPLETED;
        $this->save();
        $this->_log("Finished importing $this->_importedCount items (skipped "
            . "$this->skipped_row_count rows).");
        return true;
    }

    /**
     * Resumes the import.
     * Sets import status to self::IN_PROGRESS
     *
     * @return boolean Whether the import was successful after it was resumed 
     */
    public function resume()
    {
        if (!$this->isQueued()) {
            $this->_log("Cannot resume an import that has not been queued.");
            return false;
        }
        $this->status = self::IN_PROGRESS;
        $this->save();
        $this->_log("Resumed import.");
        $this->_importLoop($this->file_position);
        return !$this->isError();
    }

    /**
     * Stops the import. 
     * Sets import status to self::STOPPED
     * 
     * @return boolean Whether the import was stopped due to an error 
     */
    public function stop()
    {
        // Anything besides 'in progress' signifies a finished import.
        if ($this->status != self::IN_PROGRESS) {
            return false;
        }
        $logMsg = "Stopped import due to error";
        if ($error = error_get_last()) {
            $logMsg .= ": " . $error['message'];
        } else {
            $logMsg .= '.';
        }
        $this->status = self::STOPPED;
        $this->save();
        $this->_log($logMsg, Zend_Log::ERR);
        return true;
    }

    /**
     * Queue the import. 
     * Sets import status to self::QUEUED
     * 
     * @return boolean Whether the import was successfully queued 
     */
    public function queue()
    {
        if ($this->status != self::IN_PROGRESS) {
            $this->_log("Cannot queue an import that is not in progress.");
            return false;
        }
        $this->status = self::QUEUED;
        $this->save();
        $this->_log("Queued import.");
        return true;
    }
    
    /**
     * Undo the import. 
     * Sets import status to self::IN_PROGRESS_UNDO and then self::COMPLETED_UNDO
     * 
     * @return boolean Whether the import was successfully undone 
     */
    public function undo()
    {
        $this->status = self::IN_PROGRESS_UNDO;
        $this->save();
        $this->_log("Started undo import.");
        $db = $this->getDb();
        $searchSql = "SELECT `item_id` FROM $db->CsvImport_ImportedItem"
                   . " WHERE `import_id` = " . (int)$this->id
                   . " LIMIT " . self::UNDO_IMPORT_LIMIT_PER_QUERY;
        $it = $this->getTable('Item');
        while ($itemIds = $db->fetchCol($searchSql)) {
            $inClause = 'IN (' . join(', ', $itemIds) . ')';
            $items = $it->fetchObjects($it->getSelect()
                                          ->where("`items`.`id` $inClause"));
            foreach ($items as $item) {
                $item->delete();
                release_object($item);
            }
            $db->delete($db->CsvImport_ImportedItem, "`item_id` $inClause");
        }
        $this->status = self::COMPLETED_UNDO;
        $this->save();
        $this->_log("Completed undo import.");
        return true;
    }

    /**
     * Returns the CsvImport_File object for the import
     * 
     * @return CsvImport_File
     */
    public function getCsvFile()
    {
        if (empty($this->_csvFile)) {
            $this->_csvFile = new CsvImport_File($this->file_path, $this->delimiter);
        }
        return $this->_csvFile;
    }

    /**
     * Returns the set of column maps for the import
     * 
     * @throws UnexpectedValueException
     * @return CsvImport_ColumnMap_Set The set of column maps for the import
     */
    public function getColumnMaps()
    {
        if ($this->_columnMaps === null) {
            $columnMaps = unserialize($this->serialized_column_maps);
            if (!($columnMaps instanceof CsvImport_ColumnMap_Set)) {
                throw new UnexpectedValueException("Column maps must be "
                    . "an instance of CsvImport_ColumnMap_Set. Instead, the "
                    . "following was given: " . var_export($columnMaps, true));
            }
            $this->_columnMaps = $columnMaps;
        }
        return $this->_columnMaps;
    }

    /**
     * Returns the number of items currently imported.  If a user undoes an import,
     * this number decreases to the number of items left to remove.
     * 
     * @return int The number of items imported minus the number of items undone
     */
    public function getImportedItemCount()
    {
        $iit = $this->getTable('CsvImport_ImportedItem');
        $sql = $iit->getSelectForCount()->where('`import_id` = ?');
        $importedItemCount = $this->getDb()->fetchOne($sql, array($this->id));
        return $importedItemCount;
    }

    /**
     * Runs the import loop
     * 
     * @param int $startAt A row number in the CSV file.
     * @throws Exception
     * @return boolean Whether the import loop was successfully run
     */
    protected function _importLoop($startAt = null)
    {
        register_shutdown_function(array($this, 'stop'));
        $rows = $this->getCsvFile()->getIterator();
        $rows->rewind();
        if ($startAt) {
            $rows->seek($startAt);
        }
        $rows->skipInvalidRows(true);
        $this->_log("Running item import loop. Memory usage: %memory%");
        while ($rows->valid()) {
            try {
                $row = $rows->current();
                $index = $rows->key();
                $this->skipped_row_count += $rows->getSkippedCount();
                if ($item = $this->_addItemFromRow($row)) {
                    release_object($item);
                } else {
                    $this->skipped_item_count++;
                }
                $this->file_position = $this->getCsvFile()->getIterator()->tell();
                if ($this->_batchSize && ($index % $this->_batchSize == 0)) {
                    $this->_log("Finished batch of $this->_batchSize "
                        . "items. Memory usage: %memory%");
                    return $this->queue();
                }
                $rows->next();
            } catch (Omeka_Job_Worker_InterruptException $e) {
                // Interruptions usually indicate that we should resume from
                // the last stopping position.
                return $this->queue();
            } catch (Exception $e) {
                $this->status = self::ERROR;
                $this->save();
                $this->_log($e, Zend_Log::ERR);
                throw $e;
            }
        }
        return $this->finish();
    }
    
    /**
     * Adds a new item based on a row string in the CSV file and returns it.
     * 
     * @param string $row A row string in the CSV file
     * @return Item|boolean The inserted item or false if an item could not be added.
     */
    protected function _addItemFromRow($row)
    {        
        $result = $this->getColumnMaps()->map($row);
        
        $tags = $result[CsvImport_ColumnMap::TARGET_TYPE_TAG];
        $itemMetadata = array(
            'public'         => $this->is_public,
            'featured'       => $this->is_featured,
            'item_type_id'   => $this->item_type_id,
            'collection_id'  => $this->collection_id,
            'tags'           => $tags,
        );
        
        // If this is coming from CSV Report, bring in the itemmetadata coming from the report
        if (!is_null($result[CsvImport_ColumnMap::METADATA_COLLECTION])) {
            $itemMetadata['collection_id'] = $result[CsvImport_ColumnMap::METADATA_COLLECTION];
        }
        if (!is_null($result[CsvImport_ColumnMap::METADATA_PUBLIC])) {
            $itemMetadata['public'] = $result[CsvImport_ColumnMap::METADATA_PUBLIC];
        }
        if (!is_null($result[CsvImport_ColumnMap::METADATA_FEATURED])) {
            $itemMetadata['featured'] = $result[CsvImport_ColumnMap::METADATA_FEATURED];
        }
        if (!empty($result[CsvImport_ColumnMap::METADATA_ITEM_TYPE])) {
            $itemMetadata['item_type_name'] = $result[CsvImport_ColumnMap::METADATA_ITEM_TYPE];
        }

        $elementTexts = $result[CsvImport_ColumnMap::TARGET_TYPE_ELEMENT];
        try {
            $item = insert_item($itemMetadata, $elementTexts);
        } catch (Omeka_Validator_Exception $e) {
            $this->_log($e, Zend_Log::ERR);
            return false;
        }

        $fileUrls = $result[CsvImport_ColumnMap::TARGET_TYPE_FILE];
        foreach ($fileUrls as $url) {
            try {
                $file = insert_files_for_item($item,
                                              'Url', 
                                              $url,
                                              array('ignore_invalid_files' => false));
            } catch (Omeka_File_Ingest_InvalidException $e) {
                $msg = "Error occurred when attempting to ingest the "
                     . "following URL as a file: '$url': "
                     . $e->getMessage();
                $this->_log($msg, Zend_Log::ERR);
                $item->delete();
                return false;
            }
            release_object($file);
        }

        // Makes it easy to unimport the item later.
        $this->_recordImportedItemId($item->id);
        return $item;
    }

    /**
     * Records that an item was successfully imported in the database
     * 
     * @param int $itemId The id of the item imported
     */
    protected function _recordImportedItemId($itemId)
    {
        $csvImportedItem = new CsvImport_ImportedItem();
        $csvImportedItem->setArray(array('import_id' => $this->id, 
                                         'item_id' => $itemId));
        $csvImportedItem->save();
        $this->_importedCount++;
    }

    /**
     * Log an import message
     * Every message will log a timestamp and the item id.
     * Messages that have %memory% will include a memory usage information.
     * 
     * @param string $msg The message to log
     * @param int $priority The priority of the message
     */
    protected function _log($msg, $priority = Zend_Log::DEBUG)
    {
        $msg = '[CsvImport][time:%time%][id:%id%] ' . $msg;
        $msg = str_replace('%time%', Zend_Date::now()->toString(), $msg);
        $msg = str_replace('%id%', strval($this->id), $msg);
        if (strpos($msg, '%memory%') !== false) {
            $msg = str_replace('%memory%', memory_get_usage(), $msg);
        }
        _log($msg, $priority);
    }
}