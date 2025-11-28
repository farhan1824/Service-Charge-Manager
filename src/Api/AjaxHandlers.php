<?php

namespace ServiceChargeManager\Api;

use ServiceChargeManager\User\UserManager;
use ServiceChargeManager\Includes\Debug;
use ServiceChargeManager\Services\SmsService;

class AjaxHandlers
{
    private $userManager;
    private $smsService;

    /**
     * Initialize the class and set its properties.
     */
    public function __construct()
    {
        $this->userManager = new UserManager();
        $this->smsService = new SmsService();

        // Add debug action for both logged-in and logged-out users
        // Only attach debug logger when debugging is enabled to avoid accidental output
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('wp_ajax_scm_send_otp', array($this, 'debug_send_otp'), 5);
            add_action('wp_ajax_nopriv_scm_send_otp', array($this, 'debug_send_otp'), 5);
        }

        // Manager role assignment handlers
        add_action('wp_ajax_scm_assign_manager', array($this, 'assign_manager'));
        add_action('wp_ajax_scm_revoke_manager', array($this, 'revoke_manager'));

        // Add handler for both logged-in and logged-out users
        add_action('wp_ajax_scm_send_otp', array($this, 'handle_send_otp'), 10);
        add_action('wp_ajax_nopriv_scm_send_otp', array($this, 'handle_send_otp'), 10);

        // Login handler
        // Login handler for both logged-in and logged-out users
        add_action('wp_ajax_scm_login', array($this, 'handle_login'));       // logged-in users
        add_action('wp_ajax_nopriv_scm_login', array($this, 'handle_login')); // logged-out users


        // Other signup handlers
        add_action('wp_ajax_nopriv_scm_verify_otp', array($this, 'handle_verify_otp'));
        add_action('wp_ajax_scm_verify_otp', array($this, 'handle_verify_otp'));

        add_action('wp_ajax_nopriv_scm_complete_registration', array($this, 'handle_complete_registration'));
        add_action('wp_ajax_scm_complete_registration', array($this, 'handle_complete_registration'));

        // User management handlers
        add_action('wp_ajax_scm_assign_role', array($this, 'assign_role'));
        add_action('wp_ajax_scm_update_user', array($this, 'update_user'));
        add_action('wp_ajax_scm_update_profile_inline', array($this, 'handle_update_profile_inline'));

        // Apartment management handlers (manager only)
        add_action('wp_ajax_scm_add_apartment', array($this, 'handle_add_apartment'));
        add_action('wp_ajax_scm_get_apartments', array($this, 'handle_get_apartments'));
        add_action('wp_ajax_scm_delete_apartment', array($this, 'handle_delete_apartment'));
        add_action('wp_ajax_scm_update_apartment', array($this, 'handle_update_apartment'));

        // Logout handler (logged-in users only)
        add_action('wp_ajax_scm_logout', array($this, 'handle_logout'));

        add_filter('authenticate', function ($user, $username, $password) {
            // Only bypass if we're using phone login (scm_ prefix)
            if (strpos($username, 'scm_') === 0) {
                $wp_user = get_user_by('login', $username);
                if ($wp_user) {
                    return $wp_user; // bypass WordPress password check
                }
            }
            return $user;
        }, 20, 3);
    }

    /**
     * Debug handler for OTP requests
     */
    public function debug_send_otp()
    {
        // Use error_log only and avoid calling functions that may not exist
        error_log('Debug OTP Request - POST: ' . print_r($_POST, true));
        error_log('Debug OTP Request - REQUEST: ' . print_r($_REQUEST, true));
        if (function_exists('getallheaders')) {
            error_log('Debug OTP Request - Headers: ' . print_r(getallheaders(), true));
        } else {
            // Fallback: capture common HTTP headers from $_SERVER
            $headers = [];
            foreach ($_SERVER as $key => $value) {
                if (strpos($key, 'HTTP_') === 0) {
                    $headers[$key] = $value;
                }
            }
            error_log('Debug OTP Request - Server Headers: ' . print_r($headers, true));
        }
    }
    /**
     * Handle user login
     */

    public function handle_login()
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Login Request - POST: ' . print_r($_POST, true));
            error_log('Login Request - REQUEST: ' . print_r($_REQUEST, true));
        }

        try {
            // Use non-fatal nonce checks to provide clearer JSON errors. Accept either 'nonce' or '_wpnonce'
            $provided_nonce = $_REQUEST['nonce'] ?? ($_REQUEST['_wpnonce'] ?? null);
            $nonce_ok = false;

            // Check both possible request keys without dying
            if (isset($_REQUEST['nonce'])) {
                $nonce_ok = check_ajax_referer('scm_login', 'nonce', false);
            }
            if (!$nonce_ok && isset($_REQUEST['_wpnonce'])) {
                $nonce_ok = check_ajax_referer('scm_login', '_wpnonce', false);
            }
            if (!$nonce_ok && isset($_REQUEST['login_nonce'])) {
                $nonce_ok = wp_verify_nonce(sanitize_text_field($_REQUEST['login_nonce']), 'scm_login');
            }

            if (!$nonce_ok) {
                error_log('Login nonce verification failed; provided: ' . print_r($provided_nonce, true));
                wp_send_json_error(array(
                    'message' => __('Security check failed. Please refresh the page and try again.', 'service-charge-manager'),
                    'debug' => array(
                        'provided_nonce' => $provided_nonce ?? 'none',
                        'expected_keys' => array('nonce', '_wpnonce', 'login_nonce')
                    )
                ));
                return;
            }

            if (!isset($_POST['phone'])) {
                wp_send_json_error(array(
                    'message' => __('Phone number is required.', 'service-charge-manager'),
                    'debug' => 'Phone parameter missing'
                ));
                return;
            }

            $phone = sanitize_text_field($_POST['phone']);

            // Validate phone number
            if (!preg_match('/^(\+8801|8801|01)[0-9]{9}$/', $phone)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Phone validation failed: ' . $phone);
                }
                wp_send_json_error(array(
                    'message' => __('Please enter a valid Bangladesh phone number', 'service-charge-manager'),
                    'debug' => 'Invalid phone format: ' . $phone
                ));
                return;
            }

            /**
             * ========================================
             * Normalize phone number for DB lookup
             * ========================================
             */
            // Remove "+" only (keep 880)
            $phone = preg_replace('/^\+/', '', $phone);

            // If it starts with 01, add '88' prefix
            if (preg_match('/^01[0-9]{9}$/', $phone)) {
                $phone = '88' . $phone;
            }

            // Add 'scm_' prefix for DB storage
            $scm_phone = 'scm_' . $phone;

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Normalized phone: ' . $scm_phone);
            }

            // Get user by formatted phone
            $user = $this->userManager->getUserByPhone($scm_phone);

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('getUserByPhone result: ' . print_r($user, true));
            }

            if (!$user) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Login attempt failed: No user found for phone ' . $phone);
                }
                wp_send_json_error(array(
                    'message' => __('No account found with this phone number. Please sign up.', 'service-charge-manager'),
                    'redirect' => home_url('/index.php/show-signup-or-login/')
                ));
                return;
            }

            // Check user status
            if ($user['scm_status'] !== 'active') {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Login attempt failed: User inactive. Status: ' . $user['scm_status']);
                }
                wp_send_json_error(array(
                    'message' => __('Your account is not active. Please contact support.', 'service-charge-manager')
                ));
                return;
            }

            // Log user in with persistent session (remember = true makes it persistent)
            wp_clear_auth_cookie();
            wp_set_current_user($user['id']);
            wp_set_auth_cookie($user['id'], true);

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Login successful for user ID: ' . $user['id']);
            }

            // Set redirect URL based on user role
            $redirect_url = home_url('/index.php/dashboard/');

            wp_send_json_success(array(
                'message' => __('Login successful! Redirecting...', 'service-charge-manager'),
                // 'redirect' => home_url('/index.php/dashboard/'),
                'redirect' => $redirect_url,
                'user' => array(
                    'phone' => $scm_phone,
                    'status' => $user['scm_status'],
                    'role' => $user['scm_role']
                )
            ));
        } catch (\Exception $e) {
            error_log('Login error: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => __('An error occurred during login. Please try again.', 'service-charge-manager'),
                'debug' => WP_DEBUG ? $e->getMessage() : null
            ));
        }
    }



    /**
     * Handle OTP sending for signup
     */
    public function handle_send_otp()
    {
        try {
            // Early nonce verification so the handler always returns JSON on failure
            $nonce_ok = check_ajax_referer('scm_signup', 'nonce', false);
            if (!$nonce_ok) {
                wp_send_json_error(array(
                    'message' => __('Security check failed.', 'service-charge-manager'),
                    'debug' => 'Nonce verification failed or missing'
                ));
            }
            // Basic validation
            if (empty($_POST['phone'])) {
                wp_send_json_error(array(
                    'message' => __('Phone number is required.', 'service-charge-manager')
                ));
            }

            $phone = $this->smsService->formatPhone($_POST['phone']);
            $resend = isset($_POST['resend']) && $_POST['resend'] === 'true';

            // Generate OTP
            $otp = wp_rand(100000, 999999);

            // Store OTP with 10-minute expiry
            $transient_key = 'scm_otp_' . $phone;
            set_transient($transient_key, $otp, 10 * MINUTE_IN_SECONDS);

            // Prepare SMS message
            $message = sprintf(
                __('Your Service Charge Manager verification code is: %d. Valid for 10 minutes.', 'service-charge-manager'),
                $otp
            );

            // Send SMS
            $sms_result = $this->smsService->sendSms($phone, $message);

            if (is_wp_error($sms_result)) {
                error_log('SMS sending failed: ' . $sms_result->get_error_message());
                wp_send_json_error(array(
                    'message' => __('Failed to send verification code. Please try again.', 'service-charge-manager')
                ));
            }

            // Return success response
            wp_send_json_success(array(
                'message' => __('Verification code sent successfully!', 'service-charge-manager'),
                'dev_otp' => WP_DEBUG ? $otp : null, // Only in debug mode
                'debug' => WP_DEBUG ? array(
                    'phone' => $phone,
                    'sms_result' => $sms_result,
                    'transient_key' => $transient_key
                ) : null
            ));

            // Check if nonce exists
            if (!isset($_REQUEST['nonce'])) {
                Debug::log('Nonce not provided');
                wp_send_json_error(array(
                    'message' => __('Security token missing.', 'service-charge-manager'),
                    'debug' => 'Nonce not provided'
                ));
            }

            // Verify nonce with detailed logging
            $nonce = $_REQUEST['nonce'];
            Debug::log('Verifying nonce', array(
                'provided_nonce' => $nonce,
                'action' => 'scm_signup'
            ));

            if (!check_ajax_referer('scm_signup', 'nonce', false)) {
                Debug::log('Nonce verification failed');
                wp_send_json_error(array(
                    'message' => __('Security check failed.', 'service-charge-manager'),
                    'debug' => 'Nonce verification failed',
                    'nonce_info' => array(
                        'provided' => $nonce,
                        'expected_action' => 'scm_signup'
                    )
                ));
            }

            if (!isset($_POST['phone'])) {
                wp_send_json_error(array(
                    'message' => __('Phone number is required.', 'service-charge-manager'),
                    'debug' => 'Phone number not provided'
                ));
            }

            $phone = sanitize_text_field($_POST['phone']);
            $resend = isset($_POST['resend']) && $_POST['resend'];

            // Match Bangladeshi phone number format
            if (!preg_match('/^(\+8801|8801|01)[0-9]{9}$/', $phone)) {
                wp_send_json_error(array(
                    'message' => __('Please enter a valid Bangladeshi phone number.', 'service-charge-manager'),
                    'debug' => 'Invalid phone format'
                ));
            }

            // Normalize phone number to remove +88 or 88 prefix
            $phone = preg_replace('/^\+?88/', '', $phone);

            // Check if phone is already registered (only for new registrations)
            if (!$resend && !$this->userManager->isPhoneUnique($phone)) {
                wp_send_json_error(array(
                    'message' => __('This phone number is already registered. Please login.', 'service-charge-manager'),
                    'debug' => 'Phone already registered'
                ));
            }

            // Generate OTP
            $otp = wp_rand(100000, 999999);

            // Store OTP in transient (10 minutes expiry)
            $transient_set = set_transient('scm_otp_' . $phone, $otp, 10 * MINUTE_IN_SECONDS);

            if (!$transient_set) {
                wp_send_json_error(array(
                    'message' => __('Failed to generate OTP. Please try again.', 'service-charge-manager'),
                    'debug' => 'Transient not set'
                ));
            }

            // TODO: Integrate with actual SMS service
            // For development, just return success with OTP
            wp_send_json_success(array(
                'message' => __('Verification code sent successfully!', 'service-charge-manager'),
                'dev_otp' => $otp, // Remove this in production
                'debug' => array(
                    'phone' => $phone,
                    'resend' => $resend,
                    'transient_key' => 'scm_otp_' . $phone
                )
            ));
        } catch (\Exception $e) {
            wp_send_json_error(array(
                'message' => __('An error occurred. Please try again.', 'service-charge-manager'),
                'debug' => $e->getMessage()
            ));
        }
    }

    /**
     * Handle OTP verification for signup
     */
    public function handle_verify_otp()
    {
        // Log request details in debug mode to help diagnose 400 responses
        if (defined('WP_DEBUG') && WP_DEBUG) {
            Debug::log('Verify OTP request POST: ' . print_r($_POST, true));
            Debug::log('Verify OTP request REQUEST: ' . print_r($_REQUEST, true));
            $headers = [];
            foreach ($_SERVER as $k => $v) {
                if (strpos($k, 'HTTP_') === 0) {
                    $headers[$k] = $v;
                }
            }
            Debug::log('Verify OTP server headers: ' . print_r($headers, true));
        }
        // Use non-fatal nonce check so we can always respond with JSON
        $nonce_ok = check_ajax_referer('scm_signup', 'nonce', false);
        if (!$nonce_ok) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'service-charge-manager'),
                'debug' => 'Nonce verification failed or missing in verify_otp'
            ));
        }

        $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
        $otp = isset($_POST['otp']) ? sanitize_text_field($_POST['otp']) : '';

        // Basic validations
        if (empty($phone)) {
            wp_send_json_error(array('message' => __('Invalid phone number', 'service-charge-manager')));
        }

        if (!preg_match('/^\d{6}$/', $otp)) {
            wp_send_json_error(array('message' => __('Invalid verification code', 'service-charge-manager')));
        }

        // Normalize phone the same way we do when sending OTP so transient key matches
        $phone = $this->smsService->formatPhone($phone);

        // Get stored OTP using normalized phone
        $stored_otp = get_transient('scm_otp_' . $phone);

        if (!$stored_otp || $stored_otp != $otp) {
            wp_send_json_error(array(
                'message' => __('Invalid or expired verification code', 'service-charge-manager'),
                'debug' => array('provided_otp' => $otp, 'stored_otp' => $stored_otp, 'transient_key' => 'scm_otp_' . $phone)
            ));
        }

        // OTP verified - store verified phone in session
        if (!session_id()) {
            session_start();
        }
        $_SESSION['scm_verified_phone'] = $phone;

        // Delete used OTP
        delete_transient('scm_otp_' . $phone);

        // Update workflow state
        \ServiceChargeManager\Public\WorkflowManager::setCurrentStep('registration');

        // Redirect to the registration details page using index.php in the URL to maintain permalink structure
        wp_send_json_success(array(
            'redirect' => home_url('/index.php/registration-details/')
        ));
    }

    /**
     * Handle complete registration
     */
    public function handle_complete_registration()
    {
        // Require a valid nonce for this action
        check_ajax_referer('scm_signup', 'nonce');

        // Verify session and get verified phone set during OTP verification
        if (!session_id()) {
            session_start();
        }

        // Prefer the phone saved in session (set during OTP verification).
        $phone = isset($_SESSION['scm_verified_phone']) ? $_SESSION['scm_verified_phone'] : '';

        // If session doesn't have the phone (session lost), accept the hidden phone field from the form
        if (empty($phone) && !empty($_POST['phone'])) {
            $phone = $this->smsService->formatPhone(sanitize_text_field($_POST['phone']));
        }

        if (empty($phone)) {
            wp_send_json_error(array(
                'message' => __('Phone verification required. Please verify your phone again.', 'service-charge-manager')
            ));
        }

        // The registration form may send different fields depending on the view.
        // Accept both 'name' (full name) or separate 'first_name'/'last_name'.
        $raw_name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        if (empty($raw_name)) {
            $raw_first = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
            $raw_last = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
            $raw_name = trim($raw_first . ' ' . $raw_last);
        }

        // Split full name into first and last name (best effort)
        $first_name = '';
        $last_name = '';
        if (!empty($raw_name)) {
            $parts = preg_split('/\s+/', $raw_name);
            $first_name = array_shift($parts);
            $last_name = trim(implode(' ', $parts));
        }

        // Map district -> city (the project stores district in scm_city)
        $district = isset($_POST['district']) ? sanitize_text_field($_POST['district']) : (isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '');

        // Collect other fields (these may be empty/null and that's fine)
        $address = isset($_POST['address']) ? sanitize_textarea_field($_POST['address']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $state = isset($_POST['state']) ? sanitize_text_field($_POST['state']) : '';
        $postal_code = isset($_POST['postal_code']) ? sanitize_text_field($_POST['postal_code']) : '';
        $country = isset($_POST['country']) ? sanitize_text_field($_POST['country']) : '';

        // Get user-selected role from registration form
        $selected_role = isset($_POST['scm_role']) ? sanitize_text_field($_POST['scm_role']) : 'flatholder';

        // Map "flatholder" to internal role "subscriber", "manager" stays as "manager"
        $scm_role = ($selected_role === 'manager') ? 'manager' : 'subscriber';

        // Prepare user data in the shape expected by UserManager
        $userData = array(
            'phone' => $phone,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'address' => $address,
            'city' => $district,
            'state' => $state,
            'postal_code' => $postal_code,
            'country' => $country,
            'scm_role' => $scm_role,
            'scm_status' => 'active'
        );

        // ✅ Check if phone already exists before creating user
        if (!$this->userManager->isPhoneUnique($phone)) {
            // ..............................
            // Clear the workflow session
            \ServiceChargeManager\Public\WorkflowManager::resetWorkflow();

            // Clear the verified phone from session
            unset($_SESSION['scm_verified_phone']);
            // ...................................
            wp_send_json_error(array(
                'message' => __('This phone number is already registered. Please log in instead.', 'service-charge-manager'),
                'redirect' => home_url('/index.php/show-signup-or-login')
            ));
        }

        // Create user (this will also call updateUserMeta with scm_ keys)
        $user_id = $this->userManager->createUser($userData);

        if (is_wp_error($user_id)) {
            wp_send_json_error(array(
                'message' => $user_id->get_error_message()
            ));
        }


        // Clear the verification marker from session
        unset($_SESSION['scm_verified_phone']);

        // Log the user in with persistent session (remember = true makes it persistent)
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, true);

        // Update workflow state
        \ServiceChargeManager\Public\WorkflowManager::setCurrentStep('dashboard');

        // Successful registration - redirect to dashboard
        wp_send_json_success(array(
            'redirect' => home_url('/index.php/dashboard/')
        ));
    }

    /**
     * Assign role to user
     */
    public function assign_role()
    {
        check_ajax_referer('scm-admin-nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Permission denied', 'service-charge-manager')
            ));
        }

        $user_id = intval($_POST['user_id']);
        $role = sanitize_text_field($_POST['role']);

        // Validate role
        if (!in_array($role, ['tenant', 'landlord', 'admin'])) {
            wp_send_json_error(array(
                'message' => __('Invalid role specified', 'service-charge-manager')
            ));
        }

        // Update user role
        $result = update_user_meta($user_id, 'scm_role', $role);

        if (!$result) {
            wp_send_json_error(array(
                'message' => __('Failed to update user role', 'service-charge-manager')
            ));
        }

        wp_send_json_success(array(
            'message' => __('Role assigned successfully', 'service-charge-manager')
        ));
    }

    /**
     * Update user details
     */
    public function update_user()
    {
        check_ajax_referer('scm-user-nonce', 'nonce');

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array(
                'message' => __('User not logged in', 'service-charge-manager')
            ));
        }

        // Get user data from POST
        $userData = array(
            'first_name' => sanitize_text_field($_POST['first_name']),
            'last_name' => sanitize_text_field($_POST['last_name']),
            'email' => sanitize_email($_POST['email']),
            'address' => sanitize_textarea_field($_POST['address']),
            'city' => sanitize_text_field($_POST['city']),
            'state' => sanitize_text_field($_POST['state']),
            'postal_code' => sanitize_text_field($_POST['postal_code']),
            'country' => sanitize_text_field($_POST['country'])
        );

        // Update user meta
        $result = $this->userManager->updateUserMeta($user_id, $userData);

        if (!$result) {
            wp_send_json_error(array(
                'message' => __('Failed to update user details', 'service-charge-manager')
            ));
        }

        wp_send_json_success(array(
            'message' => __('Profile updated successfully', 'service-charge-manager')
        ));
    }

    /**
     * Assign manager role to a user
     */
    public function assign_manager()
    {
        check_ajax_referer('scm-admin-nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Permission denied. Only administrators can assign managers.', 'service-charge-manager')
            ));
            return;
        }

        // Get and validate inputs
        if (!isset($_POST['phone']) || !isset($_POST['district'])) {
            wp_send_json_error(array(
                'message' => __('Phone and district are required.', 'service-charge-manager')
            ));
            return;
        }

        $phone = sanitize_text_field($_POST['phone']);
        $district = sanitize_text_field($_POST['district']);

        // Validate phone number
        if (!preg_match('/^(\+8801|8801|01)[0-9]{9}$/', $phone)) {
            wp_send_json_error(array(
                'message' => __('Invalid phone number format.', 'service-charge-manager')
            ));
            return;
        }

        // Normalize the phone number to match stored format
        if (preg_match('/^01[0-9]{9}$/', $phone)) {
            $phone = '88' . $phone;
        }
        $phone = preg_replace('/^\+/', '', $phone);

        // Get user by phone
        $user = $this->userManager->getUserByPhone('scm_' . $phone);
        if (!$user) {
            wp_send_json_error(array(
                'message' => __('User not found with this phone number.', 'service-charge-manager')
            ));
            return;
        }

        $user_id = $user['id'];

        // Check if user is already a manager
        $user_obj = get_user_by('ID', $user_id);
        if (in_array('scm_manager', (array)$user_obj->roles)) {
            wp_send_json_error(array(
                'message' => __('This user is already a manager.', 'service-charge-manager')
            ));
            return;
        }

        // Add manager role
        $user_obj->add_role('scm_manager');

        // Update manager metadata
        update_user_meta($user_id, '_scm_manager_district', $district);
        update_user_meta($user_id, 'user_type', 'landlord'); // Set as landlord when promoting to manager

        // Make sure phone number is stored properly
        $normalized_phone = 'scm_' . $phone;
        update_user_meta($user_id, 'scm_phone', $normalized_phone);

        wp_send_json_success(array(
            'message' => __('Manager role assigned successfully.', 'service-charge-manager'),
            'dev_debug' => WP_DEBUG ? array(
                'user_id' => $user_id,
                'district' => $district,
                'phone' => $phone
            ) : null
        ));
    }

    /**
     * Revoke manager role from a user
     */
    public function revoke_manager()
    {
        check_ajax_referer('scm-admin-nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Permission denied. Only administrators can revoke managers.', 'service-charge-manager')
            ));
            return;
        }

        if (!isset($_POST['user_id'])) {
            wp_send_json_error(array(
                'message' => __('User ID is required.', 'service-charge-manager')
            ));
            return;
        }

        $user_id = intval($_POST['user_id']);
        $user = get_user_by('ID', $user_id);

        if (!$user) {
            wp_send_json_error(array(
                'message' => __('User not found.', 'service-charge-manager')
            ));
            return;
        }
        // Remove manager role
        $user->remove_role('scm_manager');

        // Remove manager metadata
        delete_user_meta($user_id, '_scm_manager_district');
        update_user_meta($user_id, 'user_type', 'tenant'); // Reset to tenant when revoking manager

        wp_send_json_success(array(
            'message' => __('Manager role revoked successfully.', 'service-charge-manager')
        ));
    }

    /**
     * Update profile inline (AJAX handler)
     */
    public function handle_update_profile_inline()
    {
        check_ajax_referer('scm_update_profile_nonce', 'nonce'); // Verify nonce

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => 'User not logged in']);
        }

        // Sanitize fields
        $first_name = sanitize_text_field($_POST['first_name'] ?? '');
        $last_name  = sanitize_text_field($_POST['last_name'] ?? '');
        $phone      = sanitize_text_field($_POST['scm_phone'] ?? '');
        $address    = sanitize_textarea_field($_POST['scm_address'] ?? '');

        // Update WP user fields
        wp_update_user([
            'ID'         => $user_id,
            'first_name' => $first_name,
            'last_name'  => $last_name,
        ]);

        // Update user meta with prefix
        if (!empty($phone)) {
            update_user_meta($user_id, 'scm_phone', $phone);
        } else {
            // If phone not provided, try to populate from username as fallback
            $user = get_user_by('id', $user_id);
            if ($user && strpos($user->user_login, 'scm_') === 0) {
                $extracted_phone = substr($user->user_login, 4); // Remove 'scm_' prefix
                update_user_meta($user_id, 'scm_phone', $extracted_phone);
            }
        }

        update_user_meta($user_id, 'scm_address', $address);

        wp_send_json_success(['message' => 'Profile updated successfully']);
    }

    /**
     * Add new apartment (Manager only)
     */
    public function handle_add_apartment()
    {
        check_ajax_referer('scm-user-nonce', 'nonce');

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => __('User not logged in', 'service-charge-manager')));
            return;
        }

        // Check if user is manager (check both WordPress role and custom meta)
        $user = get_userdata($user_id);
        $scm_role = get_user_meta($user_id, 'scm_role', true);
        $is_manager = in_array('scm_manager', (array)$user->roles) || $scm_role === 'manager';

        if (!$is_manager) {
            wp_send_json_error(array('message' => __('Only managers can add apartments', 'service-charge-manager')));
            return;
        }

        // Validate input - name is required, location is optional
        $name = sanitize_text_field($_POST['name'] ?? '');
        $location = isset($_POST['location']) && $_POST['location'] !== 'null' ? sanitize_text_field($_POST['location']) : null;

        if (empty($name)) {
            wp_send_json_error(array('message' => __('Apartment name is required', 'service-charge-manager')));
            return;
        }

        global $wpdb;
        $apartments_table = $wpdb->prefix . 'apartments';

        $result = $wpdb->insert(
            $apartments_table,
            array(
                'name' => $name,
                'location' => $location,
                'created_by' => $user_id,
                'created_at' => current_time('mysql')
            ),
            array('%s', $location !== null ? '%s' : null, '%d', '%s')
        );

        if ($result === false) {
            error_log('Apartment insert failed: ' . $wpdb->last_error);
            wp_send_json_error(array('message' => __('Failed to add apartment', 'service-charge-manager')));
            return;
        }

        wp_send_json_success(array(
            'message' => __('Apartment added successfully!', 'service-charge-manager'),
            'apartment_id' => $wpdb->insert_id
        ));
    }

    /**
     * Get apartments for current manager
     */
    public function handle_get_apartments()
    {
        check_ajax_referer('scm-user-nonce', 'nonce');

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => __('User not logged in', 'service-charge-manager')));
            return;
        }

        // Check if user is manager (check both WordPress role and custom meta)
        $user = get_userdata($user_id);
        $scm_role = get_user_meta($user_id, 'scm_role', true);
        $is_manager = in_array('scm_manager', (array)$user->roles) || $scm_role === 'manager';

        if (!$is_manager) {
            wp_send_json_error(array('message' => __('Only managers can view apartments', 'service-charge-manager')));
            return;
        }

        global $wpdb;
        $apartments_table = $wpdb->prefix . 'apartments';

        $apartments = $wpdb->get_results($wpdb->prepare(
            "SELECT id, name, location, created_at, updated_at FROM $apartments_table WHERE created_by = %d ORDER BY created_at DESC",
            $user_id
        ));

        wp_send_json_success(array('apartments' => $apartments));
    }

    /**
     * Delete apartment (Manager only)
     */
    public function handle_delete_apartment()
    {
        check_ajax_referer('scm-user-nonce', 'nonce');

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => __('User not logged in', 'service-charge-manager')));
            return;
        }

        // Check if user is manager (check both WordPress role and custom meta)
        $user = get_userdata($user_id);
        $scm_role = get_user_meta($user_id, 'scm_role', true);
        $is_manager = in_array('scm_manager', (array)$user->roles) || $scm_role === 'manager';

        if (!$is_manager) {
            wp_send_json_error(array('message' => __('Only managers can delete apartments', 'service-charge-manager')));
            return;
        }

        $apartment_id = intval($_POST['apartment_id'] ?? 0);
        if ($apartment_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid apartment ID', 'service-charge-manager')));
            return;
        }

        global $wpdb;
        $apartments_table = $wpdb->prefix . 'apartments';

        // Verify ownership
        $apartment = $wpdb->get_row($wpdb->prepare(
            "SELECT id, created_by FROM $apartments_table WHERE id = %d",
            $apartment_id
        ));

        if (!$apartment || $apartment->created_by != $user_id) {
            wp_send_json_error(array('message' => __('You cannot delete this apartment', 'service-charge-manager')));
            return;
        }

        $result = $wpdb->delete(
            $apartments_table,
            array('id' => $apartment_id),
            array('%d')
        );

        if ($result === false) {
            wp_send_json_error(array('message' => __('Failed to delete apartment', 'service-charge-manager')));
            return;
        }

        wp_send_json_success(array('message' => __('Apartment deleted successfully', 'service-charge-manager')));
    }

    /**
     * Update apartment (Manager only)
     */
    public function handle_update_apartment()
    {
        check_ajax_referer('scm-user-nonce', 'nonce');

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => __('User not logged in', 'service-charge-manager')));
            return;
        }

        // Check if user is manager (check both WordPress role and custom meta)
        $user = get_userdata($user_id);
        $scm_role = get_user_meta($user_id, 'scm_role', true);
        $is_manager = in_array('scm_manager', (array)$user->roles) || $scm_role === 'manager';

        if (!$is_manager) {
            wp_send_json_error(array('message' => __('Only managers can update apartments', 'service-charge-manager')));
            return;
        }

        $apartment_id = intval($_POST['apartment_id'] ?? 0);
        $name = sanitize_text_field($_POST['name'] ?? '');
        $location = isset($_POST['location']) && $_POST['location'] !== 'null' ? sanitize_text_field($_POST['location']) : null;

        if ($apartment_id <= 0 || empty($name)) {
            wp_send_json_error(array('message' => __('Invalid input data', 'service-charge-manager')));
            return;
        }

        global $wpdb;
        $apartments_table = $wpdb->prefix . 'apartments';

        // Verify ownership
        $apartment = $wpdb->get_row($wpdb->prepare(
            "SELECT id, created_by FROM $apartments_table WHERE id = %d",
            $apartment_id
        ));

        if (!$apartment || $apartment->created_by != $user_id) {
            wp_send_json_error(array('message' => __('You cannot update this apartment', 'service-charge-manager')));
            return;
        }

        $result = $wpdb->update(
            $apartments_table,
            array(
                'name' => $name,
                'location' => $location,
                'updated_by' => $user->display_name,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $apartment_id),
            array('%s', '%s', '%s', '%s'),
            array('%d')
        );

        if ($result === false) {
            wp_send_json_error(array('message' => __('Failed to update apartment', 'service-charge-manager')));
            return;
        }

        wp_send_json_success(array('message' => __('Apartment updated successfully', 'service-charge-manager')));
    }

    /**
     * Handle user logout
     */
    public function handle_logout()
    {
        check_ajax_referer('scm-user-nonce', 'nonce');

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => __('You are not logged in', 'service-charge-manager')));
            return;
        }

        // Clear auth cookies and current user
        wp_logout();

        wp_send_json_success(array(
            'message' => __('You have been logged out successfully', 'service-charge-manager'),
            'redirect' => home_url('/index.php/show-signup-or-login/')
        ));
    }
}
