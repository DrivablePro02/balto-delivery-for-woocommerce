<?php
declare(strict_types=1);

namespace Balto_Delivery\Includes\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Assets_Manager
 *
 * Handles the enqueueing of CSS and JavaScript assets for the plugin.
 *
 * @package Balto_Delivery_for_woocommerce
 * @subpackage Balto_Delivery_for_woocommerce/Core
 *
 * @since 1.0.0
 * @author Yahya Eddaqqaq
 */
class Assets_Manager {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Admin assets only
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Register and enqueue admin-specific scripts.
	 *
	 * @param string $hook The current admin page hook.
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_admin_assets( string $hook ): void {
		// Only load on settings page
		if ( 'settings_page_balto-delivery-settings' !== $hook ) {
			return;
		}

		// Settings page JavaScript
		wp_enqueue_script(
			'balto-delivery-settings',
			BALTO_DELIVERY_PLUGIN_URL . 'src/assets/admin/js/balto-settings.js',
			array( 'jquery' ),
			'1.0.2',
			true
		);

		// Localize the settings script
		wp_localize_script(
			'balto-delivery-settings',
			'baltoSettings',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'save_balto_settings' ),
				'i18n'    => array(
					'saveSettings' => __( 'Save Settings', 'balto-delivery' ),
					'saving'       => __( 'Saving...', 'balto-delivery' ),
					'errorMessage' => __( 'An error occurred while saving settings.', 'balto-delivery' ),
				),
			)
		);
	}
}
