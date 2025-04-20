<?php
declare(strict_types=1);

namespace Balto_Delivery;

use Balto_Delivery\Includes\Core\Loader;


if(!defined('ABSPATH')) exit;


/**
 * Plugin Name: Balto
 * Plugin URI: https://balto.com
 * Author: Balto
 * Author URI: https://balto.com
 * Version: 1.0.0
 * Description: A B2B plugin for tracking deliveries.
 * Text Domain: balto-delivery
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * WC requires at least: 6.0
 * WC tested up to: 8.0
 *
 * @package Balto_Delivery_for_woocommerce
 * @since 1.0.0
 * @author Yahya Eddaqqaq
 */

// Check PHP version
if (version_compare(PHP_VERSION, '7.4', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="error"><p>' . 
            sprintf(
                __('Balto Delivery requires PHP version %s or higher. You are running version %s. Please upgrade PHP to use this plugin.', 'balto-delivery'),
                '7.4',
                PHP_VERSION
            ) . 
            '</p></div>';
    });
    return;
}

// Check WordPress version
if (version_compare($GLOBALS['wp_version'], '5.0', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="error"><p>' . 
            sprintf(
                __('Balto Delivery requires WordPress version %s or higher. You are running version %s. Please upgrade WordPress to use this plugin.', 'balto-delivery'),
                '5.0',
                $GLOBALS['wp_version']
            ) . 
            '</p></div>';
    });
    return;
}

// Plugin constants
define('BALTO_DELIVERY_VERSION', '1.0.0');
define('BALTO_DELIVERY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BALTO_DELIVERY_FILE_PATH', __FILE__);
define('BALTO_DELIVERY_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BALTO_DELIVERY_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('BALTO_DELIVERY_SETTINGS_PAGE', admin_url('admin.php?page=balto-delivery-settings'));
define('BALTO_DELIVERY_MIN_WC_VERSION', '6.0');

// Autoloader
require_once BALTO_DELIVERY_PLUGIN_DIR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', function() {
        echo '<div class="error"><p>' . 
            sprintf(
                __('Balto Delivery requires WooCommerce version %s or higher to be installed and active.', 'balto-delivery'),
                BALTO_DELIVERY_MIN_WC_VERSION
            ) . 
            '</p></div>';
    });
    return;
}

// Check WooCommerce version
if (class_exists('WooCommerce')) {
    if (version_compare(WC()->version, BALTO_DELIVERY_MIN_WC_VERSION, '<')) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p>' . 
                sprintf(
                    __('Balto Delivery requires WooCommerce version %s or higher. You are running version %s. Please upgrade WooCommerce to use this plugin.', 'balto-delivery'),
                    BALTO_DELIVERY_MIN_WC_VERSION,
                    WC()->version
                ) . 
                '</p></div>';
        });
        return;
    }
}

// Initialize plugin only for admin users
if (current_user_can('manage_options')) {
    try {
        // Initialize plugin loader
        \Balto_Delivery\Includes\Core\Loader::get_instance();
    } catch (\Exception $e) {
        add_action('admin_notices', function() use ($e) {
            echo '<div class="error"><p>' . 
                sprintf(
                    __('Error initializing Balto Delivery: %s', 'balto-delivery'),
                    esc_html($e->getMessage())
                ) . 
                '</p></div>';
        });
    }
}