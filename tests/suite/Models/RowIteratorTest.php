<?php
/**
 * CsvImport_RowIteratorTest class
 *
 * @copyright Copyright 2007-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 * @package CsvImport
 */

require_once 'models/CsvImport/RowIterator.php';

class CsvImport_RowIteratorTest extends CsvImport_Test_AppTestCase
{
    private $_validHeader = array(
        'title' => 'title',
        'creator' => 'creator',
        'description' => 'description',
        'tags' => 'tags',
        'file' => 'file',
    );

    private $_validFirstRow = array(
        'title' => "Robinson Crusoe", 
        'creator' => "Daniel Defoe", 
        'description' => "A man survives on an island.", 
        'tags' => "book, classic, island", 
        'file' => "",
    );

    private $_validFile;

    public function setUp()
    {
        parent::setUp();
        $this->validFilePath = CSVIMPORT_BASE . '/tests/_files/test.csv';
    }

    public function testInvalidFile()
    {
        $iterator = new CsvImport_RowIterator('/foo/bar.csv');
        $this->assertFalse($iterator->valid());
    }

    public function testValidFile()
    {
        $iterator = new CsvImport_RowIterator($this->validFilePath);
        $this->assertTrue($iterator->valid());
    }

    public function testGetColumnNames()
    {
        $iterator = new CsvImport_RowIterator($this->validFilePath);
        $this->assertEquals(array_keys($this->_validHeader), 
            $iterator->getColumnNames());
    }

    public function testEmptyCurrentRow()
    {
        $iterator = new CsvImport_RowIterator($this->validFilePath);
        $this->assertNull($iterator->current());
    }

    public function testRewind()
    {
        $iterator = new CsvImport_RowIterator($this->validFilePath);
        $iterator->rewind();
        $this->assertEquals($this->_validFirstRow, $iterator->current());
    }

    public function testInvalidDelimiter()
    {
        $iterator = new CsvImport_RowIterator($this->validFilePath, '?');
        $iterator->rewind();
        $this->assertEquals(1, count($iterator->current()),
            "Header should only have one row because it's being read with the "
            . "wrong type of delimiter.");
    }

    public function testValidDelimiter()
    {
        $iterator = new CsvImport_RowIterator($this->validFilePath, ',');
        $iterator->rewind();
        $this->assertEquals(5, count($iterator->current()));
    }
}
