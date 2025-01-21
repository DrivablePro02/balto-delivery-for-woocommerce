<?php
declare(strict_types=1);

namespace Balto_Delivery\Includes\Db;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Balto_Delivery\Includes\Helpers\Sanitizer;

/**
 *
 *
 *
 * @package Balto_Delivery_for_woocommerce
 * @subpackage Balto_Delivery_for_woocommerce/Db
 *
 * @since 1.0.0
 * @author Yahya Eddaqqaq
 */
class Db_Handler {
    private $wpdb;
    private $sanitizer;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->sanitizer = new Sanitizer(); 
    }

    // Insert data into a table
    public function insert_data($table, $data) {
        $sanitized_data = $this->sanitizer->sanitize_data($data);
        $this->wpdb->insert($table, $sanitized_data);
    }

    // Update data in a table
    public function update_data($table, $data, $where) {
        $sanitized_data = $this->sanitizer->sanitize_data($data);
        $sanitized_where = $this->sanitizer->sanitize_data($where);
        $this->wpdb->update($table, $sanitized_data, $sanitized_where);
    }

    // Delete data from a table
    public function delete_data($table, $where) {
        $sanitized_where = $this->sanitizer->sanitize_data($where);
        $this->wpdb->delete($table, $sanitized_where);
    }

    // Select data from a table
    public function select_data($table, $columns = '*', $where = null) {
        $where_clause = $where ? 'WHERE ' . $this->sanitizer->sanitize_data($where) : '';
        return $this->wpdb->get_results("SELECT $columns FROM $table $where_clause");
    }
}

?>
