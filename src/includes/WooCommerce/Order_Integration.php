<?php
declare(strict_types=1);

namespace Balto_Delivery\Includes\WooCommerce;

if (!defined('ABSPATH')) {
    exit;
}

use Balto_Delivery\Includes\Core\Settings;
use Balto_Delivery\Includes\Api\Rest_Controller;
use Balto_Delivery\Includes\Db\Db_Handler;

/**
 * Class Order_Integration
 *
 * Handles integration between WooCommerce orders and Balto Delivery service.
 * This class manages order processing, data saving, and communication with
 * the delivery API for new orders created through checkout or admin.
 *
 * @package Balto_Delivery\Includes\WooCommerce
 * @since 1.0.0
 */
class Order_Integration {
    /**
     * Settings instance.
     *
     * @var Settings
     */
    private $settings;

    /**
     * REST API controller instance.
     *
     * @var Rest_Controller
     */
    private $rest_controller;

    /**
     * Database handler instance.
     *
     * @var Db_Handler
     */
    private $db_handler;

    /**
     * Constructor.
     *
     * Initializes required components and registers WordPress hooks
     * for order processing.
     */
    public function __construct() {
        $this->settings = Settings::get_instance();
        $this->rest_controller = new Rest_Controller();
        $this->db_handler = new Db_Handler();

        // Hook into order creation events
        add_action('woocommerce_new_order', [$this, 'handle_new_order_created'], 20, 1);
        add_action('wp_insert_post', [$this, 'handle_order_from_admin']);
    }

    /**
     * Handles orders created from the WordPress admin panel.
     *
     * This method processes orders created through the admin interface
     * rather than through the standard checkout process.
     *
     * @param int $order_id The ID of the newly created order.
     * @return void
     */
    public function handle_order_from_admin($order_id) {
        if (get_post_type($order_id) !== 'shop_order' || did_action('woocommerce_checkout_order_processed')) {
            return;
        }

        $order = wc_get_order($order_id);
        if ($order && $this->validate_order($order)) {
            $this->save_order_data($order_id, $order);
        }
    }

    /**
     * Validates an order to ensure it can be processed.
     *
     * Checks if the order has valid user metadata.
     *
     * @param \WC_Order $order The WooCommerce order object.
     * @return bool True if the order is valid, false otherwise.
     */
    private function validate_order($order): bool {
        $user_meta = get_user_meta($order->get_user_id());
        return !empty($user_meta);
    }

    /**
     * Handles newly created orders from the WooCommerce checkout.
     *
     * This method is hooked to 'woocommerce_new_order' and processes
     * data sending, storage, and metadata setting for new orders.
     *
     * @param int $order_id The ID of the newly created order.
     * @return void
     */
    public function handle_new_order_created(int $order_id): void {
        $order = wc_get_order($order_id);
        if ($order) {
            $this->send_order_data($order_id, $order);
            $this->save_order_data($order_id, $order);
            $this->set_order_shipping_provider_meta($order_id);
        }
    }

    /**
     * Sends order data to the external delivery service API.
     *
     * Collects order information and sends it to the webhook endpoint
     * for further processing by delivery service.
     *
     * @param int      $order_id The ID of the order.
     * @param \WC_Order $order    The WooCommerce order object.
     * @return void
     */
    public function send_order_data(int $order_id, object $order): void {
        $order_data = $order->get_data();
        $order_number = get_post_meta($order_id, 'addify_set_post_meta', true);
        $shipping_provider = $this->get_shipping_provider($order_id);

        $this->rest_controller->send_request(
            'https://webhook.site/61eda699-5d54-4f71-b12f-2ac895747045',
            'POST',
            [],
            [
                'order_id' => $order_id,
                'order_number' => $order_number,
                'selected_shipping_provider' => $shipping_provider,
                'order_data' => $order_data,
            ]
        );
    }

    /**
     * Saves order delivery information to the database.
     *
     * Creates a new delivery record in the custom deliveries table
     * with initial tracking and status information.
     *
     * @param int      $order_id The ID of the order.
     * @param \WC_Order $order    The WooCommerce order object.
     * @return void
     */
    public function save_order_data(int $order_id, object $order): void {
        global $wpdb;
        $table_name = $wpdb->prefix . 'balto_deliveries';

        $data = [
            'order_id' => $order_id,
            'tracking_number' => wp_generate_password(12, false),
            'status' => 'pending',
            'shipping_provider' => $this->get_shipping_provider($order_id),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ];

        $this->db_handler->insert_delivery($data);
    }

    /**
     * Sets order metadata for the shipping provider.
     *
     * Stores the selected shipping provider as order metadata
     * for future reference.
     *
     * @param int $order_id The ID of the order.
     * @return void
     */
    public function set_order_shipping_provider_meta(int $order_id): void {
        update_post_meta($order_id, '_balto_order_shipping_provider', $this->get_shipping_provider($order_id));
    }

    /**
     * Gets the shipping provider for an order.
     *
     * Retrieves the shipping provider from order metadata or falls back
     * to the default provider from plugin settings.
     *
     * @param int $order_id The ID of the order.
     * @return string The shipping provider identifier.
     */
    private function get_shipping_provider(int $order_id): string {
        return get_post_meta($order_id, '_balto_order_shipping_provider', true) 
            ?: get_option($this->settings::OPTION_NAME . '[shipping][selected_shipping_provider]', '');
    }
}