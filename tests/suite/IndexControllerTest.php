<?php

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
