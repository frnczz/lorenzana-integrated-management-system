<?php
/**
 * Expiry Date Calculation Service (Version 2)
 * 
 * Enhanced service with support for:
 * - Product-specific shelf life settings (production_settings table)
 * - Flexible time units: days, months, years
 * - Backward compatibility with legacy shelf_life_days
 * - Accurate calendar date arithmetic (handles leap years, week lengths, month boundaries)
 */

if (!function_exists('calculateExpiryDate')) {
    /**
     * Calculate expiry date by adding time interval to production date
     * 
     * Uses PHP DateTime for accurate calendar calculations
     * Properly handles leap years, month boundaries, and year transitions
     * 
     * @param string|DateTime $production_date Production date (YYYY-MM-DD) or DateTime object
     * @param int $value Time value to add
     * @param string $unit Time unit: 'days', 'months', or 'years'
     * @return string Expiry date in YYYY-MM-DD format
     * @throws Exception if parameters are invalid
     */
    function calculateExpiryDate($production_date, $value = 365, $unit = 'days') {
        // Validate and normalize inputs
        if (!is_numeric($value) || $value < 0) {
            throw new Exception("Time value must be a non-negative number. Received: " . var_export($value, true));
        }
        
        $value = (int)$value;
        $unit = strtolower(trim($unit));
        
        // Validate unit
        if (!in_array($unit, ['days', 'months', 'years'])) {
            throw new Exception("Time unit must be 'days', 'months', or 'years'. Received: " . $unit);
        }
        
        // Handle production_date
        if ($production_date instanceof DateTime) {
            $date = clone $production_date;
        } elseif (is_string($production_date) && !empty($production_date)) {
            // Validate date format YYYY-MM-DD
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($production_date))) {
                throw new Exception("Production date must be in YYYY-MM-DD format. Received: " . $production_date);
            }
            $date = DateTime::createFromFormat('Y-m-d', $production_date);
            if (!$date) {
                throw new Exception("Invalid production date: " . $production_date);
            }
        } else {
            // Default to today if not provided
            $date = new DateTime();
        }
        
        // Create and add DateInterval
        // P = Period, T = Time
        // Examples: P365D (365 days), P24M (24 months), P3Y (3 years)
        switch ($unit) {
            case 'days':
                $interval = new DateInterval('P' . $value . 'D');
                break;
            case 'months':
                $interval = new DateInterval('P' . $value . 'M');
                break;
            case 'years':
                $interval = new DateInterval('P' . $value . 'Y');
                break;
        }
        
        $date->add($interval);
        
        return $date->format('Y-m-d');
    }
}

if (!function_exists('getProductShelfLife')) {
    /**
     * Retrieve product shelf life settings
     * 
     * Tries production_settings table first (product-specific rules)
     * Falls back to products.shelf_life_days (legacy support)
     * 
     * @param mysqli $conn Database connection
     * @param int $product_id Product ID
     * @return array|null Array with keys: [value (int), unit (string), source (string)]
     *                   Example: ['value' => 24, 'unit' => 'months', 'source' => 'production_settings']
     *                   Returns null if product not found or not configured
     */
    function getProductShelfLife($conn, $product_id) {
        $product_id = (int)$product_id;
        
        if ($product_id <= 0) {
            return null;
        }
        
        // Try production_settings first (preferred - new system)
        // This table uses a key-value store pattern: setting_key and setting_value
        $query = "SELECT setting_key, setting_value FROM production_settings WHERE product_id = ? ORDER BY setting_key";
        $stmt = $conn->prepare($query);
        
        if ($stmt) {
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $settings = [];
            while ($row = $result->fetch_assoc()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            $stmt->close();
            
            // Check if we have expiry settings (either expiry_value/expiry_unit or expiry_days)
            if (isset($settings['expiry_value']) && isset($settings['expiry_unit'])) {
                return [
                    'value' => (int)$settings['expiry_value'],
                    'unit' => strtolower(trim($settings['expiry_unit'])),
                    'source' => 'production_settings'
                ];
            } elseif (isset($settings['expiry_days'])) {
                return [
                    'value' => (int)$settings['expiry_days'],
                    'unit' => 'days',
                    'source' => 'production_settings'
                ];
            }
        }
        
        // Fallback to products.shelf_life_days (legacy support)
        $query = "SELECT shelf_life_days FROM products WHERE product_id = ?";
        $stmt = $conn->prepare($query);
        
        if ($stmt) {
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $row = $result->fetch_assoc()) {
                $days = (int)$row['shelf_life_days'];
                $stmt->close();
                
                if ($days > 0) {
                    return [
                        'value' => $days,
                        'unit' => 'days',
                        'source' => 'products_legacy'
                    ];
                }
            }
            $stmt->close();
        }
        
        return null;
    }
}

if (!function_exists('computeExpiryForBatch')) {
    /**
     * Complete expiry calculation with full validation
     * 
     * Fetches product shelf life settings and computes expiry date
     * with comprehensive error handling and detailed response
     * 
     * @param mysqli $conn Database connection
     * @param int $product_id Product ID
     * @param string|null $production_date Production date (YYYY-MM-DD), defaults to today
     * @return array Response array with keys:
     *         - success (bool) - whether calculation succeeded
     *         - expiry_date (string|null) - calculated expiry date in YYYY-MM-DD format
     *         - shelf_life_value (int|null) - shelf life value (number)
     *         - shelf_life_unit (string|null) - shelf life unit (days/months/years)
     *         - shelf_life_source (string|null) - where setting came from (production_settings or products_legacy)
     *         - production_date (string) - normalized production date
     *         - error (string|null) - error message if not successful
     */
    function computeExpiryForBatch($conn, $product_id, $production_date = null) {
        try {
            // Validate product ID
            $product_id = (int)$product_id;
            if ($product_id <= 0) {
                return [
                    'success' => false,
                    'expiry_date' => null,
                    'shelf_life_value' => null,
                    'shelf_life_unit' => null,
                    'shelf_life_source' => null,
                    'production_date' => $production_date ?? date('Y-m-d'),
                    'error' => 'Invalid product ID (must be positive integer)'
                ];
            }
            
            // Normalize production date
            if (!$production_date) {
                $production_date = date('Y-m-d');
            } else {
                $production_date = trim($production_date);
                
                // Validate date format and convert to DateTime
                $check_date = DateTime::createFromFormat('Y-m-d', $production_date);
                if (!$check_date) {
                    return [
                        'success' => false,
                        'expiry_date' => null,
                        'shelf_life_value' => null,
                        'shelf_life_unit' => null,
                        'shelf_life_source' => null,
                        'production_date' => $production_date,
                        'error' => 'Production date must be in YYYY-MM-DD format (provided: ' . $production_date . ')'
                    ];
                }
                $production_date = $check_date->format('Y-m-d');
            }
            
            // Get shelf life settings from database
            $shelf_life_config = getProductShelfLife($conn, $product_id);
            
            if ($shelf_life_config === null) {
                return [
                    'success' => false,
                    'expiry_date' => null,
                    'shelf_life_value' => null,
                    'shelf_life_unit' => null,
                    'shelf_life_source' => null,
                    'production_date' => $production_date,
                    'error' => 'Product not found or shelf life not configured in database'
                ];
            }
            
            $shelf_life_value = $shelf_life_config['value'];
            $shelf_life_unit = $shelf_life_config['unit'];
            
            // Validate shelf life value
            if ($shelf_life_value < 0) {
                return [
                    'success' => false,
                    'expiry_date' => null,
                    'shelf_life_value' => $shelf_life_value,
                    'shelf_life_unit' => $shelf_life_unit,
                    'shelf_life_source' => $shelf_life_config['source'],
                    'production_date' => $production_date,
                    'error' => 'Shelf life value must be non-negative (provided: ' . $shelf_life_value . ')'
                ];
            }
            
            // Calculate expiry date
            $expiry_date = calculateExpiryDate($production_date, $shelf_life_value, $shelf_life_unit);
            
            return [
                'success' => true,
                'expiry_date' => $expiry_date,
                'shelf_life_value' => $shelf_life_value,
                'shelf_life_unit' => $shelf_life_unit,
                'shelf_life_source' => $shelf_life_config['source'],
                'production_date' => $production_date,
                'error' => null
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'expiry_date' => null,
                'shelf_life_value' => null,
                'shelf_life_unit' => null,
                'shelf_life_source' => null,
                'production_date' => $production_date ?? date('Y-m-d'),
                'error' => 'Exception: ' . $e->getMessage()
            ];
        }
    }
}

?>
