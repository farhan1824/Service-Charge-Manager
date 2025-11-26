<?php

/**
 * Registration Details Template
 * 
 * This template is shown after phone verification for collecting user details
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Start session if not already started
if (!session_id()) {
    session_start();
}

// // Verify the user is following proper workflow
// if (!isset($_SESSION['scm_verified_phone']) || empty($_SESSION['scm_verified_phone'])) {
//     wp_safe_redirect(home_url('/login/'));
//     exit;
// }

// // If user is already logged in, redirect to dashboard
// if (is_user_logged_in()) {
//     wp_safe_redirect(home_url('/dashboard/'));
//     exit;
// }

$phone = isset($_SESSION['scm_verified_phone']) ? $_SESSION['scm_verified_phone'] : '';
?>
<div class="scm-frontend">
    <div class="scm-registration-wrapper ">
        <div class="scm-registration-card">
            <h2 class="scm-reg-title"><?php _e('Complete Your Registration', 'service-charge-manager'); ?></h2>
            <p class="scm-reg-subtitle">
                <?php echo sprintf(__('Phone verified: %s', 'service-charge-manager'), '<strong>' . esc_html($phone) . '</strong>'); ?>
            </p>

            <form id="scm-registration-form" class="scm-form" novalidate>
                <input type="hidden" name="action" value="scm_complete_registration">
                <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('scm_signup')); ?>" />
                <?php if (!empty($phone)) : ?>
                    <input type="hidden" name="phone" value="<?php echo esc_attr($phone); ?>" />
                <?php endif; ?>

                <div class="scm-form-group">
                    <label for="reg-name"><?php _e('Full Name', 'service-charge-manager'); ?></label>
                    <input type="text" id="reg-name" name="name" required placeholder="Enter your full name">
                </div>

                <!-- <div class="scm-form-group">
                    <label for="reg-district"><?php _e('District', 'service-charge-manager'); ?></label>
                    <select id="reg-district" name="district" required>
                        <option value=""><?php _e('Select your district', 'service-charge-manager'); ?></option>
                        <?php
                        // $districts = [
                        //     'north' => __('North District', 'service-charge-manager'),
                        //     'south' => __('South District', 'service-charge-manager'),
                        //     'east' => __('East District', 'service-charge-manager'),
                        //     'west' => __('West District', 'service-charge-manager'),
                        //     'central' => __('Central District', 'service-charge-manager'),
                        // ];

                        // foreach ($districts as $key => $label) {
                        //     printf('<option value="%s">%s</option>', esc_attr($key), esc_html($label));
                        // }
                        ?>
                    </select>
                </div> -->

                <div class="scm-form-group">
                    <label for="reg-address"><?php _e('Address', 'service-charge-manager'); ?></label>
                    <textarea id="reg-address" name="address" required placeholder="Enter your full address"></textarea>
                </div>

                <div class="scm-form-actions">
                    <button type="submit" class="scm-button scm-button-primary">
                        <?php _e('Complete Registration', 'service-charge-manager'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>