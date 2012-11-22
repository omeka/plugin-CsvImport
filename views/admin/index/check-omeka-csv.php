<?php
    echo head(array('title' => __('CSV Import Errors'), 'bodyclass' => 'primary', 'content_class' => 'horizontal-nav'));
?>
<?php echo common('csvimport-nav'); ?>
<div id='primary'>
    <p>
    <?php echo __('The following problems were found with your CSV file and Omeka installation. 
    Usually, these are the result of the elements in your Omeka.net site not having 
    corresponding elements in this installation of Omeka. Either the Dublin Core Extended plugin is not 
    installed, or you created custom item type elements in Omeka.net, but have not yet created them here.');?>
    </p>
    <p><?php echo __('Please correct the errors, then try your import again.'); ?></p>
    <ul>
    <?php foreach($this->errors as $error):?>
        <li><?php echo __('Element "%s" is not found in element set "%s"', array($error['element'], $error['set'])); ?></li>
    <?php endforeach; ?>
    </ul>
</div>
<?php
    echo foot();
?>