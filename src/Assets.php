<?php

namespace ServiceChargeManager;

/**
 * Assets handler Class.
 */
class Assets
{
    /**
     * Class constructor.
     */
    public function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Get the list of frontend scripts
     *
     * @return array
     */
    public function get_scripts()
    {
        return [
            'scm-public-auth' => [
                'src' => SCM_ASSETS . '/js/public-auth.js',
                'version' => file_exists(SCM_ASSETS_PATH . 'js/public-auth.js') ? filemtime(SCM_ASSETS_PATH . 'js/public-auth.js') : '1.0.0',
                'deps' => ['jquery']
            ]
        ];
    }

    /**
     * Get the list of frontend styles
     *
     * @return array
     */
    public function get_styles()
    {
        return [
            'scm-public' => [
                'src' => SCM_ASSETS . '/css/public-elegent.css',
                'version' => file_exists(SCM_ASSETS_PATH . 'css/public-elegent.css') ? filemtime(SCM_ASSETS_PATH . 'css/public-elegent.css') : '1.0.0'
            ]
        ];
    }

    /**
     * Get the list of admin scripts
     *
     * @return array
     */
    public function get_admin_scripts()
    {
        return [
            'scm-admin' => [
                'src' => SCM_ASSETS . '/js/admin.js',
                'version' => file_exists(SCM_ASSETS_PATH . 'js/admin.js') ? filemtime(SCM_ASSETS_PATH . 'js/admin.js') : '1.0.0',
                'deps' => ['jquery']
            ]
        ];
    }

    /**
     * Get the list of admin styles
     *
     * @return array
     */
    public function get_admin_styles()
    {
        return [
            'scm-admin' => [
                'src' => SCM_ASSETS . '/css/admin.css',
                'version' => file_exists(SCM_ASSETS_PATH . 'css/admin.css') ? filemtime(SCM_ASSETS_PATH . 'css/admin.css') : '1.0.0'
            ]
        ];
    }

    /**
     * Register and enqueue frontend assets
     *
     * @return void
     */
    public function enqueue_assets()
    {
        $scripts = $this->get_scripts();
        $styles = $this->get_styles();

        foreach ($scripts as $handle => $script) {
            $deps = isset($script['deps']) ? $script['deps'] : false;
            wp_register_script($handle, $script['src'], $deps, $script['version'], true);
        }

        foreach ($styles as $handle => $style) {
            $deps = isset($style['deps']) ? $style['deps'] : false;
            wp_register_style($handle, $style['src'], array(), $style['version']);
        }

        // Enqueue Font Awesome 6
        wp_enqueue_style('font-awesome-6', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), '6.4.0');

        // Enqueue frontend assets
        wp_enqueue_style('scm-public');
        wp_enqueue_script('scm-public-auth');

        // Localize script
        wp_localize_script('scm-public-auth', 'scmAuth', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'siteUrl' => home_url(),
            'login_nonce'  => wp_create_nonce('scm_login'),
            'signup_nonce' => wp_create_nonce('scm_signup'),
            'user_nonce'   => wp_create_nonce('scm-user-nonce'),
            'update_profile_nonce' => wp_create_nonce('scm_update_profile_nonce'),
        ]);
    }

    /**
     * Register and enqueue admin assets
     *
     * @return void
     */
    public function enqueue_admin_assets($hook)
    {
        if ('toplevel_page_service-charge-manager' !== $hook) {
            return;
        }

        $scripts = $this->get_admin_scripts();
        $styles = $this->get_admin_styles();

        foreach ($scripts as $handle => $script) {
            $deps = isset($script['deps']) ? $script['deps'] : false;
            wp_register_script($handle, $script['src'], $deps, $script['version'], true);
        }

        foreach ($styles as $handle => $style) {
            $deps = isset($style['deps']) ? $style['deps'] : false;
            wp_register_style($handle, $style['src'], array(), $style['version']);
        }

        // Enqueue admin assets
        wp_enqueue_style('scm-admin');
        wp_enqueue_script('scm-admin');

        // Get current user's district if they're a manager
        $user_district = '';
        if (current_user_can('manage_scm') && !current_user_can('manage_options')) {
            $user_district = get_user_meta(get_current_user_id(), '_scm_manager_district', true);
        }

        // Localize script
        wp_localize_script('scm-admin', 'scmAdmin', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('scm-admin-nonce'),
            'isManager' => current_user_can('manage_scm') && !current_user_can('manage_options'),
            'managerDistrict' => $user_district,
            'debug' => defined('WP_DEBUG') && WP_DEBUG
        ]);
    }
}
