<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'procurement' && $_SESSION['role'] != 'warehouse')) {
    header("Location: login.php");
    exit;
}
include "includes/functions.php";
include "db_connect.php";

$po_id = intval($_GET['id'] ?? 0);
if ($po_id <= 0) {
    header("Location: procurement_orders.php");
    exit;
}

$po_query = $conn->prepare("
    SELECT po.*, s.supplier_name, s.contact_person, s.contact_number, s.email, s.address as supplier_address,
           pr.pr_number, u.username as created_by_name
    FROM purchase_orders po
    LEFT JOIN suppliers s ON po.supplier_id = s.supplier_id
    LEFT JOIN purchase_requisitions pr ON po.pr_id = pr.pr_id
    LEFT JOIN users u ON po.created_by = u.id
    WHERE po.po_id = ?
");
$po_query->bind_param("i", $po_id);
$po_query->execute();
$po = $po_query->get_result()->fetch_assoc();
$po_query->close();

if (!$po) {
    header("Location: procurement_orders.php");
    exit;
}

$items_query = $conn->prepare("SELECT * FROM po_items WHERE po_id = ? ORDER BY po_item_id");
$items_query->bind_param("i", $po_id);
$items_query->execute();
$items = $items_query->get_result()->fetch_all(MYSQLI_ASSOC);
$items_query->close();

// Check if GRN exists
$grn_check = $conn->query("SELECT COUNT(*) as count FROM goods_receiving_notes WHERE po_id = $po_id");
$grn_count = $grn_check->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Purchase Order | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="wrapper">
    <?php include "layouts/sidebar.php"; ?>
    <div class="main">
        <?php include "layouts/header.php"; ?>
        <div class="content">
            <h2>Purchase Order: <?php echo htmlspecialchars($po['po_number']); ?></h2>
            <?php showMessage(); ?>
            
            <div class="card">
                <h3>PO Details</h3>
                <table>
                    <tr><td style="width:200px;"><strong>PO Number:</strong></td><td><?php echo htmlspecialchars($po['po_number']); ?></td></tr>
                    <tr><td><strong>Supplier:</strong></td><td><?php echo htmlspecialchars($po['supplier_name']); ?></td></tr>
                    <tr><td><strong>Contact:</strong></td><td><?php echo htmlspecialchars($po['contact_person'] ?? '-'); ?></td></tr>
                    <tr><td><strong>Contact Number:</strong></td><td><?php echo htmlspecialchars($po['contact_number'] ?? $po['phone'] ?? '-'); ?></td></tr>
                    <tr><td><strong>Email:</strong></td><td><?php echo htmlspecialchars($po['email'] ?? '-'); ?></td></tr>
                    <tr><td><strong>Order Date:</strong></td><td><?php echo formatDate($po['order_date']); ?></td></tr>
                    <tr><td><strong>Expected Delivery:</strong></td><td><?php echo $po['expected_delivery_date'] ? formatDate($po['expected_delivery_date']) : '-'; ?></td></tr>
                    <tr><td><strong>Payment Terms:</strong></td><td><?php echo htmlspecialchars($po['payment_terms']); ?></td></tr>
                    <tr><td><strong>Status:</strong></td><td>
                        <span style="padding:4px 12px; border-radius:12px; font-size:12px; font-weight:600;
                            <?php 
                            if ($po['status'] === 'Received') echo 'background:#d1fae5; color:#065f46;';
                            elseif ($po['status'] === 'Partially Received') echo 'background:#fef3c7; color:#92400e;';
                            elseif ($po['status'] === 'Closed') echo 'background:#f3f4f6; color:#6b7280;';
                            else echo 'background:#dbeafe; color:#1e40af;';
                            ?>">
                            <?php echo htmlspecialchars($po['status']); ?>
                        </span>
                    </td></tr>
                    <tr><td><strong>Subtotal:</strong></td><td>₱<?php echo number_format($po['subtotal'], 2); ?></td></tr>
                    <tr><td><strong>Tax:</strong></td><td>₱<?php echo number_format($po['tax_amount'], 2); ?></td></tr>
                    <tr><td><strong>Total Amount:</strong></td><td><strong>₱<?php echo number_format($po['total_amount'], 2); ?></strong></td></tr>
                    <?php if ($po['delivery_address']): ?>
                        <tr><td><strong>Delivery Address:</strong></td><td><?php echo nl2br(htmlspecialchars($po['delivery_address'])); ?></td></tr>
                    <?php endif; ?>
                    <?php if ($po['notes']): ?>
                        <tr><td><strong>Notes:</strong></td><td><?php echo nl2br(htmlspecialchars($po['notes'])); ?></td></tr>
                    <?php endif; ?>
                </table>
                
                <h3 style="margin-top:30px;">Order Items</h3>
                <table style="width:100%; margin-top:15px;">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Type</th>
                            <th>Quantity Ordered</th>
                            <th>Quantity Received</th>
                            <th>Unit</th>
                            <th>Unit Price</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['item_type']); ?></td>
                                <td><?php echo number_format($item['quantity_ordered'], 2); ?></td>
                                <td><?php echo number_format($item['quantity_received'], 2); ?></td>
                                <td><?php echo htmlspecialchars($item['unit']); ?></td>
                                <td>₱<?php echo number_format($item['unit_price'], 2); ?></td>
                                <td>₱<?php echo number_format($item['subtotal'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div style="margin-top:30px; text-align:center;">
                    <?php if ($po['status'] === 'Open' || $po['status'] === 'Partially Received'): ?>
                        <a href="procurement_receiving.php?po_id=<?php echo $po_id; ?>" class="btn" style="margin-right:10px;">Receive Goods</a>
                    <?php endif; ?>
                    <?php if ($po['status'] === 'Open'): ?>
                        <a href="procurement_orders.php?id=<?php echo $po_id; ?>" class="btn" style="margin-right:10px;">Edit PO</a>
                    <?php endif; ?>
                    <?php if ($grn_count > 0): ?>
                        <a href="procurement_receiving.php?po_id=<?php echo $po_id; ?>" class="btn" style="margin-right:10px;">View GRNs (<?php echo $grn_count; ?>)</a>
                    <?php endif; ?>
                    <a href="api/generate_pdf.php?type=po&id=<?php echo $po_id; ?>" target="_blank" class="btn">Print PO</a>
                    <a href="procurement_invoices.php?new=1&po_id=<?php echo $po_id; ?>" class="btn" style="margin-left:8px; background:#10b981;">Create Invoice from PO</a>
                </div>
            </div>
        </div>
        <?php include "layouts/footer.php"; ?>
    </div>
</div>
<script src="assets/js/sidebar.js"></script>
</body>
</html>
