<?php

namespace ServiceChargeManager\Public;

class Frontend
{
    /**
     * Initialize the class and set its properties.
     */
    public function __construct()
    {
        add_action('init', array($this, 'register_shortcodes'));
        add_action('init', array('ServiceChargeManager\Public\WorkflowManager', 'init'));

        // Set extended auth cookie expiration (2 years)
        add_filter('auth_cookie_expiration', array($this, 'extend_auth_cookie_expiration'), 10, 3);
    }

    /**
     * Register shortcodes
     */
    public function register_shortcodes()
    {
        add_shortcode('scm_auth', array($this, 'render_auth_tabs'));
        add_shortcode('scm_registration_details', array($this, 'render_registration_details'));
        add_shortcode('scm_dashboard', array($this, 'render_dashboard'));
    }

    /**
     * Render auth tabs shortcode
     */
    public function render_auth_tabs()
    {
        ob_start();
        $view_file = SCM_PLUGIN_DIR . 'src/Public/Views/auth-tabs.php';

        if (file_exists($view_file)) {
            include $view_file;
        } else {
            echo '<p>' . esc_html__('Authentication module is temporarily unavailable.', 'service-charge-manager') . '</p>';
        }

        return ob_get_clean();
    }
    /**
     * Render registration details view
     */
    public function render_registration_details()
    {
        // Enforce workflow restriction
        \ServiceChargeManager\Public\WorkflowManager::enforceWorkflow('registration');

        ob_start();
        $view_file = SCM_PLUGIN_DIR . 'src/Public/Views/registration-details.php';
        // Log the resolved path in debug mode to diagnose include errors
        if (defined('WP_DEBUG') && WP_DEBUG) {
            \ServiceChargeManager\Includes\Debug::log('Including registration view file', $view_file);
        }

        if (file_exists($view_file)) {
            include $view_file;
        } else {
            // Log and show a minimal message (avoid PHP warnings which break JSON responses)
            if (defined('WP_DEBUG') && WP_DEBUG) {
                \ServiceChargeManager\Includes\Debug::log('Registration view file not found', $view_file);
            }
            echo '<p>' . esc_html__('Registration page is temporarily unavailable.', 'service-charge-manager') . '</p>';
        }

        return ob_get_clean();
    }

    /**
     * Render dashboard view
     */
    public function render_dashboard()
    {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return '<p>' . esc_html__('Please log in to view your dashboard.', 'service-charge-manager') . '</p>';
        }

        // If user is logged in, set workflow to dashboard so they can access it
        \ServiceChargeManager\Public\WorkflowManager::setCurrentStep('dashboard');

        // Enforce workflow restriction
        \ServiceChargeManager\Public\WorkflowManager::enforceWorkflow('dashboard');

        ob_start();
        $view_file = SCM_PLUGIN_DIR . 'src/Public/Views/dashboard.php';

        if (defined('WP_DEBUG') && WP_DEBUG) {
            \ServiceChargeManager\Includes\Debug::log('Including dashboard view file', $view_file);
        }

        // Get current user data
        $current_user = wp_get_current_user();
        $user_meta = get_user_meta($current_user->ID);

        // Prepare user data for the view
        $user_data = array(
            'first_name' => $user_meta['first_name'][0] ?? '',
            'last_name' => $user_meta['last_name'][0] ?? '',
            'email' => $current_user->user_email,
            'phone' => $this->normalize_phone_display($user_meta['scm_phone'][0] ?? ''),
            'address' => $user_meta['scm_address'][0] ?? '',
            'city' => $user_meta['scm_city'][0] ?? '',
            'district' => $user_meta['scm_city'][0] ?? '', // Using city field to store district
            'state' => $user_meta['scm_state'][0] ?? '',
            'postal_code' => $user_meta['scm_postal_code'][0] ?? '',
            'country' => $user_meta['scm_country'][0] ?? '',
            'role' => $user_meta['scm_role'][0] ?? 'tenant',
            'is_manager' => in_array('scm_manager', (array)$current_user->roles)
        );

        if (file_exists($view_file)) {
            include $view_file;
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                \ServiceChargeManager\Includes\Debug::log('Dashboard view file not found', $view_file);
            }
            echo '<p>' . esc_html__('Dashboard is temporarily unavailable.', 'service-charge-manager') . '</p>';
        }

        return ob_get_clean();
    }

    /**
     * Normalize phone number for display
     * Converts formats like +8801700000000 or 8801700000000 to 01700000000
     *
     * @param string $phone Phone number to normalize
     * @return string Normalized phone number
     */
    private function normalize_phone_display($phone)
    {
        if (empty($phone)) {
            return '';
        }

        // Remove all non-numeric characters except +
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // Remove + if present
        $phone = ltrim($phone, '+');

        // If starts with 88, remove it (Bangladesh country code)
        if (strpos($phone, '88') === 0) {
            $phone = substr($phone, 2);
        }

        return $phone;
    }

    /**
     * Extend auth cookie expiration to 2 years
     *
     * @param int    $expiration The default expiration time
     * @param int    $user_id    User ID
     * @param bool   $remember   Whether to remember the user
     * @return int Extended expiration time in seconds
     */
    public function extend_auth_cookie_expiration($expiration, $user_id, $remember)
    {
        // If remember is true, set 2 years (63072000 seconds)
        if ($remember) {
            return 63072000; // 2 years in seconds
        }
        return $expiration;
    }
}
