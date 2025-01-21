<?php
declare(strict_types=1);

namespace Balto_Delivery\Includes\WooCommerce;

if(!defined('ABSPATH')) exit;

/**
 * This class handles the integration of WooCommerce orders with external shipping providers.
 * It listens for the 'woocommerce_new_order' action and when triggered, sends a request to the specified URL
 * with the order data.
 * 
 * @package Balto_Delivery_for_woocommerce
 * @subpackage Balto_Delivery_for_woocommerce/Woocommerce
 * 
 * @since 1.0.0
 * @author Yahya Eddaqqaq
 */
 

use Balto_Delivery\Includes\Core\Settings;
use Balto_Delivery\Includes\Api\Rest_Controller;
use Balto_Delivery\Includes\Db\Db_Handler;

class Order_Integration
{
    /**
     * Instance of the Settings class.
     *
     * @var Settings
     */
    private $settings;

    /**
     * Instance of the Rest_Controller class.
     *
     * @var Rest_Controller
     */
    private $rest_controller;

    /**
     * Instance of the Db_Handler class
     *
     * @var Db_Handler
     */
     private $db_handler;
     
    /**
     * Constructor for the class.
     * Initializes the $settings and $rest_controller properties.
     */
    public function __construct() {
        $this->settings = Settings::get_instance();
        $this->rest_controller  =   new Rest_Controller;
        $this->db_handler       =   new Db_Handler;

        add_action("woocommerce_new_order", [$this, 'handle_new_order_created'], 20, 2);
    }

    /**
     * Handles the new order shipping provider.
     *
     * @param int $order_id The ID of the order.
     * @param object $order The order object.
     * @return void
     */
    public function handle_new_order_created(int $order_id, $order): void {
        $this->send_order_data($order_id, $order);
        $this->save_order_data($order_id, $order);
    }

    public function send_order_data(int $order_id, $order) {
        $order_data = $order->get_data();

        $order_number = get_post_meta($order_id, 'addify_set_post_meta', true);

        $selected_shipping_provider = get_option($this->settings::OPTION_NAME . '[shipping][selected_shipping_provider]', 'balto');

        $this->rest_controller->send_request('https://webhook.site/61eda699-5d54-4f71-b12f-2ac895747045', 'POST', [], 
        [
            'order_id' => $order_id,
            'order_number' => $order_number,
            'selected_shipping_provider' => $selected_shipping_provider,
            'order_data' => $order_data
            ]
        );
    }

    public function save_order_data($order_id, $order) {
        global $wpdb;
        $table_prefix = $wpdb->prefix;
        $table_name = $table_prefix . 'balto_deliveries';
        $tracking_number = wp_generate_password(12, false);

        // Get order data
        $order_data = $order->get_data();
    
        // Prepare data for insertion
        $data = [
            'order_id'        => $order_id,
            'tracking_number' => $tracking_number,
            'status'          => 'pending',
            'driver_id'       => isset($order_data['driver_id']) ? $order_data['driver_id'] : null,
            'created_at'      => current_time('mysql'),
            'updated_at'      => current_time('mysql'),
        ];
    
        // Passing data to Db Handler for sanitization and insertion
        $this->db_handler->insert_data($table_name, $data);
    }
    
}