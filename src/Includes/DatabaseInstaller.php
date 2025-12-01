<?php

namespace ServiceChargeManager\Includes;

/**
 * Database Installer Class
 * Handles table creation and upgrades following wptblight-pro pattern
 */
class DatabaseInstaller
{
    /**
     * Current database version
     */
    const VERSION = 1;

    /**
     * Version option key
     */
    const VERSION_OPTION = 'scm_db_version';

    /**
     * Install or upgrade database
     *
     * @return void
     */
    public static function install()
    {
        // Require WordPress upgrade functions
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Add custom user columns first
        self::add_user_columns();

        // Get current version
        $current_version = get_option(self::VERSION_OPTION, 0);

        // Install base tables if first install
        if ($current_version == 0) {
            self::install_base_tables();
        }

        // Run upgrades if needed
        if ($current_version < self::VERSION) {
            self::run_upgrades($current_version);
        }

        // Update version
        update_option(self::VERSION_OPTION, self::VERSION);
    }

    /**
     * Install base tables from SQL files
     *
     * @return void
     */
    private static function install_base_tables()
    {
        global $wpdb;

        $install_dir = SCM_PLUGIN_DIR . 'database/install/';

        // Get all SQL files from install directory
        $sql_files = glob($install_dir . '*.sql');

        if (empty($sql_files)) {
            return;
        }

        // Sort files to maintain order (01_, 02_, etc.)
        sort($sql_files);

        // Execute each SQL file
        foreach ($sql_files as $file) {
            $sql = file_get_contents($file);

            if ($sql === false) {
                continue;
            }

            // Replace placeholder with WordPress table prefix
            $sql = str_replace('{WPDB_PREFIX}', $wpdb->prefix, $sql);

            // Execute the SQL
            dbDelta($sql);
        }
    }

    /**
     * Run incremental upgrades
     *
     * @param int $from_version Current version
     * @return void
     */
    private static function run_upgrades($from_version)
    {
        global $wpdb;

        $updates_dir = SCM_PLUGIN_DIR . 'database/updates/';

        // Loop through each version that needs upgrading
        for ($v = $from_version + 1; $v <= self::VERSION; $v++) {
            $upgrade_file = $updates_dir . $v . '/upgrade.sql';

            if (!file_exists($upgrade_file)) {
                continue;
            }

            $sql = file_get_contents($upgrade_file);

            if ($sql === false) {
                continue;
            }

            // Replace placeholder
            $sql = str_replace('{WPDB_PREFIX}', $wpdb->prefix, $sql);

            // Execute the upgrade
            dbDelta($sql);
        }
    }

    /**
     * Add custom columns to WordPress users table
     *
     * @return void
     */
    private static function add_user_columns()
    {
        // User columns are handled through user meta, not direct table columns
    }

    /**
     * Check if database needs upgrade
     *
     * @return bool
     */
    public static function needs_upgrade()
    {
        $current_version = get_option(self::VERSION_OPTION, 0);
        return $current_version < self::VERSION;
    }

    /**
     * Get current database version
     *
     * @return int
     */
    public static function get_current_version()
    {
        return (int) get_option(self::VERSION_OPTION, 0);
    }

    /**
     * Get latest database version
     *
     * @return int
     */
    public static function get_latest_version()
    {
        return self::VERSION;
    }
}
