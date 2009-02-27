<?php

head(array('title' => 'CsvImport', 'bodyclass' => 'primary', 'content_class' => 'horizontal-nav'));

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
echo csv_import_get_file_drop_down('csv_import_file_name');
echo csv_import_get_item_types_drop_down('csv_import_item_type_id');
echo csv_import_get_collections_drop_down('csv_import_collection_id');
echo csv_import_get_items_are_public_checkbox('csv_import_items_are_public');
echo csv_import_get_items_are_featured_checkbox('csv_import_items_are_featured');
echo submit(array('name'=>'csv_import_submit', 'class'=>'submit submit-medium'), 'Next');
echo '</form>';
echo '</div>';


foot();