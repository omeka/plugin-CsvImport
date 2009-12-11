<?php
/**
 * A background process to import items from a csv file
 * 
 * @version $Id$
 * @copyright Center for History and New Media, 2009
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @package Omeka
 * @subpackage Models
 */

class CsvImport_ImportProcess extends ProcessAbstract
{
    public function run($args)
    {
        $db = get_db();        
        
        // Set the memory limit.
        $memoryLimit = get_option('csv_import_memory_limit');
        ini_set('memory_limit', $memoryLimit);
        
        // get the import object
        $importId = (int) $args['import_id'];
        $import = $db->getTable('CsvImport_Import')->find($importId);
        
        // do the import
        if ($import) {
            $import->doImport();
        }
    }
}