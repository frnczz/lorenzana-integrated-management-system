<?php
include 'db_connect.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/inventory_service.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = (int)($_POST['product_id'] ?? 0);
    $quantity = (float)($_POST['quantity'] ?? 0);
    $materials = isset($_POST['materials']) ? (array)$_POST['materials'] : [];
    $material_qty = isset($_POST['material_qty']) ? (array)$_POST['material_qty'] : [];
    $created_by = 1;

    $batch_number = generateReferenceId($conn, 'BAT');
    if (!$batch_number) {
        die("Could not generate batch number. Please try again.");
    }

    $stmt = $conn->prepare("INSERT INTO production_batches (batch_number, product_id, batch_date, quantity, status, created_by) VALUES (?, ?, NOW(), ?, 'Processing', ?)");
    $stmt->bind_param("sidi", $batch_number, $product_id, $quantity, $created_by);
    $stmt->execute();
    $batch_id = (int)$conn->insert_id;

    $materials_payload = [];
    foreach ($materials as $mid) {
        $qty_used = isset($material_qty[$mid]) ? (float)$material_qty[$mid] : 0;
        if ($qty_used > 0 && $mid > 0) {
            // Validate stock availability
            $stock_check = $conn->prepare("SELECT quantity FROM raw_materials WHERE material_id = ?");
            $stock_check->bind_param("i", $mid);
            $stock_check->execute();
            $stock_result = $stock_check->get_result();
            if ($stock_row = $stock_result->fetch_assoc()) {
                if ($stock_row['quantity'] < $qty_used) {
                    die("Insufficient stock for material ID $mid. Available: " . $stock_row['quantity'] . ", Required: " . $qty_used);
                }
            }
            $stock_check->close();
            $materials_payload[] = ['material_id' => (int)$mid, 'quantity' => $qty_used];
        }
    }

    if (!empty($materials_payload)) {
        emitSystemEvent($conn, 'production_batch', $batch_id, 'PRODUCTION_CONSUME', [
            'batch_id' => $batch_id,
            'materials' => $materials_payload,
            'created_by' => $created_by
        ]);
        try {
            processInventoryEvent($conn, 'PRODUCTION_CONSUME', [
                'batch_id' => $batch_id,
                'materials' => $materials_payload,
                'created_by' => $created_by
            ]);
            @$conn->query("UPDATE system_events SET processed = 1, processed_at = NOW() WHERE entity_type = 'production_batch' AND entity_id = $batch_id AND event_type = 'PRODUCTION_CONSUME'");
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }

    echo "Production batch saved successfully! Batch: " . $batch_number . " - Awaiting QC inspection.";
    echo "<br><a href='production.php'>Back to Production</a>";
}
?>
