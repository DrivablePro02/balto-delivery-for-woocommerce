<?php
declare(strict_types=1);

namespace Balto_Delivery\Includes\Api;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST Calls Handler class for Balto Delivery
 *
 * This class handles REST API endpoints,
 * registering routes, processing requests,
 * validating inputs, and returning responses.
 *
 * @package Balto_Delivery_for_woocommerce
 * @subpackage Balto_Delivery_for_woocommerce/Api
 *
 * @since 1.0.0
 * @author Yahya Eddaqqaq
 */
class Rest_Controller {
	/**
	 * Send a request to an endpoint
	 *
	 * @param string $endpoint The endpoint URL
	 * @param string $method The HTTP method (GET, POST, etc.)
	 * @param array  $headers The headers to include in the request
	 * @param array  $data The data to send in the request
	 * @return array|WP_Error The response or WP_Error on failure
	 */
	public function send_request( string $endpoint, string $method, array $headers, array $data ) {
		$response = wp_remote_request(
			$endpoint,
			array(
				'method'  => $method,
				'headers' => $headers,
				'body'    => json_encode( $data ),
			)
		);

		if ( is_wp_error( $response ) ) {
			error_log( 'Failed to notify endpoint: ' . $response->get_error_message() );
		} else {
			error_log( 'Successfully notified endpoint' );
		}

		return $response;
	}
}
