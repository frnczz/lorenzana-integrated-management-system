<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'qc')) {
    header("Location: login.php");
    exit;
}
include "includes/functions.php";
include "db_connect.php";

$qc_id = intval($_GET['id'] ?? 0);
$grn_item_id = intval($_GET['grn_item_id'] ?? 0);
$qc = null;
$grn_item = null;

if ($qc_id > 0) {
    $qc_query = $conn->prepare("
        SELECT qc.*, grn.grn_number, grn.received_date, po.po_number, s.supplier_name,
               gi.quantity_received, gi.lot_number as grn_lot, gi.expiry_date as grn_expiry,
               gi.warehouse_location, gi.unit_price
        FROM raw_material_qc qc
        LEFT JOIN goods_receiving_notes grn ON qc.grn_id = grn.grn_id
        LEFT JOIN purchase_orders po ON grn.po_id = po.po_id
        LEFT JOIN suppliers s ON po.supplier_id = s.supplier_id
        LEFT JOIN grn_items gi ON qc.grn_item_id = gi.grn_item_id
        WHERE qc.qc_id = ?
    ");
    $qc_query->bind_param("i", $qc_id);
    $qc_query->execute();
    $qc = $qc_query->get_result()->fetch_assoc();
    $qc_query->close();
    
    if ($qc) {
        $grn_item = [
            'quantity_received' => $qc['quantity_received'],
            'lot_number' => $qc['grn_lot'],
            'expiry_date' => $qc['grn_expiry'],
            'warehouse_location' => $qc['warehouse_location'],
            'unit_price' => $qc['unit_price']
        ];
    }
}

elseif ($grn_item_id > 0) {
    // If a GRN item id is provided and no QC exists yet, try to find existing QC or create one
    $check_qc = $conn->prepare("SELECT * FROM raw_material_qc WHERE grn_item_id = ? LIMIT 1");
    $check_qc->bind_param("i", $grn_item_id);
    $check_qc->execute();
    $existing = $check_qc->get_result()->fetch_assoc();
    $check_qc->close();

    if ($existing) {
        $qc = $existing;
        $qc_id = $qc['qc_id'];
    } else {
        // Load GRN item details to create a new QC record
        $gi_q = $conn->prepare("SELECT gi.*, grn.grn_id, grn.grn_number, grn.received_date, po.po_number, s.supplier_name
            FROM grn_items gi
            LEFT JOIN goods_receiving_notes grn ON gi.grn_id = grn.grn_id
            LEFT JOIN purchase_orders po ON grn.po_id = po.po_id
            LEFT JOIN suppliers s ON po.supplier_id = s.supplier_id
            WHERE gi.grn_item_id = ? LIMIT 1");
        $gi_q->bind_param("i", $grn_item_id);
        $gi_q->execute();
        $gi = $gi_q->get_result()->fetch_assoc();
        $gi_q->close();

        if ($gi) {
            $qc_number = generateReferenceId($conn, 'QC');
            if (!$qc_number) {
                $_SESSION['error'] = 'Could not generate QC reference.';
                header("Location: qc_raw_materials.php");
                exit;
            }

            $insert = $conn->prepare("INSERT INTO raw_material_qc (qc_number, grn_id, grn_item_id, material_id, item_name, quantity_received, qc_status, approval_status, inspected_by, inspection_date, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $material_id = (isset($gi['material_id']) && intval($gi['material_id']) > 0) ? intval($gi['material_id']) : null;
            $item_name = $gi['item_name'] ?? '';
            $quantity_received = floatval($gi['quantity_received'] ?? 0);
            $default_status = 'Pending';
            $default_approval = 'Pending';
            $grn_id = intval($gi['grn_id']);
            $inspected_by = $_SESSION['user_id'];
            $inspection_date = date('Y-m-d');
            if (!$insert) {
                $_SESSION['error'] = 'Prepare failed: ' . $conn->error;
                header("Location: qc_raw_materials.php");
                exit;
            }
            $insert->bind_param("siiisdssis", $qc_number, $grn_id, $grn_item_id, $material_id, $item_name, $quantity_received, $default_status, $default_approval, $inspected_by, $inspection_date);
            if ($insert->execute()) {
                $qc_id = $conn->insert_id;
                $insert->close();
                // fetch the newly created QC record
                $qc_q = $conn->prepare("SELECT qc.*, grn.grn_number, grn.received_date, po.po_number, s.supplier_name, gi.quantity_received, gi.lot_number as grn_lot, gi.expiry_date as grn_expiry, gi.warehouse_location, gi.unit_price
                    FROM raw_material_qc qc
                    LEFT JOIN goods_receiving_notes grn ON qc.grn_id = grn.grn_id
                    LEFT JOIN purchase_orders po ON grn.po_id = po.po_id
                    LEFT JOIN suppliers s ON po.supplier_id = s.supplier_id
                    LEFT JOIN grn_items gi ON qc.grn_item_id = gi.grn_item_id
                    WHERE qc.qc_id = ? LIMIT 1");
                $qc_q->bind_param("i", $qc_id);
                $qc_q->execute();
                $qc = $qc_q->get_result()->fetch_assoc();
                $qc_q->close();
                if ($qc) {
                    $grn_item = [
                        'quantity_received' => $qc['quantity_received'],
                        'lot_number' => $qc['grn_lot'],
                        'expiry_date' => $qc['grn_expiry'],
                        'warehouse_location' => $qc['warehouse_location'],
                        'unit_price' => $qc['unit_price']
                    ];
                }
            } else {
                $_SESSION['error'] = 'Could not create QC record: ' . $insert->error;
                header("Location: qc_raw_materials.php");
                exit;
            }
        } else {
            $_SESSION['error'] = 'GRN item not found.';
            header("Location: qc_raw_materials.php");
            exit;
        }
    }
}

if (!$qc) {
    header("Location: qc_raw_materials.php");
    exit;
}

// Calculate days to expiry
$days_to_expiry = null;
if ($qc['expiry_date'] || $grn_item['expiry_date']) {
    $expiry = $qc['expiry_date'] ?? $grn_item['expiry_date'];
    $days_to_expiry = (strtotime($expiry) - time()) / (60 * 60 * 24);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QC Inspection - Raw Material | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .info-section {
            background: #f0f9ff;
            border: 2px solid #3b82f6;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .checklist-section {
            background: #f9fafb;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
        }
        .checklist-item {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 15px;
            padding: 12px;
            margin-bottom: 10px;
            background: white;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
        }
        .auto-rule-alert {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            font-size: 12px;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <?php include "layouts/sidebar.php"; ?>
    <div class="main">
        <?php include "layouts/header.php"; ?>
        <div class="content">
            <h2>QC Inspection: <?php echo htmlspecialchars($qc['qc_number']); ?></h2>
            <?php showMessage(); ?>
            
            <!-- Item Information -->
            <div class="info-section">
                <h3 style="margin-top:0;">Item Information</h3>
                <div class="form-grid">
                    <div>
                        <strong>Item Name:</strong> <?php echo htmlspecialchars($qc['item_name'] ?? '-'); ?><br>
                        <strong>GRN Number:</strong> <?php echo htmlspecialchars($qc['grn_number'] ?? '-'); ?><br>
                        <strong>PO Number:</strong> <?php echo htmlspecialchars($qc['po_number'] ?? '-'); ?><br>
                        <strong>Supplier:</strong> <?php echo htmlspecialchars($qc['supplier_name'] ?? '-'); ?>
                    </div>
                    <div>
                        <strong>Received Date:</strong> <?php echo ($qc['received_date'] ?? null) ? formatDate($qc['received_date']) : '-'; ?><br>
                        <strong>Quantity Received:</strong> <?php echo number_format($qc['quantity_received'] ?? 0, 2); ?><br>
                        <strong>Lot Number:</strong> <?php echo htmlspecialchars(($qc['lot_number'] ?? null) ?: ($grn_item['lot_number'] ?? '-')); ?><br>
                        <strong>Expiry Date:</strong> <?php echo ($qc['expiry_date'] ?? null) || ($grn_item['expiry_date'] ?? null) ? formatDate($qc['expiry_date'] ?? $grn_item['expiry_date']) : '-'; ?>
                        <?php if ($days_to_expiry !== null): ?>
                            <br><strong>Days to Expiry:</strong> 
                            <span style="<?php echo $days_to_expiry < 0 ? 'color:#dc2626; font-weight:bold;' : ($days_to_expiry < 30 ? 'color:#f59e0b;' : ''); ?>">
                                <?php echo number_format($days_to_expiry, 0); ?> days
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- QC Checklist Form -->
            <div class="card">
                <form id="qcForm" method="POST" action="api/save_raw_material_qc.php">
                    <input type="hidden" name="qc_id" value="<?php echo $qc_id; ?>">
                    
                    <h3>QC Checklist</h3>
                    
                    <!-- Automated Rules Alerts -->
                    <div id="auto_rules_alerts"></div>
                    
                    <div class="checklist-section">
                        <div class="checklist-item">
                            <div>
                                <label><strong>Packaging Status</strong></label>
                                <small style="color:var(--text-muted);">Check if packaging is intact, damaged, or partially damaged</small>
                            </div>
                            <div>
                                <select name="packaging_status" style="width:100%; padding:8px;" required>
                                    <option value="Intact" <?php echo ($qc['packaging_status'] ?? 'Intact') === 'Intact' ? 'selected' : ''; ?>>Intact</option>
                                    <option value="Damaged" <?php echo ($qc['packaging_status'] ?? '') === 'Damaged' ? 'selected' : ''; ?>>Damaged</option>
                                    <option value="Partial" <?php echo ($qc['packaging_status'] ?? '') === 'Partial' ? 'selected' : ''; ?>>Partial</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="checklist-item">
                            <div>
                                <label><strong>Label Accuracy</strong></label>
                                <small style="color:var(--text-muted);">Verify labels are correct, incorrect, or missing</small>
                            </div>
                            <div>
                                <select name="label_accuracy" style="width:100%; padding:8px;" required>
                                    <option value="Correct" <?php echo ($qc['label_accuracy'] ?? 'Correct') === 'Correct' ? 'selected' : ''; ?>>Correct</option>
                                    <option value="Incorrect" <?php echo ($qc['label_accuracy'] ?? '') === 'Incorrect' ? 'selected' : ''; ?>>Incorrect</option>
                                    <option value="Missing" <?php echo ($qc['label_accuracy'] ?? '') === 'Missing' ? 'selected' : ''; ?>>Missing</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="checklist-item">
                            <div>
                                <label><strong>Quantity Check</strong></label>
                                <small style="color:var(--text-muted);">Verify quantity received vs ordered</small>
                            </div>
                            <div>
                                <select name="quantity_check" id="quantity_check" style="width:100%; padding:8px;" required onchange="checkAutoRules()">
                                    <option value="Pass" <?php echo ($qc['quantity_check'] ?? 'Pass') === 'Pass' ? 'selected' : ''; ?>>Pass</option>
                                    <option value="Fail" <?php echo ($qc['quantity_check'] ?? '') === 'Fail' ? 'selected' : ''; ?>>Fail</option>
                                    <option value="Conditional" <?php echo ($qc['quantity_check'] ?? '') === 'Conditional' ? 'selected' : ''; ?>>Conditional</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="checklist-item">
                            <div>
                                <label><strong>Expiry Date Check</strong></label>
                                <small style="color:var(--text-muted);">Verify expiry date is acceptable</small>
                            </div>
                            <div>
                                <select name="expiry_check" id="expiry_check" style="width:100%; padding:8px;" required onchange="checkAutoRules()">
                                    <option value="Pass" <?php echo ($qc['expiry_check'] ?? 'Pass') === 'Pass' ? 'selected' : ''; ?>>Pass</option>
                                    <option value="Fail" <?php echo ($qc['expiry_check'] ?? '') === 'Fail' ? 'selected' : ''; ?>>Fail</option>
                                    <option value="Conditional" <?php echo ($qc['expiry_check'] ?? '') === 'Conditional' ? 'selected' : ''; ?>>Conditional</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Food Industry Specific Checks -->
                        <div class="checklist-item">
                            <div>
                                <label><strong>pH Level (if applicable)</strong></label>
                                <small style="color:var(--text-muted);">Enter pH level if tested</small>
                            </div>
                            <div>
                                <input type="number" name="ph_level" value="<?php echo $qc['ph_level'] ?? ''; ?>" 
                                       step="0.01" min="0" max="14" placeholder="e.g., 7.0" style="width:100%; padding:8px;">
                            </div>
                        </div>
                        
                        <div class="checklist-item">
                            <div>
                                <label><strong>Salt Percentage (if applicable)</strong></label>
                                <small style="color:var(--text-muted);">Enter salt % if tested</small>
                            </div>
                            <div>
                                <input type="number" name="salt_percentage" value="<?php echo $qc['salt_percentage'] ?? ''; ?>" 
                                       step="0.01" min="0" max="100" placeholder="e.g., 5.5" style="width:100%; padding:8px;">
                            </div>
                        </div>
                        
                        <div class="checklist-item">
                            <div>
                                <label><strong>Odor Test</strong></label>
                                <small style="color:var(--text-muted);">Check for acceptable odor</small>
                            </div>
                            <div>
                                <select name="odor_test" style="width:100%; padding:8px;" required>
                                    <option value="Pass" <?php echo ($qc['odor_test'] ?? 'Pass') === 'Pass' ? 'selected' : ''; ?>>Pass</option>
                                    <option value="Fail" <?php echo ($qc['odor_test'] ?? '') === 'Fail' ? 'selected' : ''; ?>>Fail</option>
                                    <option value="Conditional" <?php echo ($qc['odor_test'] ?? '') === 'Conditional' ? 'selected' : ''; ?>>Conditional</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="checklist-item">
                            <div>
                                <label><strong>Color Check</strong></label>
                                <small style="color:var(--text-muted);">Verify color is acceptable</small>
                            </div>
                            <div>
                                <select name="color_check" style="width:100%; padding:8px;" required>
                                    <option value="Pass" <?php echo ($qc['color_check'] ?? 'Pass') === 'Pass' ? 'selected' : ''; ?>>Pass</option>
                                    <option value="Fail" <?php echo ($qc['color_check'] ?? '') === 'Fail' ? 'selected' : ''; ?>>Fail</option>
                                    <option value="Conditional" <?php echo ($qc['color_check'] ?? '') === 'Conditional' ? 'selected' : ''; ?>>Conditional</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="checklist-item">
                            <div>
                                <label><strong>Texture Check</strong></label>
                                <small style="color:var(--text-muted);">Verify texture is acceptable</small>
                            </div>
                            <div>
                                <select name="texture_check" style="width:100%; padding:8px;" required>
                                    <option value="Pass" <?php echo ($qc['texture_check'] ?? 'Pass') === 'Pass' ? 'selected' : ''; ?>>Pass</option>
                                    <option value="Fail" <?php echo ($qc['texture_check'] ?? '') === 'Fail' ? 'selected' : ''; ?>>Fail</option>
                                    <option value="Conditional" <?php echo ($qc['texture_check'] ?? '') === 'Conditional' ? 'selected' : ''; ?>>Conditional</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quantities -->
                    <div class="form-grid" style="margin-top:20px;">
                        <div>
                            <label>Quantity Accepted *</label>
                            <input type="number" name="quantity_accepted" id="quantity_accepted" 
                                   value="<?php echo $qc['quantity_accepted'] ?: $qc['quantity_received']; ?>" 
                                   max="<?php echo $qc['quantity_received']; ?>"
                                   step="0.01" min="0" style="width:100%; padding:8px;" required onchange="updateRejected()">
                        </div>
                        <div>
                            <label>Quantity Rejected</label>
                            <input type="number" name="quantity_rejected" id="quantity_rejected" 
                                   value="<?php echo $qc['quantity_rejected'] ?? 0; ?>" 
                                   step="0.01" min="0" style="width:100%; padding:8px;" readonly>
                        </div>
                        <div>
                            <label>Expiry Date</label>
                            <input type="date" name="expiry_date" 
                                   value="<?php echo $qc['expiry_date'] ?? $grn_item['expiry_date'] ?? ''; ?>" 
                                   style="width:100%; padding:8px;" onchange="checkAutoRules()">
                        </div>
                    </div>
                    
                    <!-- Overall QC Status -->
                    <div style="margin-top:20px; padding:15px; background:#fef3c7; border-radius:8px;">
                        <label><strong>Overall QC Status *</strong></label>
                        <select name="qc_status" id="qc_status" style="width:100%; padding:8px; margin-top:10px;" required>
                            <option value="Pending" <?php echo ($qc['qc_status'] ?? 'Pending') === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="Passed" <?php echo ($qc['qc_status'] ?? '') === 'Passed' ? 'selected' : ''; ?>>Passed</option>
                            <option value="Failed" <?php echo ($qc['qc_status'] ?? '') === 'Failed' ? 'selected' : ''; ?>>Failed</option>
                            <option value="Conditional" <?php echo ($qc['qc_status'] ?? '') === 'Conditional' ? 'selected' : ''; ?>>Conditional</option>
                        </select>
                        <p style="margin-top:10px; font-size:12px; color:#92400e;">
                            <strong>Note:</strong> Items with status "Passed" and approved will be automatically added to raw materials inventory.
                        </p>
                    </div>
                    
                    <div style="margin-top:15px;">
                        <label>QC Remarks</label>
                        <textarea name="qc_remarks" style="width:100%; padding:8px; min-height:80px;" 
                                  placeholder="Enter detailed QC inspection notes..."><?php echo htmlspecialchars($qc['qc_remarks'] ?? ''); ?></textarea>
                    </div>
                    
                    <div style="text-align:right; margin-top:20px;">
                        <a href="qc_raw_materials.php" class="btn" style="margin-right:10px;">Cancel</a>
                        <button type="submit" class="btn" id="saveQcBtn">Save QC Inspection</button>
                    </div>
                </form>
                
                <!-- Delete QC Record Option -->
                <?php if ($qc_id > 0 && ($qc['qc_status'] === 'Pending' || $_SESSION['role'] === 'admin')): ?>
                <div style="margin-top:20px; padding:15px; background:#fee2e2; border-left: 4px solid #dc2626; border-radius:6px;">
                    <strong style="color:#dc2626;">Delete This QC Record</strong>
                    <p style="margin: 10px 0 10px 0; font-size:14px; color:#6b1818;">
                        If inspection was done by mistake or needs to be redone, remove this QC record to allow re-inspection.
                    </p>
                    <form method="POST" action="api/delete_qc.php" style="display:inline-block;">
                        <input type="hidden" name="qc_id" value="<?php echo $qc_id; ?>">
                        <button type="submit" class="btn" style="background:#dc2626; color:white;" onclick="return confirm('Are you sure? This QC record will be deleted and the item will be available for re-inspection.');">
                            Delete QC Record
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if ($qc['qc_status'] === 'Conditional' && $qc['approval_status'] === 'Pending' && ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'qc')): ?>
                <div class="card" style="margin-top:20px; background:#dbeafe; border:2px solid #3b82f6;">
                    <h3>Supervisor Approval Required</h3>
                    <p>This item requires supervisor approval due to conditional QC status.</p>
                    <form method="POST" action="api/approve_raw_material_qc.php" style="display:inline-block; margin-right:10px;">
                        <input type="hidden" name="qc_id" value="<?php echo $qc_id; ?>">
                        <input type="hidden" name="action" value="approve">
                        <textarea name="supervisor_remarks" placeholder="Supervisor remarks..." style="width:100%; padding:8px; margin-bottom:10px; min-height:60px;"></textarea>
                        <button type="submit" class="btn" style="background:#10b981;">Approve</button>
                    </form>
                    <form method="POST" action="api/approve_raw_material_qc.php" style="display:inline-block;">
                        <input type="hidden" name="qc_id" value="<?php echo $qc_id; ?>">
                        <input type="hidden" name="action" value="reject">
                        <textarea name="supervisor_remarks" placeholder="Rejection reason..." style="width:100%; padding:8px; margin-bottom:10px; min-height:60px;"></textarea>
                        <button type="submit" class="btn" style="background:#dc2626;">Reject</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
        <?php include "layouts/footer.php"; ?>
    </div>
</div>
<script src="assets/js/sidebar.js"></script>
<script>
// AJAX QC form submission for real-time GRN update
document.getElementById('qcForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var form = this;
    var btn = document.getElementById('saveQcBtn');
    btn.disabled = true;
    btn.textContent = 'Saving...';
    var formData = new FormData(form);
    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(resp => resp.json())
    .then(data => {
        if (data.success) {
            btn.textContent = 'Saved!';
            // Optionally update parent window if opened in modal or iframe
            if (window.opener && !window.opener.closed) {
                window.opener.postMessage({
                    type: 'qc-updated',
                    grn_id: data.grn_id,
                    qc_status: data.qc_status,
                    total_items_received: data.total_items_received
                }, '*');
            }
            setTimeout(function() {
                window.location.reload();
            }, 800);
        } else {
            alert('Error: ' + (data.error || 'Unknown error'));
            btn.disabled = false;
            btn.textContent = 'Save QC Inspection';
        }
    })
    .catch(err => {
        alert('Network error. Please try again.');
        btn.disabled = false;
        btn.textContent = 'Save QC Inspection';
    });
});
</script>
<script>
var quantityReceived = <?php echo $qc['quantity_received']; ?>;
var daysToExpiry = <?php echo $days_to_expiry !== null ? number_format($days_to_expiry, 0) : 'null'; ?>;

function updateRejected() {
    var accepted = parseFloat(document.getElementById('quantity_accepted').value) || 0;
    var rejected = quantityReceived - accepted;
    document.getElementById('quantity_rejected').value = Math.max(0, rejected).toFixed(2);
    checkAutoRules();
}

function checkAutoRules() {
    var alerts = [];
    var quantityCheck = document.getElementById('quantity_check').value;
    var expiryCheck = document.getElementById('expiry_check').value;
    var accepted = parseFloat(document.getElementById('quantity_accepted').value) || 0;
    var percentage = (accepted / quantityReceived) * 100;
    
    // Quantity rule: < 90% = Conditional
    if (percentage < 90 && quantityCheck !== 'Fail') {
        alerts.push('⚠️ Quantity received is less than 90% of ordered. Consider marking as Conditional.');
    }
    
    // Expiry rule: < 30 days = Conditional, < 0 = Fail
    if (daysToExpiry !== null) {
        if (daysToExpiry < 0 && expiryCheck !== 'Fail') {
            alerts.push('❌ Product has expired! Must mark as Failed.');
        } else if (daysToExpiry < 30 && expiryCheck !== 'Conditional' && expiryCheck !== 'Fail') {
            alerts.push('⚠️ Product expires in less than 30 days. Consider marking as Conditional.');
        }
    }
    
    var alertsDiv = document.getElementById('auto_rules_alerts');
    if (alerts.length > 0) {
        alertsDiv.innerHTML = alerts.map(a => '<div class="auto-rule-alert">' + a + '</div>').join('');
    } else {
        alertsDiv.innerHTML = '';
    }
}

// Check rules on load
checkAutoRules();
</script>
</body>
</html>
