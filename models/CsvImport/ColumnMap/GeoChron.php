<?php
/**
 * CsvImport_ColumnMap_Tag class
 *
 * @copyright Copyright 2007-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 * @package CsvImport
 */
class CsvImport_ColumnMap_GeoChron extends CsvImport_ColumnMap
{

    const GEOCHRON_DELIMITER_OPTION_NAME = 'csv_import_tag_delimiter';
    const DEFAULT_GEOCHRON_DELIMITER = ',';
    private $_geoChronDelimiter;

    /**
     * @param string $columnName
     * @param string $tagDelimiter
     */
    public function __construct($columnName, $tagDelimiter = null)
    {
        parent::__construct($columnName);
        $this->_type = CsvImport_ColumnMap::TYPE_GEOCHRON;
        if ($tagDelimiter !== null) {
            $this->_geoChronDelimiter = $tagDelimiter;
        } else {
            $this->_geoChronDelimiter = self::getDefaultGeoChronDelimiter();
        }
    }

    /**
     * Map a row to an array of tags.
     *
     * @param array $row The row to map
     * @param array $result
     * @return array The array of tags
     */
    public function map($row, $result)
    {
        if ($this->_geoChronDelimiter == '') {
            $rawGeoChrons = array($row[$this->_columnName]);
        } else {
            $rawGeoChrons = explode($this->_geoChronDelimiter, $row[$this->_columnName]);
        }
		$geochrons=array();
		foreach($rawGeoChrons as $geoChronRec)
		{
			$geoChronFields = explode('\\', $geoChronRec);
			$trimmed = array_map('trim', $geoChronFields);
			$cleaned = array_diff($trimmed, array(''));
			array_push($geochrons,$cleaned);
			
		}
        return $geochrons;
    }

    /**
     * Return the tag delimiter.
     *
     * @return string The tag delimiter
     */
    public function getGeoChronDelimiter()
    {
        return $this->_geoChronDelimiter;
    }

    /**
     * Returns the default tag delimiter.
     * Uses the default tag delimiter specified in the options table if
     * available.
     *
     * @return string The default tag delimiter
     */
    static public function getDefaultGeoChronDelimiter()
    {
        if (!($delimiter = get_option(self::GEOCHRON_DELIMITER_OPTION_NAME))) {
            $delimiter = self::DEFAULT_GEOCHRON_DELIMITER;
        }
        return $delimiter;
    }
}
