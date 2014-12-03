<?php
    echo head(array('title' => __('CSV Import Errors')));
?>
<?php echo common('csvimport-nav'); ?>
<div id='primary'>
    <p>
    <?php echo __('The following problems were found with your CSV file and Omeka installation.'); ?>
    </p>
    <p>
    <?php echo __('Usually, these are the result of the elements in your Omeka.net site not having 
    corresponding elements in this installation of Omeka. Either the Dublin Core Extended plugin is not 
    installed, or you created custom item type elements in Omeka.net, but have not yet created them here.'); ?>
    </p>
    <p><?php echo __('Please correct the errors, then try your import again.'); ?></p>
    <?php echo flash(); ?>
</div>
<?php
    echo foot();
?>
