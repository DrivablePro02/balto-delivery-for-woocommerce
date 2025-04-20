<?php

declare(strict_types=1);

namespace Balto_Delivery\Includes\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings handler for Balto Delivery
 *
 * This class manages the settings for the Balto Delivery plugin, including
 * general settings, notification preferences, delivery zones, API integration,
 * and driver configurations. It provides methods for sanitizing, retrieving,
 * and rendering settings in the WordPress admin panel.
 *
 * @package    Balto_Delivery_for_woocommerce
 * @subpackage Balto_Delivery_for_woocommerce/Core
 *
 * @since  1.0.0
 * @author Yahya Eddaqqaq
 */

class Settings {

	/**
	 * Option name in WordPress options table
	 *
	 * @var string
	 */
	const OPTION_NAME = 'balto_delivery_settings';

	/**
	 * Default settings structure
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private static array $defaults = array(
		'general'  => array(
			'balto_delivery_api_key'	=> '',
			'enable_tracking' 			=> 'yes',
			'default_status'  			=> 'pending',
			'delivery_radius' 			=> '50',
			'delivery_unit'   			=> 'km',
			'mms_website_url' 			=> 'https://mymeatshop.co.uk/',
			'mms_webhook_url' 			=> 'https://mymeatshop.co.uk/webhook',
		),
		'shipping' => array(
			'selected_shipping_provider' => 'balto',
			'balto'                      => array(
				'name'         => 'Balto',
				'tracking_url' => 'https://balto.com/track',
				'enabled'	   => true
			),
			'fedex'                      => array(
				'name'         => 'FedEx',
				'tracking_url' => 'https://www.fedex.com/en-us/tracking.html',
				'enabled'	   => true
			),
			'ups'                        => array(
				'name'         => 'UPS',
				'tracking_url' => 'https://www.ups.com/track',
				'enabled'	   => true
			),
			'usps'                       => array(
				'name'         => 'USPS',
				'tracking_url' => 'https://tools.usps.com/go/TrackConfirmAction_input',
				'enabled'	   => true
			),
		),
	);

	/**
	 * Instance of this class
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

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
	}

	/**
	 * Get default settings
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function get_defaults(): array {
		return self::$defaults;
	}

	/**
	 * Get the unserialized settings value
	 *
	 * @param string $section Section name.
	 * @param string $key     Setting key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 * @throws \Exception When settings is not an array.
	 */
	public function get_unserialized_setting( string $section, string $key, mixed $default = '' ): mixed {
		$settings = get_option( self::OPTION_NAME );

		// Ensure correct unserialization
		if ( is_string( $settings ) ) {
			if ( is_serialized( $settings ) ) {
				$settings = unserialize( $settings );
			} elseif ( json_decode( $settings, true ) ) {
				$settings = json_decode( $settings, true );
			}
		}

		// Ensure settings is an array
		if ( ! is_array( $settings ) ) {
			throw new \Exception( 'Settings is not an array' );
		}

		// Fix for duplicate "balto" issue in "shipping"
		if ( $section === 'shipping' && $key === 'selected_shipping_provider' ) {
			if ( isset( $settings['shipping']['selected_shipping_provider'] ) && ! is_array( $settings['shipping']['selected_shipping_provider'] ) ) {
				return $settings['shipping']['selected_shipping_provider'];
			}
		}

		// Default behavior
		return $settings[ $section ][ $key ] ?? $default;
	}

	/**
	 * Get the list of available shipping providers from the database.
	 *
	 * @return array<string, string> Associative array of provider keys => names.
	 */
	public function get_shipping_providers(): array {
		$settings = get_option( self::OPTION_NAME );

		if ( is_string( $settings ) && is_serialized( $settings ) ) {
			$settings = unserialize( $settings );
		} elseif ( is_string( $settings ) ) {
			$settings = json_decode( $settings, true );
		}

		if ( ! is_array( $settings ) || ! isset( $settings['shipping'] ) ) {
			return array();
		}

		$shipping_providers = array();

		// Extract shipping providers dynamically
		foreach ( $settings['shipping'] as $key => $provider ) {
			if ( is_array( $provider ) && isset( $provider['name'] ) && isset( $provider['enabled'] ) && $provider['enabled'] === '1' ) {
				$shipping_providers[ $key ] = $provider['name'];
			}
		}

		return $shipping_providers;
	}

	/**
	 * Sanitize Balto settings
	 *
	 * @param array<string, mixed>|string $input Input data to sanitize.
	 * @return array<string, array<string, mixed>>
	 */
	public function sanitize_balto_settings( array|string $input ): array {
		// Debug logging
		error_log( 'Raw Input Data: ' . print_r( $input, true ) );
	
		// Convert string input (if serialized or JSON) to an array
		if ( is_string( $input ) ) {
			if ( is_serialized( $input ) ) {
				$input = unserialize( $input );
			} else {
				$decoded = json_decode( $input, true );
				if ( json_last_error() === JSON_ERROR_NONE ) {
					$input = $decoded;
				}
			}
		}
	
		// Ensure input is an array
		if ( ! is_array( $input ) ) {
			error_log( 'Input is not an array after decoding. Initializing empty array.' );
			$input = array();
		}
	
		// Get current settings to merge with defaults
		$current = get_option( self::OPTION_NAME, self::$defaults );
		if ( is_string( $current ) ) {
			if ( is_serialized( $current ) ) {
				$current = unserialize( $current );
			} else {
				$decoded = json_decode( $current, true );
				if ( json_last_error() === JSON_ERROR_NONE ) {
					$current = $decoded;
				}
			}
		}
	
		// Ensure current settings are an array
		if ( ! is_array( $current ) ) {
			$current = self::$defaults;
		}
	
		$sanitized = array();
	
		// Loop through default settings structure
		foreach ( self::$defaults as $section => $fields ) {
			$sanitized[ $section ] = array();
	
			foreach ( $fields as $key => $default ) {
				// Get the value from input, fallback to current settings, then default
				$value = $input[ $section ][ $key ] ?? $current[ $section ][ $key ] ?? $default;
	
				// Sanitize based on field type
				switch ( $key ) {
					// Boolean fields (yes/no)
					case 'enable_tracking':
						$sanitized[ $section ][ $key ] = filter_var( $value, FILTER_VALIDATE_BOOLEAN );
						break;
						
					// Integer fields
					case 'delivery_radius':
					case 'max_delivery_deliveries':
						$sanitized[ $section ][ $key ] = absint( filter_var( $value, FILTER_SANITIZE_NUMBER_INT ) );
						break;
	
					// Float fields
					case 'price_per_km':
					case 'min_delivery_price':
						$sanitized[ $section ][ $key ] = (float) filter_var( $value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );
						break;
	
					// Allowed shipping providers
					case 'selected_shipping_provider':
						$allowed_providers = array( 'balto', 'dhl', 'fedex', 'ups', 'usps' );
						$sanitized[ $section ][ $key ] = in_array( $value, $allowed_providers, true ) ? $value : 'balto';
						break;
	
					// Default sanitization (text)
					default:
						$sanitized[ $section ][ $key ] = is_array( $value ) 
							? array_map( 'sanitize_text_field', $value )
							: sanitize_text_field( $value );
				}
			}
		}
	
		// Debug logging
		error_log( 'Sanitized Output: ' . print_r( $sanitized, true ) );
	
		return $sanitized;
	}

	/**
	 * Get all settings
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_settings(): array {
		$settings = is_multisite() ? get_site_option( self::OPTION_NAME ) : get_option( self::OPTION_NAME );
		$settings_maybied = maybe_serialize( $settings ); 
		return $settings ? json_decode( $settings_maybied, true ) : self::$defaults;
	}
	
	/**
	 * Add new settings option
	 *
	 * @param array<string, array<string, mixed>> $settings Settings to add.
	 * @return bool Whether the settings were added successfully.
	 */
	public function add_option( array $settings ): bool {
		// Sanitize before saving
		$sanitized_settings = $this->sanitize_balto_settings( $settings );
		
		// JSON encode with error checking
		$encoded_settings = json_encode( $sanitized_settings );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			error_log( 'JSON encoding failed: ' . json_last_error_msg() );
			return false;
		}
		
		if ( is_multisite() ) {
			return add_site_option( self::OPTION_NAME, $encoded_settings );
		} else {
			return add_option( self::OPTION_NAME, $encoded_settings, '', 'no' ); // 'no' means not autoload
		}
	}
	
	/**
	 * Update existing settings option
	 *
	 * @param array<string, array<string, mixed>> $settings Settings to update.
	 * @return bool Whether the settings were updated successfully.
	 */
	public function update_option( array $settings ): bool {
		// Sanitize before saving
		$sanitized_settings = $this->sanitize_balto_settings( $settings );
		
		// JSON encode with error checking
		$encoded_settings = json_encode( $sanitized_settings );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			error_log( 'JSON encoding failed: ' . json_last_error_msg() );
			return false;
		}
		
		if ( is_multisite() ) {
			return update_site_option( self::OPTION_NAME, $encoded_settings );
		} else {
			return update_option( self::OPTION_NAME, $encoded_settings, 'no' );
		}
	}
	
	/**
	 * Get an individual setting value
	 *
	 * @param string $section Section name.
	 * @param string $key     Setting key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public function get_setting( string $section, string $key, mixed $default = null ): mixed {
		$settings = $this->get_settings();
		return $settings[ $section ][ $key ] ?? $default;
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