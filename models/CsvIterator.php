<?php
/**
* @author mortanon@gmail.com
* @link http://uk.php.net/manual/en/function.fgetcsv.php
*/
class CsvIterator implements Iterator {
    //const ROW_SIZE = 4096;
    const ROW_SIZE = 0; // 0 = maximum line length is not limited 
    /**
    * The pointer to the cvs file.
    * @var resource
    * @access private
    */
    private $filePointer = NULL;
    /**
    * The current element, which will
    * be returned on each iteration.
    * @var array
    * @access private
    */
    private $currentElement = NULL;
    /**
    * The row counter.
    * @var int
    * @access private
    */
    private $rowCounter = NULL;
    /**
    * The delimiter for the csv file.
    * @var str
    * @access private
    */
    private $delimiter = NULL;

    /**
    * This is the constructor.It try to open the csv file.The method throws an exception
    * on failure.
    *
    * @access public
    * @param str $file The csv file.
    * @param str $delimiter The delimiter.
    *
    * @throws Exception
    */
    public function __construct($file, $delimiter=',') {
        if (!$this->filePointer = @fopen($file, 'r')) {
            throw new Exception('The file "'.$file.'" cannot be read.');
        }
        $this->delimiter = $delimiter;
    }

    /**
    * This method resets the file pointer.
    *
    * @access public
    */
    public function rewind() {
        $this->rowCounter = 0;
        rewind($this->filePointer);
    }

    /**
    * This method returns the current csv row as a 2 dimensional array
    *
    * @access public
    * @return array The current csv row as a 2 dimensional array
    */
    public function current() {
        $this->currentElement = fgetcsv($this->filePointer, self::ROW_SIZE, $this->delimiter);
        $this->rowCounter++;
        return $this->currentElement;
    }
   
    /**
    * This method returns the current row number.
    *
    * @access public
    * @return int The current row number
    */
    public function key() {
        return $this->rowCounter;
    }
   
    /**
    * This method checks if the end of file is reached.
    *
    * @access public
    * @return boolean Returns true on EOF reached, false otherwise.
    */
    public function next() {
        if (is_resource($this->filePointer)) {
            return !feof($this->filePointer);
        }
        return false;
    }
   
    /**
    * This method checks if the next row is a valid row.
    *
    * @access public
    * @return boolean If the next row is a valid row.
    */
    public function valid() {
        if (!$this->next()) {
            if (is_resource($this->filePointer)) {
                fclose($this->filePointer);
            }
            return false;
        }
        return true;
    }
}