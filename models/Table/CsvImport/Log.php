<?php

class Table_CsvImport_Log extends Omeka_Db_Table
{
    public function findByImportId($importId)
    {
        $select = $this->getSelect()
            ->where('import_id = ?', $importId)
            ->order(array('created ASC'));
        return $this->fetchObjects($select);
    }
}
