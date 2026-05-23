<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'warehouse' && $_SESSION['role'] != 'procurement')) {
    header("Location: login.php");
    exit;
}
include "includes/functions.php";
include "db_connect.php";

$grn_id = intval($_GET['id'] ?? 0);
if ($grn_id <= 0) {
    header("Location: procurement_receiving.php");
    exit;
}

$grn_query = $conn->prepare("
    SELECT grn.*, po.po_number, s.supplier_name, s.contact_number,
           u1.username as received_by_name, u2.username as qc_checked_by_name
    FROM goods_receiving_notes grn
    LEFT JOIN purchase_orders po ON grn.po_id = po.po_id
    LEFT JOIN suppliers s ON po.supplier_id = s.supplier_id
    LEFT JOIN users u1 ON grn.received_by = u1.id
    LEFT JOIN users u2 ON grn.qc_checked_by = u2.id
    WHERE grn.grn_id = ?
");
$grn_query->bind_param("i", $grn_id);
$grn_query->execute();
$grn = $grn_query->get_result()->fetch_assoc();
$grn_query->close();

if (!$grn) {
    header("Location: procurement_receiving.php");
    exit;
}

$items_query = $conn->prepare("SELECT * FROM grn_items WHERE grn_id = ? ORDER BY grn_item_id");
$items_query->bind_param("i", $grn_id);
$items_query->execute();
$items = $items_query->get_result()->fetch_all(MYSQLI_ASSOC);
$items_query->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View GRN | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="wrapper">
    <?php include "layouts/sidebar.php"; ?>
    <div class="main">
        <?php include "layouts/header.php"; ?>
        <div class="content">
            <h2>GRN: <?php echo htmlspecialchars($grn['grn_number']); ?></h2>
            <?php showMessage(); ?>
            
            <div class="card">
                <h3>GRN Details</h3>
                <table>
                    <tr><td style="width:200px;"><strong>GRN Number:</strong></td><td><?php echo htmlspecialchars($grn['grn_number']); ?></td></tr>
                    <tr><td><strong>PO Number:</strong></td><td><?php echo htmlspecialchars($grn['po_number'] ?? '-'); ?></td></tr>
                    <tr><td><strong>Supplier:</strong></td><td><?php echo htmlspecialchars($grn['supplier_name'] ?? '-'); ?></td></tr>
                    <tr><td><strong>Received Date:</strong></td><td><?php echo formatDate($grn['received_date']); ?></td></tr>
                    <tr><td><strong>Received By:</strong></td><td><?php echo htmlspecialchars($grn['received_by_name'] ?? 'N/A'); ?></td></tr>
                    <tr><td><strong>QC Status:</strong></td><td>
                        <span style="padding:4px 12px; border-radius:12px; font-size:12px; font-weight:600;
                            <?php 
                            if ($grn['qc_status'] === 'Passed') echo 'background:#d1fae5; color:#065f46;';
                            elseif ($grn['qc_status'] === 'Failed') echo 'background:#fee2e2; color:#991b1b;';
                            else echo 'background:#fef3c7; color:#92400e;';
                            ?>">
                            <?php echo htmlspecialchars($grn['qc_status']); ?>
                        </span>
                    </td></tr>
                    <tr><td><strong>QC Checked By:</strong></td><td><?php echo htmlspecialchars($grn['qc_checked_by_name'] ?? '-'); ?></td></tr>
                    <?php if ($grn['qc_checked_at']): ?>
                        <tr><td><strong>QC Checked At:</strong></td><td><?php echo date('Y-m-d H:i', strtotime($grn['qc_checked_at'])); ?></td></tr>
                    <?php endif; ?>
                    <tr><td><strong>Status:</strong></td><td>
                        <span style="padding:4px 12px; border-radius:12px; font-size:12px; font-weight:600;
                            <?php 
                            if ($grn['status'] === 'QC Passed') echo 'background:#d1fae5; color:#065f46;';
                            elseif ($grn['status'] === 'QC Failed') echo 'background:#fee2e2; color:#991b1b;';
                            else echo 'background:#dbeafe; color:#1e40af;';
                            ?>">
                            <?php echo htmlspecialchars($grn['status']); ?>
                        </span>
                    </td></tr>
                    <?php if ($grn['qc_remarks']): ?>
                        <tr><td><strong>QC Remarks:</strong></td><td><?php echo nl2br(htmlspecialchars($grn['qc_remarks'])); ?></td></tr>
                    <?php endif; ?>
                    <?php if ($grn['notes']): ?>
                        <tr><td><strong>Notes:</strong></td><td><?php echo nl2br(htmlspecialchars($grn['notes'])); ?></td></tr>
                    <?php endif; ?>
                </table>

                <div style="margin-top:15px; text-align:center;">
                    <a href="api/generate_pdf.php?type=grn&id=<?php echo $grn_id; ?>" target="_blank" class="btn">Print GRN</a>
                </div>

                <h3 style="margin-top:30px;">Received Items</h3>
                <table style="width:100%; margin-top:15px;">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Qty Received</th>
                            <th>Qty Accepted</th>
                            <th>Qty Rejected</th>
                            <th>Unit</th>
                            <th>Lot Number</th>
                            <th>Expiry Date</th>
                            <th>Location</th>
                            <th>QC Status</th>
                            <th>QC Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                <td><?php echo number_format($item['quantity_received'], 2); ?></td>
                                <td><strong><?php echo number_format($item['quantity_accepted'], 2); ?></strong></td>
                                <td><?php echo number_format($item['quantity_rejected'], 2); ?></td>
                                <td><?php echo htmlspecialchars($item['unit']); ?></td>
                                <td><?php echo htmlspecialchars($item['lot_number'] ?? '-'); ?></td>
                                <td><?php echo $item['expiry_date'] ? formatDate($item['expiry_date']) : '-'; ?></td>
                                <td><?php echo htmlspecialchars(formatLocation($item['warehouse_location'] ?? null)); ?></td>
                                <td>
                                    <span style="padding:4px 8px; border-radius:8px; font-size:11px; font-weight:600;
                                        <?php 
                                        if ($item['qc_status'] === 'Passed') echo 'background:#d1fae5; color:#065f46;';
                                        elseif ($item['qc_status'] === 'Failed') echo 'background:#fee2e2; color:#991b1b;';
                                        else echo 'background:#fef3c7; color:#92400e;';
                                        ?>">
                                        <?php echo htmlspecialchars($item['qc_status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($item['qc_remarks'] ?? '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if ($grn['qc_status'] === 'Passed'): ?>
                    <div style="margin-top:30px; padding:15px; background:#d1fae5; border-radius:8px; color:#065f46;">
                        <strong>✓ Items have been automatically added to raw materials inventory.</strong>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php include "layouts/footer.php"; ?>
    </div>
</div>
<script src="assets/js/sidebar.js"></script>
</body>
</html>
