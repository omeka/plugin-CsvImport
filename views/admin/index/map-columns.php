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
    <h2>Step 2: Map Columns To Elements, Tags, or Files</h2>
    <form id="csvimport" method="post" action="">
        <?php
            if (!empty($err)) {
                echo '<p class="error">' .html_escape($err) . '</p>';
            }
            echo csv_import_get_column_mappings($csvImportFile, $csvImportItemTypeId);
        ?>
        <fieldset>
            <?php echo submit(array('name'=>'csv_import_submit', 'class'=>'submit submit-medium'), 'Import CSV File'); ?>
        </fieldset>
    </form>
</div>
<?php 
    foot(); 
?>