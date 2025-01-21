<?php
declare(strict_types=1);

namespace Balto_Delivery\Includes\Api;

use Balto_Delivery\Includes\Core\Settings;

class Webhook_Handler
{
    private $rest_controller;

    public function __construct() {
        $this->rest_controller = new Rest_Controller();
        add_action('balto_delivery_notify_observers', [$this, 'status_change'], 10, 2);
    }

    public function send_status_change($delivery_id, $new_status) {
        $settings = new Settings();
        $webhook_url = $settings->get_setting('general', 'mms_webhook_url');
        $payload = json_encode([
            'deliver_url' => $delivery_id,
            'status' => $new_status,
        ]);

        $response = $this->rest_controller->send_request($webhook_url, 'POST', [
            'Content-Type' => 'application/json',
        ], [
            'body' => $payload,
        ]);

        if (is_wp_error($response)) {
            error_log('Webhook error: ' . $response->get_error_message());
        } else {
            error_log('Webhook sent successfully: ' . wp_remote_retrieve_body($response));
        }
    }

    private function status_change(int $delivery_id, string $new_status) {
        // Example usage
        $this->send_status_change($delivery_id, $new_status);
    }
}