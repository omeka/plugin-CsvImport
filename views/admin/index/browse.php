<?php 
    echo head(array('title' => 'CSV Import', 'bodyclass' => 'primary', 
        'content_class' => 'horizontal-nav'));
?>
<?php echo common('csvimport-nav'); ?>
<div id="primary">
    <h2><?php echo __('Status'); ?></h2>
    <?php echo flash(); ?>
    <div class="pagination"><?php echo pagination_links(); ?></div>
    <table class="simple" cellspacing="0" cellpadding="0">
        <thead>
            <tr>
                <th><?php echo __('Import Date'); ?></th>
                <th><?php echo __('CSV File'); ?></th>
                <th><?php echo __('Item Count'); ?></th>
                <th><?php echo __('Status'); ?></th>
                <th><?php echo __('Action'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach(loop('csv_import_imports') as $csvImport): ?>
            <tr>
                <td><?php echo html_escape($csvImport->added); ?></td>
                <td><?php echo html_escape($csvImport->original_filename); ?></td>
                <td><?php echo html_escape($csvImport->getProgress()); ?></td>
                <td><?php echo html_escape(__(Inflector::humanize($csvImport->status, 'all'))); ?></td>
                <?php
                    if ($csvImport->isFinished() 
                        || $csvImport->isStopped()
                        || $csvImport->isError()): ?>
                    <?php 
                    $undoImportUrl = $this->url(array('action' => 'undo-import',
                                                      'id' => $csvImport->id),
                                                      'default'); 
                    ?>
                    <td>
                        <a href="<?php echo html_escape($undoImportUrl);  ?>" class="csv-undo-import delete-button"><?php echo html_escape(__('Undo Import')); ?></a>
                    </td>
                <?php elseif ($csvImport->isUndone()): ?>
                    <?php 
                    $clearHistoryImportUrl = $this->url(array('action' => 'clear-history',
                                                              'id' => $csvImport->id),
                                                              'default'); 
                    ?>
                    <td>
                        <a href="<?php echo html_escape($clearHistoryImportUrl);  ?>" class="csv-clear-history delete-button"><?php echo html_escape(__('Clear History')); ?></a>
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
    Omeka.CsvImport.confirm();
});
//]]>
</script>
<?php 
    echo foot(); 
?>