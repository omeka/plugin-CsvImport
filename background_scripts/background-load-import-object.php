<?php

// Require the core application and plugin files
$baseDir = str_replace('plugins/CsvImport/background_scripts', '', dirname(__FILE__));
require "{$baseDir}paths.php";
require "{$baseDir}application/libraries/Omeka/Core.php";

// Load only the required core phases.
$core = new Omeka_Core;
$core->phasedLoading('initializeCurrentUser');

// Set the memory limit.
$memoryLimit = get_option('csv_import_memory_limit');
ini_set('memory_limit', "$memoryLimit");

// Get the database object.
$db = get_db();

// Set the command line arguments.
$options = getopt('i:u:');

// Get the user object and set the current user to it
$userId = $options['u'];
$user = $db->getTable('User')->find($userId);
Omeka_Context::getInstance()->setCurrentUser($user);

// Get the csv import object
$importId = $options['i'];
$csvImport = $db->getTable('CsvImport_Import')->find($importId);