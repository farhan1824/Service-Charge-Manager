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
        // Install or upgrade database using DatabaseInstaller
        DatabaseInstaller::install();

        // Create necessary roles
        self::create_roles();

        // Clear permalinks
        flush_rewrite_rules();
    }

    /**
     * Create custom WordPress roles
     *
     * @return void
     */
    private static function create_roles()
    {
        // Get admin role and add capability
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->add_cap('manage_scm');
        }

        // Create Landlord role
        add_role(
            'landlord',
            __('Landlord', 'service-charge-manager'),
            array(
                'read' => true,
                'edit_posts' => false,
                'delete_posts' => false,
                'manage_tenants' => true,
                'manage_scm' => true,
            )
        );

        // Add Manager role with specific capabilities
        add_role(
            'scm_manager',
            __('SCM Manager', 'service-charge-manager'),
            array(
                'read' => true,
                'manage_scm' => true,
                'view_scm_dashboard' => true,
                'manage_district_tenants' => true
            )
        );

        // Set version
        add_option('service_charge_manager_version', SCM_VERSION);
    }
}
