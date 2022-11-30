<?php
/**
 * CsvImport_RowIterator class
 *
 * @copyright Copyright 2007-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 * @package CsvImport
 */
class CsvImport_RowIterator implements SeekableIterator
{
    const COLUMN_DELIMITER_OPTION_NAME = 'csv_import_column_delimiter';
    const DEFAULT_COLUMN_DELIMITER = ',';

    private $_filePath;
    private $_handle;
    private $_currentRow;
    private $_currentRowNumber;
    private $_columnDelimiter;
    private $_valid = true;
    private $_colNames = array();
    private $_colCount = 0;

    /**
     * @param string $filePath
     * @param string $columnDelimiter  The column delimiter
     */
    public function __construct($filePath, $columnDelimiter = null)
    {
        $this->_filePath = $filePath;
        if ($columnDelimiter !== null) {
            $this->_columnDelimiter = $columnDelimiter;
        } else {
            $this->_columnDelimiter = self::getDefaultColumnDelimiter();
        }
    }

    /**
     * Returns the column delimiter.
     *
     * @return string The column delimiter
     */
    public function getColumnDelimiter()
    {
        return $this->_columnDelimiter;
    }

    /**
     * Rewind the Iterator to the first element.
     * Similar to the reset() function for arrays in PHP.
     *
     * @throws CsvImport_DuplicateColumnException
     */
    #[\ReturnTypeWillChange]
    public function rewind()
    {
        if ($this->_handle) {
            fclose($this->_handle);
            $this->_handle = null;
        }
        $this->_currentRowNumber = 0;
        $this->_valid = true;
        // First row should always be the header.
        $colRow = $this->_getNextRow();
        $this->_colNames = array_map("trim", array_keys(array_flip($colRow)));
        $this->_colCount = count($colRow);
        $uniqueColCount = count($this->_colNames);
        if ($uniqueColCount != $this->_colCount) {
            throw new CsvImport_DuplicateColumnException("Header row "
                . "contains $uniqueColCount unique column name(s) for "
                . $this->_colCount . " columns.");
        }
        $this->_moveNext();
    }

    /**
     * Return the current element.
     * Similar to the current() function for arrays in PHP.
     *
     * @return mixed current element
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->_currentRow;
    }

    /**
     * Return the identifying key of the current element.
     * Similar to the key() function for arrays in PHP.
     *
     * @return scalar
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->_currentRowNumber;
    }

    /**
     * Move forward to next element.
     * Similar to the next() function for arrays in PHP.
     *
     * @throws Exception
     */
    #[\ReturnTypeWillChange]
    public function next()
    {
        $this->_moveNext();
    }

    /**
     * Seek to a starting position for the file.
     *
     * @param int The offset
     */
    #[\ReturnTypeWillChange]
    public function seek($index)
    {
        if (!$this->_colNames) {
            $this->rewind();
        }
        $fh = $this->_getFileHandle();
        fseek($fh, $index);
        $this->_moveNext();
    }

    /**
     * Returns current position of the file pointer.
     *
     * @return int The current position of the filer pointer
     */
    public function tell()
    {
        return ftell($this->_getFileHandle());
    }

    /**
     * Move to the next row in the file.
     */
    protected function _moveNext()
    {
        if ($nextRow = $this->_getNextRow()) {
            $this->_currentRow = $this->_formatRow($nextRow);
        } else {
            $this->_currentRow = array();
            fclose($this->_handle);
            $this->_valid = false;
            $this->_handle = null;
        }
    }

    /**
     * Returns whether the current file position is valid.
     *
     * @return boolean
     */
    #[\ReturnTypeWillChange]
    public function valid()
    {
        if (!file_exists($this->_filePath)) {
            return false;
        }
        if (!$this->_getFileHandle()) {
            return false;
        }
        return $this->_valid;
    }

    /**
     * Returns array of column names.
     *
     * @return array
     */
    public function getColumnNames()
    {
        if (!$this->_colNames) {
            $this->rewind();
        }
        return $this->_colNames;
    }

    /**
     * Formats a row.
     *
     * @throws LogicException
     * @return array|false The formatted row, false if malformed (wrong number of columns)
     */
    protected function _formatRow($row)
    {
        $formattedRow = array();
        if (!isset($this->_colNames)) {
            throw new LogicException("Row cannot be formatted until the column "
                . "names have been set.");
        }
        if (count($row) != $this->_colCount) {
            return false;
        }
        for ($i = 0; $i < $this->_colCount; $i++)
        {
            $formattedRow[$this->_colNames[$i]] = $row[$i];
        }
        return $formattedRow;
    }

    /**
     * Returns a file handle for the CSV file.
     *
     * @return resource The file handle
     */
    protected function _getFileHandle()
    {
        if (!$this->_handle) {
            ini_set('auto_detect_line_endings', true);
            $this->_handle = fopen($this->_filePath, 'r');
        }
        return $this->_handle;
    }

    /**
     * Returns the next row in the CSV file.
     *
     * @return array The row
     */
    protected function _getNextRow()
    {
        $currentRow = array();
        $handle = $this->_getFileHandle();
        while (($row = fgetcsv($handle, 0, $this->_columnDelimiter)) !== FALSE) {
            $this->_currentRowNumber++;
            return $row;
        }
    }

    /**
     * Returns the default column delimiter.
     * Uses the default column delimiter specified in the options table if
     * available.
     *
     * @return string The default column delimiter
     */
    static public function getDefaultColumnDelimiter()
    {
        if (!($delimiter = get_option(self::COLUMN_DELIMITER_OPTION_NAME))) {
            $delimiter = self::DEFAULT_COLUMN_DELIMITER;
        }
        return $delimiter;
    }
}
