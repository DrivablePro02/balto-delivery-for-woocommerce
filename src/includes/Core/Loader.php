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
 * @package    Balto_Delivery_for_woocommerce
 * @subpackage Balto_Delivery_for_woocommerce/Core
 *
 * @since  1.0.0
 * @author Yahya Eddaqqaq
 */
class Loader {

	/**
	 * Instance of this class
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Container for storing plugin components
	 *
	 * @var array<string, object>
	 */
	private array $container = array();

	/**
	 * Plugin components that need initialization
	 *
	 * @var array<string, class-string>
	 */
	private array $components = array();

	/**
	 * Get class instance | singleton pattern
	 *
	 * @return self
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
	 *
	 * @return void
	 */
	private function define_components(): void {
		$this->components = array(
			// Core components
			'core.settings'    => \Balto_Delivery\Includes\Core\Settings::class,
			'core.assets'      => \Balto_Delivery\Includes\Core\Assets_Manager::class,

			// Admin components
			'admin.menu'       => \Balto_Delivery\Includes\Admin\Menu_Manager::class,
			'admin.settings'   => \Balto_Delivery\Includes\Admin\Settings_Page::class,
			'admin.dashboard'  => \Balto_Delivery\Includes\Admin\Dashboard_Page::class,

			// WooCommerce integration
			'wc.order'         => \Balto_Delivery\Includes\WooCommerce\Order_Integration::class,
			// 'wc.shipping'      => \Balto_Delivery\Includes\WooCommerce\Shipping_Method::class,

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
	 *
	 * @return void
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
	 *
	 * @return void
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
	 *
	 * @return void
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
	 *
	 * @return void
	 */
	public function deactivate(): void {
		// Clean up if needed
		flush_rewrite_rules();
	}

	/**
	 * Load plugin textdomain
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'balto-delivery',
			false,
			dirname( BALTO_DELIVERY_PLUGIN_BASENAME ) . '/src/languages'
		);
	}

	/**
	 * Get a component from container
	 *
	 * @param string $key The component key.
	 * @return object|null The component instance or null if not found.
	 */
	public function get_component( string $key ): ?object {
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
	 *
	 * @return void
	 */
	private function install_db_tables(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		include_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table_name = $wpdb->prefix . 'balto_deliveries';
		$sql        = array();

		// First, check if table exists
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name;

		if ( $table_exists ) {
			// Correct way to check if column exists
			$column_exists = $wpdb->get_results(
				"SELECT COLUMN_NAME 
				FROM INFORMATION_SCHEMA.COLUMNS 
				WHERE TABLE_NAME = '$table_name' 
				AND COLUMN_NAME = 'driver_id'"
			);

			if ( ! empty( $column_exists ) ) {
					// Add ALTER TABLE query if the column exists
					$sql[] = "ALTER TABLE $table_name CHANGE COLUMN driver_id shipping_provider varchar(255) DEFAULT NULL;";
			}
		}

		// Create/update deliveries table
		$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}balto_deliveries (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			order_id bigint(20) NOT NULL,
			tracking_number varchar(100) NOT NULL,
			status varchar(50) NOT NULL DEFAULT 'pending',
			shipping_provider varchar(255) DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY (id),
			KEY order_id (order_id),
			KEY tracking_number (tracking_number)
		) $charset_collate;";
		

		// Execute each query separately for better error handling
		foreach ( $sql as $query ) {
			$result = $wpdb->query( $query );
			if ( $result === false ) {
				// Log or handle the error
				error_log( 'Failed to execute query: ' . $wpdb->last_error );
			}
		}
	}

	/**
	 * Install default options
	 *
	 * @return void
	 */
	private function install_options(): void {
		$settings = Settings::get_instance();
		$defaults = $settings->get_defaults();
		$all_options = array();

		foreach ( $defaults as $section => $options ) {
			foreach ( $options as $key => $value ) {
				$all_options[ $section ][ $key ] = $value;
			}
		}

		// First try to update, if that fails then add
		if ( ! $settings->update_option( $all_options ) ) {
			if ( ! $settings->add_option( $all_options ) ) {
				error_log( 'Failed to install Balto Delivery options' );
			}
		}
	}

	/**
	 * Prevent cloning
	 *
	 * @return void
	 */
	private function __clone() {
	}

	/**
	 * Prevent unserializing
	 *
	 * @throws \Exception When attempting to unserialize.
	 * @return void
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton' );
	}
}
