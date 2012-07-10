<?php
    head(array('title' => 'CSV Import -- Omeka.net import errors', 'bodyclass' => 'primary', 'content_class' => 'horizontal-nav'));
?>

<h1>CSV Import Errors</h1>
<div id='primary'>
    <p>The following problems were found with your CSV file and Omeka installation. 
    Usually, these are the result of the elements in your Omeka.net site not having 
    corresponding elements in this installation of Omeka. Either the Dublin Core Extended plugin is not installed, or 
    you created custom item type elements in Omeka.net, but have not yet created them here.
    </p>
    
    <p>Please correct the errors, then try your import again.</p>
    <ul>
    <?php foreach($this->errors as $error):?>
        <li>Element "<?php echo $error['element']; ?>" not found in element set <?php echo $error['set']; ?></li>
    <?php endforeach; ?>
    </ul>

</div>

<?php
    foot();
?>