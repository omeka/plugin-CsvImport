<?php

class CsvImport_IndexControllerTest extends Omeka_Test_AppTestCase
{
    public function setUp()
    {
        parent::setUp();
        $pluginHelper = new Omeka_Test_Helper_Plugin();
        $pluginHelper->install('CsvImport');
        get_plugin_broker()->setCurrentPluginDirName('CsvImport');
        include CSV_IMPORT_DIRECTORY . '/plugin.php';
        get_plugin_broker()->setCurrentPluginDirName(null);
        csv_import_install();
        $pluginHelper->initialize('CsvImport');
    }

    //public function setUpBootstrap($bs)
    //{
        //$this->loadPlugin($bs, 'CsvImport');
        //$this->loadPlugin($bs, 'ExhibitBuilder');
    //}

    //public function loadPlugin($pluginName)
    //{
        //$opt = $bs->getOptions();
        //$broker = $opt['resources']['pluginbroker'];
        //if (!is_array($broker)) {
            //$broker = array('plugins' => array());
        //}
        //$broker['plugins'][] = $pluginName;
        //$opt['resources']['pluginbroker'] = $broker;
        //$bs->setOptions($opt);
    //}
    
    public static function acl()
    {
        return array(
            array(true, 'super', 'CsvImport_Index', 'index'),
            array(false, 'admin', 'CsvImport_Index', 'index'),
        );
    }

    /**
     * @dataProvider acl
     */
    public function testAcl($isAllowed, $role, $resource, $privilege)
    {
        $this->assertEquals($isAllowed, $this->acl->isAllowed($role, 
            $resource, $privilege));
    }
}
