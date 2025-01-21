<?php
declare(strict_types=1);

namespace Balto_Delivery\Includes\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Main loader class for Balto Delivery
 *
 * This class handles the initialization of all plugin components, including
 * activation, deactivation, and WooCommerce-specific features. It follows
 * the Singleton design pattern to ensure only one instance of the class exists.
 *
 * @package Balto_Delivery_for_woocommerce
 * @subpackage Balto_Delivery_for_woocommerce/Core
 *
 * @since 1.0.0
 * @author Yahya Eddaqqaq
 */
class Loader {
	/**
	 * Instance of this class
	 *
	 * @var Loader
	 */
	private static $instance = null;

	/**
	 * Container for storing plugin components
	 *
	 * @var array
	 */
	private $container = array();

	/**
	 * Plugin components that need initialization
	 *
	 * @var array
	 */
	private $components = array();

	/**
	 * Get class instance | singelton pattern
	 *
	 * @return Loader
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor to prevent direct creation
	 */
	private function __construct() {
		$this->define_components();
		$this->init_hooks();
	}

	/**
	 * Define plugin components
	 */
	private function define_components(): void {
		$this->components = array(
			// Core components
			'core.settings'    => \Balto_Delivery\Includes\Core\Settings::class,
			'core.assets'      => \Balto_Delivery\Includes\Core\Assets_Manager::class,

			// // Admin components
			'admin.menu'       => \Balto_Delivery\Includes\Admin\Menu_Manager::class,
			'admin.settings'   => \Balto_Delivery\Includes\Admin\Settings_Page::class,

			// WooCommerce integration
			// 'wc.order'         => \Balto_Delivery\WooCommerce\Order_Integration::class,
			// 'wc.shipping'      => \Balto_Delivery\WooCommerce\Shipping_Method::class,

			// API components
			'api.rest'         => \Balto_Delivery\Includes\Api\Rest_Controller::class,
			'api.webhook'      => \Balto_Delivery\Includes\Api\Webhook_Handler::class,

			// Services
			'service.delivery' => \Balto_Delivery\Includes\Services\Delivery_Service::class,
			'service.tracking' => \Balto_Delivery\Includes\Services\Tracking_Service::class,

			// Helpers
			'ajax.handler'     => \Balto_Delivery\Includes\Helpers\Ajax_Handler::class,

		);
	}

	/**
	 * Initialize WordPress hooks
	 */
	private function init_hooks(): void {
		// Plugin activation/deactivation
		register_activation_hook( BALTO_DELIVERY_PLUGIN_BASENAME, array( $this, 'activate' ) );
		register_deactivation_hook( BALTO_DELIVERY_PLUGIN_BASENAME, array( $this, 'deactivate' ) );

		// Initialize components after plugins loaded
		add_action( 'plugins_loaded', array( $this, 'init_components' ), 20 );

		// Initialize WooCommerce specific features
		// add_action('woocommerce_init', [$this, 'init_woocommerce']);

		// Load textdomain
		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Initialize plugin components
	 */
	public function init_components(): void {
		foreach ( $this->components as $key => $class ) {
			if ( ! isset( $this->container[ $key ] ) ) {
				// Check if the class has a get_instance method
				if ( method_exists( $class, 'get_instance' ) ) {
					$this->container[ $key ] = $class::get_instance();
				} else {
					$this->container[ $key ] = new $class();
				}

				// If component has init method, call it
				// if (method_exists($this->container[$key], 'init')) {
				// $this->container[$key]->init();
				// }
			}
		}

		// Fire action after components are loaded
		do_action( 'balto_delivery_loaded' );
	}

	/**
	 * Initialize WooCommerce specific features
	 */
	public function init_woocommerce(): void {
		if ( ! $this->is_woocommerce_active() ) {
			return;
		}

		// Initialize WooCommerce specific components
		$this->get_component( 'wc.order' );
		$this->get_component( 'wc.shipping' );

		// Add WooCommerce specific hooks
		add_filter( 'woocommerce_shipping_methods', array( $this, 'add_shipping_method' ) );
		add_action( 'woocommerce_order_status_changed', array( $this, 'handle_order_status_change' ), 10, 4 );
	}

	/**
	 * Plugin activation handler
	 */
	public function activate(): void {
		if ( ! $this->is_woocommerce_active() ) {
			wp_die(
				esc_html__( 'Balto Delivery requires WooCommerce to be installed and activated.', 'balto-delivery' ),
				'Plugin dependency check',
				array( 'back_link' => true )
			);
		}

		// Create necessary database tables
		$this->install_db_tables();

		// Add plugin options with defaults
		$this->install_options();

		// Clear permalinks
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation handler
	 */
	public function deactivate(): void {
		// Clean up if needed
		flush_rewrite_rules();
	}

	/**
	 * Load plugin textdomain
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'balto-delivery',
			false,
			dirname( BALTO_DELIVERY_PLUGIN_BASENAME ) . '/languages'
		);
	}

	/**
	 * Get a component from container
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function get_component( string $key ) {
		if ( ! isset( $this->container[ $key ] ) && isset( $this->components[ $key ] ) ) {
			$this->container[ $key ] = new $this->components[ $key ]();
		}
		return $this->container[ $key ] ?? null;
	}

	/**
	 * Check if WooCommerce is active
	 *
	 * @return bool
	 */
	private function is_woocommerce_active(): bool {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * Install database tables
	 */
	private function install_db_tables(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = array();

		// Deliveries table
		$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}balto_deliveries (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            order_id bigint(20) NOT NULL,
            tracking_number varchar(100) NOT NULL,
            status varchar(50) NOT NULL DEFAULT 'pending',
            driver_id bigint(20) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY order_id (order_id),
            KEY tracking_number (tracking_number)
        ) $charset_collate;";

		// Tracking history table
		$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}balto_tracking_history (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            delivery_id bigint(20) NOT NULL,
            status varchar(50) NOT NULL,
            location varchar(255) DEFAULT NULL,
            notes text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY delivery_id (delivery_id)
        ) $charset_collate;";

		foreach ( $sql as $query ) {
			dbDelta( $query );
		}
	}

	/**
	 * Install default options
	 */
	private function install_options(): void {
		$settings    = Settings::get_instance();
		$defaults    = $settings->get_settings();
		$all_options = array();

		foreach ( $defaults as $section => $options ) {
			foreach ( $options as $key => $value ) {
				$option_name = $settings::OPTION_NAME . '[' . $section . ']' . '[' . $key . ']';
				if ( get_option( $option_name ) === false ) {
					add_option( $option_name, $value );
				}
				$all_options[ $section ][ $key ] = $value;
			}
		}

		// Store serialized data of all options
		if ( get_option( $settings::OPTION_NAME ) === false ) {
			add_option( $settings::OPTION_NAME, serialize( $all_options ) );
		}
	}


	/**
	 * Prevent cloning
	 */
	private function __clone() {}

	/**
	 * Prevent unserializing
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton' );
	}
}
