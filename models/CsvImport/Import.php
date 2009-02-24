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
class CsvImport_Import  {
	
	protected $_csvFile;
	protected $_itemTypeId;
	protected $_collectionId;
	protected $_columnNumsToElementIdsMap; // maps column index numbers (starting at 0) to item type element ids
	
	protected $_isPublic;
	protected $_isFeatured;
		
	// returns an array of CsvImport_Import objects from the database
	public static function getImports() 
	{
		
	}
	
	public function __construct( $csvFile, $itemTypeId, $collectionId, $isPublic, $isFeatured, $columnNumsToElementIdsMap ) 
	{
	    $this->_csvFile = $csvFile;
	    $this->_itemTypeId = $itemTypeId;
	    $this->_collectionId = $collectionId;
	    $this->_columnNumsToElementIdsMap = $columnNumsToElementIdsMap;
	    $this->_isPublic = $isPublic;
	    $this->_isFeatured = $isFeatured;
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
	    $db = get_db();
	    // add an import object
        if ($this->_csvFile->isValid()) {    	    
            
            // define item metadata	    
            $itemMetadata = array(
                'public'         => $this->_isPublic, 
                'featured'       => $this->_isFeatured, 
                'item_type_id'   => $this->_itemTypeId,
                'collection_id'  => $this->_collectionId
            );

            // create a map from the column index number to element set name and element 
            $colNumToElementInfoMap = array();
            $colCount = $this->_csvFile->getColumnCount();
            for($i = 0; $i < $colCount; $i++) {
                $elementId = $this->_columnNumsToElementIdsMap[$i];
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
            $rows = $this->_csvFile->getRows();
            $i = 0;
            foreach($rows as $row) {
                $i++;
                // ignore the first row because it is the header
                if ($i == 1) {
                    continue;
                }
                $this->addItemFromRow($row, $itemMetadata, $colNumToElementInfoMap);
            }
            return true;
        }
        return false;
	}
	
	
	// adds an item based on the row data
	// returns inserted Item
	private function addItemFromRow($row, $itemMetadata, $colNumToElementInfoMap) 
	{
                
        // define the element texts for the item
        $itemElementTexts = array();
            	    
	    // process each of the columns of the row
	    $colNum = -1;
	    foreach($row as $columnName => $columnValue) {
	        $colNum++;
            
	        // make sure that the column is used
	        if ( $colNumToElementInfoMap[$colNum] === null) {
	            continue;
	        }
	        
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
	    
	    // record the inserted item's id so that you can uninstall it later
	    $this->recordInsertedItemId($item->id);
	    
	    // return the inserted item
	    return $item;
	}
	
	private function recordInsertedItemId($itemId) 
	{
	    
	}
		
	public function getCsvFile() 
	{
		return $this->_csvFile;
	}
	
	public function getColumnNumsToElementIdsMap() 
	{
	    return $this->_columnNumsToElementIdsMap;
	}
	
	public function getItemTypeId() 
	{
	    return $this->itemTypeId;
	}
	
	public function undoImport() 
	{
	    // remove items
	    
	    // remove item elements
	}
	
	// returns true if the imported has completed
	// else returns false
	public function isComplete() 
	{
		
	}
	
	public function getStatus() 
	{
	
	}

	// if import status is completed, returns a list of items ids imported
	// else returns an empty array
	
	public function getItemsIds() 
	{
	
	}
}