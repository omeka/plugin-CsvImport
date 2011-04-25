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
class CsvImport_File implements IteratorAggregate
{

    private $_filePath;
    private $_columnNames = array();
    private $_columnExamples = array();
    private $_delimiter;
    private $_parseErrors = array();
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
     * Get iterator.
     * 
     * @return CsvImport_RowIterator
     */
    public function getIterator()
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

        $rowIterator = $this->getIterator();
        try {
            $this->_columnNames = $rowIterator->getColumnNames();
            $this->_columnExamples = $rowIterator->current(); 
        } catch (CsvImport_DuplicateColumnException $e) {
            $this->_parseErrors[] = $e->getMessage() 
                . " Please ensure that all column names are unique.";
            //_log("[CsvImport] Error parsing CSV file '{$this->_filePath}': "
                //. $e->getMessage(), Zend_Log::NOTICE);
            return false;
        } catch (CsvImport_MissingColumnException $e) {
            $this->_parseErrors[] = $e->getMessage()
                . " Please ensure that the CSV file is formatted correctly"
                . " and contains the expected number of columns for each row.";
            return false;
        }
        return true;
    }

    public function getErrorString()
    {
        return join(' ', $this->_parseErrors);
    }
}
