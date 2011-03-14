<?php head(array('title' => 'CSV Import', 'bodyclass' => 'primary', 'content_class' => 'horizontal-nav')); ?>
<h1>CSV Import</h1>
<?php echo $this->navigation()->menu()->setUlClass('section-nav'); ?>

<div id="primary">
    <h2>PHP-CLI Configuration Error</h2>
    <?php echo flash(); ?>
</div>

<?php foot(); ?>
