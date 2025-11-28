<?php

namespace ServiceChargeManager\Includes;

/**
 * Fired during plugin activation
 */
class ServiceChargeManagerActivator
{
    /**
     * Activation function
     */
    public static function activate()
    {
        // Add custom user meta fields to the WordPress users table
        global $wpdb;

        // Array of columns to add
        $columns = array(
            'phone_number' => 'VARCHAR(20) DEFAULT NULL',
            'district' => 'VARCHAR(50) DEFAULT NULL',
            'user_type' => "ENUM('tenant', 'landlord') DEFAULT NULL",
            'property_address' => 'TEXT DEFAULT NULL',
            'property_type' => 'VARCHAR(50) DEFAULT NULL',
            'service_charge_amount' => 'DECIMAL(10,2) DEFAULT 0.00',
            'last_payment_date' => 'DATE DEFAULT NULL',
            'payment_status' => "ENUM('pending', 'paid', 'overdue') DEFAULT 'pending'",
            'next_due_date' => 'DATE DEFAULT NULL',
            'landlord_id' => 'BIGINT(20) DEFAULT NULL'
        );

        // Add each column if it doesn't exist
        foreach ($columns as $column => $definition) {
            $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$wpdb->users} LIKE '$column'");
            if (empty($column_exists)) {
                $wpdb->query("ALTER TABLE {$wpdb->users} ADD $column $definition");
            }
        }

        // Add indexes if they don't exist
        $indexes = $wpdb->get_results("SHOW INDEX FROM {$wpdb->users}");
        $existing_indexes = array_column($indexes, 'Column_name');

        if (!in_array('phone_number', $existing_indexes)) {
            $wpdb->query("ALTER TABLE {$wpdb->users} ADD INDEX phone_number (phone_number)");
        }

        if (!in_array('landlord_id', $existing_indexes)) {
            $wpdb->query("ALTER TABLE {$wpdb->users} ADD INDEX landlord_id (landlord_id)");
        }

        // Create apartments table
        $apartments_table = $wpdb->prefix . 'apartments';
        $apartments_exists = $wpdb->get_var("SHOW TABLES LIKE '$apartments_table'");

        if ($apartments_exists !== $apartments_table) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE IF NOT EXISTS $apartments_table (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                name VARCHAR(255) NOT NULL,
                location VARCHAR(255) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                created_by BIGINT(20) UNSIGNED NOT NULL,
                updated_by VARCHAR(255) DEFAULT NULL,
                PRIMARY KEY (id),
                INDEX created_by (created_by),
                INDEX created_at (created_at)
            ) $charset_collate;";

            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta($sql);
        }

        // Create flats table
        $flats_table = $wpdb->prefix . 'scm_flats';
        $flats_exists = $wpdb->get_var("SHOW TABLES LIKE '$flats_table'");

        if ($flats_exists !== $flats_table) {
            $charset_collate = $wpdb->get_charset_collate();
            $apartments_table = $wpdb->prefix . 'apartments';
            $users_table = $wpdb->prefix . 'users';

            $sql = "CREATE TABLE IF NOT EXISTS $flats_table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        apartment_id BIGINT(20) UNSIGNED NOT NULL,
        name VARCHAR(255) NOT NULL,
        floor_number VARCHAR(50) DEFAULT NULL,
        holder_id BIGINT(20) UNSIGNED DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_by BIGINT(20) UNSIGNED NOT NULL,
        updated_by BIGINT(20) UNSIGNED DEFAULT NULL,

        PRIMARY KEY (id),

        -- Indexes
        INDEX apartment_id (apartment_id),
        INDEX holder_id (holder_id),
        INDEX created_by (created_by),
        INDEX updated_by (updated_by),

        -- Foreign Keys
        CONSTRAINT fk_scm_flats_apartment 
            FOREIGN KEY (apartment_id) REFERENCES {$apartments_table}(id) ON DELETE CASCADE,

        CONSTRAINT fk_scm_flats_created_by 
            FOREIGN KEY (created_by) REFERENCES {$users_table}(ID) ON DELETE RESTRICT,

        CONSTRAINT fk_scm_flats_updated_by 
            FOREIGN KEY (updated_by) REFERENCES {$users_table}(ID) ON DELETE SET NULL

    ) $charset_collate;";

            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta($sql);
        }

        // Create necessary roles
        add_role(
            'landlord',
            __('Landlord', 'service-charge-manager'),
            array(
                'read' => true,
                'edit_posts' => false,
                'delete_posts' => false,
                'manage_tenants' => true,
            )
        );

        // Add Manager role with specific capabilities
        add_role(
            'scm_manager',
            __('SCM Manager', 'service-charge-manager'),
            array(
                'read' => true,
                'manage_scm' => true, // Custom capability for SCM access
                'view_scm_dashboard' => true,
                'manage_district_tenants' => true
            )
        );

        // Set version
        add_option('service_charge_manager_version', SCM_VERSION);

        // Clear permalinks
        flush_rewrite_rules();
    }
}
