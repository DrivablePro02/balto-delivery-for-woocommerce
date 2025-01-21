<?php
declare(strict_types=1);

namespace Balto_Delivery\Includes\Services;

use Balto_Delivery\Includes\Helpers\Ajax_Handler;

if(!defined('ABSPATH')) exit;

class Delivery_Service
{
    /**
     * Instance of this class
     * @var Delivery_Service
     */
    private static $instance = null;

    /**
     * Get class instance | Singleton pattern
     * @return Delivery_Service
     */
    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
            self::$instance->register_ajax_actions();
        }
        return self::$instance;
    }

    /**
     * Private constructor to prevent direct creation
     */
    private function __construct() {}

    /**
     * Register AJAX actions
     */
    private function register_ajax_actions(): void {
        $ajax_handler = new Ajax_Handler();
        $ajax_handler->register_actions([
            'balto_update_delivery_status' => [$this, 'handle_update_delivery_status']
        ]);
    }

    /**
     * Handle AJAX request to update delivery status
     */
    public function handle_update_delivery_status(): void {
        $ajax_handler = new Ajax_Handler();
        $ajax_handler->handle_request(function($data) {
            $delivery_id = intval($data['delivery_id']);
            $new_status = sanitize_text_field($data['new_status']);
    
            $this->update_delivery_status($delivery_id, $new_status);
    
            return ['message' => 'Status updated successfully'];
        }, 'update_delivery_status_nonce');
    }

    /**
     * Update delivery status in the database
     * @param int $delivery_id
     * @param string $new_status
     */
    public function update_delivery_status(int $delivery_id, string $new_status): void {
        global $wpdb;

        $wpdb->update(
            $wpdb->prefix . 'balto_deliveries',
            ['status' => $new_status],
            ['id' => $delivery_id]
        );

        $this->send_status_change_email($delivery_id, $new_status);
        
        do_action('balto_delivery_notify_observers', $delivery_id, $new_status);
    }

    /**
     * Send notification email to the customer
     * @param int $delivery_id
     * @param string $new_status
     */
    public function send_status_change_email(int $delivery_id, string $new_status): void {
        $admin_email = get_option('admin_email');

        $subject = 'Delivery status updated';
        $message = "The status of delivery with ID $delivery_id has been updated to $new_status.";

        wp_mail($admin_email, $subject, $message);
    }
}