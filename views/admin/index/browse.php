<?php 
    head(array('title' => 'CSV Import', 'bodyclass' => 'primary', 
        'content_class' => 'horizontal-nav'));
?>
<h1>CSV Import</h1>
<?php echo $this->navigation()->menu()->setUlClass('section-nav'); ?>

<div id="primary">
    <h2>Status</h2>
    <?php echo flash(); ?>
    <div class="pagination"><?php echo pagination_links(); ?></div>
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
            <?php foreach($csvimport_imports as $csvImport): ?>
            <tr>
                <td><?php echo html_escape($csvImport->added); ?></td>
                <td><?php echo html_escape($csvImport->original_filename); ?></td>
                <td><?php echo $csvImport->getProgress(); ?></td>
                <td><?php echo html_escape(Inflector::humanize($csvImport->status)); ?></td>
                <?php
                    if ($csvImport->isFinished() 
                        || $csvImport->isStopped()
                        || $csvImport->isError()): ?>
                    <td><?php echo delete_button($this->url(
                        array('action' => 'undo-import',
                              'id' => $csvImport->id),
                        'default'),
                        'undo_import',
                        'Undo Import',
                        array('class' => 'csv-undo-import delete-button')); ?>
                <?php elseif ($csvImport->isUndone()): ?>
                    <td><?php echo delete_button($this->url(
                        array('action' => 'clear-history',
                              'id' => $csvImport->id),
                        'default'),
                        'clear_history',
                        'Clear History',
                        array('class' => 'csv-clear-history delete-button')); ?>
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
    foot(); 
?>
