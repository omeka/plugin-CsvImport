<?php
    head(array('title' => 'CSV Import', 'bodyclass' => 'primary', 'content_class' => 'horizontal-nav'));
?>
<h1>CSV Import</h1>

<ul id="section-nav" class="navigation">
    <li class="current">
        <a href="<?php echo html_escape(uri('csv-import')); ?>">Import Items</a>
    </li>
    <li class="">
        <a href="<?php echo html_escape(uri('csv-import/index/status')); ?>">Status</a>
    </li>
</ul>

<div id="primary">
    <h2>Step 1: Select File and Item Settings</h2>
    <?php echo flash(); ?>
    <form id="csvimport" method="post" action="">
        <?php
            echo csv_import_get_file_drop_down('csv_import_file_name', 'CSV File');
            echo csv_import_get_item_types_drop_down('csv_import_item_type_id', 'Item Type');
            echo csv_import_get_collections_drop_down('csv_import_collection_id', 'Collection');
            echo csv_import_checkbox('csv_import_items_are_public', 'Items Are Public?', 'field');
            echo csv_import_checkbox('csv_import_items_are_featured', 'Items Are Featured?', 'field');
            echo csv_import_checkbox('csv_import_stop_import_if_file_download_error', 'Stop Import If A File For An Item Cannot Be Downloaded?', 'field', true);
        ?>
        <fieldset><?php echo submit(array('name'=>'csv_import_submit', 'class'=>'submit submit-medium'), 'Next'); ?></fieldset>
    </form>
</div>

<?php 
    foot(); 
?>