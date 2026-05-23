<?php
session_start();
include "../db_connect.php";
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/inventory_service.php';

// --- Authentication ---
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','qc'])) {
    header("Location: ../login.php");
    exit;
}

// --- Only allow POST ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../qc_inspection.php");
    exit;
}

// --- Get POST data ---
$batch_id = intval($_POST['batch_id'] ?? 0);
$inspector_name = trim($_POST['inspector_name'] ?? '');
$inspection_date = $_POST['inspection_date'] ?? date('Y-m-d');
$test_result = $_POST['test_result'] ?? 'Pending';
$non_conformance = trim($_POST['non_conformance'] ?? '');
$corrective_action = trim($_POST['corrective_action'] ?? '');
$approval_status = $_POST['approval_status'] ?? 'For Re-inspection';
$inspected_by = $_SESSION['user_id'];

// --- Validate ---
if (!$batch_id || empty($inspector_name)) {
    $_SESSION['error'] = "Batch and inspector name are required.";
    header("Location: ../qc_inspection.php");
    exit;
}

// --- Fetch batch details (include request_id for linking to production request) ---
$has_request_id = @$conn->query("SHOW COLUMNS FROM production_batches LIKE 'request_id'")->num_rows > 0;
$batch_stmt = $conn->prepare("
    SELECT batch_number, product_id, quantity, expiry_date, status, warehouse_location " .
    ($has_request_id ? ", request_id " : " ") . "
    FROM production_batches 
    WHERE batch_id = ?
");
$batch_stmt->bind_param("i", $batch_id);
$batch_stmt->execute();
$batch_row = $batch_stmt->get_result()->fetch_assoc();
$batch_stmt->close();
$batch_number = $batch_row['batch_number'] ?? null;
$product_id = $batch_row['product_id'] ?? null;
$quantity = $batch_row['quantity'] ?? null;
$expiry_date = $batch_row['expiry_date'] ?? null;
$batch_status = $batch_row['status'] ?? null;
$warehouse_location = $batch_row['warehouse_location'] ?? null;
// Normalize empty strings to null so location matching works consistently (null vs '').
if (is_string($warehouse_location) && trim($warehouse_location) === '') {
    $warehouse_location = null;
}
$batch_request_id = $has_request_id ? (int)($batch_row['request_id'] ?? 0) : 0;

if (!$batch_number) {
    $_SESSION['error'] = "Batch not found.";
    header("Location: ../qc_inspection.php");
    exit;
}

// --- Generate QC number ---
$qc_number = generateReferenceId($conn, 'QC');
if (!$qc_number) {
    $_SESSION['error'] = "Could not generate QC number.";
    header("Location: ../qc_inspection.php");
    exit;
}

// --- Save QC record ---
$qc_stmt = $conn->prepare("
    INSERT INTO qc_records 
    (qc_number, batch_number, inspector_name, inspection_date, test_result, non_conformance_details, corrective_action, approval_status, inspected_by) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$qc_stmt->bind_param(
    "ssssssssi",
    $qc_number,
    $batch_number,
    $inspector_name,
    $inspection_date,
    $test_result,
    $non_conformance,
    $corrective_action,
    $approval_status,
    $inspected_by
);

if (!$qc_stmt->execute()) {
    $_SESSION['error'] = "Error saving QC record: " . $qc_stmt->error;
    $qc_stmt->close();
    header("Location: ../qc_inspection.php");
    exit;
}
$qc_stmt->close();

// --- If approved: emit QC_APPROVED_FG event; inventory service adds to finished goods ---
if ($approval_status === 'Approved') {
    if (empty($expiry_date)) {
        $expiry_date = date('Y-m-d', strtotime('+1 year'));
    }

    $qc_id = (int)$conn->insert_id;
    emitSystemEvent($conn, 'qc_record', $qc_id, 'QC_APPROVED_FG', [
        'product_id' => $product_id,
        'quantity' => $quantity,
        'batch_id' => $batch_id,
        'expiry_date' => $expiry_date,
        'warehouse_location' => $warehouse_location ?? null,
        'created_by' => $inspected_by
    ]);
    try {
        // Only production-request batches go to reserve stock; manual production goes to available
        $reserve_for_customer = ($batch_request_id > 0);
        processInventoryEvent($conn, 'PRODUCTION_OUTPUT', [
            'product_id' => $product_id,
            'quantity' => $quantity,
            'batch_id' => $batch_id,
            'expiry_date' => $expiry_date,
            'warehouse_location' => $warehouse_location ?? null,
            'created_by' => $inspected_by,
            'reserve_for_customer' => $reserve_for_customer
        ]);
        $mp = $conn->prepare("UPDATE system_events SET processed = 1, processed_at = NOW() WHERE entity_type = 'qc_record' AND entity_id = ? AND event_type = 'QC_APPROVED_FG'");
        $mp->bind_param("i", $qc_id);
        $mp->execute();
    } catch (Exception $ex) {
        $_SESSION['error'] = ($_SESSION['error'] ?? '') . ' ' . $ex->getMessage();
    }

    $phase_col = ($conn->query("SHOW COLUMNS FROM production_batches LIKE 'phase'")->num_rows > 0);
    $update_batch_stmt = $conn->prepare($phase_col ? "UPDATE production_batches SET status = 'Completed', phase = 'Completed' WHERE batch_id = ?" : "UPDATE production_batches SET status = 'Completed' WHERE batch_id = ?");
    $update_batch_stmt->bind_param("i", $batch_id);
    $update_batch_stmt->execute();
    $update_batch_stmt->close();

    // Mark linked production request(s) Completed and create finished customer order in sales
    if ($batch_request_id > 0) {
        $has_group = @$conn->query("SHOW COLUMNS FROM production_requests LIKE 'request_group_id'")->num_rows > 0;
        $has_from_prod = @$conn->query("SHOW COLUMNS FROM sales_orders LIKE 'from_production_request'")->num_rows > 0;
        $req_one = $conn->query("SELECT request_group_id, customer_name FROM production_requests WHERE request_id = $batch_request_id LIMIT 1");
        $r1 = $req_one && $req_one->num_rows ? $req_one->fetch_assoc() : null;
        $gid = null;
        if ($r1) {
            if ($has_group && !empty($r1['request_group_id'])) {
                $gid = $r1['request_group_id'];
                $conn->query("UPDATE production_requests SET status = 'Completed', updated_at = NOW() WHERE request_group_id = '" . $conn->real_escape_string($gid) . "'");
            } else {
                $conn->query("UPDATE production_requests SET status = 'Completed', updated_at = NOW() WHERE request_id = $batch_request_id");
            }
        } else {
            $conn->query("UPDATE production_requests SET status = 'Completed', updated_at = NOW() WHERE request_id = $batch_request_id");
        }

        // Reserve is now handled in PRODUCTION_OUTPUT via reserve_for_customer when batch_request_id > 0

        if ($has_from_prod && $r1) {
            $customer_name = $r1['customer_name'];
            $request_ids_to_use = [$batch_request_id];
            if ($gid) {
                $gq = $conn->query("SELECT request_id, product_id, requested_qty FROM production_requests WHERE request_group_id = '" . $conn->real_escape_string($gid) . "'");
                $request_ids_to_use = [];
                $group_lines = [];
                while ($row = $gq->fetch_assoc()) {
                    $request_ids_to_use[] = (int)$row['request_id'];
                    $group_lines[] = $row;
                }
            } else {
                $group_lines = [];
                $gr = $conn->query("SELECT product_id, requested_qty FROM production_requests WHERE request_id = $batch_request_id");
                if ($gr && $gr->num_rows) $group_lines[] = $gr->fetch_assoc();
            }
            if (!empty($group_lines)) {
                $customer_id = null;
                $cust = $conn->prepare("SELECT customer_id FROM customers WHERE customer_name = ? LIMIT 1");
                $cust->bind_param("s", $customer_name);
                $cust->execute();
                if ($cr = $cust->get_result()->fetch_assoc()) $customer_id = (int)$cr['customer_id'];
                else {
                    $code = generateReferenceId($conn, 'CUST');
                    if ($code) {
                        $ins = $conn->prepare("INSERT INTO customers (customer_code, customer_name) VALUES (?, ?)");
                        $ins->bind_param("ss", $code, $customer_name);
                        if ($ins->execute()) $customer_id = (int)$conn->insert_id;
                        $ins->close();
                    }
                }
                $cust->close();
                if ($customer_id !== null) {
                    $order_number = generateReferenceId($conn, 'ORD');
                    if ($order_number) {
                        $created_by = (int)($inspected_by ?? 0);
                        $gid_esc = $gid ? "'" . $conn->real_escape_string($gid) . "'" : 'NULL';
                        $conn->query("INSERT INTO sales_orders (order_number, customer_id, order_date, delivery_address, status, created_by, total_amount, from_production_request, request_group_id) VALUES ('" . $conn->real_escape_string($order_number) . "', $customer_id, CURDATE(), '', 'Confirmed', $created_by, 0, 1, $gid_esc)");
                        $order_id = (int)$conn->insert_id;
                        $total = 0;
                        $reserve_items = [];

                        foreach ($group_lines as $row) {
                            $pid = (int)$row['product_id'];
                            $qty = (float)$row['requested_qty'];
                            $unit_price = 0;

                            $pr = $conn->prepare("SELECT COALESCE(unit_price,0) AS up FROM products WHERE product_id = ?");
                            $pr->bind_param("i", $pid);
                            $pr->execute();
                            if ($pu = $pr->get_result()->fetch_assoc()) $unit_price = (float)$pu['up'];
                            $pr->close();

                            $subtotal = $unit_price * $qty;
                            $total += $subtotal;

                            // Mark these items as reserved for this customer order.
                            $ins_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal, reserved) VALUES (?, ?, ?, ?, ?, 1)");
                            $ins_item->bind_param("iiddd", $order_id, $pid, $qty, $unit_price, $subtotal);
                            $ins_item->execute();
                            $ins_item->close();

                            $reserve_items[] = ['product_id' => $pid, 'quantity' => $qty, 'warehouse_location' => $warehouse_location];
                        }

                        // Stock already added to reserved in PRODUCTION_OUTPUT (reserve_for_customer)
                        // Order items marked reserved=1 for tracking. No extra SALES_RESERVE needed.

                        $upd = $conn->prepare("UPDATE sales_orders SET total_amount = ? WHERE order_id = ?");
                        $upd->bind_param("di", $total, $order_id);
                        $upd->execute();
                        $upd->close();
                    }
                }
            }
        }
    }

} elseif ($approval_status === 'Rejected') {
    // Mark batch as Rejected
    $update_batch_stmt = $conn->prepare("UPDATE production_batches SET status = 'Rejected' WHERE batch_id = ?");
    $update_batch_stmt->bind_param("i", $batch_id);
    $update_batch_stmt->execute();
    $update_batch_stmt->close();
}

// --- Success message ---
$_SESSION['success'] = "QC record saved successfully! QC#: $qc_number";
header("Location: ../qc_inspection.php");
exit;
