<?php
/**
 * CsvImport_RowIterator class
 *
 * @copyright  Center for History and New Media, 2008-2011
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt
 * @version    $Id:$
 **/
class CsvImport_RowIterator implements SeekableIterator
{
    private $_filePath;
    private $_handle;

    private $_currentRow;
    private $_currentRowNumber;
    private $_delimiter = ',';
    private $_valid = true;
    private $_colNames = array();
    private $_colCount = 0;
    private $_skipInvalidRows = false;
    private $_skippedRowCount = 0;

    /**
     * @param string $filePath
     */
    public function __construct($filePath, $delimiter = null) 
    {
        $this->_filePath = $filePath;
        if ($delimiter) {
            $this->_delimiter = $delimiter;
        }
    }
    
    /**
     * Rewind the Iterator to the first element.
     * Similar to the reset() function for arrays in PHP
     * @return void
     */
    function rewind()
    {
        if ($this->_handle) {
            fclose($this->_handle);
            $this->_handle = null;
        }
        $this->_currentRowNumber = 0;
        $this->_valid = true;
        // First row should always be the header.
        $colRow = $this->_getNextRow();
        $this->_colNames = array_keys(array_flip($colRow));
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
     * Similar to the current() function for arrays in PHP
     * @return mixed current element from the collection
     */
    function current()
    {
        return $this->_currentRow;
    }

    /**
     * Return the identifying key of the current element.
     * Similar to the key() function for arrays in PHP
     * @return mixed either an integer or a string
     */
    function key()
    {
        return $this->_currentRowNumber;
    }

    /**
     * Move forward to next element.
     * Similar to the next() function for arrays in PHP
     * @return void
     */
    function next()
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

    public function tell()
    {
        return ftell($this->_getFileHandle());
    }

    private function _moveNext()
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

    function valid()
    {
        if (!file_exists($this->_filePath)) {
            return false;
        }

        if (!$this->_getFileHandle()) {
            return false;
        }
        return $this->_valid;
    }

    public function getColumnNames()
    {
        if (!$this->_colNames) {
            $this->rewind();
        }
        return $this->_colNames;
    }

    /**
     * Get the number of rows that were skipped since the last time 
     * the function was called.
     *
     * Skipped count is reset to 0 after each call to getSkippedCount(). This 
     * makes it easier to aggregate the number over multiple job runs. 
     */
    public function getSkippedCount()
    {
        $skipped = $this->_skippedRowCount;
        $this->_skippedRowCount = 0;
        return $skipped;
    }

    public function skipInvalidRows($flag)
    {
        $this->_skipInvalidRows = (boolean)$flag;
    }

    private function _formatRow($row)
    {
        $formattedRow = array();
        if (!isset($this->_colNames)) {
            throw new LogicException("Row cannot be formatted until the column "
                . "names have been set.");
        }
        if (count($row) != $this->_colCount) {
            $printable = substr(join($this->_delimiter, $row), 0, 30) . '...';
            throw new CsvImport_MissingColumnException("Row beginning with "
                . "'$printable' does not have the required {$this->_colCount} "
                . "rows.");
        }
        for($i = 0; $i < $this->_colCount; $i++) 
        {
            $formattedRow[$this->_colNames[$i]] = $row[$i];
        }
        return $formattedRow;
    }

    private function _getFileHandle()
    {
        if (!$this->_handle) {
            ini_set('auto_detect_line_endings', true);
            $this->_handle = fopen($this->_filePath, 'r');
        }
        return $this->_handle;
    }

    private function _getNextRow()
    {
        $currentRow = array();
        $handle = $this->_getFileHandle();
        while (($row = fgetcsv($handle, 0, $this->_delimiter)) !== FALSE) {
            $this->_currentRowNumber++;
            return $row;
        }
    }

}
