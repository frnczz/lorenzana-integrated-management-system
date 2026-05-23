<?php
/**
 * API Endpoint: Calculate Expiry Date
 * 
 * Accepts POST request with product_id and optional production_date
 * Returns calculated expiry date in JSON format
 * 
 * Usage:
 * POST /api/calculate_expiry_date.php
 * Parameters:
 *   - product_id (int, required)
 *   - production_date (string, optional, format: YYYY-MM-DD)
 * 
 * Response:
 * {
 *   "success": true/false,
 *   "expiry_date": "YYYY-MM-DD",
 *   "production_date": "YYYY-MM-DD",
 *   "shelf_life_days": 365,
 *   "error": null or error message
 * }
 */

header('Content-Type: application/json; charset=utf-8');

session_start();
include "../db_connect.php";
require_once __DIR__ . '/../includes/expiry_service.php';

// Verify user is logged in and has permission
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'production')) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized access'
    ]);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed. Use POST.'
    ]);
    exit;
}

// Get parameters
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$production_date = isset($_POST['production_date']) ? trim($_POST['production_date']) : null;

// Validate product_id
if ($product_id <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Product ID is required and must be a positive integer'
    ]);
    exit;
}

// Compute expiry date
$result = computeExpiryForBatch($conn, $product_id, $production_date);

// Return result with appropriate HTTP status
if ($result['success']) {
    http_response_code(200);
} else {
    http_response_code(400);
}

echo json_encode($result);
exit;

?>
