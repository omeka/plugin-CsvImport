<?php
/**
 * @version $Id$
 * @copyright Center for History and New Media, 2011
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @package CsvImport
 */

require_once 'models/CsvImport/File.php';

/**
 * 
 *
 * @package CsvImport
 * @copyright Center for History and New Media, 2011
 */
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
