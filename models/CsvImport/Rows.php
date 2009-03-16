<?php
/**
 * CsvImport_Rows class
 *
 * @copyright  Center for History and New Media, 2008
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt
 * @version    $Id:$
 **/

class CsvImport_Rows implements Iterator
{
    protected $_csvFile;
    protected $_handle; // handle to file

    protected $_currentRow;
    protected $_currentRowNumber;
    protected $_hasMoreRows;

 	/**
    * @param string $csvFile	the CSVImport_File object
    * 
    */
    public function __construct( $csvFile ) 
    {
        $this->_csvFile = $csvFile;
        $this->_handle = null;
    
        $this->rewind();
    }
    
    /**
    * Rewind the Iterator to the first element.
    * Similar to the reset() function for arrays in PHP
    * @return void
    */
    function rewind()
    {
        $this->_currentRowNumber = 0;
        $this->_currentRow = null;
        $this->_colCount = $this->_csvFile->getColumnCount();
        $this->_colNames = $this->_csvFile->getColumnNames();
        $this->_hasMoreRows = $this->_csvFile->isValid();

        if ($this->_handle !== null) {
            fclose($this->_handle);
            $this->_handle = null;
        }
        
        if ($this->_hasMoreRows) {
            $this->next();
        }
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
        return $_currentRowNumber;
    }

    /**
    * Move forward to next element.
    * Similar to the next() function for arrays in PHP
    * @return void
    */
    function next()
    {
        if ($this->_currentRowNumber == 0) {
            ini_set('auto_detect_line_endings', true);
            $this->_handle = fopen($this->_csvFile->getFilePath(), 'r');
        }
        
        // loop through rows until you get a line or until there are no more lines
        while (($row = fgetcsv($this->_handle)) !== FALSE) {
            $currentRow = array();
            if (count($row) == $this->_colCount) {
                for($i = 0; $i < $this->_colCount; $i++) 
                {
                    $currentRow[$i]['name'] = $this->_colNames[$i];
                    $currentRow[$i]['value'] = $row[$i];
                }
                $this->_currentRow = $currentRow;
                $this->_currentRowNumber++;
                return;
            }
        }
                        
        fclose($this->_handle);
        $this->_handle = null;
        $this->_hasMoreRows = false;
        
    }

    /**
    * Check if there is a current element after calls to rewind() or next().
    * Used to check if we've iterated to the end of the collection
    * @return boolean FALSE if there's nothing more to iterate over
    */
    function valid()
    {
        return $this->_hasMoreRows;
    }

    function getCount() 
    {
        return $this->_csvFile->getRowCount();
    }

}