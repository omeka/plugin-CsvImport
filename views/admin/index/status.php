<?php

head(array('title' => 'CsvImport'));

echo '<div id="content" class="horizontal-nav">';
echo '<h1>CsvImport</h1>';

echo '<ul id="section-nav" class="navigation">';
echo '<li class="">';
echo '<a href="' . uri('csv-import') . '">Import</a>';
echo '</li>';
echo '<li class="current">';
echo '<a href="' . uri('csv-import/index/status') . '">Status</a>';
echo '</li>';
echo '</ul>';

echo '<div id="primary">';
echo '<h2>Status</h2>';
echo '<form id="csvimport" name="csvimport" method="post">';
if (!empty($err)) {
    echo '<p class="error">' . $err . '</p>';
}

echo csv_import_get_imports();

echo '</form>';
echo '</div>';

echo '</div>';

foot();