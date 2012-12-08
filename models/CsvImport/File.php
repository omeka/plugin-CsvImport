<?php
/**
 * CsvImport_File class - represents a csv file
 *
 * @copyright Copyright 2007-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 * @package CsvImport
 */
class CsvImport_File implements IteratorAggregate
{
    private $_filePath;
    private $_columnNames = array();
    private $_columnExamples = array();
    private $_columnDelimiter;
    private $_parseErrors = array();
    private $_rowIterator;

    /**
     * @param string $filePath Absolute path to the file.
     * @param string|null $columnDelimiter Optional Column delimiter for the CSV file.
     */
    public function __construct($filePath, $columnDelimiter = null) 
    {
        $this->_filePath = $filePath;
        if ($columnDelimiter) {
            $this->_columnDelimiter = $columnDelimiter;
        }
    }

    /**
     * Absolute path to the file.
     * 
     * @return string
     */
    public function getFilePath() 
    {
        return $this->_filePath;
    }

    /**
     * Get an array of headers for the column names
     * 
     * @return array The array of headers for the column names
     */
    public function getColumnNames() 
    {
        if (!$this->_columnNames) {
            throw new LogicException("CSV file must be validated before "
                . "retrieving the list of columns.");
        }
        return $this->_columnNames;    
    }

    /**
     * Get an array of example data for the columns.
     * 
     * @return array Examples have the same order as the column names.
     */
    public function getColumnExamples() 
    {
        if (!$this->_columnExamples) {
            throw new LogicException("CSV file must be validated before "
                . "retrieving list of column examples.");
        }
        return $this->_columnExamples;    
    }

    /**
     * Get an iterator for the rows in the CSV file.
     * 
     * @return CsvImport_RowIterator
     */
    public function getIterator()
    {
        if (!$this->_rowIterator) {
            $this->_rowIterator = new CsvImport_RowIterator(
                $this->getFilePath(), $this->_columnDelimiter);
        }
        return $this->_rowIterator;
    }

    /**
     * Parse metadata.  Currently retrieves the column names and an "example" 
     * row, i.e. the first row after the header.
     *
     * @return boolean
     */
    public function parse()
    {
        if ($this->_columnNames || $this->_columnExamples) {
            throw new RuntimeException('Cannot be parsed twice.');
        }
        $rowIterator = $this->getIterator();
        try {
            $this->_columnNames = $rowIterator->getColumnNames();
            $this->_columnExamples = $rowIterator->current(); 
        } catch (CsvImport_DuplicateColumnException $e) {
            $this->_parseErrors[] = $e->getMessage() 
                . ' ' . __('Please ensure that all column names are unique.');
            return false;
        } catch (CsvImport_MissingColumnException $e) {
            $this->_parseErrors[] = $e->getMessage()
                . ' ' . __('Please ensure that the CSV file is formatted correctly'
                . ' and contains the expected number of columns for each row.');
            return false;
        }
        return true;
    }

    /**
     * Get the error string
     * 
     * @return string
     */
    public function getErrorString()
    {
        return join(' ', $this->_parseErrors);
    }
}