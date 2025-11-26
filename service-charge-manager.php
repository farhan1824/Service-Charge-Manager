<?php

/**
 * Service Charge Manager
 *
 * @package           PluginPackage
 * @author            Service Charge Manager
 * @copyright         2025 Service Charge Manager
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Service Charge Manager
 * Plugin URI:        https://example.com/service-charge-manager
 * Description:       A plugin for managing service charges with phone number based authentication and landlord management.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Service Charge Manager
 * Author URI:        https://example.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       service-charge-manager
 * Domain Path:       /languages
 */

defined('ABSPATH') || exit;

// Load custom PSR-4 autoloader
require_once(__DIR__ . '/autoload.php');

/**
 * The main plugin class
 */
final class ServiceChargeManager
{
    /**
     * Plugin version
     */
    const version = '1.0.0';

    /**
     * Class constructor.
     */
    public function __construct()
    {
        $this->define_constants();

        register_activation_hook(__FILE__, [$this, 'activate']);

        add_action('plugins_loaded', [$this, 'init_plugin']);
    }

    /**
     * Initializes a single instance
     */
    public static function init()
    {
        static $instance = false;

        if (!$instance) {
            $instance = new self();
        }

        return $instance;
    }

    /**
     * Define the plugin constants
     *
     * @return void
     */
    public function define_constants()
    {
        define('SCM_VERSION', self::version);
        define('SCM_PLUGIN_FILE', __FILE__);
        define('SCM_PLUGIN_DIR', __DIR__ . '/');
        define('SCM_PLUGIN_URL', plugins_url('', SCM_PLUGIN_FILE));
        define('SCM_ASSETS', SCM_PLUGIN_URL . '/assets');
        define('SCM_ASSETS_PATH', SCM_PLUGIN_DIR . 'assets');
        define('SCM_TEXT_DOMAIN', 'service-charge-manager');
    }

    /**
     * Init plugin
     *
     * @return void
     */
    public function init_plugin()
    {
        // for custom/local translations
        // phpcs:ignore PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound
        load_plugin_textdomain(SCM_TEXT_DOMAIN, false, dirname(plugin_basename(__FILE__)) . '/languages');

        if (defined('DOING_AJAX') && DOING_AJAX) {
            new \ServiceChargeManager\Api\AjaxHandlers();
        }

        new \ServiceChargeManager\Assets();

        if (is_admin()) {
            new \ServiceChargeManager\Admin\AdminManager();
        } else {
            new \ServiceChargeManager\Public\Frontend();
        }
    }

    /**
     * Do stuff on plugin activation
     *
     * @return void
     */
    public function activate()
    {
        $installer = new \ServiceChargeManager\Includes\Installer();
        $installer->run();
    }
}

/**
 * Initializes the main plugin
 */
function service_charge_manager()
{
    return ServiceChargeManager::init();
}

// kick-off the plugin
service_charge_manager();
