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

        $values = array(0 => 'Select A File');
        $csvFilelist = array();
        foreach ($csvFiles as $csvFile) {
            $csvFilelist[$csvFile->getFileName()] = $csvFile->getFileName();
        }
        $values = array_merge($values, $csvFilelist);
        $this->addElement('select', 'file_name', array(
            'label' => 'CSV File',
            'multiOptions' => $values,
            'required' => true,
            'validators' => array(
                array('InArray', true, array($csvFilelist, 
                    'messages' => array(
                        Zend_Validate_InArray::NOT_IN_ARRAY => "The given "
                            . "choice is not a valid CSV file.  Please choose"
                            . " a file from the list."  
                    )
                )),
            ),
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
}
