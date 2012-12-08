<?php
/**
 * CsvImport_ColumnMap_FileTest class
 *
 * @copyright Copyright 2007-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 * @package CsvImport
 */
class CsvImport_ColumnMap_FileTest extends CsvImport_Test_AppTestCase
{
    public function testConstructWithoutFileDelimiter()
    {
        $columnName = 'title';
        
        $map = new CsvImport_ColumnMap_File($columnName);
        $this->assertInstanceOf('CsvImport_ColumnMap_File', $map);
        $this->assertEquals(CsvImport_ColumnMap::TYPE_FILE, $map->getType());
    }

    public function testConstructWithFileDelimiter()
    {
        $columnName = 'title';
        $fileDelimiter = ',';
        
        $map = new CsvImport_ColumnMap_File($columnName, $fileDelimiter);
        $this->assertInstanceOf('CsvImport_ColumnMap_File', $map);
        $this->assertEquals(CsvImport_ColumnMap::TYPE_FILE, $map->getType());
        $this->assertEquals($fileDelimiter, $map->getFileDelimiter());
    }

    public function testMapWithoutFileDelimiter()
    {
        $columnName = 'title';
        $fileDelimiter = ',';
        $this->assertEquals($fileDelimiter, CsvImport_ColumnMap_File::getDefaultFileDelimiter());
        
        $beforeFileArray = array('a.jpg','B.txt','http://c.com', ' http://d.com/d', ' E.txt', ' f.txt ', ' G/G ', 'h.ico ', 'I ', ' j K ');
        $afterFileArray = array('a.jpg','B.txt','http://c.com', 'http://d.com/d', 'E.txt', 'f.txt', 'G/G', 'h.ico', 'I', 'j K');
        $fileString = implode($fileDelimiter, $beforeFileArray);
        
        $row = array($columnName => $fileString);
        
        $map = new CsvImport_ColumnMap_File($columnName);
        $this->assertInstanceOf('CsvImport_ColumnMap_File', $map);
        $this->assertEquals($afterFileArray, $map->map($row, array()));
    }
    
    public function testMapWithFileDelimiter()
    {
        $columnName = 'title';
        $defaultFileDelimiter = ',';
        $this->assertEquals($defaultFileDelimiter, CsvImport_ColumnMap_File::getDefaultFileDelimiter());
        
        $fileDelimiter = '|';
        $beforeFileArray = array('a.jpg','B.txt','http://c.com', ' http://d.com/d', ' E.txt', ' f.txt ', ' G/G ', 'h.ico ', 'I ', ' j K ');
        $afterFileArray = array('a.jpg','B.txt','http://c.com', 'http://d.com/d', 'E.txt', 'f.txt', 'G/G', 'h.ico', 'I', 'j K');
        $fileString = implode($fileDelimiter, $beforeFileArray);
        
        $row = array($columnName => $fileString);
        
        $map = new CsvImport_ColumnMap_File($columnName, $fileDelimiter);
        $this->assertInstanceOf('CsvImport_ColumnMap_File', $map);
        $this->assertEquals($afterFileArray, $map->map($row, array()));
    }
}