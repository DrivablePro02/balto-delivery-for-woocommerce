<?php
declare(strict_types=1);

namespace Balto_Delivery\Includes\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Assets Manager class for Balto Delivery
 *
 * Handles the enqueueing of CSS and JavaScript assets for the plugin.
 *
 * @package    Balto_Delivery_for_woocommerce
 * @subpackage Balto_Delivery_for_woocommerce/Core
 *
 * @since 1.0.0
 * @author Yahya Eddaqqaq
 */
class Assets_Manager {

	/**
	 * Holds the array of admin scripts and styles.
	 *
	 * @var array<string, array<string, array>>
	 */
	private $admin_assets = array();

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ), 40, 1 );
		add_action( 'admin_init', array( $this, 'register_assets_to_enqueue' ), 20, 0 );
	}

	/**
	 * Adds an admin script or style to the array of admin assets.
	 *
	 * @param string $handle            The handle of the script or style.
	 * @param array  $pages             The page where the script or style should be enqueued.
	 * @param string $file              The file name of the script or style.
	 * @param array  $dependencies      An array of the script or style's dependencies.
	 * @param array  $localization      Data to be localized for the script.
	 * @param string $version           The version number of the script or style.
	 * @param bool   $enqueue_in_footer Whether the script should be enqueued in the footer.
	 * @return void
	 */
	public function add_admin_asset(
		string $handle,
		array $pages,
		string $file,
		array $dependencies = array(),
		array $localization = array(),
		string $version = BALTO_DELIVERY_VERSION,
		bool $enqueue_in_footer = true
	): void {
		foreach ( $pages as $page ) {
			$this->admin_assets[ $page ][ $handle ] = array(
				'file'              => $file,
				'dependencies'      => $dependencies,
				'version'           => $version,
				'enqueue_in_footer' => $enqueue_in_footer,
				'localization'      => $localization,
			);
		}
	}

	/**
	 * Register and enqueue admin-specific scripts.
	 *
	 * @param  string $hook The current admin page hook.
	 * @since  1.0.0
	 * @return void
	 */
	public function enqueue_admin_assets( string $hook ): void {
		if ( ! isset( $this->admin_assets[ $hook ] ) ) {
			return;
		}

		foreach ( $this->admin_assets[ $hook ] as $handle => $asset ) {
			$file_url = BALTO_DELIVERY_PLUGIN_URL . 'src/assets/admin/' . 
				( 'css' === substr( $handle, -3 ) ? 'css/' : 'js/' ) . 
				$asset['file'];
			
			if ( 'css' === substr( $handle, -3 ) ) {
				wp_enqueue_style(
					$handle,
					$file_url,
					$asset['dependencies'],
					$asset['version']
				);
			} else {
				wp_enqueue_script(
					$handle,
					$file_url,
					$asset['dependencies'],
					$asset['version'],
					$asset['enqueue_in_footer']
				);

				if ( ! empty( $asset['localization'] ) ) {
					wp_localize_script(
						$handle,
						str_replace( '-', '_', $handle . '_data' ),
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
			array( 'balto-delivery_page_balto-delivery-settings', 'toplevel_page_balto-delivery' ),
			'balto-settings.js',
			array( 'jquery' ),
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

		$this->add_admin_asset(
			'balto-delivery-settings-css',
			array( 'balto-delivery_page_balto-delivery-settings' ),
			'balto-delivery-settings.css'
		);
	}
}
