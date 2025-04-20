<?php

declare(strict_types=1);

namespace Balto_Delivery\Includes\Admin;

use Balto_Delivery\Includes\Core\Settings;
use Balto_Delivery\Includes\Helpers\Ajax_Handler;
use Balto_Delivery\Includes\Api\Api_Key_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings Page handler for Balto Delivery
 *
 * This class manages the WordPress admin interface for displaying and editing settings.
 * Stores settings both as a serialized array and as individual options for flexibility.
 *
 * @package    Balto_Delivery_for_woocommerce
 * @subpackage Balto_Delivery_for_woocommerce/Admin
 *
 * @since 1.0.0
 */
class Settings_Page {

	/**
	 * Instance of this class
	 *
	 * @var Settings_Page|null
	 */
	private static $instance = null;

	/**
	 * Settings instance
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Ajax Handler instance
	 *
	 * @var Ajax_Handler
	 */
	private $ajax_handler;

	/**
	 * Api Key Manager instance
	 *
	 * @var Api_Key_Manager
	 */
	private $Api_Key_Manager;

	/**
	 * Get class instance | Singleton
	 *
	 * @return self
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->settings     = Settings::get_instance();
		$this->ajax_handler = new Ajax_Handler();
		$this->Api_Key_Manager = new Api_Key_Manager();

			// Debug logging
		error_log( 'issuers Registering AJAX action: save_balto_settings' );

		$this->ajax_handler->register_ajax_actions(
			array(
				'save_balto_settings' => array( $this, 'save_settings' ),
			)
		);

		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	public function register_settings(): void {
		register_setting(
			Settings::OPTION_NAME,
			Settings::OPTION_NAME,
			array(
				'type'              => 'array',
				'default'           => $this->settings->get_defaults(),
				'sanitize_callback' => array( $this->settings, 'sanitize_balto_settings' ),
			)
		);

		$this->add_sections();
		$this->add_fields();
	}

	private function add_sections(): void {
		add_settings_section(
			'balto_delivery_general',
			__( 'General Settings', 'balto-delivery' ),
			array( $this, 'render_general_section' ),
			$this->settings::OPTION_NAME
		);

		add_settings_section(
			'balto_delivery_shipping',
			__( 'Shipping Settings', 'balto-delivery' ),
			array( $this, 'render_shipping_section' ),
			$this->settings::OPTION_NAME
		);
	}

	private function add_fields(): void {
		add_settings_field(
			'enable_tracking',
			__( 'Enable Tracking', 'balto-delivery' ),
			array( $this, 'render_checkbox_field' ),
			$this->settings::OPTION_NAME,
			'balto_delivery_general',
			array(
				'id'          => 'enable_tracking',
				'section'     => 'general',
				'description' => __( 'Enable delivery tracking functionality', 'balto-delivery' ),
			)
		);

		add_settings_field(
			'balto_api_key',
			__( 'Balto Api Key', 'balto-delivery' ),
			array( $this, 'render_balto_api_field' ),
			$this->settings::OPTION_NAME,
			'balto_delivery_general',
			array(
				'id'          => 'balto_delivery_api_key',
				'section'     => 'general',
				'description' => __( 'Your balto API key', 'balto-delivery' ),
			)
		);

		add_settings_field(
			'delivery_radius',
			__( 'Delivery Radius', 'balto-delivery' ),
			array( $this, 'render_number_field' ),
			$this->settings::OPTION_NAME,
			'balto_delivery_general',
			array(
				'id'          => 'delivery_radius',
				'section'     => 'general',
				'description' => __( 'Maximum delivery radius', 'balto-delivery' ),
				'min'         => true,
			)
		);

		add_settings_field(
			'shipping_provider',
			__( 'Shipping Provider', 'balto-delivery' ),
			array( $this, 'render_dropdown_field' ),
			$this->settings::OPTION_NAME,
			'balto_delivery_shipping',
			array(
				'id'          => 'selected_shipping_provider',
				'section'     => 'shipping',
				'description' => __( 'Select the shipping provider', 'balto-delivery' ),
				'options'     => array(
					'balto' => 'Balto',
					'dhl'   => 'DHL',
					'fedex' => 'FedEx',
					'ups'   => 'UPS',
					'usps'  => 'USPS',
				),
			)
		);
	}


	public function render_checkbox_field( $args ): void {
		$value = $this->settings->get_unserialized_setting( $args['section'], $args['id'] );
		?><div class="balto-form-group balto-form-check">
			<input type="checkbox" 
					class="balto-form-check-input"
					name="<?php echo esc_attr( '[' . $this->settings::OPTION_NAME . ']' . $args['section'] . '[' . $args['id'] . ']' ); ?>"
					value="yes"
					<?php checked( $value, 'yes' ); ?>
			>
			<!-- <label class="balto-form-check-label" for="<?php echo esc_attr( $args['section'] . '-' . $args['id'] ); ?>">
				<?php echo esc_html( $args['description'] ); ?>
			</label> -->
		</div>
		<?php
	}

	public function render_balto_api_field( $args ): void {
		$value = $this->Api_Key_Manager->get_api_key();
		?>
		<div class="balto-form-group">
			<!-- <label for="<?php echo esc_attr( $args['section'] . '-' . $args['id'] ); ?>">
				<?php echo esc_html( $args['description'] ); ?>
			</label> -->
			<input type="text" 
				class="balto-form-control"
				name="<?php echo esc_attr( '[' . $this->settings::OPTION_NAME . ']' . '[' . $args['section'] . ']' . '[' . $args['id'] . ']' ); ?>"
				value="<?php echo esc_attr( $value ); ?>"
			>
		</div>
		<?php
	}
	

	public function render_number_field( $args ): void {
		$value = $this->settings->get_unserialized_setting( $args['section'], $args['id'] );
		?>
		<div class="balto-form-group">
			<!-- <label for="<?php echo esc_attr( $args['section'] . '-' . $args['id'] ); ?>">
				<?php echo esc_html( $args['description'] ); ?>
			</label> -->
			<input type="number" 
				class="balto-form-control"
				name="<?php echo esc_attr( '[' . $this->settings::OPTION_NAME . ']' . '[' . $args['section'] . ']' . '[' . $args['id'] . ']' ); ?>"
				value="<?php echo esc_attr( $value ); ?>"
				<?php
				if ( $args['min'] !== false ) :
					?>
						min="0" 
						<?php
				endif;
				?>
			>
		</div>
		<?php
	}

	public function render_dropdown_field( $args ): void {
		$value     = $this->settings->get_unserialized_setting( $args['section'], $args['id'] );
		$providers = $this->settings->get_shipping_providers(); // Get providers dynamically

		?>
		<div class="balto-form-group">
			<!-- <label for="<?php echo esc_attr( $args['section'] . '-' . $args['id'] ); ?>">
				<?php echo esc_html( $args['description'] ); ?>
			</label> -->
			<select name="<?php echo esc_attr( '[' . $this->settings::OPTION_NAME . '][' . $args['section'] . '][' . $args['id'] . ']' ); ?>" class="balto-form-control">
				<?php if ( empty( $providers ) ) : ?>
					<option value=""><?php esc_html_e( 'No shipping providers available', 'balto-delivery' ); ?></option>
				<?php else : ?>
					<?php foreach ( $providers as $key => $name ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $value, $key ); ?>>
							<?php echo esc_html( $name ); ?>
						</option>
					<?php endforeach; ?>
				<?php endif; ?>
			</select>
		</div>
		<?php
	}



	public function render_settings_page(): void {
		?>
		<div class="balto-wrap">
			<h1><?php echo esc_html__( 'Balto Delivery Settings', 'balto-delivery' ); ?></h1>
			<form id="balto-settings-form" method="post">
		<?php
		wp_nonce_field( 'save_balto_settings', '_wpnonce_save_balto_settings' );
		settings_fields( $this->settings::OPTION_NAME );
		do_settings_sections( $this->settings::OPTION_NAME );
		?>
				<input type="submit" name="submit" class="balto-button balto-button-primary" value="<?php esc_attr_e( 'Save Settings', 'balto-delivery' ); ?>">
			</form>
		<?php
	}

	/**
	 * Save both the serialized array and individual options
	 */
	public function save_settings(): void {
		$this->ajax_handler->handle_request(
			function ( $requestData ) {
				if ( isset( $_POST['balto_delivery_settings']['general']['balto_delivery_api_key'] ) ) {
					$api_key = sanitize_text_field( $_POST['balto_delivery_settings']['general']['balto_delivery_api_key'] );
					
					if ( ! empty( $api_key ) ) {
						// Store the API key securely
						$this->Api_Key_Manager->store_api_key( $api_key );
					}
					// Remove API key from payload for saving settings
					unset( $_POST['balto_delivery_settings']['general']['balto_delivery_api_key'] );
				}
	
				// Get existing settings from the database
				$existing_settings = get_option( $this->settings::OPTION_NAME );
	
				if ( is_string( $existing_settings ) ) {
					if ( is_serialized( $existing_settings ) ) {
						$existing_settings = unserialize( $existing_settings );
					} else {
						$decoded = json_decode( $existing_settings, true );
						if ( json_last_error() === JSON_ERROR_NONE ) {
							$existing_settings = $decoded;
						}
					}
				}
	
				if ( ! is_array( $existing_settings ) ) {
					$existing_settings = array(
						'general'  => array(),
						'shipping' => array(),
					);
				}
	
				// Ensure required sections exist
				if ( ! isset( $existing_settings['general'] ) ) {
					$existing_settings['general'] = array();
				}
				if ( ! isset( $existing_settings['shipping'] ) ) {
					$existing_settings['shipping'] = array();
				}
	
				// Extract new settings from AJAX request
				$new_settings = array(
					'general'  => array(
						'enable_tracking' => isset( $requestData['balto_delivery_settings']['general']['enable_tracking'] )
							? sanitize_text_field( $requestData['balto_delivery_settings']['general']['enable_tracking'] )
							: 'no',
						'delivery_radius' => sanitize_text_field( $requestData['balto_delivery_settings']['general']['delivery_radius'] ?? '50' ),
						'balto_delivery_api_key' => get_option('balto_delivery_api_key'),
					),
					'shipping' => array(
						'selected_shipping_provider' => sanitize_text_field( $requestData['balto_delivery_settings']['shipping']['selected_shipping_provider'] ?? 'balto' ),
					),
				);
	
				// Merge new settings with existing ones, preserving structure
				$merged_settings = array_replace_recursive( $existing_settings, $new_settings );
	
				// Sanitize settings before saving
				$sanitized_settings = $this->settings->sanitize_balto_settings( $merged_settings );
	
				// Save settings as an array (NOT serialized)
				$update_result = update_option( $this->settings::OPTION_NAME, $sanitized_settings );
	
				if ( $update_result ) {
					return array(
						'success' => true,
						'message' => __( 'Settings saved successfully.', 'balto-delivery' ),
						'data'    => $sanitized_settings,
					);
				}
	
				throw new \Exception( __( 'Failed to save settings.', 'balto-delivery' ) );
			},
			'save_balto_settings'
		);
	}
	


	public function render_general_section(): void {
		echo '<p>' . esc_html__( 'Configure general delivery settings.', 'balto-delivery' ) . '</p>';
	}

	public function render_shipping_section(): void {
		echo '<p>' . esc_html__( 'Configure shipping settings.', 'balto-delivery' ) . '</p>';
	}
}

