<?php
/**
 * CsvImport_Import - represents a csv import event
 * 
 * @version $Id$ 
 * @package CsvImport
 * @author CHNM
 * @copyright Center for History and New Media, 2008
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 **/
class CsvImport_Import extends Omeka_Record { 
	
	const UNDO_IMPORT_LIMIT_PER_QUERY = 100;
    
	const STATUS_IN_PROGRESS_IMPORT = 'Import In Progress';
    const STATUS_COMPLETED_IMPORT = 'Completed Import';
    const STATUS_IN_PROGRESS_UNDO_IMPORT = 'Undo Import In Progress';
    const STATUS_COMPLETED_UNDO_IMPORT = 'Completed Undo Import';
    const STATUS_IMPORT_ERROR_INVALID_CSV_FILE = 'Import Error: Invalid CSV File';
    const STATUS_IMPORT_ERROR_INVALID_ITEM = 'Import Error: Invalid Item';
    const STATUS_IMPORT_ERROR_INVALID_FILE_DOWNLOAD = 'Import Error: Invalid File Download';
		
	public $csv_file_name;
	public $item_type_id;
	public $collection_id;
	public $added; // the timestamp when the import was begun
	
	public $item_count; // the total number of items in the csv file
	public $is_public;
	public $is_featured;
	public $status;
	public $error_details;
	public $serialized_column_maps;

    public $stop_import_if_file_download_error;

	protected $_csvFile;
	protected $_columnMaps; // an array of columnMaps, where each columnMap maps a column index number (starting at 0) to an element, tag, and/or file.
		
	/**
    * Gets an array of all of the CsvImport_Import objects from the database
    * 
    * @return array
    */
	public static function getImports()
	{
		$db = get_db();
		$it = $db->getTable('CsvImport_Import');
		$s = $it->getSelect()->where('1')->order('added DESC');
		$imports = $it->fetchObjects($s, array());
        return $imports;
	}
	
	public function initialize($csvFileName, $itemTypeId, $collectionId, $isPublic, $isFeatured, $stopImportIfFileDownloadError, $columnMaps) 
	{
	     $this->setArray(array('csv_file_name' => $csvFileName, 
                                'item_type_id' => $itemTypeId, 
                                'collection_id' => $collectionId, 
                                'is_public' => $isPublic, 
                                'is_featured' => $isFeatured,
                                'status' => '',
                                'error_details' => '',
                                'stop_import_if_file_download_error' => $stopImportIfFileDownloadError,
                                '_columnMaps' => $columnMaps)
                            );
	}
		
	protected function beforeSave()
	{
	    if ($this->item_count == null) {
	        $this->item_count = 0;
	    }
	    // serialize the column num to element id mapping
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
	    // first save the import object in the database
        $this->status = self::STATUS_IN_PROGRESS_IMPORT;
        $this->item_count = $this->getItemCount();
	    $this->save(); 
	    	    
	    // add an import object
	    $db = get_db();
	    $csvFile = $this->getCsvFile();
	    $columnMaps = $this->getColumnMaps();
	    	    
        if ($csvFile->isValid()) {    	    

            // define item metadata	    
            $itemMetadata = array(
                'public'         => $this->is_public, 
                'featured'       => $this->is_featured, 
                'item_type_id'   => $this->item_type_id,
                'collection_id'  => $this->collection_id
            );
            
            // create a map from the column index number to an array of element set name and element name pairs 
            $colNumToElementInfosMap = array();

            $colNumMapsToTag = array();
    
            foreach($columnMaps as $columnMap) {
                $columnIndex = $columnMap->getColumnIndex();
                               
                // check to see if the column maps to a tag
                $mapsToTag = $colNumMapsToTag[$columnIndex];
                if (empty($mapsToTag)) {
                    $colNumMapsToTag[$columnIndex] = false;  
                }
                if ($columnMap->mapsToTag()) {
                    $colNumMapsToTag[$columnIndex] = true;                    
                }
                
                // check to see if the column maps to a file
                $mapsToFile = $colNumMapsToFile[$columnIndex];
                if (empty($mapsToFile)) {
                    $colNumMapsToFile[$columnIndex] = false;  
                }
                if ($columnMap->mapsToFile()) {
                    $colNumMapsToFile[$columnIndex] = true;                    
                }
                 
                // build element infos from the column map
                $elementIds = $columnMap->getElementIds();
                foreach($elementIds as $elementId) {
                    $et = $db->getTable('Element');
                    $element = $et->find($elementId);
                    $es = $db->getTable('ElementSet');
                    $elementSet = $es->find($element['element_set_id']);
                    $elementInfo = array('element_name' => $element->name, 'element_set_name' => $elementSet->name);
                    
                    // make sure that an array of element infos exists for the column index
                    if (!is_array($colNumToElementInfosMap[$columnIndex])) {
                        $colNumToElementInfosMap[$columnIndex] = array();
                    }
                    
                    // add the element info if it does not already exist for the column index 
                    if (!in_array($elementInfo, $colNumToElementInfosMap[$columnIndex])) {
                        $colNumToElementInfosMap[$columnIndex][] = $elementInfo;                                            
                    }
                }                                          
            }
                                    
            // add item from each row
            $rows = $csvFile->getRows();
            $i = 0;
            foreach($rows as $row) {
                $i++;
                
                // ignore the first row because it is the header
                if ($i == 1) {
                    continue;
                }
                
                //insert the item 
                try {
                    $item = $this->addItemFromRow($row, $itemMetadata, $colNumToElementInfosMap, $colNumMapsToTag, $colNumMapsToFile);
                } catch (Exception $e) {
                    $this->status = self::STATUS_IMPORT_ERROR_INVALID_ITEM;
            	    $this->error_details = $e->getMessage();
                }
                release_object($item);
                
                // stop import on error
                if ($this->hasErrorStatus()) {
        	        $this->save();
        	        return false;
        	    }
            }
                        
            $this->status = self::STATUS_COMPLETED_IMPORT;
            $this->save();
            return true;
            
        }
        
        $this->status = self::STATUS_IMPORT_ERROR_INVALID_CSV_FILE;
        $this->save();
        return false;
	}
	
	
	// adds an item based on the row data
	// returns inserted Item
	private function addItemFromRow(&$row, &$itemMetadata, &$colNumToElementInfosMap, &$colNumMapsToTag, &$colNumMapsToFile) 
	{
        // define the element texts for the item
        $itemElementTexts = array();
            	    
	    // process each of the columns of the row
	    $tags = array();
	    $urlsForFiles = array();
	    $colIndex = -1;
	    for($colIndex = 0; $colIndex < count($row); $colIndex++) {
	        
	        $columnName = $row[$colIndex]['name'];
	        $columnValue = $row[$colIndex]['value'];
            
	        // process the elements
	        if ( $colNumToElementInfosMap[$colIndex] !== null) {
    	        $elementInfos = $colNumToElementInfosMap[$colIndex];
    	        foreach($elementInfos as &$elementInfo) {

    	            // get the element name and element set name
        	        $elementName = $elementInfo['element_name'];
        	        $elementSetName = $elementInfo['element_set_name'];

        	        // make sure the element set exists
        	        if(!isset($itemElementTexts[$elementSetName])) {
        	            $itemElementTexts[$elementSetName] = array();
        	        }

        	        // make sure the element name exists
        	        if(!isset($itemElementTexts[$elementSetName][$elementName])) {
        	            $itemElementTexts[$elementSetName][$elementName] = array();
        	        }

        	        // add the element text from the column value
        	        $itemElementText = array('text' => $columnValue, 'html' => false);
        	        array_push($itemElementTexts[$elementSetName][$elementName], $itemElementText);

    	        }
	        }
	        
	        // process the tags
	        if ($colNumMapsToTag[$colIndex]) {
                $rawTags = explode(',', $columnValue);
                foreach($rawTags as &$rawTag) {
                    $tag = trim($rawTag);
                    if (!in_array($tag, $tags)) {
                        $tags[] = $tag;
                    }
                }
            }
            	        
	        // process the files
	        if ($colNumMapsToFile[$colIndex]) {
                $urlForFile = trim($columnValue);
                if (!in_array($urlForFile, $urlsForFiles)) {
                    $urlsForFiles[] = $urlForFile;
                }        
            }        
	    }
	    
	    // update the file metadata
	    if (count($urlsForFiles) > 0) {
	        $fileMetadata = array('file_transfer_type' => 'Url', 'files' => $urlsForFiles);
	    } else {
	        $fileMetadata = array();
	    }
	    
	    // update the tags metadata
	    if (count($tags) > 0) {
    	    $itemMetadata['tags'] = implode(',', $tags);	        
	    }
	    
	    // insert the item
	    $item = insert_item($itemMetadata, $itemElementTexts);
	    
	    // insert the files for the item, releasing the file from memory each time
	    foreach($urlsForFiles as $urlForFile) {
	        $url = array();
	        $url[] = $urlForFile;
    	    try {
    	        $files = insert_files_for_item($item, $fileMetadata['file_transfer_type'], $url, array('ignore_invalid_files' => (!$this->stop_import_if_file_download_error)));
    	    } catch(Exception $e) {
    	        if (!($e instanceof Omeka_File_Ingest_InvalidException) || $this->stop_import_if_file_download_error) {
    	            $this->status = self::STATUS_IMPORT_ERROR_INVALID_FILE_DOWNLOAD;
            	    $this->error_details = $urlForFile . "\n" . $e->getMessage();
            	    release_object($files);
    	            break;
    	        }
    	    }
    	    release_object($files);
	    }
	    
	    // reset the tags metadata back to null for the next row
	    $itemMetadata['tags'] = null;
	    
	    // record the imported item id so that you can uninstall the item later
	    $this->recordImportedItemId($item->id);
	    
	    // return the inserted item
	    return $item;
	}
	
	private function recordImportedItemId($itemId) 
	{
	    // create a new imported item record
	    $csvImportedItem = new CsvImport_ImportedItem();
	    $csvImportedItem->setArray(array('import_id' => $this->id, 'item_id' => $itemId));
	    $csvImportedItem->save();
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
	
	public function getItemTypeId() 
	{
	    return $this->itemTypeId;
	}
	
	public function undoImport() 
	{
	    // first save the import object in the database
        $this->status = self::STATUS_IN_PROGRESS_UNDO_IMPORT;
	    $this->save();
	    
	    // delete imported items
        $itemLimitPerQuery = self::UNDO_IMPORT_LIMIT_PER_QUERY;        
	    $db = get_db();
        $iit = $db->getTable('CsvImport_ImportedItem');
        $it = $db->getTable('Item');
    
        $sql = $iit->getSelect()->where('`import_id` = ?')->limit($itemLimitPerQuery);
        $importedItems = $iit->fetchObjects($sql, array($this->id));
        
        while(count($importedItems) > 0) {
            foreach($importedItems as $importedItem) {
                $itemId = $importedItem->getItemId();
                $item = $it->find($itemId);
                if ($item) {
                    $item->delete();
                }
                $importedItem->delete();
            }
            $sql = $iit->getSelect()->where('`import_id` = ?')->limit($itemLimitPerQuery);
            $importedItems = $iit->fetchObjects($sql, array($this->id));        
        } 
        
        $this->status = self::STATUS_COMPLETED_UNDO_IMPORT;
        $this->save();
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
	
	// returns the number of items currently imported.  
	// if a user undoes an import, it decreases the count to show the number of items left to unimport
	public function getImportedItemCount()
	{
	    $db = get_db();
        $iit = $db->getTable('CsvImport_ImportedItem');
        $sql = $iit->getSelectForCount()->where('`import_id` = ?');
        $importedItemCount = $db->fetchOne($sql, array($this->id));
        return $importedItemCount;
	}
	
	// returns the total number of items to import
	public function getItemCount() 
	{
	    if ($this->item_count == 0) {
	        $this->item_count = $this->getCsvFile()->getRowCount() - 1; // remove 1 for the header row
	    }
	    return $this->item_count;
	}
}