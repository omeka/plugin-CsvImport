<?php
/**
 * @version $Id$
 * @copyright Center for History and New Media, 2011
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @package CsvImport
 */

define('CSVIMPORT_BASE', realpath(dirname(__FILE__) . '/../')); 
set_include_path(get_include_path() . PATH_SEPARATOR . CSVIMPORT_BASE);

/**
 * 
 *
 * @package CsvImport
 * @copyright Center for History and New Media, 2011
 */
class CsvImport_AllTests extends PHPUnit_Framework_TestSuite
{
    public static function suite()
    {
        $suite = new CsvImport_AllTests('CsvImport Tests');
        $testCollector = new PHPUnit_Runner_IncludePathTestCollector(
          array(dirname(__FILE__))
        );
        $suite->addTestFiles($testCollector->collectTests());
        return $suite;
    }
}

