<?php
/**
 * Batch Tracking Test Script
 * Tests the FEFO batch allocation and batch traceability functionality
 */

require_once __DIR__ . '/includes/inventory_service.php';
require_once __DIR__ . '/db_connect.php';

echo "=== LORINIMS Batch Tracking Test ===\n\n";

try {
    global $conn;

    // Test 1: Create test raw material batches
    echo "Test 1: Creating test raw material batches...\n";

    // Create test material if it doesn't exist
    $material_code = 'TEST-MAT-001';
    $mat_check = $conn->prepare("SELECT material_id FROM raw_materials WHERE material_code = ?");
    $mat_check->bind_param("s", $material_code);
    $mat_check->execute();
    $mat_result = $mat_check->get_result();

    if ($mat_result->num_rows == 0) {
        $ins_mat = $conn->prepare("
            INSERT INTO raw_materials (material_code, material_name, category, quantity, unit, expiry_date)
            VALUES (?, 'Test Material', 'Procurement', 0, 'kg', NULL)
        ");
        $ins_mat->bind_param("s", $material_code);
        $ins_mat->execute();
        $material_id = $conn->insert_id;
        $ins_mat->close();
        echo "Created test material with ID: $material_id\n";
    } else {
        $row = $mat_result->fetch_assoc();
        $material_id = $row['material_id'];
        echo "Using existing test material with ID: $material_id\n";
    }
    $mat_check->close();

    // Create test batches with different expiry dates
    $batches = [
        ['batch_number' => 'TEST-BATCH-001', 'expiry' => '2024-12-31', 'qty' => 100],
        ['batch_number' => 'TEST-BATCH-002', 'expiry' => '2024-06-30', 'qty' => 50],
        ['batch_number' => 'TEST-BATCH-003', 'expiry' => '2025-01-15', 'qty' => 75],
    ];

    foreach ($batches as $batch) {
        // Check if batch exists
        $batch_check = $conn->prepare("SELECT batch_id FROM raw_material_batches WHERE batch_number = ?");
        $batch_check->bind_param("s", $batch['batch_number']);
        $batch_check->execute();
        if ($batch_check->get_result()->num_rows == 0) {
            $ins_batch = $conn->prepare("
                INSERT INTO raw_material_batches
                (batch_number, material_id, quantity_received, quantity_remaining, unit, expiry_date, received_date, qc_approved)
                VALUES (?, ?, ?, ?, 'kg', ?, CURDATE(), 1)
            ");
            $ins_batch->bind_param("sidds", $batch['batch_number'], $material_id, $batch['qty'], $batch['qty'], $batch['expiry']);
            $ins_batch->execute();
            $batch_id = $conn->insert_id;
            $ins_batch->close();
            echo "Created batch {$batch['batch_number']} with ID: $batch_id\n";
        } else {
            echo "Batch {$batch['batch_number']} already exists\n";
        }
        $batch_check->close();
    }

    // Update total material quantity
    $total_qty = array_sum(array_column($batches, 'qty'));
    $upd_mat = $conn->prepare("UPDATE raw_materials SET quantity = ? WHERE material_id = ?");
    $upd_mat->bind_param("di", $total_qty, $material_id);
    $upd_mat->execute();
    $upd_mat->close();

    // Test 2: Create a production batch that consumes materials
    echo "\nTest 2: Creating production batch with FEFO consumption...\n";

    // Create production batch
    $batch_number = 'PROD-' . date('Ymd-His');
    $ins_prod = $conn->prepare("
        INSERT INTO production_batches (batch_number, product_id, quantity, status, created_by, batch_date, expiry_date)
        VALUES (?, 1, 50, 'In Progress', 1, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR))
    ");
    $ins_prod->bind_param("s", $batch_number);
    $ins_prod->execute();
    $prod_batch_id = $conn->insert_id;
    $ins_prod->close();
    echo "Created production batch with ID: $prod_batch_id\n";

    // Simulate production consumption using the inventory service
    $event_data = [
        'batch_id' => $prod_batch_id,
        'materials' => [
            ['material_id' => $material_id, 'quantity' => 40] // Consume 40kg
        ],
        'created_by' => 1
    ];

    processInventoryEvent($conn, 'PRODUCTION_CONSUME', $event_data);
    echo "Processed PRODUCTION_CONSUME event\n";

    // Test 3: Verify batch allocation (FEFO)
    echo "\nTest 3: Verifying FEFO batch allocation...\n";

    $detail_query = $conn->prepare("
        SELECT bd.raw_batch_id, rmb.batch_number, rmb.expiry_date, bd.quantity_used
        FROM batch_details bd
        JOIN raw_material_batches rmb ON bd.raw_batch_id = rmb.batch_id
        WHERE bd.batch_id = ?
        ORDER BY rmb.expiry_date ASC
    ");
    $detail_query->bind_param("i", $prod_batch_id);
    $detail_query->execute();
    $detail_result = $detail_query->get_result();

    echo "Batch consumption details:\n";
    $total_used = 0;
    while ($row = $detail_result->fetch_assoc()) {
        echo "- Batch {$row['batch_number']} (Expiry: {$row['expiry_date']}): {$row['quantity_used']} kg\n";
        $total_used += $row['quantity_used'];
    }
    echo "Total consumed: $total_used kg\n";

    // Verify FEFO order (earliest expiry first)
    $detail_query->execute();
    $detail_result = $detail_query->get_result();
    $expected_order = ['TEST-BATCH-002', 'TEST-BATCH-001', 'TEST-BATCH-003']; // By expiry date
    $actual_order = [];
    while ($row = $detail_result->fetch_assoc()) {
        $actual_order[] = $row['batch_number'];
    }

    if ($actual_order === array_slice($expected_order, 0, count($actual_order))) {
        echo "✓ FEFO allocation verified\n";
    } else {
        echo "✗ FEFO allocation failed. Expected: " . implode(', ', $expected_order) . ", Got: " . implode(', ', $actual_order) . "\n";
    }

    // Test 4: Check remaining quantities
    echo "\nTest 4: Checking remaining batch quantities...\n";

    $remaining_query = $conn->prepare("
        SELECT batch_number, quantity_remaining
        FROM raw_material_batches
        WHERE material_id = ?
        ORDER BY batch_number
    ");
    $remaining_query->bind_param("i", $material_id);
    $remaining_query->execute();
    $remaining_result = $remaining_query->get_result();

    echo "Remaining quantities:\n";
    while ($row = $remaining_result->fetch_assoc()) {
        echo "- {$row['batch_number']}: {$row['quantity_remaining']} kg\n";
    }

    // Test 5: Test return processing
    echo "\nTest 5: Testing return processing with LIFO...\n";

    $return_data = [
        'return_id' => 999, // Test return ID
        'items' => [
            ['material_id' => $material_id, 'quantity' => 10, 'created_by' => 1]
        ]
    ];

    processInventoryEvent($conn, 'RETURN_PROCESSED', $return_data);
    echo "Processed RETURN_PROCESSED event\n";

    // Check updated quantities
    $remaining_query->execute();
    $remaining_result = $remaining_query->get_result();

    echo "Updated remaining quantities after return:\n";
    while ($row = $remaining_result->fetch_assoc()) {
        echo "- {$row['batch_number']}: {$row['quantity_remaining']} kg\n";
    }

    echo "\n=== Test completed successfully ===\n";

    $conn->close();

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    if (isset($conn)) $conn->close();
}
?>