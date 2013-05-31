<?php
    echo head(array('title' => 'CSV Import', 'bodyclass' => 'primary', 'content_class' => 'horizontal-nav'));
?>
<?php echo common('csvimport-nav'); ?>
<div id="primary">
    <?php echo flash(); ?>
    <h2><?php echo __('Step 1: Select file and item settings'); ?></h2>
    <?php echo $this->form; ?>
</div>
<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function () {
    jQuery('#format-Report').click(Omeka.CsvImport.updateImportOptions);
    jQuery('#format-Item').click(Omeka.CsvImport.updateImportOptions);
    jQuery('#format-File').click(Omeka.CsvImport.updateImportOptions);
    jQuery('#format-Mix').click(Omeka.CsvImport.updateImportOptions);
    jQuery('#column_delimiter_name').click(Omeka.CsvImport.updateColumnDelimiterField);
    jQuery('#element_delimiter_name').click(Omeka.CsvImport.updateElementDelimiterField);
    jQuery('#tag_delimiter_name').click(Omeka.CsvImport.updateTagDelimiterField);
    jQuery('#file_delimiter_name').click(Omeka.CsvImport.updateFileDelimiterField);
    Omeka.CsvImport.updateOnLoad(); // Need this to reset invalid forms.
});
//]]>
</script>
<?php
    echo foot();
?>
