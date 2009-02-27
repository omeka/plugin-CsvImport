<?php
require_once('background-load-import-object.php');

// do the import
if ($csvImport) {
    $csvImport->doImport();
}
