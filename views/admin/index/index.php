<?php
    echo head(array('title' => __('CSV Import')));
?>
<?php echo common('csvimport-nav'); ?>
<div id="primary">
    <?php echo flash(); ?>
    <h2><?php echo __('Step 1: Select File and Item Settings'); ?></h2>
    <?php echo $this->form; ?>
</div>
<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function () {
    jQuery('#omeka_csv_export').click(Omeka.CsvImport.updateImportOptions);
    Omeka.CsvImport.updateImportOptions(); // need this to reset invalid forms
});
//]]>
</script>
<?php
    echo foot();
?>
