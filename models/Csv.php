<?php
require_once 'CsvIterator.php';
class Csv {
    
    protected $csvIterator;
    
    protected $headers = array();
    
    protected $rows = array();
    
    protected $rowCount;
    
    protected $columnCount;
    
    public function __construct($file) {
        $this->csvIterator = new CsvIterator($file);
    }
        
    public function setHeaders() {
        // Assume the first row contains the headers, ignore all other rows
        $headers = $this->csvIterator->current();
        
        $this->headers = $headers;
        $this->columnCount = count($headers);
    }
    
    public function setRows() {
        $rows = array();
        foreach ($this->csvIterator as $row) {
            // The iterator returns false at the last iteration, so check if the row is an array 
            // before adding it to the rows array.
            if (is_array($row)) $rows[] = $row;
        }
        // Delete the first row, as it should only contain headers
        unset($rows[0]);
        
        $this->rows = $rows;
        $this->rowCount = count($rows);
    }
    
    public function getHeaders() {
        return $this->headers;
    }
    
    public function getColumnCount() {
        return $this->columnCount;
    }
    
    public function getRows() {
        return $this->rows;
    }
    
    public function getRowCount() {
        return $this->rowCount;
    }
    
    // Must have already called $this->setHeaders() and $this->setRows() to use this method.
    public function isValidCsv() {
        // Check if every row has the same number of columns as the header.
        foreach ($this->rows as $row) {
            if (count($row) != $this->columnCount) return false;
        }
        return true;
    }
    
}