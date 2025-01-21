<?php
declare(strict_types=1);

namespace Balto_Delivery\Includes\Admin;

use Balto_Delivery\Includes\Db\Db_Handler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dashboard Page handler for Balto Delivery
 *
 * This class handles the dashboard page for the Balto Delivery plugin.
 * It provides functionality for rendering the dashboard page.
 *
 * @package Balto_Delivery_for_woocommerce
 * @subpackage Balto_Delivery_for_woocommerce/Admin
 *
 * @since 1.0.0
 */
class Dashboard_Page
{
    /**
     * Instance of the Db_Handler class
     *
     * @var Db_Handler
     */
    private static $db_handler;
	/**
	 * Instance of this class
	 *
	 * @var Dashboard_Page
	 */
	private static $instance = null;

	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

    private function __construct() {
        self::$db_handler = new Db_Handler;
    }

    /**
	 * Prevent cloning
	 */
	private function __clone() {}

	/**
	 * Prevent unserializing
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton' );
	}
	public function render_dashboard_page() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'balto_deliveries';
	
		$data = self::$db_handler->select_data($table_name);
		?>
		<div class="wrap">
			<h1><?php echo esc_html__('Balto Delivery Dashboard', 'balto-delivery'); ?></h1>
			<?php if (!empty($data)) { ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php echo esc_html__('Id', 'balto-delivery'); ?></th>
							<th><?php echo esc_html__('Order Id', 'balto-delivery'); ?></th>
							<th><?php echo esc_html__('Tracking Number', 'balto-delivery'); ?></th>
							<th><?php echo esc_html__('Status', 'balto-delivery'); ?></th>
							<th><?php echo esc_html__('Shipping Provider', 'balto-delivery'); ?></th>
							<th><?php echo esc_html__('Created At', 'balto-delivery'); ?></th>
							<th><?php echo esc_html__('Updated At', 'balto-delivery'); ?></th>
							<th><?php echo esc_html__('Actions', 'balto-delivery'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($data as $row) { ?>
							<tr>
								<td><?php echo esc_html($row->id); ?></td>
								<td><?php echo esc_html($row->order_id); ?></td>
								<td><?php echo esc_html($row->tracking_number); ?></td>
								<td><?php echo esc_html($row->status); ?></td>
								<td><?php echo esc_html($row->shipping_provider); ?></td>
								<td><?php echo esc_html($row->created_at); ?></td>
								<td><?php echo esc_html($row->updated_at); ?></td>
							</tr>
						<?php } ?>
					</tbody>
				</table>
			<?php } else { ?>
				<p><?php echo esc_html__('No data available.', 'balto-delivery'); ?></p>
			<?php } ?>
		</div>
		<?php
	}
	
}