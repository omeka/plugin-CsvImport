<?php
/**
 * @version $Id$
 * @copyright Center for History and New Media, 2008-2011
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @package CsvImport
 */

/**
 * The form on csv-import/index/index.
 *
 * @package CsvImport
 * @author CHNM
 * @copyright Center for History and New Media, 2008-2011
 */
class CsvImport_Form_Main extends Omeka_Form
{
    public function init()
    {
        parent::init();
        $this->setAttrib('id', 'csvimport');
        $this->setMethod('post'); 
        $csvFiles = CsvImport_File::getFiles();
        foreach ($csvFiles as $csvFile) {
            $values[$csvFile->getFileName()] = $csvFile->getFileName();
        }
        array_unshift($values, 'Select A File');
        $this->addElement('select', 'csv_import_file_name', array(
            'label' => 'CSV File',
            'multiOptions' => $values,
            'required' => true,
            'validators' => array(
                'NotEmpty'
            ),
        ));
        $values = get_db()->getTable('ItemType')->findPairsForSelectForm();
        array_unshift($values, 'Select Item Type');
        $this->addElement('select', 'csv_import_item_type_id', array(
            'label' => 'Item Type',
            'multiOptions' => $values,
        ));
        $values = get_db()->getTable('Collection')->findPairsForSelectForm();
        array_unshift($values, 'Select Collection');
        $this->addElement('select', 'csv_import_collection_id', array(
            'label' => 'Collection',
            'multiOptions' => $values,
        ));
        $this->addElement('checkbox', 'csv_import_items_are_public', array(
            'label' => 'Items Are Public?',
        ));
        $this->addElement('checkbox', 'csv_import_items_are_featured', array(
            'label' => 'Items Are Featured?',
        ));
        $this->addElement('checkbox', 'csv_import_stop_import_if_file_download_error', array(
            'label' => 'Stop Import If A File For An Item Cannot Be Downloaded?',
            'checked' => true,
        ));
        $this->addElement('submit', 'csv_import_submit', array(
            'label' => 'Next',
            'class' => 'submit submit-medium',
        ));
    }
}
