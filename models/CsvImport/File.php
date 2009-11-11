<?php
/**
 * CsvImport_File class
 *
 * @copyright  Center for History and New Media, 2008
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

	protected $_fileName;
	protected $_lineCount;
	protected $_isValid;
	
	protected $_rowCount;
	protected $_columnCount;
	protected $_columnNames;
	protected $_columnExamples;
	
	/**
   * Gets an array of CsvImport_File objects from the plugin directory
   * 
   * @return array
   */
	public static function getFiles($requireValid = false) 
	{
	    $fileNames = array();
        $paths = new DirectoryIterator(CSV_IMPORT_CSV_FILES_DIRECTORY);
        foreach ($paths as $file) {
            if (!$file->isDot() && !$file->isDir()) {
                if (strrchr($file, '.') == '.csv') {
                    $fileNames[] = $file->getFilename();                    
                }
            }
        }
        
        // sort the files by filenames
        natsort($fileNames); 
        
        // create CsvImport_File objects for each filename
        $csvFiles = array();
        foreach ($fileNames as $fileName) {
            $csvFile = new CsvImport_File($fileName);
            if (!$requireValid || $csvFile->isValid()) {
                $csvFiles[] = $csvFile;
            }
        }
        return $csvFiles;
	}

    /**
   * @param string $fileName The path of the CSV file
   * 
   * Warning: before using the class, you should test whether the csv file is valid or not
   */
    public function __construct($fileName) 
	{
		$this->_fileName = $fileName;
        $this->_isValid = null;
        $this->_lineCount = null;
        $this->_columnNames = null;
        $this->_columnExamples = null;
	}
	
	
	/**
    * Get the file name for the file
    * 
    * @return string
    */	
 	public function getFileName() 
 	{
 	    return $this->_fileName;
 	}
	
	/**
   * Get the file path for the file
   * 
   * @return string
   */	
	public function getFilePath() 
	{
	    return CSV_IMPORT_CSV_FILES_DIRECTORY .  DIRECTORY_SEPARATOR . $this->_fileName;
	}

	/**
   * Get an array of headers for the column names
   * 
   * @return array
   */
	public function getColumnNames() 
	{
	    if ($this->isValid(2)) {
	        return $this->_columnNames;    
	    } else {
	        return array();
	    }
	}
	
	/**
    * Get an array of example data for the columns.
    * 
    * @return array Examples have the same order as the column names.
    */
 	public function getColumnExamples() 
 	{
 	    if ($this->isValid(2)) {
 	        return $this->_columnExamples;    
 	    } else {
 	        return array();
 	    }
 	}
	
	/**
   * Get the number of columns in the csv file,
   * assuming that the header row has the same number of columns as all other rows
   * 
   * @return int
   */
	public function getColumnCount() 
	{
	    if ($this->isValid(2)) {
	        return $this->_columnCount;
	    } else {
	        return 0;   
	    }
	}
	 
	/**
   * Get the number of rows for the csv file, excluding the header row
   * 
   * @return int
   */
	public function getRowCount() 
	{
	    if ($this->isValid()) {
	        return $this->_rowCount;
	    } else {
	        return 0;
	    }
	}

 	/**
    * Get an array of rows for the csv file
    * 
    * @return array   if valid csv file, returns an iterator of rows, where each row is an associative array keyed with the column names, else returns an empty array
    **/
 	public function getRows()
 	{
 	    // make sure that the csv file is valid, else return an empty array
	    if (!$this->isValid()) {
	        return array();
	    }
	    
 	    return new CsvImport_Rows($this);
 	}
	
	/**
   * Test whether the csv file has the correct format
   * 
   * CSV files should have:
   *    The first row should contain the column names
   *    The file should have at least one row of data
   *
   * @param int $maxRowsToValidate The maximum number of rows to validate. if null, it validates all of the rows
   * @return boolean
   **/
    public function isValid($maxRowsToValidate = null) 
	{
	    // make sure the csv file has not already been validated
        if ($maxRowsToValidate === null) {     
            if ($this->_isValid === null) {
                $this->_isValid = $this->_validate();
            }
            return $this->_isValid;
        } else {
            return $this->_validate($maxRowsToValidate);
        }
	}

	/**
    * Gets the number of lines in the file
    * 
    * @return integer
    **/	
	public function getLineCount() 
	{
	    if ($this->_lineCount === null) {
	        $this->_lineCount = $this->_computeLineCount();            
	    }
	    return $this->_lineCount;
	}
	
	/**
    * Computes the number of lines in the file
    * 
    * @return integer
    **/
	private function _computeLineCount()
	{
	    ini_set('auto_detect_line_endings', true);
	    $lineCount = 0;
        if ($handle = fopen($this->getFilePath(), 'r')) {
            while ($chunk = fread($handle, 1024000)) {
                $lineCount += substr_count($chunk, "\n");
            }
            fclose($handle);
        }
        return $lineCount;
	}

    /**
    * Validates the csv file, making sure it has a valid format.
    * If so, it initializes the column count, row count, column names, and column examples
    * This process can take a very long time for large files.  
    * If don't need complete validation, specify the maximum number of rows to validate instead.
    * 
    * @param int $maxRowsToValidate The maximum number of rows to validate. if null, it validates all of the rows
    * @return boolean
    **/
	private function _validate($maxRowsToValidate = null)
	{
        $colCount = 0;
        $rowCount = 0;

        ini_set('auto_detect_line_endings', true);
        if ($handle = fopen($this->getFilePath(), 'r')) {
            // process each row of data
            while (($row = fgetcsv($handle)) !== FALSE) {                
                // make sure the line is not empty and has the appropriate number of columns
                if ( ($colCount > 0 && count($row) == $colCount) || 
                     ($colCount == 0 && trim($row[0]) != '') ||
                     ($colCount == 0 && count($row) > 1) ) {  
                    $rowCount++;
                    if ($rowCount == 1) {
                        // initialize the column count and column names
                        $colCount = count($row);
                        $this->_columnCount = $colCount;
                        $this->_columnNames = $row;
                    } else {
                        // get examples for each column
                        if ($this->_columnExamples === null && $rowCount > 1) {
                            $this->_columnExamples = $row;
                        }
                    }
                } else {
                    if (!(count($row) == 1 && trim($row[0]) == '') &&
                        ($colCount > 0 && count($row) != $colCount) ) {
                        // the line does not have the appropriate number of columns
                        return false;
                    }
                }
                if ($maxRowsToValidate !== null && $rowCount >= ((int)$maxRowsToValidate)) {
                    break;
                }
            }
            fclose($handle);
        } else {
            // the file could not be opened
            return false;
        }
        
        // make sure the file has a header column and at least one data column
        if ($rowCount < 2) {
            return false;
        }
                
        // initialize the row count
        $this->_rowCount = $rowCount;
        return true;
    }
    
    public function testInfo()
    {
        echo 'valid first 2 lines:' . $this->isValid(2) . '<br/><br/>';
        echo 'valid:' . $this->isValid() . '<br/><br/>';
        echo 'column count:' . $this->getColumnCount() . '<br/><br/>';
        echo 'line count: ' . $this->getLineCount() . '<br/><br/>';
        echo 'row count: ' . $this->getRowCount() . '<br/><br/>';
        print_r($this->getColumnNames());
        print_r($this->getColumnExamples());
    } 
}