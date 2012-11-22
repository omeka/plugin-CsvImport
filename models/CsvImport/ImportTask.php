<?php
/**
 * CsvImport_ImportTask class
 *
 * @copyright Copyright 2007-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 * @version    $Id:$
 * @package CsvImport
 * @author CHNM
 */
class CsvImport_ImportTask extends Omeka_Job_AbstractJob
{
    const QUEUE_NAME = 'imports';
    
    private $_importId;
    private $_method = 'start';
    private $_memoryLimit;
    private $_batchSize;

    public function perform()
    {
        if ($this->_memoryLimit) {
            ini_set('memory_limit', $this->_memoryLimit);
        }
        if (!($import = $this->_getImport())) {
            return;
        }    

        $import->setBatchSize($this->_batchSize);
        call_user_func(array($import, $this->_method));
        
        if ($import->isQueued()) {
            $this->_dispatcher->setQueueName(self::QUEUE_NAME);
            $this->_dispatcher->sendLongRunning(__CLASS__, 
                array(
                    'importId' => $import->id, 
                    'memoryLimit' => $this->_memoryLimit,
                    'method' => 'resume',
                    'batchSize' => $this->_batchSize,
                )
            );
        }
    }

    public function setBatchSize($size)
    {
        $this->_batchSize = (int)$size;
    }

    public function setMemoryLimit($limit)
    {
        $this->_memoryLimit = $limit;
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
