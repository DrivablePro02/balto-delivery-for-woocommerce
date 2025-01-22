<?php
declare(strict_types=1);

namespace Balto_Delivery;

use Balto_Delivery\Includes\Core\Loader;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Plugin Name: Balto
 * Author: Balto
 * Version: 1.0.0
 * Description: A B2B plugin for tracking deliveries.
 * Text Domain: balto-delivery
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * WC requires at least: 6.0
 *
 * @package Balto_Delivery_for_woocommerce
 * @since 1.0.0
 * @author Yahya Eddaqqaq
 */

final class Balto_Delivery_Plugin {
    /**
     * @var Balto_Delivery_Plugin|null Single instance of the plugin
     */
    private static ?Balto_Delivery_Plugin $instance = null;

    /**
     * @var Loader|null Plugin loader instance
     */
    private ?Loader $loader = null;

    /**
     * Plugin constructor.
     * Private to enforce singleton pattern.
     */
    private function __construct() {
        $this->define_constants();
        $this->check_requirements();
        $this->init_loader();
    }

    /**
     * Get the single instance of the plugin
     */
    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Define plugin constants
     */
    private function define_constants(): void {
        define('BALTO_DELIVERY_VERSION', '1.0.0');
        define('BALTO_DELIVERY_PLUGIN_DIR', plugin_dir_path(__FILE__));
        define('BALTO_DELIVERY_PLUGIN_URL', plugin_dir_url(__FILE__));
        define('BALTO_DELIVERY_PLUGIN_BASENAME', plugin_basename(__FILE__));
    }

    /**
     * Check if requirements are met
     */
    private function check_requirements(): void {
        require_once BALTO_DELIVERY_PLUGIN_DIR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

        if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            add_action('admin_notices', function() {
                echo '<div class="error"><p>' . 
                    esc_html__('Balto Delivery requires WooCommerce to be installed and active', 'balto-delivery') . 
                    '</p></div>';
            });
            return;
        }
    }

    /**
     * Initialize the plugin loader
     */
    private function init_loader(): void {
        $this->loader = Loader::get_instance();
    }

    /**
     * Prevent cloning of the instance
     */
    private function __clone() {}

    /**
     * Prevent unserializing of the instance
     */
    public function __wakeup() {}
}

// Initialize the plugin
add_action('plugins_loaded', function() {
    Balto_Delivery_Plugin::get_instance();
});