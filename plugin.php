<?php
/**
 * CsvImport plugin
 *
 * @copyright  Center for History and New Media, 2008
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt
 * @version    $Id:$
 **/


/**
 * CsvImport plugin
 * 
 * @package CsvImport
 * @author CHNM
 **/
 
require_once 'config.php';

add_plugin_hook('install', 'csv_import_install');
add_plugin_hook('uninstall', 'csv_import_uninstall');
add_plugin_hook('initialize', 'csv_import_initialize');

add_filter('admin_navigation_main', 'csv_import_admin_navigation');

function csv_import_install()
{
    set_option('csv_import_plugin_version', CSV_IMPORT_PLUGIN_VERSION);
}

function csv_import_uninstall()
{
    delete_option('csv_import_plugin_version');
}

function csv_import_initialize()
{
}

function csv_import_admin_navigation($tabs)
{
  $tabs['CsvImport'] = uri('csv-import');
  return $tabs;
}

function csv_import_get_default($htmlInputElementName) 
{
    // set the default file if the form is already submitted
    $default = null;
    if (isset($_POST[$htmlInputElementName])) {
        $default = $_POST[$htmlInputElementName];
    }
    return $default;
}

function csv_import_get_file_drop_down($dropDownName) 
{
    $ht = '';
    $csvFiles = CsvImport_File::getFiles();
    foreach ($csvFiles as $csvFile) {
        $values[$csvFile->getFileName()] = $csvFile->getFileName();
    }
    
    $ht .= '<div class="field">';
    $ht .= select( array('name' => $dropDownName, 'id' => $dropDownName), $values, $default = csv_import_get_default($dropDownName), $label= 'Csv File');
    $ht .= '</div>';
    return $ht;
}

function csv_import_get_item_types_drop_down($dropDownName) 
{
    $ht = '';

    $db = get_db();
    $itt = $db->getTable('ItemType'); // get ItemTypeTable
    $itemTypes = $itt->findAll();
    $itemTypeIdsToNames = array();
    foreach($itemTypes as $itemType) {
        $itemTypeIdsToNames[$itemType['id']] = $itemType['name'];
    }
    
    $ht .= '<div class="field">';
    $ht .= select( array('name' => $dropDownName, 'id' => $dropDownName), $itemTypeIdsToNames, $default = csv_import_get_default($dropDownName), $label= 'Item Type');
    $ht .= '</div>';    
    return $ht;
}

function csv_import_get_column_mappings($csvImportFile, $csvImportItemTypeId) 
{   
    $ht = '';    
    $colNames = $csvImportFile->getColumnNames();
    $colExamples = $csvImportFile->getColumnExamples();
    
    $itemElementIdsToNames = array();
    for($i = 0; $i < count($colNames); $i++) {
        $ht .= '<div class="field">';
        $ht .= '<h3>Column: '. $colNames[$i] . '</h3>';
        $ht .= '<p class="csv_import_column_examples">Example: ' . $colExamples[$i] . '</p>'; 
        $ht .= csv_import_get_item_elements_drop_down(CSV_IMPORT_SELECT_COLUMN_DROPDOWN_PREFIX . $i, $csvImportItemTypeId);
        $ht .= '</div>';
    }
    return $ht;
}


/**
* Get the drop down html code that includes item elements from all of the item element sets,
* except for the "Item Type Metadata" element set, only get the elements for the item type
*  
* @return string
*/

function csv_import_get_item_elements_drop_down($dropDownName, $itemTypeId)
{    
    $ht = '';
    
    // get an associative array of elements where the key is the element set name and the value is the array of elements associated with the element set
    // order the element sets by: Dublin Core, item type, and then all other element sets
    $elements_by_element_set_name = csv_import_get_elements_by_element_set_name($itemTypeId);
    
    // get the select dropdown box
    $ht .= select( array('name' => $dropDownName, 'id' => $dropDownName), $elements_by_element_set_name, $default = csv_import_get_default($dropDownName), $label = '');
    
    return $ht;
}

/**
* Get an associative array of elements where the key is the element set name and the value is an array of elements.
* The associative array will include the following sets of elements in the following order: 
* Dublin Core element set,
* the set of elements associated with the item type,
* and then every other element set
*  
* @return string
*/

function csv_import_get_elements_by_element_set_name($itemTypeId)
{
    $db = get_db();
    $itt = $db->getTable('ItemType');
    $itemType = $itt->find($itemTypeId);
    
    $es = $db->getTable('ElementSet');
    $elementSets = $es->findAll();
    
    $elementsByElementSetName = array(); // associative array that maps element set name to arrays of item elements
        
    foreach($elementSets as $elementSet) {
        switch(trim($elementSet['name'])) {
            
            // get the elements from the Dublic Core element set
            case 'Dublin Core':
                $dcElementSet = $elementSet->getElements();
                $dcElementIdsToElementNames = array();
                foreach($dcElementSet as $dcElement) {
                    $dcElementIdsToElementNames[$dcElement['id']] = $dcElement['name']; 
                }
                $elementsByElementSetName[$elementSet['name']] = $dcElementIdsToElementNames;
            break;
            
            // get the elements for the item type
            case 'Item Type Metadata':
                
                $sql = "SELECT e.id, e.name FROM `{$db->prefix}item_types_elements` AS ite, `{$db->prefix}elements` AS e
                        WHERE `ite`.`item_type_id` = ? AND `e`.`id` = `ite`.`element_id`";        
                $query = $db->query($sql, array($itemTypeId));
                $itElementIdsToElementNames = array();
                while ($itElement = $query->fetch()) {
                    $itElementIdsToElementNames[$itElement['id']] = $itElement['name'];
                }
                $elementsByElementSetName[$elementSet['name'] . ' - ' . $itemType['name']] = $itElementIdsToElementNames;

                
            break;
            
            // get the elements from each of the other element sets
            default:
                $oElementIdsToElementNames = array();
                $oElements = $elementSet->getElements();
                foreach($oElements as $oElement) {
                    $oElementIdsToElementNames[$oElement['id']] = $oElement['name'];
                }
                $elementsByElementSetName[$elementSet['name']] =  $oElementIdsToElementNames;
            break;
        }
    }
    
    return $elementsByElementSetName;
}


/**
* Get the drop down html code that for the collections
*  
* @return string
*/

function csv_import_get_collections_drop_down($dropDownName)
{    
    $ht = '';
    
    // get the collection id/collection name pairs
    $db = get_db();
    $ct = $db->getTable('Collection');
    $values = $ct->findPairsForSelectForm();
    
    // get the select dropdown box
    $ht .= '<div class="field">';
    $ht .= select( array('name' => $dropDownName, 'id' => $dropDownName), $values, $default = csv_import_get_default($dropDownName), $label = 'Collection');
    $ht .= '</div>';

    return $ht;
}

function csv_import_get_items_are_public_checkbox($htmlElementName) 
{
    $checked = (csv_import_get_default($htmlElementName) == '1');
    $ht = '';
    $ht .= '<div class="field">';
    $ht .= checkbox($attributes = array('name' => $htmlElementName, 'id' => $htmlElementName), $checked, $value=null, $label = 'Items Are Public?' );
    $ht .= '</div>';
    return $ht;
}

function csv_import_get_items_are_featured_checkbox($htmlElementName) 
{
    $checked = (csv_import_get_default($htmlElementName) == '1');
    $ht = '';
    $ht .= '<div class="field">';
    $ht .= checkbox($attributes = array('name' => $htmlElementName, 'id' => $htmlElementName), $checked, $value=null, $label = 'Items Are Featured?' );
    $ht .= '</div>';
    return $ht;
}