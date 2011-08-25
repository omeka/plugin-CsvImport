<?php
    head(array('title' => 'CSV Import', 'bodyclass' => 'primary', 'content_class' => 'horizontal-nav'));
?>
<h1>CSV Import</h1>
<?php echo $this->navigation()->menu()->setUlClass('section-nav'); ?>

<div id="primary">
    <h2>Step 1: Select File and Item Settings</h2>
    <?php echo flash(); ?>
    <?php echo $this->form; ?>
</div>
<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function () {
    jQuery('#omeka_csv_export').click(Omeka.CsvImport.toggleImportOptions);
});
//]]>
</script>
<?php
    foot();
?>
