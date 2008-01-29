<?php
get_db()->addTable('CsvImport', 'csvimport');

require_once 'CsvImportTable.php';

class CsvImport extends Omeka_Record {
    public $id;
    public $file;
    public $headers;
    public $type_id;
    public $log;
    public $timestamp_begin;
    public $timestamp_end;
}