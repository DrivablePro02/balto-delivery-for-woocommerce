<?php

declare(strict_types=1);

namespace Balto_Delivery\Includes\Api;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * API Key Manager for Balto Delivery
 *
 * Handles secure storage and retrieval of API keys using encryption.
 * Prevents unauthorized access from other plugins and systems.
 *
 * @package    Balto_Delivery_for_woocommerce
 * @subpackage Balto_Delivery_for_woocommerce/Api
 * 
 * @since 1.0.0
 * @author Yahya Eddaqqaq
 */
class Api_Key_Manager {
    
    /**
     * Encryption key
     *
     * @var string
     */
    private string $encryption_key;

    /**
     * Option name for the API key
     *
     * @var string
     */
    private const OPTION_NAME = 'balto_delivery_api_key';

    /**
     * Encryption algorithm
     *
     * @var string
     */
    private const ENCRYPTION_ALGO = 'AES-256-CBC';

    /**
     * Constructor to initialize the encryption key
     */
    public function __construct() {
        $this->encryption_key = $this->generate_encryption_key();
    }

    /**
     * Generate a secure encryption key
     *
     * @return string
     */
    private function generate_encryption_key(): string {
        return hash('sha256', 
            AUTH_SALT . 
            BALTO_DELIVERY_FILE_PATH . 
            wp_salt('auth') . 
            NONCE_SALT
        );
    }

    /**
     * Store the API key securely
     *
     * @param string $key The API key to store.
     * @return bool True if successful, false otherwise.
     * @throws \Exception If OpenSSL extension is not available or encryption fails.
     */
    public function store_api_key(string $key): bool {
        if (!extension_loaded('openssl')) {
            throw new \Exception('OpenSSL extension is required for secure API key storage.');
        }

        // Generate a secure IV
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::ENCRYPTION_ALGO));
        if ($iv === false) {
            throw new \Exception('Failed to generate initialization vector.');
        }

        // Encrypt the API key
        $encrypted = openssl_encrypt(
            $key,
            self::ENCRYPTION_ALGO,
            $this->encryption_key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($encrypted === false) {
            throw new \Exception('Failed to encrypt API key.');
        }

        // Prepare data for storage
        $data = array(
            'key' => base64_encode($encrypted),
            'iv'  => base64_encode($iv),
            'algo' => self::ENCRYPTION_ALGO,
            'version' => BALTO_DELIVERY_VERSION
        );

        // Store in WordPress options
        $result = update_option(self::OPTION_NAME, $data);
        if ($result === false) {
            error_log('Failed to store encrypted API key in WordPress options.');
            return false;
        }

        return true;
    }

    /**
     * Retrieve the stored API key
     *
     * @return string|false The decrypted API key or false on failure.
     * @throws \Exception If OpenSSL extension is not available or decryption fails.
     */
    public function get_api_key(): string|false {
        if (!extension_loaded('openssl')) {
            throw new \Exception('OpenSSL extension is required for secure API key retrieval.');
        }

        $data = get_option(self::OPTION_NAME);
        if (!is_array($data) || !isset($data['key']) || !isset($data['iv'])) {
            return false;
        }

        // Verify encryption algorithm
        if (!isset($data['algo']) || $data['algo'] !== self::ENCRYPTION_ALGO) {
            error_log('Invalid encryption algorithm detected in stored API key.');
            return false;
        }

        // Decode the stored data
        $encrypted = base64_decode($data['key']);
        $iv = base64_decode($data['iv']);

        if ($encrypted === false || $iv === false) {
            error_log('Failed to decode stored API key data.');
            return false;
        }

        // Decrypt the API key
        $decrypted = openssl_decrypt(
            $encrypted,
            self::ENCRYPTION_ALGO,
            $this->encryption_key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($decrypted === false) {
            error_log('Failed to decrypt API key.');
            return false;
        }

        return $decrypted;
    }

    /**
     * Delete the stored API key
     *
     * @return bool True if successful, false otherwise.
     */
    public function delete_api_key(): bool {
        return delete_option(self::OPTION_NAME);
    }

    /**
     * Check if an API key is stored
     *
     * @return bool True if an API key exists, false otherwise.
     */
    public function has_api_key(): bool {
        $data = get_option(self::OPTION_NAME);
        return is_array($data) && isset($data['key']) && isset($data['iv']);
    }
}