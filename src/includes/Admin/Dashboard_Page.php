<?php
declare(strict_types=1);

namespace Balto_Delivery\Includes\Admin;

use Balto_Delivery\Includes\Db\Db_Handler;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Dashboard Page handler for Balto Delivery
 *
 * This class handles the dashboard page for the Balto Delivery plugin.
 * It provides functionality for rendering the dashboard page.
 *
 * @package    Balto_Delivery_for_woocommerce
 * @subpackage Balto_Delivery_for_woocommerce/Admin
 *
 * @since 1.0.0
 * @author Yahya Eddaqqaq
 */
class Dashboard_Page {
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
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        self::$db_handler = new Db_Handler();
    }

    /**
     * Prevent cloning
     */
    private function __clone() {
    }

    /**
     * Prevent unserializing
     */
    public function __wakeup() {
        throw new \Exception('Cannot unserialize singleton');
    }

    /**
     * Get the deliveries with pagination and filtering
     */
    private function get_filtered_deliveries() {
        // Get current page and filter parameters
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;
        $offset = ($current_page - 1) * $per_page;

        $args = [
            'limit' => $per_page,
            'offset' => $offset,
            'orderby' => isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'id',
            'order' => isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC'
        ];

        // Add status filter if set
        if (!empty($_GET['status'])) {
            $args['status'] = sanitize_text_field($_GET['status']);
        }

        // Add carrier filter if set
        if (!empty($_GET['carrier'])) {
            $args['carrier'] = sanitize_text_field($_GET['carrier']);
        }

        return self::$db_handler->get_deliveries($args);
    }

    /**
     * Render the dashboard page
     */
    public function render_dashboard_page() {
        $deliveries = $this->get_filtered_deliveries();
        $total_deliveries = self::$db_handler->count_deliveries();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Balto Delivery Dashboard', 'balto-delivery'); ?></h1>
            
            <!-- Filters -->
            <div class="tablenav top">
                <form method="get">
                    <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page']); ?>">
                    <select name="status">
                        <option value=""><?php esc_html_e('All Statuses', 'balto-delivery'); ?></option>
                        <option value="pending" <?php selected(isset($_GET['status']) && $_GET['status'] === 'pending'); ?>>
                            <?php esc_html_e('Pending', 'balto-delivery'); ?>
                        </option>
                        <option value="completed" <?php selected(isset($_GET['status']) && $_GET['status'] === 'completed'); ?>>
                            <?php esc_html_e('Completed', 'balto-delivery'); ?>
                        </option>
                    </select>
                    <input type="submit" class="button" value="<?php esc_attr_e('Filter', 'balto-delivery'); ?>">
                </form>
            </div>

            <?php if (!empty($deliveries)) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('ID', 'balto-delivery'); ?></th>
                            <th><?php echo esc_html__('Order ID', 'balto-delivery'); ?></th>
                            <th><?php echo esc_html__('Tracking Number', 'balto-delivery'); ?></th>
                            <th><?php echo esc_html__('Status', 'balto-delivery'); ?></th>
                            <th><?php echo esc_html__('Carrier', 'balto-delivery'); ?></th>
                            <th><?php echo esc_html__('Created At', 'balto-delivery'); ?></th>
                            <th><?php echo esc_html__('Updated At', 'balto-delivery'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deliveries as $delivery) : ?>
                            <tr>
                                <td><?php echo esc_html($delivery->id); ?></td>
                                <td><?php echo esc_html($delivery->order_id); ?></td>
                                <td><?php echo esc_html($delivery->tracking_number); ?></td>
                                <td><?php echo esc_html($delivery->status); ?></td>
                                <td><?php echo esc_html($delivery->shipping_provider); ?></td>
                                <td><?php echo esc_html($delivery->created_at); ?></td>
                                <td><?php echo esc_html($delivery->updated_at); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php
                // Add pagination
                $total_pages = ceil($total_deliveries / 20);
                if ($total_pages > 1) {
                    echo '<div class="tablenav bottom">';
                    echo '<div class="tablenav-pages">';
                    echo paginate_links([
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo;'),
                        'next_text' => __('&raquo;'),
                        'total' => $total_pages,
                        'current' => max(1, get_query_var('paged')),
                    ]);
                    echo '</div>';
                    echo '</div>';
                }
                ?>

            <?php else : ?>
                <p><?php echo esc_html__('No deliveries found.', 'balto-delivery'); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }
}