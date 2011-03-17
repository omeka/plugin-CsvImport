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

        $fileValidators = array(
            new Omeka_Validate_File_Extension(array('txt', 'csv')),
            new Omeka_Validate_File_MimeType(array('text/csv')),
            new Zend_Validate_File_Size(array(
                'max' => $this->_getMaxUploadSize())),
        );
        $this->addElement('file', 'csv_file', array(
            'label' => 'Upload Your CSV File',
            'required' => true,
            'validators' => $fileValidators,
        ));

        $values = get_db()->getTable('ItemType')->findPairsForSelectForm();
        array_unshift($values, 'Select Item Type');
        $this->addElement('select', 'item_type_id', array(
            'label' => 'Item Type',
            'multiOptions' => $values,
        ));
        $values = get_db()->getTable('Collection')->findPairsForSelectForm();
        array_unshift($values, 'Select Collection');
        $this->addElement('select', 'collection_id', array(
            'label' => 'Collection',
            'multiOptions' => $values,
        ));
        $this->addElement('checkbox', 'items_are_public', array(
            'label' => 'Items Are Public?',
        ));
        $this->addElement('checkbox', 'items_are_featured', array(
            'label' => 'Items Are Featured?',
        ));
        $this->addElement('checkbox', 'stop_on_file_error', array(
            'label' => 'Stop Import If A File For An Item Cannot Be Downloaded?',
            'checked' => true,
        ));
        $this->addElement('submit', 'submit', array(
            'label' => 'Next',
            'class' => 'submit submit-medium',
        ));
    }

    private function _getMaxUploadSize()
    {
        return 1024 * 1024;
    }
}
