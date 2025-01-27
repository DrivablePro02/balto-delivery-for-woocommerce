<?php
declare(strict_types=1);

namespace Balto_Delivery\Includes\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Manages AJAX requests for WordPress plugins with enhanced security and flexibility.
 *
 * @package Balto_Delivery_for_WooCommerce
 * @subpackage Balto_Delivery_for_WooCommerce/Helpers
 * 
 * @since   1.0.0
 * @author Yahya Eddaqqaq
 */
class Ajax_Handler {

	/**
	 * Registers multiple AJAX actions with WordPress.
	 *
	 * @param array<string, callable> $actions Associative array of action names and callback functions.
	 */
	public function registerActions( array $actions ): void {
		foreach ( $actions as $action => $callback ) {
			add_action( "wp_ajax_{$action}", $callback );
			add_action( "wp_ajax_nopriv_{$action}", $callback );
		}
	}

	/**
	 * Processes an AJAX request with comprehensive security checks.
	 *
	 * @param  callable $callback    Function to handle the AJAX request.
	 * @param  string   $nonceAction Unique nonce action for security verification.
	 * @throws \Exception If nonce validation fails.
	 */
	public function handleRequest( callable $callback, string $nonceAction ): void {
		try {
			// Validate nonce with strict security
			$this->validateNonce( $nonceAction );

			// Sanitize and prepare input data
			$requestData = $this->sanitizeRequestData( $_POST );

			// Execute callback and handle response
			$response = $this->executeCallback( $callback, $requestData );

			// Send successful JSON response
			wp_send_json_success( $response );

		} catch ( \Exception $e ) {
			// Send error response with appropriate status
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
					'code'    => $e->getCode(),
				),
				403
			);
		}
	}

	/**
	 * Validates the AJAX nonce.
	 *
	 * @param  string $nonceAction Nonce action to verify.
	 * @throws \Exception If nonce is invalid.
	 */
	private function validateNonce( string $nonceAction ): void {
		if ( ! isset( $_POST['security'] ) || ! wp_verify_nonce( $_POST['security'], $nonceAction ) ) {
			throw new \Exception( 'Invalid or expired security token', 403 );
		}
	}

	/**
	 * Sanitizes incoming request data.
	 *
	 * @param  array $data Raw request data.
	 * @return array Sanitized request data.
	 */
	private function sanitizeRequestData( array $data ): array {
		return array_map(
			function ( $value ) {
				return is_string( $value ) ? sanitize_text_field( $value ) : $value;
			},
			$data
		);
	}

	/**
	 * Executes the AJAX callback with error handling.
	 *
	 * @param  callable $callback Callback function to execute.
	 * @param  array    $data     Sanitized request data.
	 * @return mixed Callback response.
	 * @throws \Exception If callback returns a WordPress error.
	 */
	private function executeCallback( callable $callback, array $data ) {
		$response = call_user_func( $callback, $data );

		if ( is_wp_error( $response ) ) {
			throw new \Exception( $response->get_error_message(), $response->get_error_code() );
		}

		return $response;
	}
}
