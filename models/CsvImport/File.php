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
class CsvImport_File {

	protected $_filePath;
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
	public static function getFiles() 
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
            if ($csvFile->isValid()) {
                $csvFiles[] = $csvFile;
            }
        }
        return $csvFiles;
	}

	/**
   * @param string $filePath	the path of the CSV file
   * 
   * Warning: before using the class, you should test whether the csv file is valid or not
   */
    public function __construct( $fileName ) 
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
	    if ($this->isValid()) {
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
 	    if ($this->isValid()) {
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
	    if ($this->isValid()) {
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
   * @return array   if valid csv file, returns an array of rows, where each row is an associative array keyed with the column names, else returns an empty array
   */
	public function getRows() 
	{
	    // make sure that the csv file is valid, else return an empty array
	    if (!$this->isValid()) {
	        return array();
	    }
	    
	    ini_set('auto_detect_line_endings', true);
        $handle = fopen($this->getFilePath(), 'r');
        $rowCount = 0;
        $rows = array();
        $processedLineCount = 0;
        $colCount  = 0;
        while (($data = fgetcsv($handle)) !== FALSE)
        {
            $lineCount++;
            if ($data !== null) 
            {  
                $rowCount++;

                if ($rowCount == 1) 
                {
                    $headers = $data;
                    $colCount = count($data);
                }
            
                for($i = 0; $i < $colCount; $i++) 
                {
                    $rows[$headers[$i]] = $data[$i];
                }
            }
       }
       fclose($handle);
       
       // check to see if the row processing stopped midway
       if ($processedLineCount != $this->getLineCount()) {
           return array();
       }
       
       return $rows;
	}
	
	/**
   * Test whether the csv file has the correct format
   * 
   * CSV files should have:
   *    The first row should contain the column names
   *    The file should have at least one row of data 
   * 
   * @return boolean
   */
	public function isValid() 
	{
	    $this->_validate();
        return $this->_isValid;
	}
	
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
    * @return string
    */
	private function _computeLineCount()
	{
	    ini_set('auto_detect_line_endings', true);
	    $lineCount = 0;
        $handle = fopen($this->getFilePath(), 'r');
        while ($chunk = fread($handle, 1024000)) 
        {
            $lineCount += substr_count($chunk, "\n");
        }
        return $lineCount;
	}
	
	/**
    * Determines if the csv file has a valid format, and if so, it initializes the columncount, rowcount, and columnnames
    */
	private function _validate()
	{
        // make sure the csv file has not already been validated
        if ($this->_isValid !== null) {
            return;
        }        
        
        ini_set('auto_detect_line_endings', true);
        $handle = fopen($this->getFilePath(), 'r');

        $processedLineCount = 0;
        $colCount = 0;
        $rowCount = 0;

        // process each line of data
        while (($data = fgetcsv($handle)) !== FALSE) {
            $processedLineCount++;
            if ($data[0] !== null) {  
                $rowCount++;

                if ($rowCount == 1) {
                    $colCount = count($data);
                    
                    // make sure the first line has column names
                    if ($colCount <= 1 && trim($data[0]) == '') {
                        $this->_isValid = false;
                        return;
                    }
                    
                    // initialize the column count and column names
                    $this->_columnCount = $colCount;
                    $this->_columnNames = $data;
                } else {
                    // make sure the current row has the same number of columns as the header row
                    $colCountCheck = count($data);
                    
                    if ($colCountCheck != $colCount) {
                        
                        // make sure the current row is not an empty row
                        if (trim($data[0]) == '') {
                            $rowCount--;
                        } else {                   
                            $this->_isValid = false;                        
                            return;
                        }
                    } else {
                        
                        // get examples for each column
                        if ($_columnExamples == null && $rowCount > 1) {
                            $this->_columnExamples = $data;
                        }
                        
                    }
                    
                    
                }
                
            }
        }
        fclose($handle);
        
        
                
        // initialize the row count
        $this->_rowCount = $rowCount;

        // make sure the file has a header column and at least one data column
        if ($rowCount < 2) {
            $this->_isValid = false;
            return;
        }

        // make sure every line was processed
        if ($processedLineCount != $this->getLineCount()) {
            $this->_isValid = false;
            return;
        }

        $this->_isValid = true;
        return;
    }
	
}