<?php
declare(strict_types=1);

namespace Balto_Delivery\Includes\Helpers;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Sanitizer class for handling data sanitization in the Balto Delivery plugin.
 * 
 * This class provides methods to sanitize various types of data including strings,
 * integers, emails, phone numbers, dates, json and URLs to prevent security vulnerabilities.
 * It also includes validation and error handling capabilities.
 */
class Sanitizer {
    /**
     * Special fields with their corresponding data types and validation rules.
     * 
     * @var array<string, array> Mapping of field names to their validation rules
     */
    private const SPECIAL_FIELDS = [
        'shipping_provider' => [
            'type' => 'string',
            'required' => true,
            'max_length' => 255
        ],
        'status' => [
            'type' => 'string',
            'required' => true,
            'allowed_values' => ['pending', 'processing', 'completed', 'cancelled']
        ],
        'tracking_number' => [
            'type' => 'string',
            'required' => true,
            'max_length' => 100,
            'pattern' => '/^[A-Z0-9-]+$/'
        ],
        'order_id' => [
            'type' => 'int',
            'required' => true,
            'min' => 1
        ],
        'email' => [
            'type' => 'email',
            'required' => true
        ],
        'phone' => [
            'type' => 'phone',
            'required' => true,
            'pattern' => '/^\+?[0-9-() ]+$/'
        ],
        'url' => [
            'type' => 'url',
            'required' => false
        ]
    ];

    /**
     * Collection of validation errors
     *
     * @var array<string, string>
     */
    private array $errors = [];

    /**
     * Collection of sanitization results
     *
     * @var array<string, mixed>
     */
    private array $results = [];

    /**
     * Main method to sanitize input data based on its type.
     * 
     * @param mixed $data The data to sanitize
     * @param string $field_name Optional field name to determine special handling
     * @return mixed Sanitized data
     * @throws \InvalidArgumentException If data is invalid
     */
    public function sanitize_data($data, string $field_name = ''): mixed {
        $this->errors = [];
        $this->results = [];

        if (is_array($data)) {
            return $this->sanitize_array($data);
        }

        if ($field_name && isset(self::SPECIAL_FIELDS[$field_name])) {
            $rules = self::SPECIAL_FIELDS[$field_name];
            if (!$this->validate_field($data, $field_name, $rules)) {
                throw new \InvalidArgumentException(
                    "Invalid data for field {$field_name}: " . implode(', ', $this->errors)
                );
            }
            return $this->handle_special_field($data, $rules['type']);
        }

        return $this->sanitize_by_type($data);
    }

    /**
     * Validates a field against its rules
     *
     * @param mixed $data The data to validate
     * @param string $field_name The field name
     * @param array $rules The validation rules
     * @return bool Whether the data is valid
     */
    private function validate_field($data, string $field_name, array $rules): bool {
        // Check required
        if ($rules['required'] && empty($data)) {
            $this->errors[$field_name] = "{$field_name} is required";
            return false;
        }

        // Check max length
        if (isset($rules['max_length']) && strlen((string)$data) > $rules['max_length']) {
            $this->errors[$field_name] = "{$field_name} exceeds maximum length of {$rules['max_length']}";
            return false;
        }

        // Check allowed values
        if (isset($rules['allowed_values']) && !in_array($data, $rules['allowed_values'])) {
            $this->errors[$field_name] = "{$field_name} must be one of: " . implode(', ', $rules['allowed_values']);
            return false;
        }

        // Check pattern
        if (isset($rules['pattern']) && !preg_match($rules['pattern'], (string)$data)) {
            $this->errors[$field_name] = "{$field_name} does not match required pattern";
            return false;
        }

        // Check minimum value for numbers
        if (isset($rules['min']) && $data < $rules['min']) {
            $this->errors[$field_name] = "{$field_name} must be greater than or equal to {$rules['min']}";
            return false;
        }

        return true;
    }

    /**
     * Prevents XSS attacks by escaping output
     *
     * @param mixed $data The data to escape
     * @return string|array<string|int, string> Escaped data
     */
    public function prevent_xss($data): string|array {
        if (is_array($data)) {
            return array_map([$this, 'prevent_xss'], $data);
        }
        return htmlspecialchars((string)$data, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Prevents SQL injection by escaping SQL strings
     *
     * @param mixed $data The data to escape
     * @return string|array<string|int, string> Escaped data
     */
    public function prevent_sql_injection($data): string|array {
        if (is_array($data)) {
            return array_map([$this, 'prevent_sql_injection'], $data);
        }
        return addslashes((string)$data);
    }

    /**
     * Validates CSRF token
     *
     * @param string $token The token to validate
     * @return bool Whether the token is valid
     */
    public function validate_csrf_token(string $token): bool {
        return wp_verify_nonce($token, 'balto_delivery_nonce');
    }

    /**
     * Gets validation errors
     *
     * @return array<string, string>
     */
    public function get_errors(): array {
        return $this->errors;
    }

    /**
     * Gets sanitization results
     *
     * @return array<string, mixed>
     */
    public function get_results(): array {
        return $this->results;
    }

    /**
     * Checks if there are any validation errors
     *
     * @return bool
     */
    public function has_errors(): bool {
        return !empty($this->errors);
    }

    /**
     * Sanitizes an array by iterating through each key-value pair.
     * 
     * @param array $data The array to sanitize
     * @return array Sanitized array
     */
    private function sanitize_array(array $data): array {
        $sanitized = [];
        foreach ($data as $key => $value) {
            // Skip timestamp fields as they're handled by MySQL
            if ($key === 'created_at' || $key === 'updated_at') {
                continue;
            }
            $sanitized[$key] = $this->sanitize_data($value, $key);
        }
        return $sanitized;
    }

    /**
     * Handles sanitization for special fields based on their designated type.
     * 
     * @param mixed $data The data to sanitize
     * @param string $type The type of the field
     * @return string|int Sanitized value
     */
    private function handle_special_field($data, string $type):string|int {
        switch ($type) {
            case 'string':
                return $this->sanitize_string($data);
            case 'int':
                return $this->sanitize_int($data);
            case 'email':
                return $this->sanitize_email($data);
            case 'phone':
                return $this->sanitize_phone($data);
            case 'url':
                return $this->sanitize_url($data);
            default:
                return $this->sanitize_by_type($data);
        }
    }

    /**
     * Determines the type of data and applies appropriate sanitization.
     * 
     * @param mixed $data The data to sanitize
     * @return int|null|float|bool|string Sanitized value
     */
    private function sanitize_by_type($data):int|null|float|bool|string {
        if (is_null($data)) {
            return null;
        }

        if (is_int($data)) {
            return $this->sanitize_int($data);
        }

        if (is_float($data)) {
            return $this->sanitize_float($data);
        }

        if (is_bool($data)) {
            return $this->sanitize_bool($data);
        }

        if (is_string($data)) {
            if (filter_var($data, FILTER_VALIDATE_EMAIL)) {
                return $this->sanitize_email($data);
            }
            if (filter_var($data, FILTER_VALIDATE_URL)) {
                return $this->sanitize_url($data);
            }
            return $this->sanitize_string($data);
        }

        return $data;
    }

    /**
     * Escapes output data for safe display in HTML.
     * 
     * @param mixed $data The data to escape
     * @return object|string Escaped data
     */
    public function escape_output($data):object|string {
        if(is_array($data)) {
            array_map([$this, 'escape_output'], $data);
        } else if(is_object($data)) {
            foreach ($data as $key => $value) {
                $data->$key = esc_html($value);
            }
            return $data;
        }
        return esc_html($data);
    }

    /**
     * Sanitizes an integer value.
     * 
     * @param mixed $data The data to sanitize
     * @return int Sanitized integer
     */
    private function sanitize_int($data): int {
        return (int) $data;
    }

    /**
     * Sanitizes a string value.
     * 
     * @param mixed $data The data to sanitize
     * @return string Sanitized string
     */
    private function sanitize_string($data): string {
        if (is_array($data) || is_object($data)) {
            return '';
        }
        return sanitize_text_field((string) $data);
    }

    /**
     * Sanitizes a boolean value.
     * 
     * @param mixed $data The data to sanitize
     * @return bool Sanitized boolean
     */
    private function sanitize_bool($data): bool {
        return (bool) filter_var($data, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
    }

    /**
     * Sanitizes a float value.
     * 
     * @param mixed $data The data to sanitize
     * @return float Sanitized float
     */
    private function sanitize_float($data): float {
        return (float) $data;
    }

    /**
     * Sanitizes a URL value.
     * 
     * @param mixed $data The URL to sanitize
     * @return string Sanitized URL
     */
    public function sanitize_url($data): string {
        return esc_url_raw((string) $data);
    }

    /**
     * Sanitizes an email address.
     * 
     * @param mixed $data The email to sanitize
     * @return string Sanitized email
     */
    public function sanitize_email($data): string {
        return sanitize_email((string) $data);
    }

    /**
     * Sanitizes a phone number by removing non-numeric and special characters.
     * 
     * @param mixed $data The phone number to sanitize
     * @return string Sanitized phone number
     */
    public function sanitize_phone($data): string {
        $phone = (string) $data;
        return preg_replace('/[^0-9+()\-]/', '', $phone);
    }

    /**
     * Sanitizes a date string and converts it to a DateTime object.
     * 
     * @param mixed $data The date to sanitize
     * @return \DateTime|null DateTime object or null
     */
    public function sanitize_date($data): ?\DateTime {
        $date = sanitize_text_field($data);
        $date_obj = \DateTime::createFromFormat('Y-m-d', $date);
        if ($date_obj) {
            return $date_obj;
        }
        return null;
    }

    /**
     * Sanitizes JSON data.
     * 
     * @param mixed $row_json_date The raw JSON data
     * @return object|string Sanitized JSON string
     */
    public function sanitize_json($row_json_date):object|string{
        $json_date = sanitize_text_field($row_json_date);
        $data = json_decode($json_date);

        if(is_array($data)){
            foreach($data as $values) {
                sanitize_text_field($values);
            }
        }
        return json_encode($data);
    }
}