<?php
/*
Q: How do I load the necessary database scripts to establish a connection?. And how do I load 
    the necessary model scripts to access the tables and perform queries? I need to load these files 
    in import.php because it runs as a background script, separate from the core Omeka system.
Q: Why does the "Import" navigation link not remain highlighted throughout the import workflow (when 
    using a controller named anything but IndexController)?
Q: How do I include secondary navigation links under a plugin link?
Q: Why have add_routes hook if add_theme_pages() can be used? Am I missing the idea?

todo: insert a csvimport table during hook_install. This will record the successful imports and their timestamps.
Maybe even insert an import logging table that records the progress of the most recently run import. This can be 
used to resume an import if a problem occurs during import. An maybe even insert a full import history table that 
records all item IDs that were created for each import. This way an import can be undone if necessary.
*/

require_once 'config.php';

add_plugin_hook('initialize', 'csvimport_initialize');
function csvimport_initialize() {
    add_controllers('controllers');
    add_theme_pages('admin', 'admin');
    add_navigation('CsvImport', 'csvimport', 'main');
}

add_plugin_hook('install', 'csvimport_install');
function csvimport_install()
{	
    $db = get_db();
    $db->exec("CREATE TABLE IF NOT EXISTS `$db->CsvImport` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, 
        `file` TEXT NOT NULL, 
        `headers` TEXT NOT NULL
        `type_id` INT UNSIGNED NOT NULL, 
        `log` TEXT, 
        `timestamp_begin` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
        `timestamp_end` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
        ) ENGINE = MYISAM ;");
    set_option('csvimport_plugin_version', CSVIMPORT_PLUGIN_VERSION);
}