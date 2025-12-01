<?php

namespace ServiceChargeManager\Admin;

class AdminManager
{
    /**
     * Initialize the class and set its properties.
     */
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_menu_page'));
    }

    /**
     * Register the admin menu page
     */
    public function add_menu_page()
    {
        // Main menu for both admin and manager
        $main_page = add_menu_page(
            __('Service Charge Manager', 'service-charge-manager'),
            __('Service Charges', 'service-charge-manager'),
            'manage_scm', // Custom capability that both admin and manager have
            'service-charge-manager',
            array($this, 'render_dashboard_page'),
            'dashicons-money-alt',
            30
        );

        // Add Dashboard submenu
        add_submenu_page(
            'service-charge-manager',
            __('Dashboard', 'service-charge-manager'),
            __('Dashboard', 'service-charge-manager'),
            'manage_scm',
            'service-charge-manager',
            array($this, 'render_dashboard_page')
        );

        // Add Users submenu
        add_submenu_page(
            'service-charge-manager',
            __('Users', 'service-charge-manager'),
            __('Users', 'service-charge-manager'),
            'manage_scm',
            'scm-users',
            array($this, 'render_users_page')
        );
    }

    /**
     * Render the dashboard page
     */
    public function render_dashboard_page()
    {
        $view_file = SCM_PLUGIN_DIR . 'src/Admin/Views/dashboard.php';

        if (file_exists($view_file)) {
            include $view_file;
        } else {
            echo '<p>' . esc_html__('Dashboard page is temporarily unavailable.', 'service-charge-manager') . '</p>';
        }
    }

    /**
     * Render the users page
     */
    public function render_users_page()
    {
        $view_file = SCM_PLUGIN_DIR . 'src/Admin/Views/users-list.php';

        if (file_exists($view_file)) {
            include $view_file;
        } else {
            echo '<p>' . esc_html__('Users page is temporarily unavailable.', 'service-charge-manager') . '</p>';
        }
    }
}
