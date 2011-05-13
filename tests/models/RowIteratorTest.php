<?php
/**
 * @version $Id$
 * @copyright Center for History and New Media, 2011
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @package CsvImport
 */

require_once 'models/CsvImport/RowIterator.php';

/**
 * 
 *
 * @package CsvImport
 * @copyright Center for History and New Media, 2011
 */
class CsvImport_RowIteratorTest extends PHPUnit_Framework_TestCase
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
