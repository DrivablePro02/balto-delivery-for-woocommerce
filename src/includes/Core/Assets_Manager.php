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
 */
class Assets_Manager {

	/**
	 * Holds the array of admin scripts and styles.
	 *
	 * @var array
	 */
	private $admin_assets = array();

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Admin assets only
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ), 40, 1 );
		add_action('admin_init', [$this, 'register_assets_to_enqueue'], 20, 0);
	}

	/**
	 * Adds an admin script or style to the array of admin assets.
	 *
	 * @param string $handle          The handle of the script or style.
	 * @param string $page            The page where the script or style should be enqueued.
	 * @param string $file            The file name of the script or style.
	 * @param array  $dependencies    An array of the script or style's dependencies.
	 * @param string $version         The version number of the script or style.
	 * @param bool   $enqueue_in_footer Whether the script should be enqueued in the footer.
	 */
	public function add_admin_asset(
		string $handle,
		string $page,
		string $file,
		array $dependencies = array(),
		string $version = BALTO_DELIVERY_VERSION,
		bool $enqueue_in_footer = true,
		array $localization
	): void {
		$this->admin_assets[ $page ][ $handle ] = array(
			'file'            => $file,
			'dependencies'    => $dependencies,
			'version'         => $version,
			'enqueue_in_footer' => $enqueue_in_footer,
			'localization'		=> $localization
		);
	}

	/**
	 * Register and enqueue admin-specific scripts.
	 *
	 * @param string $hook The current admin page hook.
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_admin_assets( string $hook ): void {
		// Enqueue assets registered for the specific admin page.
		if ( isset( $this->admin_assets[ $hook ] ) ) {
			foreach ( $this->admin_assets[ $hook ] as $handle => $asset ) {
				if ( 'css' === substr( $handle, -3 ) ) {
					wp_enqueue_style(
						$handle,
						BALTO_DELIVERY_PLUGIN_URL . 'src/assets/admin/css/' . $asset['file'],
						$asset['dependencies'],
						$asset['version']
					);
				} else {
					wp_enqueue_script(
						$handle,
						BALTO_DELIVERY_PLUGIN_URL . 'src/assets/admin/js/' . $asset['file'],
						$asset['dependencies'],
						$asset['version'],
						$asset['enqueue_in_footer']
					);

					wp_localize_script(
						$handle,
						str_replace( '-', '_', $handle . '_data'),
						$asset['localization']
					);
				}
			}
		}
	}

	/**
	 * Registers assets to enqueue
	 *
	 * @return void
	 */
	public function register_assets_to_enqueue(): void {

		$this->add_admin_asset(
			'balto-delivery-settings',
			'balto-delivery_page_balto-delivery-settings',
			'balto-settings.js',
			array('jquery'),
			BALTO_DELIVERY_VERSION,
			true,
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'save_balto_settings' ),
				'i18n'    => array(
					'saveSettings' => __( 'Save Settings', 'balto-delivery' ),
					'saving'       => __( 'Saving...', 'balto-delivery' ),
					'errorMessage' => __( 'An error occurred while saving settings.', 'balto-delivery' ),
				)
			)
		);
	}
}
