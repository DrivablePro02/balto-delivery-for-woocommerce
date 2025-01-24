<?php

declare(strict_types=1);

namespace Balto_Delivery\Includes\Admin;

use Balto_Delivery\includes\Core\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings Page handler for Balto Delivery
 *
 * This class manages the WordPress admin interface for displaying and editing settings.
 * Stores settings both as a serialized array and as individual options for flexibility.
 *
 * @package Balto_Delivery_for_woocommerce
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
		$this->settings = Settings::get_instance();

		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'wp_ajax_save_balto_settings', array( $this, 'save_settings' ) );
	}

	public function load_textdomain(): void {
		load_plugin_textdomain( 'balto-delivery', false, dirname( BALTO_DELIVERY_PLUGIN_BASENAME ) . '/languages' );
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
			)
		);
	}


	public function render_checkbox_field( $args ): void {
		$value       = $this->settings->get_setting( $args['section'], $args['id'] );
		$option_name = $this->settings::OPTION_NAME . '[' . $args['section'] . '][' . $args['id'] . ']';
		?>
		<label>
			<input type="checkbox" 
					name="<?php echo esc_attr( $option_name ); ?>"
					value="yes"
					<?php checked( $value, 'yes' ); ?>>
			<?php echo esc_html( $args['description'] ); ?>
		</label>
		<?php
	}

	public function render_number_field( $args ): void {
		$value       = $this->settings->get_setting( $args['section'], $args['id'] );
		$option_name = $this->settings::OPTION_NAME . '[' . $args['section'] . '][' . $args['id'] . ']';
		?>
		<input type="number" 
				name="<?php echo esc_attr( $option_name ); ?>"
				value="<?php echo esc_attr( $value ); ?>"
				class="regular-text"
				<?php if ( $args['min'] !== false ) : ?> min="0" <?php endif; ?>>
		<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php
	}

	public function render_dropdown_field( $args ): void {
		$value       = $this->settings->get_setting( $args['section'], $args['id'] ) ?? 'balto';
		$option_name = $this->settings::OPTION_NAME . '[' . $args['section'] . '][' . $args['id'] . ']';
		?>
		<select name="<?php echo esc_attr( $option_name ); ?>">
			<option value="balto" <?php selected( $value, 'balto' ); ?>>Balto</option>
			<option value="dhl" <?php selected( $value, 'dhl' ); ?>>DHL</option>
		</select>
		<?php
	}
	public function render_settings_page(): void {
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Balto Delivery Settings', 'balto-delivery' ); ?></h1>
			<form id="balto-settings-form" method="post">
				<?php
				wp_nonce_field( 'save_balto_settings', '_wpnonce_save_balto_settings' );
				settings_fields( $this->settings::OPTION_NAME );
				do_settings_sections( $this->settings::OPTION_NAME );
				?>
				<input type="submit" name="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Settings', 'balto-delivery' ); ?>">
			</form>
		</div>
		<?php
	}

	/**
	 * Save both the serialized array and individual options
	 */
	public function save_settings(): void {
		check_ajax_referer( 'save_balto_settings', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You do not have sufficient permissions to access this page.', 'balto-delivery' ) );
		}

		// Log received data for debugging
		error_log( 'Received POST data: ' . print_r( $_POST, true ) );

		$settings = $_POST[ $this->settings::OPTION_NAME ] ?? array();

		// Ensure 'general' section exists
		if ( ! isset( $settings['general'] ) ) {
			$settings['general'] = array();
		}

		if ( ! isset( $settings['shipping'] ) ) {
			$settings['shipping'] = array();
		}

		// Handle checkbox fields
		$checkbox_fields = array( 'enable_tracking' );
		foreach ( $checkbox_fields as $field ) {
			$settings['general'][ $field ] = $settings['general'][ $field ] ?? 'no';
		}

		// Handle dropdown fields
		$dropdown_fields = array( 'selected_shipping_provider' );
		foreach ( $dropdown_fields as $field ) {
			$settings['shipping'][ $field ] = $settings['shipping'][ $field ] ?? 'balto';
		}

		// Sanitize settings
		$sanitized_settings = $this->settings->sanitize_balto_settings( $settings );

		// Update the main settings option
		if ( ! update_option( $this->settings::OPTION_NAME, $sanitized_settings ) ) {
			wp_send_json_error( __( 'Failed to save the main settings.', 'balto-delivery' ) );
		}

		// Update individual options and track results
		$updated_options = array();
		$failed_options  = array();

		foreach ( $sanitized_settings as $section => $section_settings ) {
			foreach ( $section_settings as $key => $value ) {
				$option_name = $this->settings::OPTION_NAME . "[$section][$key]";
				$old_value   = get_option( $option_name );

				if ( $old_value !== $value ) {
					if ( update_option( $option_name, $value ) ) {
						$updated_options[] = $option_name;
					} else {
						$failed_options[] = $option_name;
					}
				}
			}
		}

		// Send response based on results
		if ( ! empty( $updated_options ) ) {
			wp_send_json_success(
				array(
					'message'         => __( 'Settings saved successfully.', 'balto-delivery' ),
					'updated_options' => $updated_options,
					'failed_options'  => $failed_options,
				)
			);
		} else {
			wp_send_json_error(
				array(
					'message'        => __( 'No changes were made or some settings failed to save.', 'balto-delivery' ),
					'failed_options' => $failed_options,
				)
			);
		}
	}


	public function render_general_section(): void {
		echo '<p>' . esc_html__( 'Configure general delivery settings.', 'balto-delivery' ) . '</p>';
	}

	public function render_shipping_section(): void {
		echo '<p>' . esc_html__( 'Configure shipping settings.', 'balto-delivery' ) . '</p>';
	}
}