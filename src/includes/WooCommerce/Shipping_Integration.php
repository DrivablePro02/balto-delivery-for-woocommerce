<?php
declare(strict_types=1);

namespace Balto_Delivery\Includes\WooCommerce;

use WC_Shipping_Method;
use Balto_Delivery\Includes\Core\Settings;

class Shipping_Integration extends WC_Shipping_Method {
    /**
     * WordPress option key for delivery options
     *
     * @var Settings
     */
    private $settings;

    /**
     * Available shipping providers
     *
     * @var array
     */
    private $shipping_providers = [];

    /**
     * Singleton instance
     *
     * @var self
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return self
     */
    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor for Balto Shipping Method
     *
     * @param int $instance_id Shipping method instance ID
     */
    public function __construct($instance_id = 0) {
        parent::__construct($instance_id);

        $this->settings = Settings::get_instance();
        
        $this->id = 'balto_shipping';
        $this->method_title = __('Balto Shipping Methods', 'balto-shipping');
        $this->method_description = __('Manage shipping methods from Balto delivery options', 'balto-shipping');
        
        $this->init();
        $this->register_shipping_methods();
    }

    /**
     * Initialize shipping method settings
     */
    private function init(): void {
        $this->init_form_fields();
        $this->init_settings();
        
        // Load shipping providers
        $this->load_shipping_providers();
        
        // Add action for updating shipping options
        add_action('woocommerce_update_options_shipping_' . $this->id, [$this, 'process_admin_options']);
    }

    /**
     * Register shipping methods with WooCommerce
     */
    private function register_shipping_methods(): void {
        add_filter('woocommerce_shipping_methods', function($methods) {
            $methods['balto_shipping'] = self::class;
            return $methods;
        });
    }

    /**
     * Initialize form fields for admin settings
     */
    public function init_form_fields(): void {
        $this->form_fields = [
            'enabled' => [
                'title'   => __('Enable/Disable', 'balto-shipping'),
                'type'    => 'checkbox',
                'label'   => __('Enable Balto Shipping Methods', 'balto-shipping'),
                'default' => 'yes'
            ],
            'title' => [
                'title'       => __('Method Title', 'balto-shipping'),
                'type'        => 'text',
                'description' => __('Title shown to customers during checkout', 'balto-shipping'),
                'default'     => __('Balto Shipping', 'balto-shipping'),
                'desc_tip'    => true,
            ]
        ];
    }

    /**
     * Load and process shipping providers from options
     */
    private function load_shipping_providers(): void {
        $delivery_options = get_option($this->settings::OPTION_NAME);
        
        if (isset($delivery_options['shipping'])) {
            $shipping_data = $delivery_options['shipping'];
            
            // Remove selected provider key
            unset($shipping_data['selected_shipping_provider']);
            
            // Filter enabled providers
            $this->shipping_providers = array_filter($shipping_data, function($provider) {
                return isset($provider['enabled']) && $provider['enabled'] === true;
            });
        }
    }

    /**
     * Calculate shipping rates
     *
     * @param array $package Shipping package data
     */
    public function calculate_shipping($package = []): void {
        foreach ($this->shipping_providers as $provider_key => $provider) {
            $rate = [
                'id'    => $this->id . '_' . $provider_key,
                'label' => sprintf('%s - %s', $this->title, $provider['name']),
                'cost'  => $this->get_shipping_cost($provider, $package),
            ];

            $this->add_rate($rate);
        }
    }

    /**
     * Calculate shipping cost for a specific provider
     *
     * @param array $provider Provider configuration
     * @param array $package Shipping package
     * @return float Shipping cost
     */
    private function get_shipping_cost(array $provider, array $package): float {
        // Base implementation - override in child classes or via filters
        $base_cost = 0.00;
        
        /**
         * Filter to allow custom shipping cost calculation
         *
         * @param float $base_cost Base shipping cost
         * @param array $provider Shipping provider details
         * @param array $package Shipping package
         */
        return apply_filters('balto_shipping_cost', $base_cost, $provider, $package);
    }

    /**
     * Get all available shipping providers
     *
     * @return array List of shipping providers
     */
    public function get_shipping_providers(): array {
        return $this->shipping_providers;
    }

    /**
     * Get tracking URL for a specific shipping provider
     *
     * @param string $provider Provider key
     * @return string|null Tracking URL
     */
    public function get_tracking_url(string $provider): ?string {
        return $this->shipping_providers[$provider]['tracking_url'] ?? null;
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserializing
     */
    public function __wakeup() {
        throw new \Exception('Cannot unserialize singleton');
    }
}