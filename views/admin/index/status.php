<?php 
    head(array('title' => 'CSV Import', 'bodyclass' => 'primary', 'content_class' => 'horizontal-nav'));
?>
<h1>CSV Import</h1>
<ul id="section-nav" class="navigation">
    <li class="">
        <a href="<?php echo html_escape(uri('csv-import')); ?>">Import Items</a>
    </li>
    <li class="current">
        <a href="<?php echo html_escape(uri('csv-import/index/status')); ?>">Status</a>
    </li>
</ul>

<div id="primary">
    <h2>Status</h2>
    <?php echo flash(); ?>
    <form id="csvimport" method="post" action="">
        <?php
            if (!empty($err)) {
                echo '<p class="error">' . html_escape($err) . '</p>';
            }
        ?>
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
                    <td>
                    <?php
                          $importedItemCount = $csvImport->getImportedItemCount();
                          $itemCount = $csvImport->getItemCount();
                          if ($itemCount != -1) {
                              echo $importedItemCount . ' / ' . $itemCount;
                          } else {
                              echo 'NA';
                          }
                    ?>
                    </td>
                    <td>
                    <?php 
                        echo $csvImport->status;
                        $errorDetails = $csvImport->error_details;
                        if ($csvImport->hasErrorStatus() && !empty($errorDetails)) {
                           echo '<br/>' . str_replace("\n", '<br/><br/>', html_escape($csvImport->error_details));
                        }
                    ?>
                    </td>
                    <?php
                    if ( $csvImport->status == CsvImport_Import::STATUS_COMPLETED_IMPORT ||
                         $csvImport->status == CsvImport_Import::STATUS_IMPORT_ERROR_INVALID_FILE_DOWNLOAD) {
                        echo '<td><a href="' . html_escape(uri('csv-import/index/undo-import/id/' . $csvImport->id)) . '" class="csv-undo-import">Undo Import</a></td>';
                    } else if ($csvImport->status == CsvImport_Import::STATUS_COMPLETED_UNDO_IMPORT || 
                               $csvImport->status == CsvImport_Import::STATUS_IMPORT_ERROR_INVALID_CSV_FILE) {
                        echo '<td><a href="' . html_escape(uri('csv-import/index/clear-history/id/' . $csvImport->id)) . '">Clear History</a></td>';
                    } else {
                        echo '<td></td>';
                    }
                    ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </form>
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