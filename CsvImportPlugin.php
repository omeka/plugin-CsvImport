<?php
/**
 * CsvImportPlugin class - represents the Csv Import plugin
 *
 * @copyright Copyright 2008-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 * @package CsvImport
 */

defined('CSV_IMPORT_DIRECTORY') or define('CSV_IMPORT_DIRECTORY', dirname(__FILE__));

/**
 * Csv Import plugin.
 */
class CsvImportPlugin extends Omeka_Plugin_AbstractPlugin
{
    const MEMORY_LIMIT_OPTION_NAME = 'csv_import_memory_limit';
    const PHP_PATH_OPTION_NAME = 'csv_import_php_path';

    /**
     * @var array Hooks for the plugin.
     */
    protected $_hooks = array(
        'install',
        'uninstall',
        'upgrade',
        'initialize',
        'admin_head',
        'define_acl',
    );

    /**
     * @var array Filters for the plugin.
     */
    protected $_filters = array('admin_navigation_main');

    /**
     * @var array Options and their default values.
     */
    protected $_options = array(
        self::MEMORY_LIMIT_OPTION_NAME => '',
        self::PHP_PATH_OPTION_NAME => '',
        'csv_import_html_elements' => TRUE,
        'csv_import_automap_columns' => TRUE,
        CsvImport_RowIterator::COLUMN_DELIMITER_OPTION_NAME => CsvImport_RowIterator::DEFAULT_COLUMN_DELIMITER,
        CsvImport_RowIterator::ENCLOSURE_OPTION_NAME => CsvImport_RowIterator::DEFAULT_ENCLOSURE,
        CsvImport_ColumnMap_Element::ELEMENT_DELIMITER_OPTION_NAME => CsvImport_ColumnMap_Element::DEFAULT_ELEMENT_DELIMITER,
        CsvImport_ColumnMap_Tag::TAG_DELIMITER_OPTION_NAME => CsvImport_ColumnMap_Tag::DEFAULT_TAG_DELIMITER,
        CsvImport_ColumnMap_File::FILE_DELIMITER_OPTION_NAME => CsvImport_ColumnMap_File::DEFAULT_FILE_DELIMITER,
    );

    /**
     * Install the plugin.
     */
    public function hookInstall()
    {
        $db = $this->_db;

        // Create csv imports table.
        $db->query("CREATE TABLE IF NOT EXISTS `{$db->prefix}csv_import_imports` (
            `id` int(10) unsigned NOT NULL auto_increment,
            `item_type_id` int(10) unsigned NULL,
            `collection_id` int(10) unsigned NULL,
            `format` varchar(255) collate utf8_unicode_ci NOT NULL,
            `owner_id` int unsigned NOT NULL,
            `delimiter` varchar(1) collate utf8_unicode_ci NOT NULL,
            `enclosure` varchar(1) collate utf8_unicode_ci NOT NULL,
            `original_filename` text collate utf8_unicode_ci NOT NULL,
            `file_path` text collate utf8_unicode_ci NOT NULL,
            `file_position` bigint unsigned NOT NULL,
            `status` varchar(255) collate utf8_unicode_ci,
            `row_count` int(10) unsigned NOT NULL,
            `skipped_row_count` int(10) unsigned NOT NULL,
            `skipped_item_count` int(10) unsigned NOT NULL,
            `is_public` tinyint(1) default '0',
            `is_featured` tinyint(1) default '0',
            `serialized_column_maps` text collate utf8_unicode_ci NOT NULL,
            `added` timestamp NOT NULL default '0000-00-00 00:00:00',
            PRIMARY KEY  (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");

        // create csv imported items table
        $db->query("CREATE TABLE IF NOT EXISTS `{$db->prefix}csv_import_imported_items` (
            `id` int(10) unsigned NOT NULL auto_increment,
            `item_id` int(10) unsigned NOT NULL,
            `source_item_id` varchar(255) collate utf8_unicode_ci,
            `import_id` int(10) unsigned NOT NULL,
            PRIMARY KEY  (`id`),
            KEY `source_item_id_import_id` (`source_item_id`, `import_id`),
            KEY (`import_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");

        $this->_installOptions();
    }

    /**
     * Uninstall the plugin.
     */
    public function hookUninstall()
    {
        $db = $this->_db;

        // drop the tables
        $sql = "DROP TABLE IF EXISTS `{$db->prefix}csv_import_imports`";
        $db->query($sql);
        $sql = "DROP TABLE IF EXISTS `{$db->prefix}csv_import_imported_items`";
        $db->query($sql);

        $this->_uninstallOptions();
    }

    /**
     * Upgrade the plugin.
     */
    public function hookUpgrade($args)
    {
        $oldVersion = $args['old_version'];
        $newVersion = $args['new_version'];
        $db = $this->_db;

        if (version_compare($oldVersion, '2.0-dev', '<=')) {
            $sql = "UPDATE `{$db->prefix}csv_import_imports` SET `status` = ? WHERE `status` = ?";
            $db->query($sql, array('other_error', 'error'));
        }

        if (version_compare($oldVersion, '2.0', '<=')) {
            set_option('csv_import_html_elements', $this->_options['csv_import_html_elements']);
            set_option('csv_import_automap_columns', $this->_options['csv_import_automap_columns']);
            set_option(CsvImport_RowIterator::COLUMN_DELIMITER_OPTION_NAME, CsvImport_RowIterator::DEFAULT_COLUMN_DELIMITER);
            set_option(CsvImport_ColumnMap_Element::ELEMENT_DELIMITER_OPTION_NAME, CsvImport_ColumnMap_Element::DEFAULT_ELEMENT_DELIMITER);
            set_option(CsvImport_ColumnMap_Tag::TAG_DELIMITER_OPTION_NAME, CsvImport_ColumnMap_Tag::DEFAULT_TAG_DELIMITER);
            set_option(CsvImport_ColumnMap_File::FILE_DELIMITER_OPTION_NAME, CsvImport_ColumnMap_File::DEFAULT_FILE_DELIMITER);
        }

        if (version_compare($oldVersion, '2.0.1', '<=')) {
            $sql = "
                ALTER TABLE `{$db->prefix}csv_import_imports`
                CHANGE `item_type_id` `item_type_id` INT( 10 ) UNSIGNED NULL,
                CHANGE `collection_id` `collection_id` INT( 10 ) UNSIGNED NULL
            ";
            $db->query($sql);
        }

        if (version_compare($oldVersion, '2.0.2', '<=')) {
            $sql = "SHOW COLUMNS FROM `{$db->prefix}csv_import_imports` LIKE 'enclosure'";
            $result = $db->query($sql)->fetch();
            if (empty($result)) {
                $sql = "
                    ALTER TABLE `{$db->prefix}csv_import_imports`
                    ADD `enclosure` varchar(1) collate utf8_unicode_ci NOT NULL AFTER `delimiter`
                ";
                $db->query($sql);
            }
            set_option(CsvImport_RowIterator::ENCLOSURE_OPTION_NAME, CsvImport_RowIterator::DEFAULT_ENCLOSURE);
        }

        if (version_compare($oldVersion, '2.1', '<=')) {
            $sql = "SHOW COLUMNS FROM `{$db->prefix}csv_import_imports` LIKE 'record_type_id'";
            $result = $db->query($sql)->fetch();
            if (!empty($result)) {
                $sql = "
                    ALTER TABLE `{$db->prefix}csv_import_imports`
                    CHANGE `record_type_id` `format` varchar(255) collate utf8_unicode_ci NOT NULL AFTER `collection_id`
                ";
                $db->query($sql);
            }

            $sql = "SHOW COLUMNS FROM `{$db->prefix}csv_import_imports` LIKE 'format'";
            $result = $db->query($sql)->fetch();
            if (empty($result)) {
                $sql = "
                    ALTER TABLE `{$db->prefix}csv_import_imports`
                    ADD `format` varchar(255) collate utf8_unicode_ci NOT NULL AFTER `collection_id`
                ";
                $db->query($sql);
            }

            $sql = "SHOW COLUMNS FROM `{$db->prefix}csv_import_imports` LIKE 'row_count'";
            $result = $db->query($sql)->fetch();
            if (empty($result)) {
                $sql = "
                    ALTER TABLE `{$db->prefix}csv_import_imports`
                    ADD `row_count` int(10) unsigned NOT NULL AFTER `status`
                ";
                $db->query($sql);
            }

            $sql = "SHOW COLUMNS FROM `{$db->prefix}csv_import_imported_items` LIKE 'source_item_id'";
            $result = $db->query($sql)->fetch();
            if (empty($result)) {
                $sql = "
                    ALTER TABLE `{$db->prefix}csv_import_imported_items`
                    ADD `source_item_id` varchar(255) collate utf8_unicode_ci NOT NULL AFTER `item_id`
                ";
                $db->query($sql);
            }

            // Update index. Item id is no more unique, because CsvImport can
            // import files separately, so an item can be updated. Furthermore,
            // now, any metadata can be updated individualy too.
            $sql = "SHOW INDEX FROM `{$db->prefix}csv_import_imported_items` WHERE KEY_NAME = 'source_item_id_import_id'";
            $result = $db->query($sql)->fetch();
            if (!empty($result)) {
                $sql = "
                    ALTER TABLE `{$db->prefix}csv_import_imported_items`
                    DROP INDEX `source_item_id_import_id`
                ";
                $db->query($sql);
            }
            $sql = "
                ALTER TABLE `{$db->prefix}csv_import_imported_items`
                ADD INDEX `source_item_id_import_id` (`source_item_id`, `import_id`)
            ";
            $db->query($sql);
            $sql = "SHOW INDEX FROM `{$db->prefix}csv_import_imported_items` WHERE KEY_NAME = 'item_id'";
            $result = $db->query($sql)->fetch();
            if (!empty($result)) {
                $sql = "
                    ALTER TABLE `{$db->prefix}csv_import_imported_items`
                    DROP INDEX `item_id`
                ";
                $db->query($sql);
            }
            $sql = "SHOW INDEX FROM `{$db->prefix}csv_import_imported_items` WHERE KEY_NAME = 'import_id'";
            $result = $db->query($sql)->fetch();
            if (!empty($result)) {
                $sql = "
                    ALTER TABLE `{$db->prefix}csv_import_imported_items`
                    ADD INDEX (`import_id`)
                ";
                $db->query($sql);
            }
        }
    }

    /**
     * Add the translations.
     */
    public function hookInitialize()
    {
        add_translation_source(dirname(__FILE__) . '/languages');
    }

    /**
     * Define the ACL.
     *
     * @param array $args
     */
    public function hookDefineAcl($args)
    {
        $acl = $args['acl']; // get the Zend_Acl

        $acl->addResource('CsvImport_Index');

        // Hack to disable CRUD actions.
        $acl->deny(null, 'CsvImport_Index', array('show', 'add', 'edit', 'delete'));
        $acl->deny('admin', 'CsvImport_Index');
    }

    /**
     * Configure admin theme header.
     *
     * @param array $args
     */
    public function hookAdminHead($args)
    {
        $request = Zend_Controller_Front::getInstance()->getRequest();
        if ($request->getModuleName() == 'csv-import') {
            queue_css_file('csv-import-main');
            queue_js_file('csv-import');
        }
    }

    /**
     * Add the Simple Pages link to the admin main navigation.
     *
     * @param array Navigation array.
     * @return array Filtered navigation array.
     */
    public function filterAdminNavigationMain($nav)
    {
        $nav[] = array(
            'label' => __('Csv Import'),
            'uri' => url('csv-import'),
            'resource' => 'CsvImport_Index',
            'privilege' => 'index',
        );
        return $nav;
    }
}
