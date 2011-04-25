<?php

/**
 * Install the plugin.
 * 
 * @return void
 */
function csv_import_install()
{    
    $db = get_db();
    
    // create csv imports table
    $db->exec("CREATE TABLE IF NOT EXISTS `{$db->prefix}csv_import_imports` (
       `id` int(10) unsigned NOT NULL auto_increment,
       `item_type_id` int(10) unsigned NOT NULL,
       `collection_id` int(10) unsigned NOT NULL,       
       `owner_id` int unsigned NOT NULL,
       `delimiter` varchar(1) collate utf8_unicode_ci NOT NULL,
       `original_filename` text collate utf8_unicode_ci NOT NULL,
       `file_path` text collate utf8_unicode_ci NOT NULL,
       `file_position` bigint unsigned NOT NULL,
       `status` varchar(255) collate utf8_unicode_ci,
       `skipped_row_count` int(10) unsigned NOT NULL,
       `skipped_item_count` int(10) unsigned NOT NULL,
       `is_public` tinyint(1) default '0',
       `is_featured` tinyint(1) default '0',
       `serialized_column_maps` text collate utf8_unicode_ci NOT NULL,
       `added` timestamp NOT NULL default '0000-00-00 00:00:00',
       PRIMARY KEY  (`id`)
       ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
   
   // create csv imported items table
   $db->exec("CREATE TABLE IF NOT EXISTS `{$db->prefix}csv_import_imported_items` (
      `id` int(10) unsigned NOT NULL auto_increment,
      `item_id` int(10) unsigned NOT NULL,
      `import_id` int(10) unsigned NOT NULL,       
      PRIMARY KEY  (`id`),
      KEY (`import_id`),
      UNIQUE (`item_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");    
       
}

/**
 * Uninstall the plugin.
 * 
 * @return void
 */
function csv_import_uninstall()
{
    // delete the plugin options
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
        'CsvImport_Index' => array(
            'index', 
            'map-columns', 
            'undo-import', 
            'clear-history', 
            'browse'
        )
    ));
    // Hack to disable CRUD actions.
    $acl->deny(null, 'CsvImport_Index', array('show', 'add', 'edit', 'delete'));
    $acl->deny('admin', 'CsvImport_Index');
}

/**
 * Add the admin navigation for the plugin.
 * 
 * @return array
 */
function csv_import_admin_navigation($tabs)
{
    if (get_acl()->isAllowed(current_user(), 'CsvImport_Index', 'index')) {
        $tabs['CSV Import'] = uri('csv-import');        
    }
    return $tabs;
}

function csv_import_admin_header($request)
{
    if ($request->getModuleName() == 'csv-import') {
        queue_css('csv_import_main');
        queue_js('csv-import');
    }
}

/**
 * @return array
 */
function csv_import_get_elements_by_element_set_name($itemTypeId)
{
    $params = $itemTypeId ? array('item_type_id' => $itemTypeId)
                          : array('exclude_item_type' => true);
    return get_db()->getTable('Element')->findPairsForSelectForm($params);
}
