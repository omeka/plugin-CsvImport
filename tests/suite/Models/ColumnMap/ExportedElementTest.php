<?php
/**
 * CsvImport_ColumnMap_ElementTest class
 *
 * @copyright Copyright 2007-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 * @package CsvImport
 */
class CsvImport_ColumnMap_ExportedElementTest extends CsvImport_Test_AppTestCase
{
    public function testConstructWithoutElementDelimiter()
    {
        $columnName = 'Dublin Core:Title';
        $element = get_db()->getTable('Element')
                           ->findByElementSetNameAndElementName('Dublin Core', 'Title');
        $map = new CsvImport_ColumnMap_ExportedElement($columnName);
        $this->assertInstanceOf('CsvImport_ColumnMap_ExportedElement', $map);
        $this->assertEquals(CsvImport_ColumnMap::TYPE_ELEMENT, $map->getType());
        $this->assertEquals($element->id, $map->getElementId());
        $this->assertTrue($map->isHtml());
        
        $elementDelimiter = '^^';
        $this->assertEquals($elementDelimiter, CsvImport_ColumnMap_ExportedElement::DEFAULT_ELEMENT_DELIMITER);
    }

    public function testMap()
    {
        $columnName = 'Dublin Core:Title';
        $element = get_db()->getTable('Element')
                           ->findByElementSetNameAndElementName('Dublin Core', 'Title');
        $isHtml = true;
        
        $elementDelimiter = '^^';
        $this->assertEquals($elementDelimiter, CsvImport_ColumnMap_ExportedElement::DEFAULT_ELEMENT_DELIMITER);
        
        $beforeElementArray = array('a','B','c D ');
        $afterElementArray = array(
            array(
                'element_id' => $element->id,
                'html' => $isHtml ? 1 : 0,
                'text' => 'a',
            ),
            array(
                'element_id' => $element->id,
                'html' => $isHtml ? 1 : 0,
                'text' => 'B',
            ),
            array(
                'element_id' => $element->id,
                'html' => $isHtml ? 1 : 0,
                'text' => 'c D ',
            )
        );
        $elementString = implode($elementDelimiter, $beforeElementArray);
        
        $row = array($columnName => $elementString);
        
        $map = new CsvImport_ColumnMap_ExportedElement($columnName);
        
        $this->assertInstanceOf('CsvImport_ColumnMap_ExportedElement', $map);
        $this->assertEquals($afterElementArray, $map->map($row, array()));
    }
}