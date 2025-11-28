<?php

/**
 * Dashboard view template
 * 
 * This template displays the user's dashboard with their personal information
 */

// If accessed directly, exit
if (!defined('ABSPATH')) {
    exit;
}

// Ensure $user_data is available
if (!isset($user_data)) {
    return;
}
?>
<div class="scm-mobile-app">
    <!-- Plugin Header -->
    <div class="scm-app-header">
        <div class="scm-app-logo">
            <h1><?php echo esc_html__('Service Charge Manager', 'service-charge-manager'); ?></h1>
        </div>
    </div>

    <!-- Main Content -->
    <div class="scm-app-content">

        <!-- User Information Card -->
        <div class="scm-card scm-user-info-card">
            <div class="scm-card-header">
                <div class="scm-card-header-top">
                    <h2><?php echo esc_html__('Your Information', 'service-charge-manager'); ?></h2>
                    <button type="button" id="scm-edit-icon-btn" class="scm-edit-icon-btn" title="<?php echo esc_attr__('Edit Information', 'service-charge-manager'); ?>">
                        ✎
                    </button>
                </div>
            </div>
            <div class="scm-card-body">
                <div class="scm-info-row">
                    <span class="scm-info-label"><?php echo esc_html__('Name', 'service-charge-manager'); ?></span>
                    <span class="scm-value" data-key="first_name"><?php echo esc_html($user_data['first_name'] . " " . $user_data["last_name"]); ?></span>
                    <input class="scm-edit-field" data-key="first_name" type="text"
                        value="<?php echo esc_attr($user_data['first_name']); ?>" style="display:none;">
                </div>

                <div class="scm-info-row">
                    <span class="scm-info-label"><?php echo esc_html__('Phone', 'service-charge-manager'); ?></span>
                    <span class="scm-value" data-key="phone"><?php echo esc_html($user_data['phone']); ?></span>
                </div>

                <div class="scm-info-row">
                    <span class="scm-info-label"><?php echo esc_html__('District', 'service-charge-manager'); ?></span>
                    <span class="scm-value" data-key="district"><?php echo esc_html($user_data['district']); ?></span>
                </div>

                <div class="scm-info-row">
                    <span class="scm-info-label"><?php echo esc_html__('Address', 'service-charge-manager'); ?></span>
                    <span class="scm-value" data-key="address"><?php echo esc_html($user_data['address']); ?></span>
                    <input class="scm-edit-field" data-key="address" type="textarea"
                        value="<?php echo esc_attr($user_data['address']); ?>" style="display:none;">
                </div>

                <div id="scm-update-response"></div>
            </div>
            <div class="scm-card-footer" id="scm-edit-actions" style="display:none;">
                <button type="button" id="scm-save-btn" class="scm-btn-success scm-btn-full-width"><?php echo esc_html__('Save Changes', 'service-charge-manager'); ?></button>
                <button type="button" id="scm-cancel-btn" class="scm-btn-secondary scm-btn-full-width"><?php echo esc_html__('Cancel', 'service-charge-manager'); ?></button>
            </div>
        </div>

        <?php if ($user_data['is_manager']): ?>
            <!-- Manager Apartment Management Section -->
            <div class="scm-apartments-section">
                <!-- Apartments List Card -->
                <div class="scm-card scm-apartments-list-card">
                    <div class="scm-card-header">
                        <div class="scm-card-header-top">
                            <h2><?php echo esc_html__('Your Apartments', 'service-charge-manager'); ?></h2>
                            <button type="button" id="scm-add-apartment-icon-btn" class="scm-add-icon-btn" title="<?php echo esc_attr__('Add New Apartment', 'service-charge-manager'); ?>">
                                +
                            </button>
                        </div>
                    </div>
                    <div class="scm-card-body">
                        <div class="scm-apartments-list" id="scm-apartments-tbody">
                            <div class="scm-loading">
                                <span class="scm-spinner"></span>
                                <span><?php echo esc_html__('Loading apartments...', 'service-charge-manager'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Logout Button at Bottom -->
        <div class="scm-logout-section">
            <button id="scm-logout-btn" class="scm-btn-logout scm-btn-full-width"><?php echo esc_html__('Logout', 'service-charge-manager'); ?></button>
        </div>
    </div>
</div>