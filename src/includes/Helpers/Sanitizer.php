<?php
declare(strict_types=1);

namespace Balto_Delivery\Includes\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Comprehensive data sanitization class
 *
 * @package Balto_Delivery_for_woocommerce
 * @subpackage Balto_Delivery_for_woocommerce/Helpers
 *
 * @since 1.0.0
 * @author Yahya Eddaqqaq
 */
class Sanitizer {
	/**
	 * Special fields that need specific sanitization
	 */
	private const SPECIAL_FIELDS = array(
		'shipping_provider' => 'string',
		'status'            => 'string',
		'tracking_number'   => 'string',
		'order_id'          => 'int',
		'email'             => 'email',
		'phone'             => 'phone',
		'url'               => 'url',
		'date'              => 'date',
	);

	/**
	 * Main sanitization method that handles different types of data
	 *
	 * @param mixed  $data Data to sanitize
	 * @param string $field_name Optional field name for context-specific sanitization
	 * @return mixed Sanitized data
	 */
	public function sanitize_data( $data, string $field_name = '' ) {
		if ( is_array( $data ) ) {
			return $this->sanitize_array( $data );
		}

		// Check for special fields first
		if ( $field_name && isset( self::SPECIAL_FIELDS[ $field_name ] ) ) {
			return $this->handle_special_field( $data, self::SPECIAL_FIELDS[ $field_name ] );
		}

		// Handle regular data types
		return $this->sanitize_by_type( $data );
	}

	/**
	 * Sanitizes array data
	 *
	 * @param array $data Array to sanitize
	 * @return array Sanitized array
	 */
	private function sanitize_array( array $data ): array {
		$sanitized = array();
		foreach ( $data as $key => $value ) {
			$sanitized[ $key ] = $this->sanitize_data( $value, $key );
		}
		return $sanitized;
	}

	/**
	 * Handles special field sanitization
	 *
	 * @param mixed  $data Data to sanitize
	 * @param string $type Type of sanitization to apply
	 * @return mixed Sanitized data
	 */
	private function handle_special_field( $data, string $type ) {
		switch ( $type ) {
			case 'string':
				return $this->sanitize_string( $data );
			case 'int':
				return $this->sanitize_int( $data );
			case 'email':
				return $this->sanitize_email( $data );
			case 'phone':
				return $this->sanitize_phone( $data );
			case 'url':
				return $this->sanitize_url( $data );
			case 'date':
				return $this->sanitize_date( $data );
			default:
				return $this->sanitize_by_type( $data );
		}
	}

	/**
	 * Sanitizes data based on its type
	 *
	 * @param mixed $data Data to sanitize
	 * @return mixed Sanitized data
	 */
	private function sanitize_by_type( $data ) {
		if ( is_null( $data ) ) {
			return null;
		}

		if ( is_int( $data ) ) {
			return $this->sanitize_int( $data );
		}

		if ( is_float( $data ) ) {
			return $this->sanitize_float( $data );
		}

		if ( is_bool( $data ) ) {
			return $this->sanitize_bool( $data );
		}

		if ( is_string( $data ) ) {
			if ( strtotime( $data ) ) {
				return $this->sanitize_date( $data );
			}
			if ( filter_var( $data, FILTER_VALIDATE_EMAIL ) ) {
				return $this->sanitize_email( $data );
			}
			if ( filter_var( $data, FILTER_VALIDATE_URL ) ) {
				return $this->sanitize_url( $data );
			}
			return $this->sanitize_string( $data );
		}

		return $data;
	}

	/**
	 * Sanitizes integer values
	 *
	 * @param mixed $data Data to sanitize as integer
	 * @return int Sanitized integer
	 */
	private function sanitize_int( $data ): int {
		return (int) $data;
	}

	/**
	 * Sanitizes string values
	 *
	 * @param mixed $data Data to sanitize as string
	 * @return string Sanitized string
	 */
	private function sanitize_string( $data ): string {
		if ( is_array( $data ) || is_object( $data ) ) {
			return '';
		}
		return sanitize_text_field( (string) $data );
	}

	/**
	 * Sanitizes boolean values
	 *
	 * @param mixed $data Data to sanitize as boolean
	 * @return bool Sanitized boolean
	 */
	private function sanitize_bool( $data ): bool {
		return (bool) filter_var( $data, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE ) ?? false;
	}

	/**
	 * Sanitizes float values
	 *
	 * @param mixed $data Data to sanitize as float
	 * @return float Sanitized float
	 */
	private function sanitize_float( $data ): float {
		return (float) $data;
	}

	/**
	 * Sanitizes URL values
	 *
	 * @param mixed $data Data to sanitize as URL
	 * @return string Sanitized URL
	 */
	public function sanitize_url( $data ): string {
		return esc_url_raw( (string) $data );
	}

	/**
	 * Sanitizes email addresses
	 *
	 * @param mixed $data Data to sanitize as email
	 * @return string Sanitized email
	 */
	public function sanitize_email( $data ): string {
		return sanitize_email( (string) $data );
	}

	/**
	 * Sanitizes phone numbers
	 *
	 * @param mixed $data Data to sanitize as phone number
	 * @return string Sanitized phone number
	 */
	public function sanitize_phone( $data ): string {
		$phone = (string) $data;
		return preg_replace( '/[^0-9+()\-]/', '', $phone );
	}

	/**
	 * Sanitizes dates
	 *
	 * @param mixed $data Data to sanitize as date
	 * @return string Sanitized date in Y-m-d format
	 */
	public function sanitize_date( $data ): string {
		$timestamp = strtotime( (string) $data );
		return $timestamp ? date( 'Y-m-d', $timestamp ) : '';
	}
}
