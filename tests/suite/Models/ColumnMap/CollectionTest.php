<?php
/**
 * CsvImport_ColumnMap_CollectionTest class
 *
 * @copyright Copyright 2007-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 * @package CsvImport
 */
class CsvImport_ColumnMap_CollectionTest extends CsvImport_Test_AppTestCase
{
    public function testConstruct()
    {
        $map = new CsvImport_ColumnMap_Collection('title');
        $this->assertInstanceOf('CsvImport_ColumnMap_Collection', $map);
        $this->assertEquals(CsvImport_ColumnMap::TYPE_COLLECTION, $map->getType());
    }

    public function testMapWithCollection()
    {        
        $collection = new Collection();        
        $collection->addElementTextsByArray(
            array('Dublin Core' => array(
                'Title' => array(array('text' => 'Animals', 'html' => false)),
            ))
        );
        $collection->save();
        
        $row = array(
            'title' => 'Animals',
            'description' => 'Foo',
        );
        $map = new CsvImport_ColumnMap_Collection('title');
        
        $this->assertEquals($collection->id, $map->map($row, array()));
    }
    
    public function testMapWithoutCollection()
    {
        $collection = new Collection();        
        $collection->addElementTextsByArray(
            array('Dublin Core' => array(
                'Title' => array(array('text' => 'Plants', 'html' => false)),
            ))
        );
        $collection->save();
        
        $row = array(
            'title' => 'Animals',
            'description' => 'Foo',
        );
        $map = new CsvImport_ColumnMap_Collection('title');
        
        $this->assertFalse($map->map($row, array()));
    }
}
