<?php

class CsvImport_Log extends Omeka_Record_AbstractRecord
{
    public $id;
    public $import_id;
    public $priority;
    public $created;
    public $message;
    public $params;
}
