<?php
declare(strict_types=1);

namespace Balto_Delivery\Includes\Services;

if(!defined('ABSPATH')) exit;

class Tracking_Service
{
    /**
     * Instance of this class
     * @var Tracking_Service
     */
    private static $instance = null;

    /**
     * Get class instance | Singleton pattern
     * @return Tracking_Service
     */
    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {}

}