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
echo '<h2>Step 1:&nbsp;&nbsp; Select File and Item Settings</h2>';
echo '<form id="csvimport" name="csvimport" method="post">';
if (!empty($err)) {
    echo '<p class="error">' . $err . '</p>';
}
echo csv_import_get_file_drop_down('csv_import_file_name', 'CSV File');
echo csv_import_get_item_types_drop_down('csv_import_item_type_id', 'Item Type');
echo csv_import_get_collections_drop_down('csv_import_collection_id', 'Collection');
echo csv_import_checkbox('csv_import_items_are_public', 'Items Are Public?');
echo csv_import_checkbox('csv_import_items_are_featured', 'Items Are Featured?');
echo csv_import_checkbox('csv_import_stop_import_if_file_download_error', 'Stop Import If A File For An Item Cannot Be Downloaded?', true);
echo submit(array('name'=>'csv_import_submit', 'class'=>'submit submit-medium'), 'Next');
echo '</form>';
echo '</div>';


foot();