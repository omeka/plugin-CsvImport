<?php
/**
 * CsvImport_File class
 *
 * @copyright  Center for History and New Media, 2008-2011
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt
 * @version    $Id:$
 **/

/**
 * CsvImport_File - represents a csv file
 * 
 * @package CsvImport
 * @author CHNM
 **/
class CsvImport_File 
{

    private $_filePath;
    private $_columnNames = array();
    private $_columnExamples = array();
    private $_delimiter;

    private $_rowIterator;

    /**
     * @param string $filePath Absolute path to the file.
     * @param string|null $delimiter Optional Column delimiter for the CSV file.
     */
    public function __construct($filePath, $delimiter = null) 
    {
        $this->_filePath = $filePath;
        if ($delimiter) {
            $this->_delimiter = $delimiter;
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
     * @return array
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
     * Get row iterator.
     * 
     * @return array   if valid csv file, returns an iterator of rows, where 
     * each row is an associative array keyed with the column names, else 
     * returns an empty array
     */
    public function getRowIterator()
    {
        if (!$this->_rowIterator) {
            $this->_rowIterator = new CsvImport_RowIterator(
                $this->getFilePath(), $this->_delimiter);
        }
        return $this->_rowIterator;
    }

    /**
     * Parse metadata.  Currently retrieves the column names and an "example" 
     * row, i.e. the first row after the header.
     */
    public function parse()
    {
        if ($this->_columnNames || $this->_columnExamples) {
            throw new RuntimeException("Cannot be parsed twice.");
        }

        $rowIterator = $this->getRowIterator();
        // Rewind() opens the file handle.
        $rowIterator->rewind();
        $this->_columnNames = $rowIterator->getColumnNames();
        $rowIterator->next();
        $this->_columnExamples = $rowIterator->current(); 
        return $this->_columnNames && $this->_columnExamples;
    }
}
