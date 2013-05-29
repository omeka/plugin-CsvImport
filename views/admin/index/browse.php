<?php
    echo head(array('title' => 'CSV Import', 'bodyclass' => 'primary',
        'content_class' => 'horizontal-nav'));
?>
<?php echo common('csvimport-nav'); ?>
<div id="primary">
    <h2><?php echo __('Status'); ?></h2>
    <?php echo flash(); ?>
    <div class="pagination"><?php echo pagination_links(); ?></div>
    <?php if (iterator_count(loop('CsvImport_Import'))): ?>
    <table class="simple" cellspacing="0" cellpadding="0">
        <thead>
            <tr>
                <?php
                $browseHeadings[__('Import date')] = 'added';
                $browseHeadings[__('CSV file')] = 'original_filename';
                $browseHeadings[__('Import type')] = null;
                $browseHeadings[__('Row count')] = 'row_count';
                $browseHeadings[__('Skipped rows')] = 'skipped_row_count';
                $browseHeadings[__('Imported items')] = null;
                $browseHeadings[__('Skipped records')] = 'skipped_item_count';
                $browseHeadings[__('Status')] = 'status';
                $browseHeadings[__('Action')] = null;
                echo browse_sort_links($browseHeadings, array('link_tag' => 'th scope="col"', 'list_tag' => ''));
                ?>
            </tr>
        </thead>
        <tbody>
            <?php $key = 0; ?>
            <?php foreach (loop('CsvImport_Import') as $csvImport): ?>
            <tr class="<?php if (++$key%2 == 1) echo 'odd'; else echo 'even'; ?>">

                <td><?php echo html_escape(format_date($csvImport->added, Zend_Date::DATETIME_SHORT)); ?></td>
                <td><?php echo html_escape($csvImport->original_filename); ?></td>
                <td><?php switch ($csvImport->format) {
                    case 'Report': echo __('Csv Report'); break;
                    case 'Item': echo __('Items'); break;
                    case 'File': echo __('Files metadata'); break;
                    case 'Mix': echo __('Mixed records'); break;
                    // Imports made with the standard plugin.
                    default: echo __('Unknown'); break;
                } ?></td>
                <?php $importedItemCount = $csvImport->getImportedItemCount(); ?>
                <td><?php echo html_escape($csvImport->row_count); ?></td>
                <td><?php echo html_escape($csvImport->skipped_row_count); ?></td>
                <td><?php echo html_escape($importedItemCount); ?></td>
                <td><?php echo html_escape($csvImport->skipped_item_count); ?></td>

                <td><?php echo html_escape(__(Inflector::humanize($csvImport->status, 'all'))); ?></td>
                <?php if ($csvImport->format == 'File'
                            && ($csvImport->isCompleted()
                                || $csvImport->isStopped()
                                || ($csvImport->isImportError() && $importedItemCount > 0))): ?>
                    <td><?php echo __('No action'); ?></td>
                <?php elseif ($csvImport->format != 'File'
                        && ($csvImport->isCompleted()
                            || $csvImport->isStopped()
                            || ($csvImport->isImportError() && $importedItemCount > 0))): ?>
                    <?php
                    $undoImportUrl = $this->url(array(
                            'action' => 'undo-import',
                            'id' => $csvImport->id,
                        ),
                        'default');
                    ?>
                    <td>
                        <a href="<?php echo html_escape($undoImportUrl); ?>" class="csv-undo-import delete-button"><?php echo html_escape(__('Undo Import')); ?></a>
                    </td>
                <?php elseif ($csvImport->isUndone()
                        || $csvImport->isUndoImportError()
                        || $csvImport->isOtherError()
                        || ($csvImport->isImportError() && $importedItemCount == 0)): ?>
                    <?php
                    $clearHistoryImportUrl = $this->url(array(
                            'action' => 'clear-history',
                            'id' => $csvImport->id,
                        ),
                        'default');
                    ?>
                    <td>
                        <a href="<?php echo html_escape($clearHistoryImportUrl); ?>" class="csv-clear-history delete-button"><?php echo html_escape(__('Clear History')); ?></a>
                    </td>
                <?php else: ?>
                    <td></td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p><?php echo __('You have no imports yet.'); ?></p>
    <?php endif; ?>

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
