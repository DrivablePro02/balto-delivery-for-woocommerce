<?php
declare(strict_types=1);

namespace Balto_Delivery\Includes\Db;

if (!defined('ABSPATH')) {
    exit;
}

use Balto_Delivery\Includes\Helpers\Sanitizer;

/**
 * Database Handler for Balto Delivery
 * 
 * Handles all database operations with proper security measures.
 * 
 * @package    Balto_Delivery_for_woocommerce
 * @subpackage Balto_Delivery_for_woocommerce/Db
 * 
 * @since 1.0.0
 * @author Yahya Eddaqqaq
 */
class Db_Handler {
    /**
     * WordPress database instance
     *
     * @var \wpdb
     */
    private \wpdb $wpdb;

    /**
     * Sanitizer instance
     *
     * @var Sanitizer
     */
    private Sanitizer $sanitizer;

    /**
     * Table name
     *
     * @var string
     */
    private string $table_name = 'balto_deliveries';

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->sanitizer = new Sanitizer();
    }

    /**
     * Get table prefix with proper escaping
     *
     * @return string
     */
    private function get_table_prefix(): string {
        return is_multisite() ? 
            $this->wpdb->get_blog_prefix(get_current_blog_id()) : 
            $this->wpdb->prefix;
    }

    /**
     * Get full table name with proper escaping
     *
     * @return string
     */
    private function get_full_table_name(): string {
        return $this->get_table_prefix() . $this->table_name;
    }

    /**
     * Verify user has required capabilities
     *
     * @return bool
     */
    private function verify_capabilities(): bool {
        return current_user_can('manage_woocommerce');
    }

    /**
     * Get a single delivery record
     *
     * @param int $id Delivery ID
     * @return object|false Delivery object or false if not found
     * @throws \Exception If user lacks capabilities
     */
    public function get_delivery(int $id): object|false {
        if (!$this->verify_capabilities()) {
            throw new \Exception('Insufficient permissions to access delivery data.');
        }

        if ($id <= 0) {
            return false;
        }

        $table = $this->get_full_table_name();
        $result = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id)
        );

        if ($result) {
            // Escape output data
            $result = $this->sanitizer->escape_output($result);
        }

        return $result;
    }

    /**
     * Get multiple delivery records with filtering
     *
     * @param array $args Query arguments
     * @return array Array of delivery objects
     * @throws \Exception If user lacks capabilities
     */
    public function get_deliveries(array $args = []): array {
        if (!$this->verify_capabilities()) {
            throw new \Exception('Insufficient permissions to access delivery data.');
        }

        $table = $this->get_full_table_name();
        $defaults = [
            'status' => '',
            'shipping_provider' => '',
            'date_from' => '',
            'date_to' => '',
            'orderby' => 'id',
            'order' => 'DESC',
            'limit' => 20,
            'offset' => 0,
        ];
    
        $args = wp_parse_args($args, $defaults);
        $where = [];
        $prepare_values = [];
    
        // Validate and sanitize input parameters
        if (!empty($args['status'])) {
            $where[] = 'status = %s';
            $prepare_values[] = sanitize_text_field($args['status']);
        }
    
        if (!empty($args['shipping_provider'])) {
            $where[] = 'shipping_provider = %s';
            $prepare_values[] = sanitize_text_field($args['shipping_provider']);
        }
    
        if (!empty($args['date_from'])) {
            $where[] = 'created_at >= %s';
            $prepare_values[] = sanitize_text_field($args['date_from']);
        }
    
        if (!empty($args['date_to'])) {
            $where[] = 'created_at <= %s';
            $prepare_values[] = sanitize_text_field($args['date_to']);
        }
    
        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // Validate orderby to prevent SQL injection
        $allowed_orderby = ['id', 'created_at', 'updated_at', 'status'];
        $orderby = in_array($args['orderby'], $allowed_orderby) ? $args['orderby'] : 'id';
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
        
        $query = "SELECT * FROM {$table} {$where_clause} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
        
        $prepare_values[] = (int) min($args['limit'], 100); // Cap maximum limit
        $prepare_values[] = (int) max(0, $args['offset']);
    
        $results = $this->wpdb->get_results(
            $this->wpdb->prepare($query, ...$prepare_values)
        );

        // Escape output data
        return array_map([$this->sanitizer, 'escape_output'], $results);
    }
    
    /**
     * Insert a new delivery record
     *
     * @param array $data Delivery data
     * @return bool True on success, false on failure
     * @throws \Exception If user lacks capabilities or data is invalid
     */
    public function insert_delivery(array $data): bool {
        if (!$this->verify_capabilities()) {
            throw new \Exception('Insufficient permissions to create delivery.');
        }

        $table = $this->get_full_table_name();
        $defaults = [
            'order_id' => 0,
            'tracking_number' => '',
            'status' => 'pending',
            'shipping_provider' => '',
            'created_at' => current_time('mysql', true),
            'updated_at' => current_time('mysql', true)
        ];
    
        $data = wp_parse_args($data, $defaults);
    
        // Validate required fields
        if (empty($data['order_id']) || empty($data['tracking_number'])) {
            throw new \Exception('Required fields are missing.');
        }
    
        $sanitized_data = $this->sanitizer->sanitize_data($data);
        $sanitized_data['created_at'] = current_time('mysql', true);
        $sanitized_data['updated_at'] = current_time('mysql', true);

        return (bool) $this->wpdb->insert($table, $sanitized_data);
    }

    /**
     * Update an existing delivery record
     *
     * @param int $id Delivery ID
     * @param array $data Updated data
     * @return bool True on success, false on failure
     * @throws \Exception If user lacks capabilities or data is invalid
     */
    public function update_delivery(int $id, array $data): bool {
        if (!$this->verify_capabilities()) {
            throw new \Exception('Insufficient permissions to update delivery.');
        }

        if ($id <= 0) {
            throw new \Exception('Invalid delivery ID.');
        }

        $table = $this->get_full_table_name();
        $data['updated_at'] = current_time('mysql', true);
        
        $sanitized_data = $this->sanitizer->sanitize_data($data);
        return (bool) $this->wpdb->update($table, $sanitized_data, ['id' => $id]);
    }

    /**
     * Delete a delivery record
     *
     * @param int $id Delivery ID
     * @return bool True on success, false on failure
     * @throws \Exception If user lacks capabilities
     */
    public function delete_delivery(int $id): bool {
        if (!$this->verify_capabilities()) {
            throw new \Exception('Insufficient permissions to delete delivery.');
        }

        if ($id <= 0) {
            return false;
        }

        $table = $this->get_full_table_name();
        return (bool) $this->wpdb->delete($table, ['id' => $id]);
    }

    /**
     * Count deliveries with optional status filter
     *
     * @param string $status Optional status filter
     * @return int Number of deliveries
     * @throws \Exception If user lacks capabilities
     */
    public function count_deliveries(string $status = ''): int {
        if (!$this->verify_capabilities()) {
            throw new \Exception('Insufficient permissions to count deliveries.');
        }

        $table = $this->get_full_table_name();
        
        if ($status) {
            return (int) $this->wpdb->get_var(
                $this->wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE status = %s", 
                    sanitize_text_field($status)
                )
            );
        }
        
        return (int) $this->wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    }
}
