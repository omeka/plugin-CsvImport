<?php
/**
 * CsvImport plugin
 *
 * @copyright  Center for History and New Media, 2008
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt
 * @version    $Id:$
 * @package CsvImport
 * @author CHNM
 **/

define('CSV_IMPORT_PLUGIN_VERSION',  get_plugin_ini('CsvImport', 'version'));
define('CSV_IMPORT_DIRECTORY', dirname(__FILE__));
define('CSV_IMPORT_CSV_FILES_DIRECTORY', CSV_IMPORT_DIRECTORY . DIRECTORY_SEPARATOR . 'csv_files');
define('CSV_IMPORT_BACKGROUND_SCRIPTS_DIRECTORY', CSV_IMPORT_DIRECTORY . DIRECTORY_SEPARATOR . 'background_scripts');

define('CSV_IMPORT_COLUMN_MAP_TAG_CHECKBOX_PREFIX', 'csv_import_column_map_tag_');
define('CSV_IMPORT_COLUMN_MAP_FILE_CHECKBOX_PREFIX', 'csv_import_column_map_file_');

define('CSV_IMPORT_COLUMN_MAP_ELEMENTS_LIST_PREFIX', 'csv_import_column_map_elements_list_');
define('CSV_IMPORT_COLUMN_MAP_ELEMENTS_DROPDOWN_PREFIX', CSV_IMPORT_COLUMN_MAP_ELEMENTS_LIST_PREFIX . 'dropdown_');
define('CSV_IMPORT_COLUMN_MAP_ELEMENTS_HIDDEN_INPUT_PREFIX', CSV_IMPORT_COLUMN_MAP_ELEMENTS_LIST_PREFIX . 'hidden_input_'); 

add_plugin_hook('install', 'csv_import_install');
add_plugin_hook('uninstall', 'csv_import_uninstall');
add_plugin_hook('config_form', 'csv_import_config_form');
add_plugin_hook('config', 'csv_import_config');
add_plugin_hook('admin_theme_header', 'csv_import_admin_header');
add_plugin_hook('define_acl', 'csv_import_define_acl');

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
       `error_details` TEXT collate utf8_unicode_ci,
       `item_count` int(10) unsigned NOT NULL,
       `is_public` tinyint(1) default '0',
       `is_featured` tinyint(1) default '0',
       `stop_import_if_file_download_error` tinyint(1) default '0',
       `serialized_column_maps` text collate utf8_unicode_ci NOT NULL,
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
    delete_option('csv_import_memory_limit');
    delete_option('csv_import_php_path');
    
    // drop the tables
    $db = get_db();
    $sql = "DROP TABLE IF EXISTS `{$db->prefix}csv_import_imports`";
    $db->query($sql);
    $sql = "DROP TABLE IF EXISTS `{$db->prefix}csv_import_imported_items`";
    $db->query($sql);
    
}

/**
 * Defines the ACL for the reports controllers.
 *
 * @param Omeka_Acl $acl Access control list
 */
function csv_import_define_acl($acl)
{
    // only allow super users and admins to import csv files
    $acl->loadResourceList(array(
                                    'CsvImport_Index' => array('index', 'map-columns', 'undo-import', 'clear-history', 'status')
                          ));
}

/**
 * Add the admin navigation for the plugin.
 * 
 * @return array
 */
function csv_import_admin_navigation($tabs)
{
    if (get_acl()->checkUserPermission('CsvImport_Index', 'index')) {
        $tabs['CSV Import'] = uri('csv-import');        
    }
    return $tabs;
}

function csv_import_admin_header($request)
{
    if ($request->getModuleName() == 'csv-import') {
        echo '<link rel="stylesheet" href="' . css('csv_import_main') . '" />';
        echo js('csv_import_main');
    }
}

/**
* Get the default value for an element.  
* If the user has already submitted the value, then use that as the default, 
* else return null
*
* @param string Element input name
* @param string Default value of the element  
* @return string
*/
function csv_import_get_default_value($htmlInputElementName, $defaultValue = null) 
{
    // set the default file if the form is already submitted
    if (isset($_POST[$htmlInputElementName])) {
        $defaultValue = $_POST[$htmlInputElementName];
    }
    return $defaultValue;
}

/**
* Get the dropdown html code for csv files.
*  
* @return string
*/
function csv_import_get_file_drop_down($dropDownName, $dropDownLabel) 
{
    $ht = '';
    $csvFiles = CsvImport_File::getFiles();
    foreach ($csvFiles as $csvFile) {
        $values[$csvFile->getFileName()] = $csvFile->getFileName();
    }
            
    $ht .= '<div class="field">';
    $ht .= select( array('name' => $dropDownName, 'id' => $dropDownName), $values, csv_import_get_default_value($dropDownName), $dropDownLabel);
    $ht .= '</div>';
    return $ht;
}

/**
* Get the dropdown box html code for item types
*  
* @return string
*/
function csv_import_get_item_types_drop_down($dropDownName, $dropDownLabel) 
{
    $ht = '';
    $ht .= '<div class="field">';
    $ht .= select_item_type(array('name' => $dropDownName, 'id' => $dropDownName), csv_import_get_default_value($dropDownName), $dropDownLabel);
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
	$ht .= '<style type="text/css">table td {vertical-align:top;}</style>';
	$ht .= '<table class="simple" cellspacing="0" cellpadding="0">';
	$ht .= '<thead>';
	$ht .= '<tr>';
	$ht .= '<th>Column</th>';
	$ht .= '<th>Example from CSV File</th>';
	$ht .= '<th>Map To Element</th>';
	$ht .= '<th>Tags?</th>';
	$ht .= '<th>File?</th>';
	$ht .= '</thead>';
	$ht .= '<tbody>';
	
    for($i = 0; $i < count($colNames); $i++) {
        $ht .= '<tr>';
        $ht .= '<td><strong>'.$colNames[$i].'</strong></td>';
        $ht .= '<td>&#8220;' . $colExamples[$i] . '&#8221;</td>';         
        $ht .= '<td>'.csv_import_get_elements_for_column_mapping($i, $csvImportItemTypeId).'</td>';
        $ht .= '<td>'.csv_import_checkbox(CSV_IMPORT_COLUMN_MAP_TAG_CHECKBOX_PREFIX . $i).'</td>';
        $ht .= '<td>'.csv_import_checkbox(CSV_IMPORT_COLUMN_MAP_FILE_CHECKBOX_PREFIX . $i).'</td>';
        $ht .= '</tr>';
    }
	$ht .= '</tbody>';
	$ht .= '</table>';
	
    return $ht;
}

/**
* Gets a div that allows users to add and remove elements for an column mapping
* 
* @todo Fix the hidden helper function so it does not echo the output and then use it. 
* @return string
*/

function csv_import_get_elements_for_column_mapping($columnIndex, $itemTypeId)
{
    $elementsDropDownName = CSV_IMPORT_COLUMN_MAP_ELEMENTS_DROPDOWN_PREFIX . $columnIndex;
    $elementsHiddenInputName = CSV_IMPORT_COLUMN_MAP_ELEMENTS_HIDDEN_INPUT_PREFIX . $columnIndex;
    $elementsListName = CSV_IMPORT_COLUMN_MAP_ELEMENTS_LIST_PREFIX . $columnIndex;
    
    $ht = '';
    $ht .= '<div>';
    $ht .= csv_import_get_item_elements_drop_down($elementsDropDownName, $itemTypeId, $elementsListName, $elementsHiddenInputName);
    //$ht .= '<a class="add-element" onclick="' . "csvImportAddElementToColumnMap('" . $elementsListName . "', '" . $elementsDropDownName ."', '" . $elementsHiddenInputName . "')" . ';">Add Element</a>';
    $ht .= '<input type="hidden" value="' . csv_import_get_default_value($elementsHiddenInputName) . '" name="' . $elementsHiddenInputName . '" id="' . $elementsHiddenInputName .'" />';
    $ht .= '<ul id="' . $elementsListName . '"></ul>';
    //$ht .= hidden(array('name' => $elementsHiddenInputName, 'id' => $elementsHiddenInputName),  csv_import_get_default_value($elementsHiddenInputName)));
    $ht .= '</div>';
    return $ht;
}

/**
* Get the drop down html code that includes item elements from all of the item element sets,
* except for the "Item Type Metadata" element set, only get the elements for the item type
*  
* @return string
*/
function csv_import_get_item_elements_drop_down($elementsDropDownName, $itemTypeId, $elementsListName, $elementsHiddenInputName)
{    
    $ht = '';
    
    // get an associative array of elements where the key is the element set name and the value is the array of elements associated with the element set
    // order the element sets by: Dublin Core, item type, and then all other element sets
    $elementsByElementSetName = csv_import_get_elements_by_element_set_name($itemTypeId);
    
    $onChange .= "csvImportAddElementToColumnMap('" . $elementsListName . "', '" . $elementsDropDownName ."', '" . $elementsHiddenInputName . "');this.selectedIndex=0;";
    
        
    // get the select dropdown box
    $ht .= select( array('name' => $elementsDropDownName, 'id' => $elementsDropDownName, 'onchange'=>$onChange), $elementsByElementSetName, csv_import_get_default_value($elementsDropDownName), null);
    
    return $ht;
}

/**
* Get an associative array of elements where the key is the element set name and the value is an array of elements.
* The associative array will include the following sets of elements in the following order: 
* Dublin Core element set,
* the set of elements associated with the item type,
* and then every other element set.  Assumes that Dublin Core element set is the first element set in the database.
*  
* @return string
*/
function csv_import_get_elements_by_element_set_name($itemTypeId)
{
    $db = get_db();    
    $es = $db->getTable('ElementSet');
    $elementSets = $es->findAll();
    
    $elementsByElementSetName = array(); // associative array that maps element set name to arrays of item elements
        
    foreach($elementSets as $elementSet) {
        switch(trim($elementSet['name'])) {
        
            // get the elements for the item type
            case 'Item Type Metadata':
                if (!empty($itemTypeId)) {
                    $sql = "SELECT e.id, e.name FROM `{$db->prefix}item_types_elements` AS ite, `{$db->prefix}elements` AS e
                            WHERE `ite`.`item_type_id` = ? AND `e`.`id` = `ite`.`element_id`";        
                    $query = $db->query($sql, array($itemTypeId));
                    $itElementIdsToElementNames = array();
                    while ($itElement = $query->fetch()) {
                        $itElementIdsToElementNames[$itElement['id']] = $itElement['name'];
                    }
                    
                    $itt = $db->getTable('ItemType');
                    $itemType = $itt->find($itemTypeId);
                    $elementsByElementSetName[$elementSet['name'] . ' - ' . $itemType['name']] = $itElementIdsToElementNames;   
                }
            break;
            
            // get the elements from the Dublin Core and each of the other element sets
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
function csv_import_get_collections_drop_down($dropDownName, $dropDownLabel)
{    
    $ht = '';
    $ht .= '<div class="field">';
    $ht .= select_collection( array('name' => $dropDownName, 'id' => $dropDownName), csv_import_get_default_value($dropDownName), $dropDownLabel);
    $ht .= '</div>';
    return $ht;
}

/**
* Get the checkbox html code.  Used for specifying whether items are public or featured
*
* @param string $checkBoxName
* @param string $checkBoxLabel
* @param string $isCheckedByDefault 
* @return string
*/
function csv_import_checkbox($checkBoxName, $checkBoxLabel='', $isCheckedByDefault=false) 
{
    $ht = '';
    $ht .= '<div class="field">';
    $checked = (bool) csv_import_get_default_value($checkBoxName, $isCheckedByDefault);
    $ht .= checkbox($attributes = array('name' => $checkBoxName, 'id' => $checkBoxName), $checked, null, $checkBoxLabel);
    $ht .= '</div>';
    return $ht;
}

function csv_import_config_form()
{
    if (!$path = get_option('csv_import_php_path')) {
        // Get the path to the PHP-CLI command. This does not account for
        // servers without a PHP CLI or those with a different command name for
        // PHP, such as "php5".
        $command = 'which php 2>&0';
        $lastLineOutput = exec($command, $output, $returnVar);
        $path = $returnVar == 0 ? trim($lastLineOutput) : '';
    }
   
    if (!$memoryLimit = get_option('csv_import_memory_limit')) {
        $memoryLimit = ini_get('memory_limit');
    }
?>
    <div class="field">
        <label for="csv_import_php_path">Path to PHP-CLI</label>
        <?php echo __v()->formText('csv_import_php_path', $path, null);?>
        <p class="explanation">Path to your server's PHP-CLI command. The PHP
        version must correspond to normal Omeka requirements. Some web hosts use PHP
        4.x for their default PHP-CLI, but many provide an alternative path to a
        PHP-CLI 5 binary. Check with your web host for more information.</p>
    </div>
    <div class="field">
        <label for="csv_import_memory_limit">Memory Limit</label>
        <?php echo __v()->formText('csv_import_memory_limit', $memoryLimit, null);?>
        <p class="explanation">Set a high memory limit to avoid memory allocation
        issues during harvesting. Examples include 128M, 1G, and -1. The available
        options are K (for Kilobytes), M (for Megabytes) and G (for Gigabytes).
        Anything else assumes bytes. Set to -1 for an infinite limit. Be advised
        that many web hosts set a maximum memory limit, so this setting may be
        ignored if it exceeds the maximum allowable limit. Check with your web host
        for more information.</p>
    </div>
<?php
}

function csv_import_config()
{
    set_option('csv_import_php_path', $_POST['csv_import_php_path']);
    set_option('csv_import_memory_limit', $_POST['csv_import_memory_limit']);
}