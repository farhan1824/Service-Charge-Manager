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

                <!-- Role Selection -->
                <div class="scm-form-group">
                    <label><?php _e('Select Your Role', 'service-charge-manager'); ?></label>
                    <div class="scm-role-options">
                        <label class="scm-role-option">
                            <input type="radio" name="scm_role" value="manager" required>
                            <span><?php _e('Manager', 'service-charge-manager'); ?></span>
                        </label>
                        <label class="scm-role-option">
                            <input type="radio" name="scm_role" value="flatholder" required>
                            <span><?php _e('Flatholder', 'service-charge-manager'); ?></span>
                        </label>
                    </div>
                </div>

                <!-- Apartment Selection (Flatholder only) -->
                <div class="scm-form-group scm-apartment-selection" style="display: none;">
                    <label for="reg-apartment"><?php _e('Select Your Apartment', 'service-charge-manager'); ?> <span class="scm-required">*</span></label>
                    <select id="reg-apartment" name="apartment_id" class="scm-apartment-dropdown">
                        <option value=""><?php _e('-- Select Apartment --', 'service-charge-manager'); ?></option>
                    </select>
                    <small class="scm-help-text" style="display: none; color: #d32f2f;"></small>
                </div>

                <!-- Flat Selection (Flatholder only) -->
                <div class="scm-form-group scm-flat-selection" style="display: none;">
                    <label for="reg-flat"><?php _e('Select Your Flat/Unit', 'service-charge-manager'); ?> <span class="scm-required">*</span></label>
                    <select id="reg-flat" name="flat_id" class="scm-flat-dropdown">
                        <option value=""><?php _e('-- Select Flat/Unit --', 'service-charge-manager'); ?></option>
                    </select>
                    <small class="scm-help-text" style="display: none; color: #d32f2f;"></small>
                </div>

                <div class="scm-form-group">
                    <label for="reg-name"><?php _e('Full Name', 'service-charge-manager'); ?></label>
                    <input type="text" id="reg-name" name="name" required placeholder="Enter your full name">
                </div>

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