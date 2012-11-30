<?php
/**
 * CsvImport_FileTest class
 *
 * @copyright Copyright 2007-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 * @package CsvImport
 */

require_once 'models/CsvImport/File.php';

class CsvImport_FileTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->validFilePath = CSVIMPORT_BASE . '/tests/_files/test.csv';
        $this->invalidFilePath = dirname(__FILE__) . '/foo/bar.csv';
    }

    public function testConstruct()
    {
        $file = new CsvImport_File($this->invalidFilePath);
        $this->assertInstanceOf('CsvImport_File', $file);
    }

    public function testGetFilename()
    {
        $filename = '/foo/bar.csv';
        $file = new CsvImport_File($filename);
        $this->assertEquals($filename, $file->getFilePath());
    }
}
