<?php
/**
 * CsvImport_ImportTest class
 *
 * @copyright Copyright 2007-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 * @package CsvImport
 */

require_once 'models/CsvImport/Import.php';

class CsvImport_ImportTest extends CsvImport_Test_AppTestCase
{
    protected $_import;
    
    public function setUp()
    {
        parent::setUp();
        $this->_import = new CsvImport_Import();
    }

    public function testConstruct()
    {
        $this->assertFalse($this->_import->isCompleted());
        $this->assertFalse($this->_import->isUndone());
        $this->assertFalse($this->_import->isStopped());
        $this->assertFalse($this->_import->isQueued());
        $this->assertFalse($this->_import->isQueuedUndo());
        $this->assertFalse($this->_import->isError());
        $this->assertFalse($this->_import->isImportError());
        $this->assertFalse($this->_import->isUndoImportError());
        $this->assertFalse($this->_import->isOtherError());        
    }

    public function testIsError()
    {
       $this->_import->setStatus(CsvImport_Import::IMPORT_ERROR);
       $this->assertTrue($this->_import->isError());
       
       $this->_import->setStatus(CsvImport_Import::UNDO_IMPORT_ERROR);
       $this->assertTrue($this->_import->isError());
       
       $this->_import->setStatus(CsvImport_Import::OTHER_ERROR);
       $this->assertTrue($this->_import->isError());
       
       $this->_import->setStatus('error');
       $this->assertFalse($this->_import->isError());
       
       $falseStatusList = $this->_getStatusListExcept(array(CsvImport_Import::IMPORT_ERROR, 
                                                            CsvImport_Import::UNDO_IMPORT_ERROR, 
                                                            CsvImport_Import::OTHER_ERROR));
       foreach($falseStatusList as $falseStatus) {
           $this->_import->setStatus($falseStatus);
           $this->assertFalse($this->_import->isImportError());
       }
    }
    
    public function testIsImportError()
    {
       $this->_import->setStatus(CsvImport_Import::IMPORT_ERROR);
       $this->assertTrue($this->_import->isImportError());
       
       $falseStatusList = $this->_getStatusListExcept(array(CsvImport_Import::IMPORT_ERROR));
       foreach($falseStatusList as $falseStatus) {
           $this->_import->setStatus($falseStatus);
           $this->assertFalse($this->_import->isImportError());
       }
    }
    
    public function testIsUndoImportError()
    {
       $this->_import->setStatus(CsvImport_Import::UNDO_IMPORT_ERROR);
       $this->assertTrue($this->_import->isUndoImportError());
       
       $falseStatusList = $this->_getStatusListExcept(array(CsvImport_Import::UNDO_IMPORT_ERROR));
       foreach($falseStatusList as $falseStatus) {
           $this->_import->setStatus($falseStatus);
           $this->assertFalse($this->_import->isUndoImportError());
       }
    }
    
    public function testIsOtherImportError()
    {
       $this->_import->setStatus(CsvImport_Import::OTHER_ERROR);
       $this->assertTrue($this->_import->isOtherError());
       
       $falseStatusList = $this->_getStatusListExcept(array(CsvImport_Import::OTHER_ERROR));
       foreach($falseStatusList as $falseStatus) {
           $this->_import->setStatus($falseStatus);
           $this->assertFalse($this->_import->isOtherError());
       }
    }
    
    protected function _getStatusListExcept(array $statusListA) 
    {
        $statusListB = array(CsvImport_Import::QUEUED, 
                             CsvImport_Import::IN_PROGRESS, 
                             CsvImport_Import::COMPLETED, 
                             CsvImport_Import::QUEUED_UNDO, 
                             CsvImport_Import::IN_PROGRESS_UNDO, 
                             CsvImport_Import::COMPLETED_UNDO, 
                             CsvImport_Import::IMPORT_ERROR, 
                             CsvImport_Import::UNDO_IMPORT_ERROR, 
                             CsvImport_Import::OTHER_ERROR, 
                             CsvImport_Import::STOPPED, 
                             CsvImport_Import::PAUSED);
                          
       return array_diff($statusListB, $statusListA); 
    }
}