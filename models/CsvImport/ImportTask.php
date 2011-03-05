<?php
/**
 *
 */
class CsvImport_ImportTask extends Omeka_JobAbstract
{
    private $_importId;
    private $_method = 'doImport';

    public function perform()
    {
        // Set the memory limit.
        // This should be injected rather than assumed via global.
        //$memoryLimit = get_option('csv_import_memory_limit');
        //ini_set('memory_limit', $memoryLimit);
        if ($import = $this->_getImport()) {
            call_user_func(array($import, $this->_method));
        }    
    }

    public function setImportId($id)
    {
        $this->_importId = $id;
    }

    public function setMethod($name)
    {
        $this->_method = $name;
    }

    private function _getImport()
    {
        return $this->_db->getTable('CsvImport_Import')->find($this->_importId);
    }
}
