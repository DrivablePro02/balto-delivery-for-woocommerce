<?php
declare(strict_types=1);

namespace Balto_Delivery\Includes\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Menu Manager class for Balto Delivery
 *
 * This class handles the creation, registration, and management of admin menu items
 * and submenus within the WordPress admin dashboard. It follows the Singleton design
 * pattern to ensure only one instance of the class exists.
 *
 * @package Balto_Delivery_for_woocommerce
 * @subpackage Balto_Delivery_for_woocommerce/Admin
 *
 * @since 1.0.0
 */
class Menu_Manager {

	/**
	 * Instance of this class
	 *
	 * @var Menu_Manager
	 */
	private static $instance = null;

	/**
	 * Get class instance | Singleton pattern
	 *
	 * @return Menu_Manager
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor to prevent direct instantiation
	 */
	private function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menus' ) );
	}

	/**
	 * Register admin menus and submenus
	 */
	public function register_menus(): void {
		add_menu_page(
			__( 'Balto Delivery', 'balto-delivery' ),
			__( 'Balto Delivery', 'balto-delivery' ),
			'manage_options',
			'balto-delivery',
			array( $this, 'render_main_menu' ),
			'dashicons-admin-generic',
			6
		);

		add_submenu_page(
			'balto-delivery',
			__( 'Settings', 'balto-delivery' ),
			__( 'Settings', 'balto-delivery' ),
			'manage_options',
			'balto-delivery-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Render the main menu page
	 */
	public function render_main_menu(): void {
		echo '<h1>' . esc_html__( 'Balto Delivery Main Menu', 'balto-delivery' ) . '</h1>';
	}

	/**
	 * Render the settings page
	 */
	public function render_settings_page(): void {
		Settings_Page::get_instance()->render_settings_page();
	}
}
