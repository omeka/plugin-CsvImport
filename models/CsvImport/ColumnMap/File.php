<?php
/**
 * CsvImport_ColumnMap_File class
 *
 * @copyright Copyright 2007-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 * @package CsvImport
 */
class CsvImport_ColumnMap_File extends CsvImport_ColumnMap
{
    const FILE_DELIMTER_OPTION_NAME = 'csv_import_file_delimiter';
    const DEFAULT_FILE_DELIMITER = ',';
    
    public function __construct($columnName)
    {
        parent::__construct($columnName);
        $this->_targetType = CsvImport_ColumnMap::TARGET_TYPE_FILE;
    }

    public function map($row, $result)
    {
        $delimiter = $this->_getDelimiter();
        $urlString = trim($row[$this->_columnName]);
        if ($urlString) {
            $urls = explode($delimiter, $urlString);
            $result[] = $urls;
        }
        return $result;
    }
    
    protected function _getDelimiter()
    {
        if (!($delimiter = get_option(self::FILE_DELIMTER_OPTION_NAME))) {
            $delimiter = self::DEFAULT_FILE_DELIMITER;
        }
        return $delimiter;
    }
}
