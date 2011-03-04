<?php
/**
 * Class for import table.
 * 
 * @version $Id$ 
 * @package CsvImport
 * @author CHNM
 * @copyright Center for History and New Media, 2008-2011
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 **/
class CsvImport_ImportTable extends Omeka_Db_Table
{
    public function getSelect()
    {
        $select = parent::getSelect();
        $select->order('added DESC');
        return $select;
    }
}
