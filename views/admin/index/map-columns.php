<?php
    echo head(array('title' => 'CSV Import', 'bodyclass' => 'primary',
        'content_class' => 'horizontal-nav'));
?>
<?php echo common('csvimport-nav'); ?>
<div id="primary">
    <h2><?php echo __('Step 2: Map columns to elements, tags, or files'); ?></h2>
    <?php echo flash(); ?>
    <?php echo $this->form; ?>
</div>
<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function () {
    Omeka.CsvImport.enableElementMapping();
    Omeka.CsvImport.assistWithMapping();
});
//]]>
</script>
<?php
    echo foot();
?>
