<?php 
    echo head(array('title' => __('CSV Import')));
?>
<?php echo common('csvimport-nav'); ?>
<div id="primary">
    <h2><?php echo __('Step 2: Map Columns To Elements, Tags, or Files'); ?></h2>
    <?php echo flash(); ?>
    <?php echo $this->form; ?>
</div>
<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function () {
    Omeka.CsvImport.enableElementMapping();
});
//]]>
</script>
<?php 
    echo foot(); 
?>
