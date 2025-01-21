<?php
declare(strict_types=1);

namespace Balto_Delivery\Includes\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX handler class for Balto Delivery
 *
 * This class handles the registration and processing of AJAX requests for the plugin.
 * It provides a centralized way to manage AJAX actions and ensures security checks are performed.
 *
 * @package Balto_Delivery_for_woocommerce
 * @subpackage Balto_Delivery_for_woocommerce/Ajax
 *
 * @since 1.0.0
 * @author Yahya Eddaqqaq
 */

class Ajax_Handler {
	/**
	 * Register AJAX actions.
	 *
	 * @param array $actions Associative array of action names and their corresponding callback functions.
	 */
	public function register_actions( array $actions ): void {
		foreach ( $actions as $action => $callback ) {
			add_action( "wp_ajax_$action", $callback );
			add_action( "wp_ajax_nopriv_$action", $callback );
		}
	}

	/**
	 * Handle AJAX request.
	 *
	 * @param callable $callback The callback function to handle the request.
	 */
	public function handle_request( callable $callback, string $nonce_action ): void {
		// Validate nonce for security
		if ( ! isset( $_POST['security'] ) || ! check_ajax_referer( $nonce_action, 'security', false ) ) {
			wp_send_json_error( array( 'message' => 'Invalid nonce' ) );
			return;
		}

		// Sends the AJAX data to the callback function
		$response = call_user_func( $callback, $_POST );

		// Checks if the response from call_user_func is a WP_ERROR object
		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array( 'message' => $response->get_error_message() ) );
		} else {
			wp_send_json_success( $response );
		}
	}
}
