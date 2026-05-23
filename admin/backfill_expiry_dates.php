<?php
/**
 * Backfill Expiry Dates for Finished Goods Batches
 *
 * This script updates finished goods batches that are missing expiry dates.
 * It uses the following logic:
 * 1. For batches with corresponding production batches: use production batch expiry date
 * 2. For batches without production batches: calculate based on product shelf life
 */

// Include database connection
include "../db_connect.php";
require_once __DIR__ . '/../includes/expiry_service_v2.php';

// Simulate admin session for script execution
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';

echo "<h1>Backfilling Expiry Dates for Finished Goods Batches</h1>";
echo "<pre>";

// Get all finished goods batches without expiry dates
$query = "
    SELECT fgb.batch_id, fgb.batch_number, fgb.product_id, fgb.production_date, fgb.created_at,
           p.product_name, pb.expiry_date as prod_batch_expiry
    FROM finished_goods_batches fgb
    LEFT JOIN products p ON fgb.product_id = p.product_id
    LEFT JOIN production_batches pb ON fgb.batch_number = pb.batch_number
    WHERE fgb.expiry_date IS NULL
    ORDER BY fgb.created_at ASC
";

$result = $conn->query($query);
$batches_to_update = [];

if ($result && $result->num_rows > 0) {
    echo "Found " . $result->num_rows . " finished goods batches without expiry dates:\n\n";

    while ($batch = $result->fetch_assoc()) {
        $batches_to_update[] = $batch;
        echo "Batch: {$batch['batch_number']} | Product: {$batch['product_name']} | Production Date: {$batch['production_date']}\n";
        echo "  Production Batch Expiry: " . ($batch['prod_batch_expiry'] ?? 'N/A') . "\n";
        echo "\n";
    }

    echo "Processing updates...\n\n";

    $updated_count = 0;
    $errors = [];

    foreach ($batches_to_update as $batch) {
        $expiry_date = null;

        // Method 1: Use production batch expiry date if available
        if (!empty($batch['prod_batch_expiry'])) {
            $expiry_date = $batch['prod_batch_expiry'];
            echo "Using production batch expiry for {$batch['batch_number']}: $expiry_date\n";
        }
        // Method 2: Calculate based on product shelf life
        else {
            $production_date = $batch['production_date'] ?? date('Y-m-d', strtotime($batch['created_at']));

            $expiry_result = computeExpiryForBatch($conn, $batch['product_id'], $production_date);

            if ($expiry_result['success']) {
                $expiry_date = $expiry_result['expiry_date'];
                echo "Calculated expiry for {$batch['batch_number']}: $expiry_date (from {$expiry_result['shelf_life_value']} {$expiry_result['shelf_life_unit']})\n";
            } else {
                $errors[] = "Failed to calculate expiry for batch {$batch['batch_number']}: " . $expiry_result['error'];
                echo "ERROR: Failed to calculate expiry for {$batch['batch_number']}: " . $expiry_result['error'] . "\n";
                continue;
            }
        }

        // Update the batch with the expiry date
        if ($expiry_date) {
            $update_stmt = $conn->prepare("UPDATE finished_goods_batches SET expiry_date = ? WHERE batch_id = ?");
            $update_stmt->bind_param("si", $expiry_date, $batch['batch_id']);

            if ($update_stmt->execute()) {
                $updated_count++;
                echo "  ✓ Updated batch {$batch['batch_number']}\n";
            } else {
                $errors[] = "Failed to update batch {$batch['batch_number']}: " . $update_stmt->error;
                echo "  ✗ Failed to update batch {$batch['batch_number']}: " . $update_stmt->error . "\n";
            }

            $update_stmt->close();
        }

        echo "\n";
    }

    echo "\nSummary:\n";
    echo "Updated: $updated_count batches\n";
    echo "Errors: " . count($errors) . "\n";

    if (!empty($errors)) {
        echo "\nErrors:\n";
        foreach ($errors as $error) {
            echo "  - $error\n";
        }
    }

} else {
    echo "No finished goods batches found without expiry dates.\n";
}

echo "</pre>";
?>

echo "<h1>Backfilling Expiry Dates for Finished Goods Batches</h1>";
echo "<pre>";

// Get all finished goods batches without expiry dates
$query = "
    SELECT fgb.batch_id, fgb.batch_number, fgb.product_id, fgb.production_date, fgb.created_at,
           p.product_name, pb.expiry_date as prod_batch_expiry
    FROM finished_goods_batches fgb
    LEFT JOIN products p ON fgb.product_id = p.product_id
    LEFT JOIN production_batches pb ON fgb.batch_number = pb.batch_number
    WHERE fgb.expiry_date IS NULL
    ORDER BY fgb.created_at ASC
";

$result = $conn->query($query);
$batches_to_update = [];

if ($result && $result->num_rows > 0) {
    echo "Found " . $result->num_rows . " finished goods batches without expiry dates:\n\n";

    while ($batch = $result->fetch_assoc()) {
        $batches_to_update[] = $batch;
        echo "Batch: {$batch['batch_number']} | Product: {$batch['product_name']} | Production Date: {$batch['production_date']}\n";
        echo "  Production Batch Expiry: " . ($batch['prod_batch_expiry'] ?? 'N/A') . "\n";
        echo "\n";
    }

    echo "Processing updates...\n\n";

    $updated_count = 0;
    $errors = [];

    foreach ($batches_to_update as $batch) {
        $expiry_date = null;

        // Method 1: Use production batch expiry date if available
        if (!empty($batch['prod_batch_expiry'])) {
            $expiry_date = $batch['prod_batch_expiry'];
            echo "Using production batch expiry for {$batch['batch_number']}: $expiry_date\n";
        }
        // Method 2: Calculate based on product shelf life
        else {
            $production_date = $batch['production_date'] ?? date('Y-m-d', strtotime($batch['created_at']));

            $expiry_result = computeExpiryForBatch($conn, $batch['product_id'], $production_date);

            if ($expiry_result['success']) {
                $expiry_date = $expiry_result['expiry_date'];
                echo "Calculated expiry for {$batch['batch_number']}: $expiry_date (from {$expiry_result['shelf_life_value']} {$expiry_result['shelf_life_unit']})\n";
            } else {
                $errors[] = "Failed to calculate expiry for batch {$batch['batch_number']}: " . $expiry_result['error'];
                echo "ERROR: Failed to calculate expiry for {$batch['batch_number']}: " . $expiry_result['error'] . "\n";
                continue;
            }
        }

        // Update the batch with the expiry date
        if ($expiry_date) {
            $update_stmt = $conn->prepare("UPDATE finished_goods_batches SET expiry_date = ? WHERE batch_id = ?");
            $update_stmt->bind_param("si", $expiry_date, $batch['batch_id']);

            if ($update_stmt->execute()) {
                $updated_count++;
                echo "  ✓ Updated batch {$batch['batch_number']}\n";
            } else {
                $errors[] = "Failed to update batch {$batch['batch_number']}: " . $update_stmt->error;
                echo "  ✗ Failed to update batch {$batch['batch_number']}: " . $update_stmt->error . "\n";
            }

            $update_stmt->close();
        }

        echo "\n";
    }

    echo "\nSummary:\n";
    echo "Updated: $updated_count batches\n";
    echo "Errors: " . count($errors) . "\n";

    if (!empty($errors)) {
        echo "\nErrors:\n";
        foreach ($errors as $error) {
            echo "  - $error\n";
        }
    }

} else {
    echo "No finished goods batches found without expiry dates.\n";
}

echo "</pre>";
?>