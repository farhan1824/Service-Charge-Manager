<?php

/**
 * Flat Details view template
 *
 * This template displays all flats under a selected apartment
 */

// If accessed directly, exit
if (!defined('ABSPATH')) {
    exit;
}

// Ensure $apartment_data is available
if (!isset($apartment_data)) {
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

        <!-- Flats Header Card -->
        <div class="scm-card scm-flats-header-card">
            <div class="scm-card-header">
                <div class="scm-card-header-top">
                    <div>
                        <h2><?php echo esc_html__('Flats', 'service-charge-manager'); ?></h2>
                        <p class="scm-apartment-subtitle"><?php echo esc_html($apartment_data['name']); ?></p>
                    </div>
                    <button type="button" id="scm-add-flat-icon-btn" class="scm-add-icon-btn" title="<?php echo esc_attr__('Add New Flat', 'service-charge-manager'); ?>">
                        +
                    </button>
                </div>
            </div>
        </div>

        <!-- Flats Table Card -->
        <div class="scm-card scm-flats-table-card">
            <div class="scm-card-body">
                <div class="scm-flats-table-wrapper">
                    <table class="scm-flats-table">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__('Flat Name', 'service-charge-manager'); ?></th>
                                <th><?php echo esc_html__('Floor', 'service-charge-manager'); ?></th>
                                <th><?php echo esc_html__('Holder', 'service-charge-manager'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="scm-flats-tbody">
                            <tr>
                                <td colspan="3" class="scm-loading">
                                    <span class="scm-spinner"></span>
                                    <span><?php echo esc_html__('Loading flats...', 'service-charge-manager'); ?></span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Back to Apartment Button -->
        <div class="scm-back-button-section">
            <button type="button" id="scm-back-to-apartments-btn" class="scm-btn-secondary scm-btn-full-width">
                <?php echo esc_html__('Back to Apartments', 'service-charge-manager'); ?>
            </button>
        </div>

        <!-- Logout Button at Bottom -->
        <div class="scm-logout-section">
            <button id="scm-logout-btn" class="scm-btn-logout scm-btn-full-width"><?php echo esc_html__('Logout', 'service-charge-manager'); ?></button>
        </div>
    </div>
</div>

<!-- Store apartment ID for JavaScript -->
<script type="text/javascript">
    window.scmApartmentId = <?php echo intval($apartment_data['id']); ?>;
</script>