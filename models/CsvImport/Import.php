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
	
	// returns an array of CsvImport_Import objects from the database
	public static function list() {
		
	}
	
	public function __construct( $csvFile, $itemType, $columnToElementMap ) {
	
	}
	
	/**
   * Imports the csv file.  This function can only be run once.
   * To import the same csv file, you will have to
   * create another instance of CsvImport_Import and run doImport
   * 
   * @return void
   */
	public function doImport() {
		
	}
	
	public function getCsvFile() {
		
	}
	
	public function getColumnToElementMap() {
	
	}
	
	public function getItemType() {
	
	}
	
	public function undoImport() {
	
	}
	
	// returns true if the imported has completed
	// else returns false
	public function isComplete() {
		
	}
	
	public function getStatus() {
	
	}

	// if import status is completed, returns a list of items imported
	// else returns an empty array
	
	public function getItems() {
	
	}
}