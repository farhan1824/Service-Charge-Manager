<?php

namespace ServiceChargeManager\User;

class UserManager
{
    /**
     * Create a new user with custom meta
     *
     * @param array $userData Array containing user data
     * @return int|WP_Error User ID on success, WP_Error on failure
     */
    public function createUser($userData)
    {
        // Generate username from phone (we'll use phone as login)
        $base = 'scm_' . preg_replace('/[^0-9]/', '', $userData['phone']);
        $username = $base;
        // Ensure username uniqueness. If phone is empty or collision occurs, append random suffixes.
        $attempt = 0;
        while (username_exists($username) && $attempt < 10) {
            $username = $base . '_' . wp_rand(1000, 9999);
            $attempt++;
        }
        // If still exists or base empty, generate a fallback username
        if (username_exists($username) || empty($username)) {
            $username = 'scm_' . uniqid();
        }

        // Generate a random password (user can set it later if needed)
        $password = wp_generate_password(16, true, true);

        // Create WP user
        $userArgs = array(
            'user_login' => $username,
            'user_pass' => $password,
            'user_email' => $userData['email'] ?? '',
            'display_name' => trim($userData['first_name'] . ' ' . $userData['last_name']),
            'first_name' => $userData['first_name'] ?? '',
            'last_name' => $userData['last_name'] ?? '',
            'role' => 'subscriber' // Default WP role
        );

        $user_id = wp_insert_user($userArgs);

        if (is_wp_error($user_id)) {
            return $user_id;
        }

        // Add our custom meta
        $this->updateUserMeta($user_id, $userData);

        return $user_id;
    }

    /**
     * Update user meta with our custom fields
     *
     * @param int $user_id WordPress user ID
     * @param array $userData Array containing user data
     * @return bool True on success, false on failure
     */
    public function updateUserMeta($user_id, $userData)
    {
        $meta_fields = [
            'scm_phone' => $userData['phone'] ?? '',
            'scm_address' => $userData['address'] ?? '',
            'scm_city' => $userData['city'] ?? '',
            'scm_state' => $userData['state'] ?? '',
            'scm_postal_code' => $userData['postal_code'] ?? '',
            'scm_country' => $userData['country'] ?? '',
            'scm_role' => $userData['scm_role'] ?? 'tenant',
            'scm_status' => $userData['scm_status'] ?? 'pending'
        ];

        foreach ($meta_fields as $key => $value) {
            // Always update meta for keys present in our map. Allow empty strings to be saved
            // so user-provided empty values are persisted and not skipped.
            update_user_meta($user_id, $key, $value);
        }

        return true;
    }

    /**
     * Get user by phone number
     *
     * @param string $phone Phone number
     * @return array|false User data or false if not found
     */

    public function getUserByPhone($phone)
    {
        $normalized_phone = preg_replace('/[^0-9+]/', '', $phone);

        // 1️⃣ Try to find user by phone meta first
        $users = get_users([
            'meta_key' => 'scm_phone',
            'meta_value' => $normalized_phone,
            'number' => 1
        ]);

        if (!empty($users)) {
            error_log("✅ User found by meta: {$normalized_phone}");
            return $this->getUserData($users[0]->ID);
        }

        // 2️⃣ If not found, try by user_login (e.g. scm_8801234569981)
        $login_username = 'scm_' . preg_replace('/[^0-9]/', '', $phone);
        $user = get_user_by('login', $login_username);

        if ($user) {
            error_log("✅ User found by login: {$login_username}");
            return $this->getUserData($user->ID);
        }

        // 3️⃣ Not found at all
        error_log("❌ No user found for phone or login: {$phone}");
        return false;
    }

    /**
     * Get combined user data (WP user + our meta)
     *
     * @param int $user_id WordPress user ID
     * @return array Combined user data
     */
    public function getUserData($user_id)
    {
        $user = get_userdata($user_id);

        if (!$user) {
            return false;
        }

        // Get raw stored phone
        $raw_phone = get_user_meta($user_id, 'scm_phone', true);

        // Remove scm_ prefix ALWAYS
        $clean_phone = preg_replace('/^scm_/', '', $raw_phone);

        // Get our custom meta
        $meta_fields = [
            'phone' => $clean_phone,
            'address' => get_user_meta($user_id, 'scm_address', true),
            'city' => get_user_meta($user_id, 'scm_city', true),
            'state' => get_user_meta($user_id, 'scm_state', true),
            'postal_code' => get_user_meta($user_id, 'scm_postal_code', true),
            'country' => get_user_meta($user_id, 'scm_country', true),
            'scm_role' => get_user_meta($user_id, 'scm_role', true),
            'scm_status' => get_user_meta($user_id, 'scm_status', true)
        ];

        return array_merge([
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'display_name' => $user->display_name,
            'registered' => $user->user_registered
        ], $meta_fields);
    }


    /**
     * Get users by SCM role
     *
     * @param string $role Role to filter by (tenant, landlord, admin)
     * @return array Array of users
     */
    public function getUsersByRole($role)
    {
        $users = get_users([
            'meta_key' => 'scm_role',
            'meta_value' => $role
        ]);

        return array_map(function ($user) {
            return $this->getUserData($user->ID);
        }, $users);
    }

    /**
     * Update user status
     *
     * @param int $user_id WordPress user ID
     * @param string $status New status (active, inactive, pending)
     * @return bool True on success, false on failure
     */
    public function updateUserStatus($user_id, $status)
    {
        if (!in_array($status, ['active', 'inactive', 'pending'])) {
            return false;
        }

        return update_user_meta($user_id, 'scm_status', $status);
    }

    /**
     * Delete user and all associated meta
     *
     * @param int $user_id WordPress user ID
     * @return bool True on success, false on failure
     */
    public function deleteUser($user_id)
    {
        // WordPress will automatically clean up user meta
        return wp_delete_user($user_id);
    }

    /**
     * Verify if phone number is unique
     *
     * @param string $phone Phone number to check
     * @param int $exclude_user_id Optional user ID to exclude from check
     * @return bool True if unique, false if exists
     */
    public function isPhoneUnique($phone, $exclude_user_id = null)
    {
        $args = [
            'meta_key' => 'scm_phone',
            'meta_value' => $phone,
            'number' => 1
        ];

        if ($exclude_user_id) {
            $args['exclude'] = [$exclude_user_id];
        }

        $users = get_users($args);

        return empty($users);
    }
}
