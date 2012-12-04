<nav id="section-nav" class="navigation vertical">
<?php
    $navArray = array(
        array(
            'label' => 'Import Items',
            'action' => 'index',
            'module' => 'csv-import',
        ),
        array(
            'label' => 'Status',
            'action' => 'browse',
            'module' => 'csv-import',
        ),
    );
    echo nav($navArray, 'admin_navigation_settings');
?>
</nav>