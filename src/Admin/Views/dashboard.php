<?php

/**
 * Admin Dashboard View
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php esc_html_e('Service Charge Manager Dashboard', 'service-charge-manager'); ?></h1>

    <div class="scm-dashboard-container" style="margin-top: 30px;">
        <!-- Welcome Section -->
        <div class="notice notice-info" style="padding: 20px; margin-bottom: 30px;">
            <h2><?php esc_html_e('Welcome to Service Charge Manager', 'service-charge-manager'); ?></h2>
            <p><?php esc_html_e('This dashboard provides an overview of your service charge management system and available shortcodes for frontend views.', 'service-charge-manager'); ?></p>
        </div>

        <!-- Shortcodes Documentation -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin-bottom: 30px;">

            <!-- Shortcode 1: Apartments List -->
            <div style="background: #fff; border: 1px solid #ccc; border-radius: 4px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <h3 style="margin-top: 0; color: #0073aa;"><?php esc_html_e('Dashboard', 'service-charge-manager'); ?></h3>
                <p><strong><?php esc_html_e('Description:', 'service-charge-manager'); ?></strong></p>
                <p><?php esc_html_e('Displays a list of all apartments in the system.', 'service-charge-manager'); ?></p>

                <p><strong><?php esc_html_e('Shortcode:', 'service-charge-manager'); ?></strong></p>
                <code style="background: #f0f0f0; padding: 10px; display: block; border-radius: 3px; margin: 10px 0;">
                    [scm_dashboard]
                </code>
                <p><strong><?php esc_html_e('Slug:', 'service-charge-manager'); ?></strong></p>
                <code style="background: #f0f0f0; padding: 10px; display: block; border-radius: 3px; margin: 10px 0;">
                    dashboard
                </code>

                <p><strong><?php esc_html_e('Usage:', 'service-charge-manager'); ?></strong></p>
                <p><?php esc_html_e('Add this shortcode to any page or post to display all apartments.', 'service-charge-manager'); ?></p>
            </div>

            <!-- Shortcode 2: Flats/Units List -->
            <div style="background: #fff; border: 1px solid #ccc; border-radius: 4px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <h3 style="margin-top: 0; color: #0073aa;"><?php esc_html_e('Flat-details', 'service-charge-manager'); ?></h3>
                <p><strong><?php esc_html_e('Description:', 'service-charge-manager'); ?></strong></p>
                <p><?php esc_html_e('Displays a list of all flats/units with their apartment information.', 'service-charge-manager'); ?></p>

                <p><strong><?php esc_html_e('Shortcode:', 'service-charge-manager'); ?></strong></p>
                <code style="background: #f0f0f0; padding: 10px; display: block; border-radius: 3px; margin: 10px 0;">
                    [scm_flat_details]
                </code>
                <p><strong><?php esc_html_e('Slug:', 'service-charge-manager'); ?></strong></p>
                <code style="background: #f0f0f0; padding: 10px; display: block; border-radius: 3px; margin: 10px 0;">
                    flat-details
                </code>

                <p><strong><?php esc_html_e('Usage:', 'service-charge-manager'); ?></strong></p>
                <p><?php esc_html_e('Add this shortcode to any page or post to display all flats/units.', 'service-charge-manager'); ?></p>
            </div>

            <!-- Shortcode 3: Tenant Dashboard -->
            <div style="background: #fff; border: 1px solid #ccc; border-radius: 4px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <h3 style="margin-top: 0; color: #0073aa;"><?php esc_html_e('Registration Details', 'service-charge-manager'); ?></h3>
                <p><strong><?php esc_html_e('Description:', 'service-charge-manager'); ?></strong></p>
                <p><?php esc_html_e('Displays a personalized dashboard for tenants to view their service charges and payment status.', 'service-charge-manager'); ?></p>

                <p><strong><?php esc_html_e('Shortcode:', 'service-charge-manager'); ?></strong></p>
                <code style="background: #f0f0f0; padding: 10px; display: block; border-radius: 3px; margin: 10px 0;">
                    [scm_registration_details]
                </code>
                <p><strong><?php esc_html_e('Slug:', 'service-charge-manager'); ?></strong></p>
                <code style="background: #f0f0f0; padding: 10px; display: block; border-radius: 3px; margin: 10px 0;">
                    registration-details
                </code>

                <p><strong><?php esc_html_e('Usage:', 'service-charge-manager'); ?></strong></p>
                <p><?php esc_html_e('Add this shortcode to a page dedicated for tenants. It will show their charges and payment history.', 'service-charge-manager'); ?></p>
            </div>

            <!-- Shortcode 4: Landlord Dashboard -->
            <div style="background: #fff; border: 1px solid #ccc; border-radius: 4px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <h3 style="margin-top: 0; color: #0073aa;"><?php esc_html_e('Login/Signup Form', 'service-charge-manager'); ?></h3>
                <p><strong><?php esc_html_e('Description:', 'service-charge-manager'); ?></strong></p>
                <p><?php esc_html_e('Displays a landlord dashboard to manage their properties and tenants.', 'service-charge-manager'); ?></p>

                <p><strong><?php esc_html_e('Shortcode:', 'service-charge-manager'); ?></strong></p>
                <code style="background: #f0f0f0; padding: 10px; display: block; border-radius: 3px; margin: 10px 0;">
                    [scm_auth]
                </code>
                <p><strong><?php esc_html_e('slug:', 'service-charge-manager'); ?></strong></p>
                <code style="background: #f0f0f0; padding: 10px; display: block; border-radius: 3px; margin: 10px 0;">
                    show-signup-or-login
                </code>

                <p><strong><?php esc_html_e('Usage:', 'service-charge-manager'); ?></strong></p>
                <p><?php esc_html_e('Add this shortcode to a page for landlords. It allows them to manage their properties and view tenant information.', 'service-charge-manager'); ?></p>
            </div>
        </div>
    </div>