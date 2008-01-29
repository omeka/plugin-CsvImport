<?php
// CsvImport import script

// Set the PHP memory limit high or risk exceeding the maximum memory limit.
ini_set('memory_limit', '500M');

// Require the necessary scripts.
require_once 'config.php';
require_once CSVIMPORT_MODELS_DIRECTORY . DIRECTORY_SEPARATOR . 'Import.php';

try {
    // Set the base directory by removing the path to the CsvImport directory from the Omeka root.
    $baseDirectory = str_replace(DIRECTORY_SEPARATOR . 'plugins'. DIRECTORY_SEPARATOR .'CsvImport', '', dirname(__FILE__));
    
    require_once $baseDirectory . DIRECTORY_SEPARATOR . 'paths.php';
    require_once $baseDirectory . DIRECTORY_SEPARATOR . 'application/libraries/Omeka/Core.php';
    
    $core = new Omeka_Core;
    $core->phasedLoading('loadModelClasses');
    /*
    $core->initializeClassLoader();
    $core->initializeConfigFiles();
    $core->initializeDb();
    $core->initializeOptions();
    $core->loadModelClasses();
    */
    
    // Do the import. ($argv is an automatically generated array containing the arguments 
    // in a shell command.)
    $import = new Import($argv);

} catch (Exception $e) {
    echo $e->getMessage();
}