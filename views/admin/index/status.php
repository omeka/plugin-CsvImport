<?php 
    head(array('title' => 'CSV Import', 'bodyclass' => 'primary', 
        'content_class' => 'horizontal-nav'));
?>
<h1>CSV Import</h1>
<?php echo $this->navigation()->menu()->setUlClass('section-nav'); ?>

<div id="primary">
    <h2>Status</h2>
    <?php echo flash(); ?>
    <table class="simple" cellspacing="0" cellpadding="0">
        <thead>
            <tr>
                <th>Import Date</th>
                <th>CSV File</th>
                <th>Item Count</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($csvImports as $csvImport): ?>
            <tr>
                <td><?php echo html_escape($csvImport->added); ?></td>
                <td><?php echo html_escape($csvImport->csv_file_name); ?></td>
                <td><?php echo $csvImport->getProgress(); ?></td>
                <td>
                <?php 
                    echo $csvImport->status;
                    $errorDetails = $csvImport->error_details;
                    if ($csvImport->hasErrorStatus() 
                        && !empty($errorDetails)
                    ) {
                       echo '<br/>' . str_replace("\n", '<br/><br/>', 
                           html_escape($csvImport->error_details));
                    }
                ?>
                </td>
                <?php
                if ($csvImport->status == 
                    CsvImport_Import::STATUS_COMPLETED_IMPORT ||
                    $csvImport->status == 
                    CsvImport_Import::STATUS_IMPORT_ERROR_INVALID_FILE_DOWNLOAD
                ): ?>
                    <td><?php echo delete_button($this->url(
                        array('action' => 'undo-import',
                              'id' => $csvImport->id),
                        'default'),
                        'undo_import',
                        'Undo Import',
                        array('class' => 'csv-undo-import delete-button')); ?>
                <?php elseif ($csvImport->status == 
                    CsvImport_Import::STATUS_COMPLETED_UNDO_IMPORT || 
                    $csvImport->status == 
                    CsvImport_Import::STATUS_IMPORT_ERROR_INVALID_CSV_FILE): ?>
                    <td><?php echo delete_button($this->url(
                        array('action' => 'clear-history',
                              'id' => $csvImport->id),
                        'default'),
                        'clear_history',
                        'Clear History'); ?>
                    </td>
                <?php else: ?>
                    <td></td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function () {
    Omeka.CsvImport.confirmUndoImport();
});
//]]>
</script>
<?php 
    foot(); 
?>
