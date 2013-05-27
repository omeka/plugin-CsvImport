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
    private $_skipInvalidRows = false;
    private $_skippedRowCount = 0;

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
    public function next()
    {
        try {
            $this->_moveNext();
        } catch (CsvImport_MissingColumnException $e) {
            if ($this->_skipInvalidRows) {
                $this->_skippedRowCount++;
                $this->next();
            } else {
                throw $e;
            }
        }
    }

    /**
     * Seek to a starting position for the file.
     *
     * @param int The offset
     */
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
        }

        if (!$this->_currentRow) {
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
     * Returns the number of rows that were skipped since the last time the
     * function was called.
     *
     * Skipped count is reset to 0 after each call to getSkippedCount(). This
     * makes it easier to aggregate the number over multiple job runs.
     *
     * @return int The number of rows skipped since last time function was called
     */
    public function getSkippedCount()
    {
        $skipped = $this->_skippedRowCount;
        $this->_skippedRowCount = 0;
        return $skipped;
    }

    /**
     * Sets whether to skip invalid rows.
     *
     * @param boolean $flag
     */
    public function skipInvalidRows($flag)
    {
        $this->_skipInvalidRows = (boolean)$flag;
    }

    /**
     * Formats a row.
     *
     * @throws LogicException
     * @throws CsvImport_MissingColumnException
     * @return array The formatted row
     */
    protected function _formatRow($row)
    {
        $formattedRow = array();
        if (!isset($this->_colNames)) {
            throw new LogicException("Row cannot be formatted until the column "
                . "names have been set.");
        }
        if (count($row) != $this->_colCount) {
            $printable = substr(join($this->_columnDelimiter, $row), 0, 30) . '...';
            throw new CsvImport_MissingColumnException("Row beginning with "
                . "'$printable' does not have the required {$this->_colCount} "
                . "rows.");
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
