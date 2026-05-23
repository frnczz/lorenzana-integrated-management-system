<?php
session_start();
include "../db_connect.php";
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/inventory_service.php';
require_once __DIR__ . '/../includes/expiry_service_v2.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'production')) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../production_record.php");
    exit;
}

$created_by = (int)$_SESSION['user_id'];
$production_date = $_POST['production_date'] ?? date('Y-m-d');
$packaging_status = $_POST['packaging_status'] ?? 'Pending';
$warehouse_location = $_POST['warehouse_location'] ?? 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas';
$material_qty = $_POST['material_qty'] ?? [];

// Fermentation duration days from production settings (per product).
$fermentation_duration_days = [];
$fdq = @$conn->query("SELECT product_id, setting_value FROM production_settings WHERE setting_key = 'fermentation_duration_days'");
if ($fdq) {
    while ($row = $fdq->fetch_assoc()) {
        $fermentation_duration_days[(int)$row['product_id']] = max(0, (int)$row['setting_value']);
    }
}

function autoFermentationStatusForDate(int $product_id, int $fermentation_eligible, string $production_date, array $duration_map): string {
    if ($fermentation_eligible === 0) {
        return 'Not Applicable';
    }
    $days = isset($duration_map[$product_id]) ? max(0, (int)$duration_map[$product_id]) : 0;
    if ($days <= 0) {
        return 'Not Started';
    }
    $start_ts = strtotime($production_date . ' 00:00:00');
    if ($start_ts === false) {
        return 'Not Started';
    }
    $today_ts = strtotime(date('Y-m-d') . ' 00:00:00');
    if ($today_ts < $start_ts) {
        return 'Not Started';
    }
    $elapsed_days = (int)floor(($today_ts - $start_ts) / 86400);
    if ($elapsed_days >= $days) {
        return 'Completed';
    }
    if ($elapsed_days <= 0) {
        return 'Not Started';
    }
    return 'Ongoing';
}

// Request IDs: comma-separated or single
$request_ids_raw = $_POST['request_ids'] ?? '';
$request_ids = array_filter(array_map('intval', preg_split('/[\s,]+/', $request_ids_raw)));

// Multiple lines: product_id[], quantity[], line_status[]
$product_ids = isset($_POST['product_id']) ? (array)$_POST['product_id'] : [];
$quantities = isset($_POST['quantity']) ? (array)$_POST['quantity'] : [];
$line_statuses = isset($_POST['line_status']) ? (array)$_POST['line_status'] : [];

$lines = [];
foreach ($product_ids as $i => $pid) {
    $pid = (int)$pid;
    $qty = isset($quantities[$i]) ? (float)$quantities[$i] : 0;
    // Fermentation is fully automatic (computed later from production date + duration).
    $ferm = 'Not Applicable';
    $status = isset($line_statuses[$i]) ? $line_statuses[$i] : 'Processing';
    // Batches from production request go straight to QC (status Ready) so they appear in inspection
    if (!empty($request_ids)) {
        $status = 'Ready';
    }
    if ($pid <= 0 || $qty <= 0) {
        continue;
    }
    // For Production Request batches we usually have a single
    // request_ids string like "12,13". Use the first ID for all
    // lines so they are still linked to the original request.
    $request_id = !empty($request_ids) ? (int)reset($request_ids) : 0;
    $lines[] = [
        'product_id' => $pid,
        'quantity' => $qty,
        'fermentation_status' => $ferm,
        'status' => $status,
        'request_id' => $request_id
    ];
}

if (empty($lines)) {
    $_SESSION['error'] = "At least one product with quantity is required.";
    header("Location: ../production_record.php");
    exit;
}

// Validate raw materials are required
if (empty($material_qty) || count($material_qty) === 0) {
    $_SESSION['error'] = "At least one raw material is required for the production batch.";
    header("Location: ../production_record.php");
    exit;
}

$conn->begin_transaction();
try {
    $has_phase = ($conn->query("SHOW COLUMNS FROM production_batches LIKE 'phase'")->num_rows > 0);
    $has_request_id = ($conn->query("SHOW COLUMNS FROM production_batches LIKE 'request_id'")->num_rows > 0);
    $first_batch_id = null;
    $batch_numbers = [];

    foreach ($lines as $line) {
        $product_id = $line['product_id'];
        $quantity = $line['quantity'];
        $fermentation_status = $line['fermentation_status'];
        $status = $line['status'];
        $request_id = $line['request_id'];

        $fermentation_eligible = 1;
        $eq = $conn->query("SELECT COALESCE(fermentation_eligible, 1) AS fe FROM products WHERE product_id = $product_id");
        if ($eq && $er = $eq->fetch_assoc()) $fermentation_eligible = (int)$er['fe'];
        $fermentation_status = autoFermentationStatusForDate(
            $product_id,
            $fermentation_eligible,
            $production_date,
            $fermentation_duration_days
        );

        $batch_number = generateReferenceId($conn, 'BAT');
        if (!$batch_number) throw new Exception("Could not generate batch number.");

        // Calculate expiry date using expiry_service_v2
        $expiry_result = computeExpiryForBatch($conn, $product_id, $production_date);
        if (!$expiry_result['success']) {
            throw new Exception("Could not calculate expiry date for product: " . $expiry_result['error']);
        }
        $expiry_date = $expiry_result['expiry_date'];

        $phase = ($status === 'Ready' || $status === 'Completed') ? ($status === 'Completed' ? 'Completed' : 'Output Pending QC') : 'In Progress';
        if ($has_phase && $has_request_id) {
            $stmt = $conn->prepare("INSERT INTO production_batches (batch_number, product_id, batch_date, quantity, fermentation_status, packaging_status, warehouse_location, status, phase, created_by, request_id, expiry_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sisdsssssiis", $batch_number, $product_id, $production_date, $quantity, $fermentation_status, $packaging_status, $warehouse_location, $status, $phase, $created_by, $request_id, $expiry_date);
        } elseif ($has_phase) {
            $stmt = $conn->prepare("INSERT INTO production_batches (batch_number, product_id, batch_date, quantity, fermentation_status, packaging_status, warehouse_location, status, phase, created_by, expiry_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sisdsssssis", $batch_number, $product_id, $production_date, $quantity, $fermentation_status, $packaging_status, $warehouse_location, $status, $phase, $created_by, $expiry_date);
        } elseif ($has_request_id) {
            $stmt = $conn->prepare("INSERT INTO production_batches (batch_number, product_id, batch_date, quantity, fermentation_status, packaging_status, warehouse_location, status, created_by, request_id, expiry_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sisdssssiis", $batch_number, $product_id, $production_date, $quantity, $fermentation_status, $packaging_status, $warehouse_location, $status, $created_by, $request_id, $expiry_date);
        } else {
            $stmt = $conn->prepare("INSERT INTO production_batches (batch_number, product_id, batch_date, quantity, fermentation_status, packaging_status, warehouse_location, status, created_by, expiry_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sisdssssss", $batch_number, $product_id, $production_date, $quantity, $fermentation_status, $packaging_status, $warehouse_location, $status, $created_by, $expiry_date);
        }
        if (!$stmt->execute()) throw new Exception("Error saving batch: " . $stmt->error);
        $batch_id = $conn->insert_id;
        $stmt->close();
        if ($first_batch_id === null) $first_batch_id = $batch_id;
        $batch_numbers[] = $batch_number;

        if (function_exists('logActivity')) {
            logActivity($conn, $created_by, 'create', 'production_batch', $batch_id, "Batch $batch_number");
        }
    }

    // Raw materials: validate stock availability and prepare payload
    $materials_payload = [];
    if (!empty($material_qty) && is_array($material_qty)) {
        foreach ($material_qty as $material_id => $qty_used) {
            $material_id = intval($material_id);
            $qty_used = floatval($qty_used);
            if ($material_id <= 0 || $qty_used <= 0) continue;
            $mat_check = $conn->prepare("SELECT material_name, quantity FROM raw_materials WHERE material_id = ?");
            $mat_check->bind_param("i", $material_id);
            $mat_check->execute();
            $mat_result = $mat_check->get_result();
            if ($mat_row = $mat_result->fetch_assoc()) {
                if ($mat_row['quantity'] < $qty_used) {
                    throw new Exception("Insufficient stock for " . $mat_row['material_name'] . ". Available: " . $mat_row['quantity'] . ", Required: " . $qty_used);
                }
                $materials_payload[] = ['material_id' => $material_id, 'quantity' => $qty_used];
            }
            $mat_check->close();
        }
        if (!empty($materials_payload)) {
            emitSystemEvent($conn, 'production_batch', $first_batch_id, 'PRODUCTION_CONSUME', [
                'batch_id' => $first_batch_id,
                'materials' => $materials_payload,
                'created_by' => $created_by
            ]);
            processInventoryEvent($conn, 'PRODUCTION_CONSUME', [
                'batch_id' => $first_batch_id,
                'materials' => $materials_payload,
                'created_by' => $created_by
            ]);
            $mp = $conn->prepare("UPDATE system_events SET processed = 1, processed_at = NOW() WHERE entity_type = 'production_batch' AND entity_id = ? AND event_type = 'PRODUCTION_CONSUME'");
            $mp->bind_param("i", $first_batch_id);
            $mp->execute();
        }
    }

    // Mark all linked production requests as For Inspection (batch created, going to QC)
    if (!empty($request_ids)) {
        $placeholders = implode(',', array_fill(0, count($request_ids), '?'));
        $stmt = $conn->prepare("UPDATE production_requests SET status = 'For Inspection', updated_at = NOW() WHERE request_id IN ($placeholders)");
        $types = str_repeat('i', count($request_ids));
        $stmt->bind_param($types, ...$request_ids);
        $stmt->execute();
        $stmt->close();
    }

    $conn->commit();

    $any_completed_ferm = false;
    foreach ($lines as $ln) {
        if (($ln['fermentation_status'] ?? '') === 'Completed') {
            $any_completed_ferm = true;
            break;
        }
    }
    if ($any_completed_ferm) {
        $bn = implode(', ', $batch_numbers);
        notifyFermentationCompletedStakeholders(
            $conn,
            'Fermentation completed on one or more new batch(es): ' . $bn . '. Line status was set to Ready where applicable — review Production Batch Records.',
            'production_records.php'
        );
    }

    $material_count = count($materials_payload);
    $_SESSION['success'] = count($batch_numbers) . " batch(es) created: " . implode(', ', $batch_numbers) .
        ($material_count > 0 ? " (Used $material_count raw material(s))" : "") . " — Awaiting QC where status is Ready.";
} catch (Exception $e) {
    $conn->rollback();
$_SESSION['error'] = $e->getMessage();
}

// After successfully creating the production batch(es),
// redirect to the Production Batch Records page so users
// immediately see the newly created batch entries.
header("Location: ../production_records.php");
exit;
