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
        add_menu_page(
            __('Service Charge Manager', 'service-charge-manager'),
            __('Service Charges', 'service-charge-manager'),
            'manage_scm', // Custom capability that both admin and manager have
            'service-charge-manager',
            array($this, 'render_admin_page'),
            'dashicons-money-alt',
            30
        );
    }

    /**
     * Render the admin page
     */
    public function render_admin_page()
    {
        $view_file = SCM_PLUGIN_DIR . 'src/Admin/Views/users-list.php';

        if (file_exists($view_file)) {
            include $view_file;
        } else {
            echo '<p>' . esc_html__('Admin page is temporarily unavailable.', 'service-charge-manager') . '</p>';
        }
    }
}
