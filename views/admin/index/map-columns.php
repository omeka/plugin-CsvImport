<?php

head(array('title' => 'CsvImport', 'bodyclass' => 'primary', 'content_class' => 'horizontal-nav'));

echo '<h1>CSV Import</h1>';

echo '<ul id="section-nav" class="navigation">';
echo '<li class="current">';
echo '<a href="' . uri('csv-import') . '">Import Items</a>';
echo '</li>';
echo '<li class="">';
echo '<a href="' . uri('csv-import/index/status') . '">Status</a>';
echo '</li>';
echo '</ul>';

echo '<div id="primary">';
echo '<h2>Step 2:&nbsp;&nbsp; Map Columns To Elements, Tags, or Files</h2>';
echo '<form id="csvimport" name="csvimport" method="post">';
if (!empty($err)) {
    echo '<p class="error">' . $err . '</p>';
}
echo csv_import_get_column_mappings($csvImportFile, $csvImportItemTypeId);
echo submit(array('name'=>'csv_import_submit', 'class'=>'submit submit-medium'), 'Import CSV File');
echo '</form>';
echo '</div>';


foot();