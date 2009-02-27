<?php
/**
 * CsvImport_ImportedItem - represents an imported item for a specific csv import event
 * 
 * @version $Id$ 
 * @package CsvImport
 * @author CHNM
 * @copyright Center for History and New Media, 2008
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 **/
class CsvImport_ImportedItem extends Omeka_Record
{
    public $import_id;
    public $item_id;
	
	public function getItemId()
	{
	    return $this->item_id;
	}
	
	public function getImportId() 
	{
	    return $this->import_id;
	}
}