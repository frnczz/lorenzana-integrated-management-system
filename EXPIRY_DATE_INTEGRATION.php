<?php
/**
 * Updated Production Batch Saving Logic with Expiry Date Calculation
 * 
 * This file shows the key sections that need to be modified in
 * api/save_production_batch.php to include automatic expiry date calculation
 * 
 * Integration Instructions:
 * 1. Add require_once for expiry_service.php at the top
 * 2. Update the batch insert loop as shown below
 */

// ============================================================================
// SECTION 1: Add to the top of save_production_batch.php (after existing includes)
// ============================================================================

require_once __DIR__ . '/../includes/expiry_service.php';

// ============================================================================
// SECTION 2: In the main batch insert loop, BEFORE the INSERT statement
// ============================================================================

foreach ($lines as $line) {
    $product_id = $line['product_id'];
    $quantity = $line['quantity'];
    $fermentation_status = $line['fermentation_status'];
    $status = $line['status'];
    $request_id = $line['request_id'];

    // EXISTING CODE - fermentation processing
    $fermentation_eligible = 1;
    $eq = $conn->query("SELECT COALESCE(fermentation_eligible, 1) AS fe FROM products WHERE product_id = $product_id");
    if ($eq && $er = $eq->fetch_assoc()) $fermentation_eligible = (int)$er['fe'];
    if ($fermentation_eligible === 0) $fermentation_status = 'Not Applicable';

    // EXISTING CODE - generate batch number
    $batch_number = generateReferenceId($conn, 'BAT');
    if (!$batch_number) throw new Exception("Could not generate batch number.");

    // ========================================================================
    // NEW CODE: Compute expiry date based on product shelf life
    // ========================================================================
    $expiry_result = computeExpiryForBatch($conn, $product_id, $production_date);
    
    if (!$expiry_result['success']) {
        throw new Exception("Cannot compute expiry date for product $product_id: " . $expiry_result['error']);
    }
    
    $expiry_date = $expiry_result['expiry_date'];
    // Optional: Log the computation for debugging
    error_log("Batch $batch_number - Prod Date: {$expiry_result['production_date']}, Expiry: {$expiry_date}, Shelf Life: {$expiry_result['shelf_life_days']} days");
    // ========================================================================

    // EXISTING CODE - phase calculation
    $phase = ($status === 'Ready' || $status === 'Completed') ? ($status === 'Completed' ? 'Completed' : 'Output Pending QC') : 'In Progress';

    // ========================================================================
    // UPDATED CODE: INSERT statement with expiry_date
    // ========================================================================
    // Check for optional columns
    $has_phase = ($conn->query("SHOW COLUMNS FROM production_batches LIKE 'phase'")->num_rows > 0);
    $has_request_id = ($conn->query("SHOW COLUMNS FROM production_batches LIKE 'request_id'")->num_rows > 0);
    $has_expiry_date = ($conn->query("SHOW COLUMNS FROM production_batches LIKE 'expiry_date'")->num_rows > 0);

    // Build INSERT based on available columns
    if ($has_phase && $has_request_id && $has_expiry_date) {
        $stmt = $conn->prepare("
            INSERT INTO production_batches 
            (batch_number, product_id, batch_date, quantity, fermentation_status, 
             packaging_status, status, phase, expiry_date, created_by, request_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "sisdsssssi",
            $batch_number,
            $product_id,
            $production_date,
            $quantity,
            $fermentation_status,
            $packaging_status,
            $status,
            $phase,
            $expiry_date,        // NEW: Computed expiry date
            $created_by,
            $request_id
        );
    } elseif ($has_phase && $has_expiry_date) {
        $stmt = $conn->prepare("
            INSERT INTO production_batches 
            (batch_number, product_id, batch_date, quantity, fermentation_status, 
             packaging_status, status, phase, expiry_date, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "sisdssssi",
            $batch_number,
            $product_id,
            $production_date,
            $quantity,
            $fermentation_status,
            $packaging_status,
            $status,
            $phase,
            $expiry_date,        // NEW: Computed expiry date
            $created_by
        );
    } elseif ($has_request_id && $has_expiry_date) {
        $stmt = $conn->prepare("
            INSERT INTO production_batches 
            (batch_number, product_id, batch_date, quantity, fermentation_status, 
             packaging_status, status, expiry_date, created_by, request_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "sisdssssii",
            $batch_number,
            $product_id,
            $production_date,
            $quantity,
            $fermentation_status,
            $packaging_status,
            $status,
            $expiry_date,        // NEW: Computed expiry date
            $created_by,
            $request_id
        );
    } elseif ($has_expiry_date) {
        $stmt = $conn->prepare("
            INSERT INTO production_batches 
            (batch_number, product_id, batch_date, quantity, fermentation_status, 
             packaging_status, status, expiry_date, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "sisdssssi",
            $batch_number,
            $product_id,
            $production_date,
            $quantity,
            $fermentation_status,
            $packaging_status,
            $status,
            $expiry_date,        // NEW: Computed expiry date
            $created_by
        );
    } else {
        // Fallback if expiry_date column doesn't exist (not recommended)
        throw new Exception("expiry_date column not found in production_batches table. Run migration first.");
    }

    if (!$stmt->execute()) {
        throw new Exception("Error saving batch: " . $stmt->error);
    }

    $batch_id = $conn->insert_id;
    $stmt->close();
    
    // ... rest of existing code continues ...
}

/**
 * ============================================================================
 * QUICK REFERENCE: Complete Working Example
 * ============================================================================
 * 
 * Minimal example showing all required pieces:
 */

/*
// At the top of file:
require_once __DIR__ . '/../includes/expiry_service.php';

// In the batch creation loop:
$product_id = $_POST['product_id'];
$production_date = $_POST['production_date'] ?? date('Y-m-d');

// Compute expiry date (with full error handling)
$expiry_result = computeExpiryForBatch($conn, $product_id, $production_date);
if (!$expiry_result['success']) {
    throw new Exception("Expiry calculation failed: " . $expiry_result['error']);
}
$expiry_date = $expiry_result['expiry_date'];

// Insert batch with expiry_date
$stmt = $conn->prepare("
    INSERT INTO production_batches 
    (batch_number, product_id, batch_date, quantity, expiry_date, created_by)
    VALUES (?, ?, ?, ?, ?, ?)
");
$stmt->bind_param("sisdsi", $batch_number, $product_id, $production_date, $quantity, $expiry_date, $created_by);
$stmt->execute();
*/

?>
