<?php

head(array('title' => 'CsvImport'));

echo '<div id="content" class="horizontal-nav">';
echo '<h1>CsvImport</h1>';

echo '<ul id="section-nav" class="navigation">';
echo '<li class="current">';
echo '<a href="' . uri('csv-import') . '">Import</a>';
echo '</li>';
echo '<li class="">';
echo '<a href="' . uri('csv-import/index/status') . '">Status</a>';
echo '</li>';
echo '</ul>';

echo '<div id="primary">';
echo '<h2>Step 1:&nbsp;&nbsp; Select File and Item Type</h2>';
echo '<form id="csvimport" name="csvimport" method="post">';
if (!empty($err)) {
    echo '<p class="error">' . $err . '</p>';
}
echo csv_import_get_file_drop_down('csv_import_file');
echo csv_import_get_item_types_drop_down('csv_import_item_type');
echo submit(array('name'=>'csv_import_submit', 'class'=>'submit submit-medium'), 'Next');
echo '</form>';
echo '</div>';

echo '</div>';

foot();