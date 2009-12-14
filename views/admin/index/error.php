<?php head(array('title' => 'CSV Import', 'bodyclass' => 'primary', 'content_class' => 'horizontal-nav')); ?>
<h1>CSV Import</h1>
<ul id="section-nav" class="navigation">
    <li class="">
        <a href="<?php echo html_escape(uri('csv-import')); ?>">Import Items</a>
    </li>
    <li class="">
        <a href="<?php echo html_escape(uri('csv-import/index/status')); ?>">Status</a>
    </li>
</ul>

<div id="primary">
    <h2>PHP-CLI Configuration Error</h2>
    <?php echo flash(); ?>
</div>

<?php foot(); ?>