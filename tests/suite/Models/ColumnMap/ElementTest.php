<?php
/**
 * CsvImport_ColumnMap_ElementTest class
 *
 * @copyright Copyright 2007-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 * @package CsvImport
 */
class CsvImport_ColumnMap_ElementTest extends CsvImport_Test_AppTestCase
{
    public function testConstructWithoutElementDelimiter()
    {
        $columnName = 'title';
        
        $map = new CsvImport_ColumnMap_Element($columnName);
        $this->assertInstanceOf('CsvImport_ColumnMap_Element', $map);
        $this->assertEquals(CsvImport_ColumnMap::TYPE_ELEMENT, $map->getType());
    }

    public function testConstructWithElementDelimiter()
    {
        $columnName = 'title';
        $elementDelimiter = ',';
        
        $map = new CsvImport_ColumnMap_Element($columnName, $elementDelimiter);
        $this->assertInstanceOf('CsvImport_ColumnMap_Element', $map);
        $this->assertEquals(CsvImport_ColumnMap::TYPE_ELEMENT, $map->getType());
        $this->assertEquals($elementDelimiter, $map->getElementDelimiter());
    }

    public function testMapWithoutElementDelimiter()
    {
        $columnName = 'title';
        $isHtml = false;
        $elementId = 1;
        
        $elementDelimiter = '';
        $this->assertEquals($elementDelimiter, CsvImport_ColumnMap_Element::getDefaultElementDelimiter());
        
        $beforeElementArray = array('a','B','c D ');
        $afterElementArray = array(
            array(
                'element_id' => $elementId,
                'html' => $isHtml,
                'text' => 'aBc D ',
            )
        );
        $elementString = implode($elementDelimiter, $beforeElementArray);
        
        $row = array($columnName => $elementString);
        
        $map = new CsvImport_ColumnMap_Element($columnName);
        $map->setOptions(
            array(
                'isHtml' => $isHtml ? 1 : 0,
                'elementId' => $elementId,
            )
        );
        
        $this->assertInstanceOf('CsvImport_ColumnMap_Element', $map);
        $this->assertEquals($afterElementArray, $map->map($row, array()));
    }
    
    public function testMapWithElementDelimiter()
    {
        $columnName = 'title';
        $isHtml = false;
        $elementId = 1;
        
        $defaultElementDelimiter = '';
        $this->assertEquals($defaultElementDelimiter, CsvImport_ColumnMap_Element::getDefaultElementDelimiter());
        
        $elementDelimiter = ',';
        $beforeElementArray = array('a','B','c D ');
        $afterElementArray = array(
            array(
                'element_id' => $elementId,
                'html' => $isHtml ? 1 : 0,
                'text' => 'a',
            ),
            array(
                'element_id' => $elementId,
                'html' => $isHtml ? 1 : 0,
                'text' => 'B',
            ),
            array(
                'element_id' => $elementId,
                'html' => $isHtml ? 1 : 0,
                'text' => 'c D ',
            )
        );
        $elementString = implode($elementDelimiter, $beforeElementArray);
        
        $row = array($columnName => $elementString);
        
        $map = new CsvImport_ColumnMap_Element($columnName, $elementDelimiter);
        $map->setOptions(
            array(
                'isHtml' => $isHtml,
                'elementId' => $elementId,
            )
        );
        
        $this->assertInstanceOf('CsvImport_ColumnMap_Element', $map);
        $this->assertEquals($afterElementArray, $map->map($row, array()));
    }
}