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
	
	public $csv_file_name;
	public $item_type_id;
	public $collection_id;
	public $added; // the timestamp when the import was begun
	
	public $is_public;
	public $is_featured;
	public $status;
	public $serialized_col_nums_to_element_ids_map;

	protected $_csvFile;
	protected $_columnNumsToElementIdsMap; // maps column index numbers (starting at 0) to item type element ids
		
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
	
	public function initialize($csvFileName, $itemTypeId, $collectionId, $isPublic, $isFeatured, $columnNumsToElementIdsMap) 
	{
	     $this->setArray(array('csv_file_name' => $csvFileName, 
                                'item_type_id' => $itemTypeId, 
                                'collection_id' => $collectionId, 
                                'is_public' => $isPublic, 
                                'is_featured' => $isFeatured,
                                'status' => '', 
                                '_columnNumsToElementIdsMap' => $columnNumsToElementIdsMap)
                            );
	}
		
	protected function beforeSave()
	{
	    // serialize the column num to element id mapping
	    $this->serialized_col_nums_to_element_ids_map = serialize($this->getColumnNumsToElementIdsMap());
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
        $this->status = CSV_IMPORT_STATUS_IN_PROGRESS_IMPORT;
	    $this->save(); 
	    	    
	    // add an import object
	    $db = get_db();
	    $csvFile = $this->getCsvFile();
	    $columnNumsToElementIdsMap = $this->getColumnNumsToElementIdsMap();	    
	    	    
        if ($csvFile->isValid()) {    	    

            // define item metadata	    
            $itemMetadata = array(
                'public'         => $this->is_public, 
                'featured'       => $this->is_featured, 
                'item_type_id'   => $this->item_type_id,
                'collection_id'  => $this->collection_id
            );

            // create a map from the column index number to element set name and element 
            $colNumToElementInfoMap = array();
            $colCount = $this->_csvFile->getColumnCount();
            for($i = 0; $i < $colCount; $i++) {
                $elementId = $columnNumsToElementIdsMap[$i];
                if ($elementId) {
                    $et = $db->getTable('Element');
                    $element = $et->find($elementId);
                    $es = $db->getTable('ElementSet');
                    $elementSet = $es->find($element['element_set_id']);
                    $elementInfo = array('element_name' => $element->name, 'element_set_name' => $elementSet->name);
                    $colNumToElementInfoMap[$i] = $elementInfo;
                } else {
                    $colNumToElementInfoMap[$i] = null;
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
                $this->addItemFromRow($row, $itemMetadata, $colNumToElementInfoMap);
            }
                        
            $this->status = CSV_IMPORT_STATUS_COMPLETED_IMPORT;
            $this->save();
            return true;
            
        }
        
        $this->status = CSV_IMPORT_STATUS_IMPORT_ERROR_INVALID_CSV_FILE;
        $this->save();
        return false;
	}
	
	
	// adds an item based on the row data
	// returns inserted Item
	private function addItemFromRow($row, $itemMetadata, $colNumToElementInfoMap) 
	{
        // define the element texts for the item
        $itemElementTexts = array();
            	    
	    // process each of the columns of the row as an element text
	    $colNum = -1;
	    foreach($row as $columnName => $columnValue) {
	        $colNum++;
            
	        // make sure that the column is used
	        if ( $colNumToElementInfoMap[$colNum] === null) {
	            continue;
	        }
	        
	        // get the element name and element set name
	        $elementName = $colNumToElementInfoMap[$colNum]['element_name'];
	        $elementSetName = $colNumToElementInfoMap[$colNum]['element_set_name'];
	        
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
	     	        
	    $item = insert_item($itemMetadata, $itemElementTexts);
	    
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
	
	public function getColumnNumsToElementIdsMap() 
	{
	    if(empty($this->_columnNumsToElementIdsMap)) {
	        $this->_columnNumsToElementIdsMap = unserialize($this->serialized_col_nums_to_element_ids_map);
	    }
	    
	    return $this->_columnNumsToElementIdsMap;
	}
	
	public function getItemTypeId() 
	{
	    return $this->itemTypeId;
	}
	
	public function undoImport() 
	{
	    // first save the import object in the database
        $this->status = CSV_IMPORT_STATUS_IN_PROGRESS_UNDO_IMPORT;
	    $this->save();
	    
	    // delete imported items
        $itemLimitPerQuery = CSV_IMPORT_UNDO_IMPORT_LIMIT_PER_QUERY;        
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
        
        $this->status = CSV_IMPORT_STATUS_COMPLETED_UNDO_IMPORT;
        $this->save();
	}
	
	// returns true if the import has completed
	// else returns false
	public function isComplete() 
	{
		return (($this->status == CSV_IMPORT_STATUS_COMPLETED_IMPORT) || 
		       ($this->status == CSV_IMPORT_STATUS_COMPLETED_UNDO_IMPORT));
	}
	
	public function getStatus() 
	{
	    return $this->status;
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
}