<?php
/**
 * Expiry Date Calculation API Endpoint (Version 2)
 * 
 * POST /api/calculate_expiry_date.php
 * 
 * Calculates expiry date for a product based on:
 * - Product shelf life settings (from production_settings or products table)
 * - Production date (defaults to today)
 * 
 * Supports flexible time units: days, months, years
 */

header('Content-Type: application/json');

// Security and access control
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Only POST requests allowed'
    ]);
    exit;
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Not authenticated'
    ]);
    exit;
}

// Check user role (admin or production)
$allowed_roles = ['admin', 'production'];
if (!in_array($_SESSION['role'] ?? '', $allowed_roles)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Permission denied: admin or production role required'
    ]);
    exit;
}

require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../includes/expiry_service_v2.php';

// Get parameters
$product_id = isset($_POST['product_id']) ? trim($_POST['product_id']) : '';
$production_date = isset($_POST['production_date']) ? trim($_POST['production_date']) : '';

// Validate product_id parameter
if (!$product_id || !is_numeric($product_id)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'product_id is required and must be a numeric value'
    ]);
    exit;
}

// Compute expiry date using service function
$result = computeExpiryForBatch($conn, $product_id, $production_date);

// Return JSON response
http_response_code($result['success'] ? 200 : 400);
echo json_encode($result);

$conn->close();
?>
