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
        add_shortcode('scm_flat_details', array($this, 'render_flat_details'));
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

        // Debug: Log the phone meta
        if (defined('WP_DEBUG') && WP_DEBUG) {
            \ServiceChargeManager\Includes\Debug::log('User meta for phone:', isset($user_meta['scm_phone']) ? $user_meta['scm_phone'] : 'NOT FOUND');
        }

        // Get phone from meta, or extract from username as fallback
        $phone_value = $user_meta['scm_phone'][0] ?? '';
        if (empty($phone_value) && strpos($current_user->user_login, 'scm_') === 0) {
            // Extract phone from username (e.g., scm_8801234567890 -> 8801234567890)
            $phone_value = substr($current_user->user_login, 4); // Remove 'scm_' prefix
        }

        // Get the user's role - check both custom meta and WordPress roles
        $scm_role = $user_meta['scm_role'][0] ?? 'tenant';
        $has_manager_role = in_array('scm_manager', (array)$current_user->roles);
        $is_manager = ($scm_role === 'manager' || $has_manager_role);

        // Prepare user data for the view
        $user_data = array(
            'first_name' => $user_meta['first_name'][0] ?? '',
            'last_name' => $user_meta['last_name'][0] ?? '',
            'email' => $current_user->user_email,
            'phone' => $this->normalize_phone_display($phone_value),
            'address' => $user_meta['scm_address'][0] ?? '',
            'city' => $user_meta['scm_city'][0] ?? '',
            'district' => $user_meta['scm_city'][0] ?? '', // Using city field to store district
            'state' => $user_meta['scm_state'][0] ?? '',
            'postal_code' => $user_meta['scm_postal_code'][0] ?? '',
            'country' => $user_meta['scm_country'][0] ?? '',
            'role' => $scm_role,
            'is_manager' => $is_manager
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

    /**
     * Render flat details view
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function render_flat_details($atts = [])
    {
        // Get apartment ID from shortcode attribute
        $apartment_id = isset($atts['apartment_id']) ? intval($atts['apartment_id']) : 0;

        // If apartment_id not provided in shortcode, try to get from session/query param
        if (empty($apartment_id) && isset($_GET['apartment_id'])) {
            $apartment_id = intval($_GET['apartment_id']);
        }

        if (empty($apartment_id)) {
            return '<p>' . esc_html__('No apartment selected. Please select an apartment first.', 'service-charge-manager') . '</p>';
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            return '<p>' . esc_html__('Please log in to view flat details.', 'service-charge-manager') . '</p>';
        }

        // Get apartment data
        global $wpdb;
        $apartment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}apartments WHERE id = %d",
            $apartment_id
        ));

        if (!$apartment) {
            return '<p>' . esc_html__('Apartment not found.', 'service-charge-manager') . '</p>';
        }

        // Prepare apartment data for view
        $apartment_data = [
            'id' => $apartment->id,
            'name' => $apartment->name,
            'location' => $apartment->location,
            'created_by' => $apartment->created_by
        ];

        ob_start();
        $view_file = SCM_PLUGIN_DIR . 'src/Public/Views/flat-details.php';

        if (file_exists($view_file)) {
            include $view_file;
        } else {
            echo '<p>' . esc_html__('Flat details view is temporarily unavailable.', 'service-charge-manager') . '</p>';
        }

        return ob_get_clean();
    }

    /**
     * Get all flats for an apartment
     *
     * @param int $apartment_id
     * @return array
     */
    public function get_flats_by_apartment($apartment_id)
    {
        global $wpdb;
        $apartment_id = intval($apartment_id);
        $flats_table = $wpdb->prefix . 'scm_flats';

        $flats = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $flats_table WHERE apartment_id = %d ORDER BY created_at DESC",
            $apartment_id
        ));

        return $flats ? $flats : [];
    }

    /**
     * Add a new flat
     *
     * @param array $data Flat data
     * @return int|false Flat ID on success, false on failure
     */
    public function add_flat($data)
    {
        global $wpdb;
        $user_id = get_current_user_id();
        $flats_table = $wpdb->prefix . 'scm_flats';

        $insert_data = [
            'apartment_id' => intval($data['apartment_id'] ?? 0),
            'name' => sanitize_text_field($data['name'] ?? ''),
            'floor_number' => sanitize_text_field($data['floor_number'] ?? ''),
            'created_by' => $user_id,
            'updated_by' => $user_id,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ];

        // Validate required fields
        if (empty($insert_data['apartment_id']) || empty($insert_data['name'])) {
            return false;
        }

        $result = $wpdb->insert(
            $flats_table,
            $insert_data,
            ['%d', '%s', '%s', '%d', '%s', '%s', '%s']
        );

        if ($result) {
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Update a flat
     *
     * @param int $flat_id
     * @param array $data
     * @return bool
     */
    public function update_flat($flat_id, $data)
    {
        global $wpdb;
        $user_id = get_current_user_id();
        $flats_table = $wpdb->prefix . 'scm_flats';

        $update_data = [
            'name' => sanitize_text_field($data['name'] ?? ''),
            'floor_number' => sanitize_text_field($data['floor_number'] ?? ''),
            'updated_by' => $user_id,
            'updated_at' => current_time('mysql'),
        ];

        $result = $wpdb->update(
            $flats_table,
            $update_data,
            ['id' => intval($flat_id)],
            ['%s', '%s', '%d', '%s'],
            ['%d']
        );

        return $result !== false;
    }

    /**
     * Delete a flat
     *
     * @param int $flat_id
     * @return bool
     */
    public function delete_flat($flat_id)
    {
        global $wpdb;
        $flats_table = $wpdb->prefix . 'scm_flats';

        $result = $wpdb->delete(
            $flats_table,
            ['id' => intval($flat_id)],
            ['%d']
        );

        return $result !== false;
    }

    /**
     * Delete all flats for an apartment
     *
     * @param int $apartment_id
     * @return bool
     */
    public function delete_flats_by_apartment($apartment_id)
    {
        global $wpdb;
        $flats_table = $wpdb->prefix . 'scm_flats';

        $result = $wpdb->delete(
            $flats_table,
            ['apartment_id' => intval($apartment_id)],
            ['%d']
        );

        return $result !== false;
    }
}
