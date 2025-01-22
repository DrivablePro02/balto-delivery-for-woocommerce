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
 * @package Balto_Delivery_for_woocommerce
 * @subpackage Balto_Delivery_for_woocommerce/Core
 *
 * @since 1.0.0
 * @author Yahya Eddaqqaq
 */

class Settings {
	const OPTION_NAME = 'balto_delivery_settings';

	private static $defaults = array(
		'general'  => array(
			'enable_tracking' => 'yes',
			'default_status'  => 'pending',
			'delivery_radius' => '50',
			'delivery_unit'   => 'km',
			'mms_website_url' => 'https://mymeatshop.co.uk/',
			'mms_webhook_url' => 'https://mymeatshop.co.uk/webhook',
		),
		'shipping' => array(
			'selected_shipping_provider' => 'balto',
			'balto'                      => array(
				'name'         => 'Balto',
				'tracking_url' => 'https://balto.com/track',
			),
			'fedex'                      => array(
				'name'         => 'FedEx',
				'tracking_url' => 'https://www.fedex.com/en-us/tracking.html',
			),
			'ups'                        => array(
				'name'         => 'UPS',
				'tracking_url' => 'https://www.ups.com/track',
			),
			'usps'                       => array(
				'name'         => 'USPS',
				'tracking_url' => 'https://tools.usps.com/go/TrackConfirmAction_input',
			),
		),
	);


	private static $instance;

	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
	}

	public static function get_defaults(): array {
		return self::$defaults;
	}

	public function sanitize_balto_settings( $input ): array {
		if ( ! is_array( $input ) ) {
			error_log( 'Input is not an array' );
			return self::$defaults;
		}

		$sanitized = array();
		$current   = $this->get_settings();

		foreach ( self::$defaults as $section => $fields ) {
			if ( ! isset( $input[ $section ] ) ) {
				$input[ $section ] = array();
			}

			foreach ( $fields as $key => $default ) {
				if ( ! isset( $input[ $section ][ $key ] ) ) {
					$sanitized[ $section ][ $key ] = $default;
					continue;
				}

				switch ( $key ) {
					// Boolean fields
					case 'enable_tracking':
					case 'enable_customer_notifications':
					case 'enable_admin_notifications':
					case 'sms_notifications':
					case 'enable_zones':
					case 'enable_rest_api':
					case 'enable_driver_app':
					case 'auto_assign_orders':
						$sanitized[ $section ][ $key ] = $input[ $section ][ $key ] === 'yes' ? 'yes' : 'no';
						break;

					// Integer fields
					case 'delivery_radius':
					case 'max_delivery_deliveries':
						$sanitized[ $section ][ $key ] = absint( $input[ $section ][ $key ] );
						break;

					// Float fields
					case 'price_per_km':
					case 'min_delivery_price':
						$sanitized[ $section ][ $key ] = (float) $input[ $section ][ $key ];
						break;

					// Shipping-specific sanitization
					case 'selected_shipping_provider':
						$allowed_providers             = array( 'balto', 'dhl', 'fedex', 'ups' );
						$sanitized[ $section ][ $key ] = in_array( $input[ $section ][ $key ], $allowed_providers, true )
							? $input[ $section ][ $key ]
							: 'balto';
						break;

					// Default sanitization
					default:
						$sanitized[ $section ][ $key ] = sanitize_text_field( $input[ $section ][ $key ] );
				}
			}
		}

		return $sanitized;
	}


	public function get_settings(): array {
		return get_option( self::OPTION_NAME, self::$defaults );
	}

	/**
	 * Helper method to get an individual setting value
	 *
	 * @param string $section
	 * @param string $key
	 * @return mixed
	 */
	public function get_setting( string $section, string $key, $default = null ) {
		$option_name = self::OPTION_NAME . '[' . $section . ']' . '[' . $key . ']';
		return get_option( $option_name );
	}
}
