<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap scm-admin-page">
    <h1><?php _e('Service Charge Manager - User Management', 'service-charge-manager'); ?></h1>

    <?php if (current_user_can('manage_options')) : ?>
        <h2><?php _e('Assign Manager Access', 'service-charge-manager'); ?></h2>
        <form id="scm-assign-manager-form" class="scm-admin-form">
            <div class="scm-form-group">
                <label for="scm-phone"><?php _e('User Phone', 'service-charge-manager'); ?></label>
                <input type="text"
                    id="scm-phone"
                    name="phone"
                    class="regular-text"
                    pattern="(\+8801|8801|01)[0-9]{9}"
                    placeholder="01XXXXXXXXX or +8801XXXXXXXXX"
                    title="Please enter a valid Bangladesh phone number"
                    required>
                <p class="description"><?php _e('Enter the phone number of an existing user to promote them to Manager.', 'service-charge-manager'); ?></p>
            </div>

            <div class="scm-form-group">
                <label for="scm-district"><?php _e('Assign District', 'service-charge-manager'); ?></label>
                <input type="text"
                    id="scm-district"
                    name="district"
                    class="regular-text"
                    placeholder="e.g., Dhaka"
                    required>
                <p class="description"><?php _e('The district this manager will be responsible for.', 'service-charge-manager'); ?></p>
            </div>

            <?php wp_nonce_field('scm-admin-nonce', 'nonce'); ?>

            <div class="submit">
                <button id="scm-assign-manager"
                    class="button button-primary"
                    type="submit">
                    <?php _e('Promote to Manager', 'service-charge-manager'); ?>
                </button>
            </div>
        </form>

        <div id="scm-assign-result" style="display:none;" class="notice"></div>
    <?php endif; ?>

    <hr />

    <h2><?php _e('Service Charge Manager Users', 'service-charge-manager'); ?></h2>
    <?php
    // Setup base query for SCM users
    $args = [
        'number' => 50,
        'orderby' => 'ID',
        'order' => 'DESC',
        'meta_query' => [
            'relation' => 'OR',
            [
                'key' => 'scm_phone',
                'compare' => 'EXISTS'
            ],
            [
                'key' => '_scm_manager_district',
                'compare' => 'EXISTS'
            ]
        ]
    ];

    // If current user is a manager, filter by their district
    if (!current_user_can('manage_options') && current_user_can('manage_scm')) {
        $manager_district = get_user_meta(get_current_user_id(), '_scm_manager_district', true);
        if ($manager_district) {
            $args['meta_query'][] = array(
                'key' => 'district',
                'value' => $manager_district
            );
        }
    }

    $users = get_users($args);

    if (!empty($users)) : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Name', 'service-charge-manager'); ?></th>
                    <th><?php _e('Phone', 'service-charge-manager'); ?></th>
                    <th><?php _e('Role', 'service-charge-manager'); ?></th>
                    <th><?php _e('District', 'service-charge-manager'); ?></th>
                    <th><?php _e('User Type', 'service-charge-manager'); ?></th>
                    <th><?php _e('Payment Status', 'service-charge-manager'); ?></th>
                    <th><?php _e('Actions', 'service-charge-manager'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user) :
                    // Get all relevant metadata at once
                    $phone_number = get_user_meta($user->ID, 'scm_phone', true);
                    // Remove 'scm_' prefix from phone number for display
                    $phone_number = str_replace('scm_', '', $phone_number);
                    $district = get_user_meta($user->ID, 'district', true);
                    $manager_district = get_user_meta($user->ID, '_scm_manager_district', true);
                    $property_type = get_user_meta($user->ID, 'property_type', true);
                    $payment_status = get_user_meta($user->ID, 'payment_status', true) ?: 'pending';
                    $is_manager = in_array('scm_manager', (array)$user->roles);

                    // Use manager_district if district is empty
                    $display_district = !empty($district) ? $district : $manager_district;
                ?>
                    <tr>
                        <td><?php echo esc_html($user->display_name); ?></td>
                        <td><?php echo esc_html($phone_number); ?></td>
                        <td><?php echo esc_html(implode(', ', (array)$user->roles)); ?></td>
                        <td><?php echo esc_html($display_district); ?></td>
                        <td><?php echo esc_html($property_type); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo esc_attr($payment_status); ?>">
                                <?php echo esc_html(ucfirst($payment_status)); ?>
                            </span>
                        </td>
                        <?php if (current_user_can('manage_options')) : ?>
                            <td>
                                <?php if (!$is_manager) : ?>
                                    <button class="button button-small promote-to-manager"
                                        data-user-id="<?php echo esc_attr($user->ID); ?>"
                                        data-phone="<?php echo esc_attr($phone_number); ?>"
                                        data-name="<?php echo esc_attr($user->display_name); ?>">
                                        <?php _e('Promote to Manager', 'service-charge-manager'); ?>
                                    </button>
                                <?php else : ?>
                                    <button class="button button-small scm-revoke-manager"
                                        data-user-id="<?php echo esc_attr($user->ID); ?>"
                                        data-nonce="<?php echo wp_create_nonce('scm-admin-nonce'); ?>">
                                        <?php _e('Revoke Manager', 'service-charge-manager'); ?>
                                    </button>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else : ?>
        <p><?php _e('No SCM users found yet.', 'service-charge-manager'); ?></p>
    <?php endif; ?>
</div>