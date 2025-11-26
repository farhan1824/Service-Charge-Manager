<?php

namespace ServiceChargeManager\Includes;

/**
 * Fired during plugin deactivation
 */
class ServiceChargeManagerDeactivator
{
    /**
     * Deactivation function
     */
    public static function deactivate()
    {
        // Remove custom role
        remove_role('landlord');

        // Clear any scheduled events
        wp_clear_scheduled_hook('scm_daily_check');

        // Clear permalinks
        flush_rewrite_rules();
    }
}
