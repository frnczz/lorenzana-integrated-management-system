<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'warehouse' && $_SESSION['role'] != 'procurement')) {
    header("Location: login.php");
    exit;
}
include "includes/functions.php";
include "db_connect.php";

$show_form = isset($_GET['new']) || isset($_GET['po_id']);
$po_id = intval($_GET['po_id'] ?? 0);
$po = null;
$po_items = [];

// If showing form, load PO data
if ($show_form && $po_id > 0) {
    $po_query = $conn->prepare("
        SELECT po.*, s.supplier_name
        FROM purchase_orders po
        LEFT JOIN suppliers s ON po.supplier_id = s.supplier_id
        WHERE po.po_id = ? AND po.status IN ('Open', 'Partially Received')
    ");
    $po_query->bind_param("i", $po_id);
    $po_query->execute();
    $po = $po_query->get_result()->fetch_assoc();
    $po_query->close();
    
    if ($po) {
        $items_query = $conn->prepare("
            SELECT poi.*, 
                   (poi.quantity_ordered - poi.quantity_received) as remaining_qty
            FROM po_items poi
            WHERE poi.po_id = ? AND poi.quantity_ordered > poi.quantity_received
        ");
        $items_query->bind_param("i", $po_id);
        $items_query->execute();
        $items_result = $items_query->get_result();
        while ($row = $items_result->fetch_assoc()) {
            $po_items[] = $row;
        }
        $items_query->close();
    }
}

// Fetch open POs for selection
$open_pos = [];
if ($show_form) {
    $pos_query = $conn->query("
        SELECT po.po_id, po.po_number, po.order_date, s.supplier_name
        FROM purchase_orders po
        LEFT JOIN suppliers s ON po.supplier_id = s.supplier_id
        WHERE po.status IN ('Open', 'Partially Received')
        ORDER BY po.order_date DESC
        LIMIT 50
    ");
    if ($pos_query) {
        while ($row = $pos_query->fetch_assoc()) {
            $open_pos[] = $row;
        }
    }
}

// Fetch rejected items from QC
$rejected_items = [];
$rejected_query = $conn->query("
    SELECT gi.grn_item_id, gi.grn_id, gi.material_id, gi.item_name, gi.quantity_received, gi.quantity_rejected,
           gi.lot_number, gi.unit, gi.warehouse_location, gi.unit_price,
           grn.grn_number, grn.received_date, po.po_number, s.supplier_name, s.supplier_id,
           qc.qc_number, qc.qc_status, qc.approval_status, qc.qc_remarks
    FROM grn_items gi
    LEFT JOIN goods_receiving_notes grn ON gi.grn_id = grn.grn_id
    LEFT JOIN purchase_orders po ON grn.po_id = po.po_id
    LEFT JOIN suppliers s ON po.supplier_id = s.supplier_id
    LEFT JOIN raw_material_qc qc ON gi.grn_item_id = qc.grn_item_id
    WHERE gi.quantity_rejected > 0 OR (qc.qc_status = 'Failed' AND qc.approval_status = 'Rejected')
    ORDER BY grn.created_at DESC
    LIMIT 100
");
if ($rejected_query) {
    while ($row = $rejected_query->fetch_assoc()) {
        $rejected_items[] = $row;
    }
}

// prepare list of recent GRNs for the main table (prevents undefined variable later)
$grns = [];
$grns_query = $conn->query("
    SELECT 
        grn.grn_id,
        grn.grn_number,
        grn.po_id,
        grn.invoice_id,
        grn.received_date,
        grn.total_items_received,
        grn.qc_status,
        grn.status,
        po.po_number,
        s.supplier_name,
        s.supplier_id,
        u1.username AS received_by_name,
        u2.username AS qc_checked_by_name
    FROM goods_receiving_notes grn
    LEFT JOIN purchase_orders po ON grn.po_id = po.po_id
    LEFT JOIN suppliers s ON po.supplier_id = s.supplier_id
    LEFT JOIN users u1 ON grn.received_by = u1.id
    LEFT JOIN users u2 ON grn.qc_checked_by = u2.id
    ORDER BY grn.created_at DESC
    LIMIT 100
");
if ($grns_query) {
    while ($row = $grns_query->fetch_assoc()) {
        $grns[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Goods Receiving | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-draft { background: #f3f4f6; color: #374151; }
        .status-received { background: #dbeafe; color: #1e40af; }
        .status-qc-passed { background: #d1fae5; color: #065f46; }
        .status-qc-failed { background: #fee2e2; color: #991b1b; }
        .status-partial { background: #fef3c7; color: #92400e; }
    </style>
</head>
<body>
<div class="wrapper">
    <?php include "layouts/sidebar.php"; ?>
    <div class="main">
        <?php include "layouts/header.php"; ?>
        <div class="content">
            <h2>Goods Receiving Notes (GRN)</h2>
            <div class="info-box" style="background:#e0f2fe; color:#0369a1; padding:12px 18px; border-radius:8px; margin-bottom:12px;">
                <strong>Instructions:</strong> Use this page to track goods received from suppliers. To add a new GRN, click <b>+ New GRN</b>. After receiving goods, QC records are created automatically for inspection. Click <b>View</b> to see details or <b>Generate Invoice</b> to create a supplier invoice. For returns, use the <b>Return</b> button.
            </div>
            <?php showMessage(); ?>
            
            <!-- GRN List -->
            <div id="grnList" style="display:<?php echo $show_form ? 'none' : 'block'; ?>;">
                <div style="text-align:right; margin-bottom:15px;">
                    <button onclick="showForm()" class="btn">+ New GRN</button>
                </div>
                
                <div class="card">
                <h3>Goods Receiving Notes</h3>
                <table style="width:100%;">
                    <thead>
                        <tr>
                            <th>GRN Number</th>
                            <th>PO Number</th>
                            <th>Supplier</th>
                            <th>Received Date</th>
                            <th>Items</th>
                            <th>QC Status</th>
                            <th>Status</th>
                            <th>Received By</th>
                            <th>QC Checked By</th>
                            <th>Actions</th>
                            <th>Return</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($grns) > 0): ?>
                            <?php foreach ($grns as $grn): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($grn['grn_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($grn['po_number'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($grn['supplier_name'] ?? '-'); ?></td>
                                    <td><?php echo formatDate($grn['received_date']); ?></td>
                                    <td><?php echo $grn['total_items_received']; ?></td>
                                    <td>
                                        <span style="padding:4px 8px; border-radius:8px; font-size:11px; font-weight:600;
                                            <?php 
                                            if ($grn['qc_status'] === 'Passed') echo 'background:#d1fae5; color:#065f46;';
                                            elseif ($grn['qc_status'] === 'Failed') echo 'background:#fee2e2; color:#991b1b;';
                                            else echo 'background:#fef3c7; color:#92400e;';
                                            ?>">
                                            <?php echo htmlspecialchars($grn['qc_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $grn['status'])); ?>">
                                            <?php echo htmlspecialchars($grn['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($grn['received_by_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($grn['qc_checked_by_name'] ?? '-'); ?></td>
                                    <td>
                                        <a href="procurement_receiving_view.php?id=<?php echo $grn['grn_id']; ?>" class="btn" style="padding:4px 12px; font-size:12px;">View</a>
                                        <?php if (empty($grn['invoice_id'])): ?>
                                            <button class="btn" style="padding:4px 12px; font-size:12px; margin-left:4px;" 
                                                    onclick="generateInvoice(<?php echo $grn['grn_id']; ?>, <?php echo $grn['supplier_id'] ?? 'null'; ?>, <?php echo $grn['po_id'] ?? 'null'; ?>, this)">Generate Invoice</button>
                                        <?php else: ?>
                                            <a href="procurement_invoice_view.php?id=<?php echo $grn['invoice_id']; ?>" class="btn" style="padding:4px 12px; font-size:12px; margin-left:4px;">View Invoice</a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="procurement_returns.php?new=1&supplier_id=<?php echo $grn['supplier_id']; ?>&grn_id=<?php echo $grn['grn_id']; ?>" class="btn" style="padding:4px 12px; font-size:12px;">Return</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="10" style="text-align:center;padding:30px;color:var(--text-muted);">No GRNs found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Rejected Items from QC -->
            <div class="card" style="margin-top:20px; border: 2px solid #fee2e2;">
                <h3 style="color:#dc2626;">Rejected Items (QC Returned)</h3>
                <div class="info-box" style="background:#fef3c7; color:#92400e; padding:10px 16px; border-radius:8px; margin-bottom:10px;">
                    <strong>Instructions:</strong> This section lists items that failed QC inspection or were rejected. Click <b>Details</b> to view QC info, or <b>Return</b> to process a return to the supplier.
                </div>
                <table style="width:100%;">
                    <thead>
                        <tr style="background:#fee2e2;">
                            <th>GRN Number</th>
                            <th>Item Name</th>
                            <th>Supplier</th>
                            <th>Qty Received</th>
                            <th>Qty Rejected</th>
                            <th>Lot Number</th>
                            <th>QC Number</th>
                            <th>QC Status</th>
                            <th>QC Remarks</th>
                            <th>Actions</th>
                            <th>Return</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($rejected_items) > 0): ?>
                            <?php foreach ($rejected_items as $item): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($item['grn_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                    <td><?php echo htmlspecialchars($item['supplier_name'] ?? '-'); ?></td>
                                    <td><?php echo number_format($item['quantity_received'], 2); ?></td>
                                    <td style="color:#dc2626; font-weight:bold;"><?php echo number_format($item['quantity_rejected'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($item['lot_number'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($item['qc_number'] ?? '-'); ?></td>
                                    <td>
                                        <span style="padding:4px 8px; border-radius:8px; font-size:11px; font-weight:600;
                                            <?php 
                                            if ($item['qc_status'] === 'Failed' || $item['approval_status'] === 'Rejected') echo 'background:#fee2e2; color:#991b1b;';
                                            else echo 'background:#fef3c7; color:#92400e;';
                                            ?>">
                                            <?php echo htmlspecialchars($item['qc_status'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td style="font-size:12px;"><?php echo htmlspecialchars(substr($item['qc_remarks'] ?? '-', 0, 50)); ?></td>
                                    <td>
                                        <a href="qc_raw_material_form.php?grn_item_id=<?php echo $item['grn_item_id']; ?>" class="btn" style="padding:4px 12px; font-size:12px;">Inspect QC</a>
                                    </td>
                                    <td>
                                        <a href="procurement_returns.php?new=1&supplier_id=<?php echo $item['supplier_id']; ?>&grn_id=<?php echo $item['grn_id']; ?>&material_id=<?php echo $item['material_id']; ?>&qty=<?php echo $item['quantity_rejected']; ?>&unit=<?php echo urlencode($item['unit']); ?>&price=<?php echo urlencode($item['unit_price'] ?? ''); ?>&item_name=<?php echo urlencode($item['item_name']); ?>&item_reason=<?php echo urlencode($item['qc_remarks'] ?? 'QC rejected'); ?>&grn_item_id=<?php echo $item['grn_item_id']; ?>" class="btn" style="padding:4px 12px; font-size:12px;">Return</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="10" style="text-align:center;padding:30px;color:var(--text-muted);">No rejected items reported.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            </div>
            
            <!-- GRN Form -->
            <div id="grnForm" style="display:<?php echo $show_form ? 'block' : 'none'; ?>;">
                <?php include "procurement_receiving_form.php"; ?>
            </div>
        </div>
        <?php include "layouts/footer.php"; ?>
    </div>
</div>
<script src="assets/js/sidebar.js"></script>
<script>
function showForm() {
    document.getElementById('grnList').style.display = 'none';
    document.getElementById('grnForm').style.display = 'block';
    window.scrollTo(0, 0);
}

function hideForm() {
    document.getElementById('grnForm').style.display = 'none';
    document.getElementById('grnList').style.display = 'block';
}

function generateInvoice(grnId, supplierId, poId, btn) {
    if (!confirm('Are you sure you want to generate a supplier invoice for this GRN?')) return;
    btn.disabled = true;
    var originalText = btn.textContent;
    btn.textContent = 'Processing...';
    fetch('api/auto_generate_supplier_invoice.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ grn_id: grnId })
    })
    .then(resp => resp.json())
    .then(data => {
        if (data.success) {
            btn.textContent = 'Invoice Generated! Redirecting...';
            setTimeout(function() {
                window.location.href = 'procurement_invoice_view.php?id=' + data.invoice_id;
            }, 900);
        } else {
            alert('Could not generate invoice: ' + (data.error || 'An unknown error occurred. Please try again or contact support.'));
            btn.disabled = false;
            btn.textContent = originalText;
        }
    })
    .catch(err => {
        alert('Network error. Please check your connection and try again.');
        btn.disabled = false;
        btn.textContent = originalText;
    });
}
</script>
</body>
</html>
