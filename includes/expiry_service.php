<?php
/**
 * Expiry Date Calculation Helper Functions
 * 
 * Handles automatic computation of expiry dates based on product shelf life
 * Accurately handles leap years and calendar dates
 */

if (!function_exists('calculateExpiryDate')) {
    /**
     * Calculate expiry date based on production date and shelf life days
     * 
     * @param string|DateTime $production_date - Format: YYYY-MM-DD or DateTime object
     * @param int $shelf_life_days - Number of days the product is shelf-stable
     * @return string - Expiry date in YYYY-MM-DD format
     * @throws Exception if parameters are invalid
     */
    function calculateExpiryDate($production_date, $shelf_life_days) {
        // Validate shelf_life_days
        if (!is_numeric($shelf_life_days) || $shelf_life_days < 0) {
            throw new Exception("Shelf life days must be a non-negative number. Received: " . var_export($shelf_life_days, true));
        }
        
        $shelf_life_days = (int)$shelf_life_days;
        
        // Handle production_date
        if ($production_date instanceof DateTime) {
            $date = $production_date;
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
        
        // Add shelf_life_days to the production date
        // PHP's DateTime::add() correctly handles leap years and month lengths
        $date->add(new DateInterval('P' . $shelf_life_days . 'D'));
        
        return $date->format('Y-m-d');
    }
}

if (!function_exists('getProductShelfLife')) {
    /**
     * Retrieve shelf life days from a product
     * 
     * @param mysqli $conn - Database connection
     * @param int $product_id - Product ID
     * @return int|null - Shelf life days, or null if product not found
     */
    function getProductShelfLife($conn, $product_id) {
        $product_id = (int)$product_id;
        
        $stmt = $conn->prepare("SELECT shelf_life_days FROM products WHERE product_id = ?");
        if (!$stmt) {
            error_log("Database prepare error: " . $conn->error);
            return null;
        }
        
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row ? (int)$row['shelf_life_days'] : null;
    }
}

if (!function_exists('computeExpiryForBatch')) {
    /**
     * Compute expiry date for a production batch
     * Complete validation and error handling
     * 
     * @param mysqli $conn - Database connection
     * @param int $product_id - Product ID
     * @param string|null $production_date - Production date (YYYY-MM-DD), null for today
     * @return array - ['success' => bool, 'expiry_date' => string|null, 'error' => string|null]
     */
    function computeExpiryForBatch($conn, $product_id, $production_date = null) {
        try {
            // Sanitize product_id
            $product_id = (int)$product_id;
            if ($product_id <= 0) {
                return [
                    'success' => false,
                    'expiry_date' => null,
                    'error' => 'Invalid product ID'
                ];
            }
            
            // Get shelf life days from product
            $shelf_life_days = getProductShelfLife($conn, $product_id);
            
            if ($shelf_life_days === null) {
                return [
                    'success' => false,
                    'expiry_date' => null,
                    'error' => 'Product not found or shelf_life_days not defined'
                ];
            }
            
            // Use current date if production_date not provided
            if (empty($production_date)) {
                $production_date = date('Y-m-d');
            } else {
                $production_date = trim($production_date);
            }
            
            // Calculate expiry date
            $expiry_date = calculateExpiryDate($production_date, $shelf_life_days);
            
            return [
                'success' => true,
                'expiry_date' => $expiry_date,
                'error' => null,
                'shelf_life_days' => $shelf_life_days,
                'production_date' => $production_date
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'expiry_date' => null,
                'error' => $e->getMessage()
            ];
        }
    }
}

?>
