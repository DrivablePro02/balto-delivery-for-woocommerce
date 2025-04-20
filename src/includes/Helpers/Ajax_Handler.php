<?php
declare(strict_types=1);

namespace Balto_Delivery\Includes\Helpers;

defined('ABSPATH') || exit;

/**
 * Manages AJAX requests for WordPress plugins with enhanced security and flexibility.
 *
 * This class provides a standardized way to register and handle AJAX actions
 * with proper security measures, data sanitization, and error handling.
 *
 * @package Balto_Delivery_for_WooCommerce
 * @subpackage Balto_Delivery_for_WooCommerce/Helpers
 * @since 1.0.0
 * @author Yahya Eddaqqaq
 */
class Ajax_Handler {
    /**
     * Registers multiple AJAX actions with WordPress.
     *
     * @param array<string, callable> $actions Associative array of action names and callback functions.
     * @return void
     */
	public function register_ajax_actions(array $actions): void {
		foreach ($actions as $action => $callback) {
			add_action("wp_ajax_{$action}", $callback);
			add_action("wp_ajax_nopriv_{$action}", array($this, 'handle_non_logged_user_requests'));
		}
	}
	
	/**
	 * Handles requests from non-authenticated users.
	 *
	 * @return void
	 */
	public function handle_non_logged_user_requests(): void {
		wp_send_json_error(array('message' => "You're not allowed to do that"), 401);
	}

    /**
     * Processes an AJAX request with comprehensive security checks.
     *
     * @param callable $callback    Function to handle the AJAX request.
     * @param string   $nonce_action Unique nonce action for security verification.
     * @return void
     * @throws \Exception If security validation fails.
     */
    public function handle_request(callable $callback, string $nonce_action): void {
        try {
            // Validate nonce with strict security
            $this->validate_nonce($nonce_action);

            // Sanitize and prepare input data
            $request_data = $this->sanitize_request_data($_POST);

            // Execute callback and handle response
            $response = $this->execute_callback($callback, $request_data);

            // Send successful JSON response
            wp_send_json_success($response);
        } catch (\Exception $e) {
            // Send error response with appropriate status
            wp_send_json_error(
                [
                    'message' => $e->getMessage(),
                    'code'    => $e->getCode(),
                ],
                403
            );
        }
    }

    /**
     * Validates the AJAX nonce.
     *
     * @param string $nonce_action Nonce action to verify.
     * @return void
     * @throws \Exception If nonce is invalid or user lacks permissions.
     */
    private function validate_nonce(string $nonce_action): void {
        if (!isset($_POST['security'])) {
            throw new \Exception('Security token is missing', 403);
        }
        
        $security_token = sanitize_text_field(wp_unslash($_POST['security']));
        if (!wp_verify_nonce($security_token, $nonce_action)) {
            throw new \Exception('Invalid security token', 403);
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            throw new \Exception('Insufficient permissions', 403);
        }
    }

    /**
     * Recursively sanitizes request data.
     *
     * @param array $data The raw input data.
     * @return array Sanitized data.
     */
    private function sanitize_request_data(array $data): array {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitize_request_data($value);
            } else {
                // Handle different data types appropriately
                if (is_string($value)) {
                    $sanitized[$key] = sanitize_text_field(wp_unslash($value));
                } else {
                    $sanitized[$key] = $value;
                }
            }
        }
        
        return $sanitized;
    }

    /**
     * Executes the AJAX callback with error handling.
     *
     * @param callable $callback Callback function to execute.
     * @param array    $data     Sanitized request data.
     * @return mixed Callback response.
     * @throws \Exception If callback returns a WordPress error.
     */
    private function execute_callback(callable $callback, array $data) {
        $response = call_user_func($callback, $data);

        if (is_wp_error($response)) {
            throw new \Exception(
                $response->get_error_message(), 
                (int) $response->get_error_code() ?: 400
            );
        }

        return $response;
    }
}