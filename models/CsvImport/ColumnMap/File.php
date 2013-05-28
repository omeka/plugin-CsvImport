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
    const FILE_DELIMITER_OPTION_NAME = 'csv_import_file_delimiter';
    const DEFAULT_FILE_DELIMITER = ',';

    private $_fileDelimiter;

    /**
     * @param string $columnName
     * @param string $fileDelimiter
     */
    public function __construct($columnName, $fileDelimiter = null)
    {
        parent::__construct($columnName);
        $this->_type = CsvImport_ColumnMap::TYPE_FILE;
        if ($fileDelimiter !== null) {
            $this->_fileDelimiter = $fileDelimiter;
        } else {
            $this->_fileDelimiter = self::getDefaultFileDelimiter();
        }
    }

    /**
     * Map a row to an array that can be parsed by insert_item() or
     * insert_files_for_item().
     *
     * @param array $row The row to map
     * @param array $result
     * @return array The result
     */
    public function map($row, $result)
    {
        $urlString = trim($row[$this->_columnName]);
        if ($urlString) {
            if ($this->_fileDelimiter == '') {
                $rawUrls = array($urlString);
            } else {
                $rawUrls = explode($this->_fileDelimiter, $urlString);
            }
            $trimmedUrls = array_map('trim', $rawUrls);
            $cleanedUrls = array_diff($trimmedUrls, array(''));
            $result = array_merge($result, $cleanedUrls);
            $result = array_unique($result);
        }
        return $result;
    }

    /**
     * Return the file delimiter.
     *
     * @return string The file delimiter
     */
    public function getFileDelimiter()
    {
        return $this->_fileDelimiter;
    }

    /**
     * Returns the default file delimiter.
     * Uses the default file delimiter specified in the options table if
     * available.
     *
     * @return string The default file delimiter
     */
    static public function getDefaultFileDelimiter()
    {
        if (!($delimiter = get_option(self::FILE_DELIMITER_OPTION_NAME))) {
            $delimiter = self::DEFAULT_FILE_DELIMITER;
        }
        return $delimiter;
    }
}
