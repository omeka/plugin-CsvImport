<?php

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

function csv_import_get_file_drop_down($elementName) {
    $ht = '';
    $csvFiles = CsvImport_File::getFiles();
    foreach ($csvFiles as $csvFile) {
        $values[$csvFile->getFileName()] = $csvFile->getFileName();
    }
    
    $ht .= '<div class="field">';
    $ht .= select( array('name' => $elementName, 'id' => $elementName), $values, $default = csv_import_get_default($elementName), $label= 'Csv File');
    $ht .= '</div>';
    return $ht;
}

function csv_import_get_default($elementName) {
    // set the default file if the form is already submitted
    $default = null;
    if (isset($_POST[$elementName])) {
        $default = $_POST[$elementName];
    }
    return $default;
}

function csv_import_get_item_types_drop_down($elementName) {
    $ht = '';

    $db = get_db();
    $itt = $db->getTable('ItemType'); // get ItemTypeTable
    $itemTypes = $itt->findAll();
    $itemTypeIdsToNames = array();
    foreach($itemTypes as $itemType) {
        $itemTypeIdsToNames[$itemType['id']] = $itemType['name'];
    }
    
    $ht .= '<div class="field">';
    $ht .= select( array('name' => $elementName, 'id' => $elementName), $itemTypeIdsToNames, $default = csv_import_get_default($elementName), $label= 'Item Type');
    $ht .= '</div>';    
    return $ht;
}

function csv_import_get_column_mappings($csvImportFile, $csvImportItemTypeId) {
        
    $ht = '';    
    $colNames = $csvImportFile->getColumnNames();
    $colExamples = $csvImportFile->getColumnExamples();
    
    $itemTypeElementIdsToNames = array();
    for($i = 0; $i < count($colNames); $i++) {
        $ht .= '<div class="field">';
        $ht .= '<h3>'. $colNames[$i] . '</h3>';
        $ht .= '<p class="csv_import_column_examples">Example: ' . $colExamples[$i] . '</p>'; 
        $ht .= csv_import_get_item_type_elements_drop_down(CSV_IMPORT_SELECT_COLUMN_DROPDOWN_PREFIX . $i, $csvImportItemTypeId);
        $ht .= '</div>';
    }
    return $ht;
}

function csv_import_get_item_type_elements_drop_down($dropDownName, $itemTypeId)
{    
    $ht = '';
    
    // get the item type element ids to names mapping
    $itemTypeElementIdsToNames = array();
    $itemTypeElements = csv_import_get_item_type_elements($itemTypeId);
    foreach($itemTypeElements as $itemTypeElement) {
        $itemTypeElementIdsToNames[$itemTypeElement['id']] = $itemTypeElement['name'];        
    }
    
    // get the select dropdown box
    $ht .= select( array('name' => $dropDownName, 'id' => $dropDownName), $itemTypeElementIdsToNames, $default = csv_import_get_default($dropDownName), $label = '');
    
    return $ht;
}

function csv_import_get_item_type_elements($itemTypeId)
{
    $itemTypeElements = array();
    $db = get_db();

    // get the elements of the itemType
    $sql = "SELECT e.* FROM `{$db->prefix}item_types_elements` AS ite, `{$db->prefix}elements` AS e
            WHERE `ite`.`item_type_id` = ? AND `e`.`id` = `ite`.`element_id`";        
    $query = $db->query($sql, array($itemTypeId));
    
    while ($itemTypeElement = $query->fetch()) {
        $itemTypeElements[] = $itemTypeElement;
    }
    
    return $itemTypeElements;
}