<?php

/**
 * Auth Tabs Template
 * 
 * This template displays the login/signup tabs interface
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="scm-frontend">
    <div class="scm-auth-container ">
        <div class="scm-auth-tabs">
            <button class="scm-tab-button active" data-tab="login"><?php _e('Login', 'service-charge-manager'); ?></button>
            <button class="scm-tab-button" data-tab="signup"><?php _e('Sign Up', 'service-charge-manager'); ?></button>
        </div>

        <div class="scm-tab-content active" id="scm-login-tab">
            <form id="scm-login-form" class="scm-form">
                <?php wp_nonce_field('scm_login'); // This creates a nonce field with name="_wpnonce" 
                ?>

                <div class="scm-form-group">
                    <label for="login-phone"><?php _e('Phone Number', 'service-charge-manager'); ?></label>
                    <input
                        type="tel"
                        id="login-phone"
                        name="phone"
                        required
                        pattern="^(\+8801|8801|01)[0-9]{9}$"
                        placeholder="Enter your phone number, e.g., 017XXXXXXXX or +88017XXXXXXXX"
                        title="Enter a valid BD phone number, e.g., 017XXXXXXXX or +88017XXXXXXXX">

                    <small class="scm-help-text">
                        <?php _e('Enter your registered phone number to login, e.g., 017XXXXXXXX or +88017XXXXXXXX', 'service-charge-manager'); ?>
                    </small>
                </div>

                <div class="scm-form-actions">
                    <button type="submit" class="scm-button scm-button-primary" id="login-submit">
                        <?php _e('Login', 'service-charge-manager'); ?>
                    </button>
                </div>
            </form>
        </div>


        <div class="scm-tab-content" id="scm-signup-tab">
            <form id="scm-signup-form" class="scm-form">
                <?php wp_nonce_field('scm_signup', 'scm_signup_nonce'); ?>

                <!-- Step 1: Phone Number -->
                <div id="signup-step-1">
                    <div class="scm-form-group">
                        <label for="signup-phone"><?php _e('Phone Number', 'service-charge-manager'); ?></label>
                        <input
                            type="tel"
                            id="signup-phone"
                            name="phone"
                            required
                            pattern="^(\+8801|8801|01)[0-9]{9}$"
                            placeholder="Enter your phone number, e.g., 017XXXXXXXX or +88017XXXXXXXX"
                            title="Enter a valid BD phone number, e.g., 017XXXXXXXX or +88017XXXXXXXX">

                        <small class="scm-help-text"><?php _e('We will send a verification code to this number', 'service-charge-manager'); ?></small>
                    </div>
                    <div class="scm-form-actions">
                        <button type="submit" class="scm-button scm-button-primary" id="send-signup-otp">
                            <?php _e('Send Verification Code', 'service-charge-manager'); ?>
                        </button>
                    </div>
                </div>

                <!-- Step 2: OTP Verification -->
                <div id="signup-step-2" style="display: none;">
                    <div class="scm-form-group">
                        <label for="signup-otp"><?php _e('Enter Verification Code', 'service-charge-manager'); ?></label>
                        <input
                            type="text"
                            id="signup-otp"
                            name="otp"
                            pattern="[0-9]{6}"
                            placeholder="Enter 6-digit OTP"
                            title="Enter the 6-digit OTP sent to your phone">

                        <small class="scm-help-text"><?php _e('Enter the 6-digit code sent to your phone', 'service-charge-manager'); ?></small>
                    </div>
                    <div class="scm-form-actions">
                        <button type="button" class="scm-button scm-button-secondary" id="resend-otp">
                            <?php _e('Resend Code', 'service-charge-manager'); ?>
                        </button>
                        <button type="submit" class="scm-button scm-button-primary" id="verify-otp">
                            <?php _e('Verify & Continue', 'service-charge-manager'); ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>

    </div>
</div>