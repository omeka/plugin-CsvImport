<?php
/**
 * CsvImport_IndexControllerTest - represents the Csv Import index controller test.
 *
 * @copyright Copyright 2007-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 * @package CsvImport
 */
 
class CsvImport_IndexControllerTest extends CsvImport_Test_AppTestCase
{    
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
