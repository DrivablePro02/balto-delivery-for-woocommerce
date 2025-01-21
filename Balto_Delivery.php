<?php
// declare(strict_types=1);

namespace Balto_Delivery;

use Balto_Delivery\Includes\Core\Loader;


if(!defined('ABSPATH')) exit;


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
*
* @since 1.0.0
* @author Yahya Eddaqqaq
*
*/

// Define plugin constants
define('BALTO_DELIVERY_VERSION', '1.0.0');
define('BALTO_DELIVERY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BALTO_DELIVERY_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BALTO_DELIVERY_PLUGIN_BASENAME', plugin_basename(__FILE__));

require_once BALTO_DELIVERY_PLUGIN_DIR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

// if(!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))){
//     add_action('admin_notices', function() {
//         echo '<div class="error"><p>' . __('Balto Delivery requires woocommerce to be installed and active', 'balto-delivery') . '</p></div>';
//     });
//     return;
// }

// require_once BALTO_DELIVERY_PLUGIN_DIR . 'src' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . 'Loader.php';

//Intilize plugin loader
Loader::get_instance();