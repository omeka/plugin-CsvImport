<?php
/**
 * CsvImport_Test_AppTestCase - represents the base class for Csv Import tests.
 *
 * @copyright Copyright 2007-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 * @package CsvImport
 */
class CsvImport_Test_AppTestCase extends Omeka_Test_AppTestCase
{
    const PLUGIN_NAME = 'CsvImport';
    
    public function setUp()
    {
        parent::setUp();
        
        // Authenticate and set the current user 
        $this->user = $this->db->getTable('User')->find(1);
        $this->_authenticateUser($this->user);
        
        $pluginHelper = new Omeka_Test_Helper_Plugin;
        $pluginHelper->setUp(self::PLUGIN_NAME);
        Omeka_Test_Resource_Db::$runInstaller = true;
    }
    
    public function assertPreConditions()
    {
        $imports = $this->db->getTable('CsvImport_Import')->findAll();
        $this->assertEquals(0, count($imports), 'There should be no imports.');
    }
    
    protected function _deleteAllImports()
    {
        $imports = $this->db->getTable('CsvImport_Import')->findAll();
        foreach($imports as $imports) {
            $imports->delete();
        }
        $imports = $this->db->getTable('CsvImport_Import')->findAll();
        $this->assertEquals(0, count($imports), 'There should be no imports.');
    }
}
