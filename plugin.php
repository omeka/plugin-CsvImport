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

/**
 * Install the plugin.
 * 
 * @return void
 */
function csv_import_install()
{
    // set the plugin version option
    set_option('csv_import_plugin_version', CSV_IMPORT_PLUGIN_VERSION);
    
    $db = get_db();
    
    // create csv imports table
    $db->exec("CREATE TABLE IF NOT EXISTS `{$db->prefix}csv_import_imports` (
       `id` int(10) unsigned NOT NULL auto_increment,
       `item_type_id` int(10) unsigned NOT NULL,
       `collection_id` int(10) unsigned NOT NULL,       
       `csv_file_name` text collate utf8_unicode_ci NOT NULL,
       `status` varchar(255) collate utf8_unicode_ci,
       `is_public` tinyint(1) default '0',
       `is_featured` tinyint(1) default '0',
       `serialized_col_nums_to_element_ids_map` text collate utf8_unicode_ci NOT NULL,
       `added` timestamp NOT NULL default '0000-00-00 00:00:00',
       PRIMARY KEY  (`id`)
       ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
   
   // create csv imported items table
   $db->exec("CREATE TABLE IF NOT EXISTS `{$db->prefix}csv_import_imported_items` (
      `id` int(10) unsigned NOT NULL auto_increment,
      `item_id` int(10) unsigned NOT NULL,
      `import_id` int(10) unsigned NOT NULL,       
      PRIMARY KEY  (`id`),
      KEY (`import_id`)
      ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");    
       
}

/**
 * Uninstall the plugin.
 * 
 * @return void
 */
function csv_import_uninstall()
{
    // delete the plugin version number.
    delete_option('csv_import_plugin_version');
    
    // drop the tables
    $db = get_db();
    $sql = "DROP TABLE IF EXISTS `{$db->prefix}csv_import_imports`";
    $db->query($sql);
    $sql = "DROP TABLE IF EXISTS `{$db->prefix}csv_import_imported_items`";
    $db->query($sql);
    
}

function csv_import_initialize()
{
}

/**
 * Add the admin navigation for the plugin.
 * 
 * @return array
 */
function csv_import_admin_navigation($tabs)
{
  $tabs['CsvImport'] = uri('csv-import');
  return $tabs;
}

/**
* Get the default value for an element.  
* If the user has already submitted the value, then use that as the default, 
* else return null
*  
* @return string
*/
function csv_import_get_default($htmlInputElementName) 
{
    // set the default file if the form is already submitted
    $default = null;
    if (isset($_POST[$htmlInputElementName])) {
        $default = $_POST[$htmlInputElementName];
    }
    return $default;
}

/**
* Get the checkbox html code for csv files.
*  
* @return string
*/
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

/**
* Get the dropdown box html code for item types
*  
* @return string
*/
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

/**
* Get the html code for mapping columns in the csv file to elements
*  
* @return string
*/
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
* Get the drop down html code for the collections
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

/**
* Get the checkbox html code for specifying whether items are added as public items.
*  
* @return string
*/
function csv_import_get_items_are_public_checkbox($htmlElementName) 
{
    $checked = (csv_import_get_default($htmlElementName) == '1');
    $ht = '';
    $ht .= '<div class="field">';
    $ht .= checkbox($attributes = array('name' => $htmlElementName, 'id' => $htmlElementName), $checked, $value=null, $label = 'Items Are Public?' );
    $ht .= '</div>';
    return $ht;
}

/**
* Get the checkbox html code for specifying whether items are added as featured items.
*  
* @return string
*/
function csv_import_get_items_are_featured_checkbox($htmlElementName) 
{
    $checked = (csv_import_get_default($htmlElementName) == '1');
    $ht = '';
    $ht .= '<div class="field">';
    $ht .= checkbox($attributes = array('name' => $htmlElementName, 'id' => $htmlElementName), $checked, $value=null, $label = 'Items Are Featured?' );
    $ht .= '</div>';
    return $ht;
}

/**
* Get the html code the list of imports
*  
* @return string
*/
function csv_import_get_imports() 
{
    $csvImports = CsvImport_Import::getImports();
    $ht = '';
    $ht .= '<table>';
    $ht .= '<tr>';
    $ht .= '<th>Import Date</th>';
    $ht .= '<th>Csv File</th>';
    $ht .= '<th>Status</th>';
    $ht .= '<th></th>';    
    $ht .= '</tr>';
    
    $hti = '';
    foreach($csvImports as $csvImport) {
        $htr = '';
        
        $htr .= '<tr>';
        $htr .= '<td>' . $csvImport->added . '</td>';
        $htr .= '<td>' . $csvImport->csv_file_name . '</td>';
        $htr .= '<td>' . $csvImport->status . '</td>';
        if ( $csvImport->status  == CSV_IMPORT_STATUS_COMPLETED_IMPORT) {
            $htr .= '<td><a href="' . uri('csv-import/index/unimport/id/' . $csvImport->id) . '">Unimport</a></td>';
        } else {
            $htr .= '<td></td>';
        }
        $htr .= '</tr>'; 
                
        $hti .= $htr;    
    }
    $ht .= $hti;
    $ht .= '</table>';
    return $ht;
}