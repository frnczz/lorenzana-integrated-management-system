<?php
session_start();
include "../db_connect.php";
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/inventory_service.php';

// Authentication
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'production')) {
    http_response_code(403);
    echo "Unauthorized";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $batch_id = intval($_POST['batch_id'] ?? 0);
    $status = $_POST['status'] ?? '';

    $valid_statuses = ['Processing','In Progress','Ready','Output Pending QC','Completed','Rejected'];
    if ($batch_id <= 0 || !in_array($status, $valid_statuses)) {
        http_response_code(400);
        echo "Invalid data";
        exit;
    }

    // Get batch info before update (for Completed: add to finished_goods and update production request + sales)
    $batch_row = null;
    $batch_cols = $conn->query("SELECT product_id, quantity, request_id FROM production_batches WHERE batch_id = " . (int)$batch_id . " LIMIT 1");
    if ($batch_cols && ($batch_row = $batch_cols->fetch_assoc()));

    $phase_col = @$conn->query("SHOW COLUMNS FROM production_batches LIKE 'phase'");
    $has_phase = $phase_col && $phase_col->num_rows > 0;
    $phase = in_array($status, ['Completed','Rejected']) ? $status : ($status === 'Ready' ? 'Output Pending QC' : 'In Progress');
    $stmt = $has_phase
        ? $conn->prepare("UPDATE production_batches SET status=?, phase=? WHERE batch_id=?")
        : $conn->prepare("UPDATE production_batches SET status=? WHERE batch_id=?");
    if ($has_phase) {
        $stmt->bind_param("ssi", $status, $phase, $batch_id);
    } else {
        $stmt->bind_param("si", $status, $batch_id);
    }
    if (!$stmt->execute()) {
        $stmt->close();
        http_response_code(500);
        echo "Database error";
        exit;
    }
    $stmt->close();

    // When batch is marked Ready (sent to QC): set linked production request(s) to For Inspection
    if ($status === 'Ready' && $batch_row) {
        $request_id = isset($batch_row['request_id']) ? (int)$batch_row['request_id'] : 0;
        if ($request_id > 0) {
            $conn->query("UPDATE production_requests SET status = 'For Inspection', updated_at = NOW() WHERE request_id = $request_id");
        }
    }

    // When batch is marked Completed: add output to finished_goods, mark linked production request Completed, update sales
    if ($status === 'Completed' && $batch_row && (int)$batch_row['product_id'] > 0 && (float)$batch_row['quantity'] > 0) {
        $product_id = (int)$batch_row['product_id'];
        $qty = (float)$batch_row['quantity'];
        $request_id = isset($batch_row['request_id']) ? (int)$batch_row['request_id'] : 0;
        $created_by = (int)($_SESSION['user_id'] ?? 0);

        // Get batch number and expiry date from production_batches
        $batch_info = $conn->prepare("SELECT batch_number, expiry_date, warehouse_location FROM production_batches WHERE batch_id = ?");
        $batch_info->bind_param("i", $batch_id);
        $batch_info->execute();
        $batch_info_result = $batch_info->get_result();
        $batch_data = $batch_info_result->fetch_assoc();
        $batch_info->close();

        $batch_number = $batch_data['batch_number'] ?? null;
        $expiry_date = $batch_data['expiry_date'] ?? date('Y-m-d', strtotime('+1 year'));
        $warehouse_location = $batch_data['warehouse_location'] ?? null;

        try {
            emitSystemEvent($conn, 'production_batch', $batch_id, 'PRODUCTION_OUTPUT', [
                'product_id' => $product_id,
                'quantity' => $qty,
                'batch_id' => $batch_id,
                'batch_number' => $batch_number,
                'expiry_date' => $expiry_date,
                'warehouse_location' => $warehouse_location,
                'created_by' => $created_by
            ]);
            processInventoryEvent($conn, 'PRODUCTION_OUTPUT', [
                'product_id' => $product_id,
                'quantity' => $qty,
                'batch_id' => $batch_id,
                'batch_number' => $batch_number,
                'expiry_date' => $expiry_date,
                'warehouse_location' => $warehouse_location,
                'created_by' => $created_by
            ]);
            $conn->query("UPDATE system_events SET processed = 1, processed_at = NOW() WHERE entity_type = 'production_batch' AND entity_id = $batch_id AND event_type = 'PRODUCTION_OUTPUT'");
        } catch (Exception $e) {
            // Log but don't fail the status update
        }

        if ($request_id > 0) {
            $conn->query("UPDATE production_requests SET status = 'Completed', updated_at = NOW() WHERE request_id = $request_id");
            $req_row = $conn->query("SELECT sales_order_id FROM production_requests WHERE request_id = $request_id AND sales_order_id IS NOT NULL LIMIT 1");
            if ($req_row && ($r = $req_row->fetch_assoc()) && !empty($r['sales_order_id'])) {
                $oid = (int)$r['sales_order_id'];
                $conn->query("UPDATE sales_orders SET status = 'Confirmed' WHERE order_id = $oid AND status = 'Pending'");
            }
        }
    }

    echo "Updated successfully";
    exit;
}
?>
