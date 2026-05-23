<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'warehouse' && $_SESSION['role'] != 'procurement')) {
    header("Location: login.php");
    exit;
}
include "includes/functions.php";
include "db_connect.php";

$return_id = intval($_GET['id'] ?? 0);
if ($return_id <= 0) {
    header("Location: procurement_returns.php");
    exit;
}

$return_query = $conn->prepare("
    SELECT sr.*, s.supplier_name, po.po_number, grn.grn_number,
           u1.username as created_by_name, u2.username as approved_by_name
    FROM supplier_returns sr
    LEFT JOIN suppliers s ON sr.supplier_id = s.supplier_id
    LEFT JOIN purchase_orders po ON sr.po_id = po.po_id
    LEFT JOIN goods_receiving_notes grn ON sr.grn_id = grn.grn_id
    LEFT JOIN users u1 ON sr.created_by = u1.id
    LEFT JOIN users u2 ON sr.approved_by = u2.id
    WHERE sr.return_id = ?
");
$return_query->bind_param("i", $return_id);
$return_query->execute();
$return = $return_query->get_result()->fetch_assoc();
$return_query->close();

if (!$return) {
    header("Location: procurement_returns.php");
    exit;
}

$items_query = $conn->prepare("SELECT * FROM return_items WHERE return_id = ? ORDER BY return_item_id");
$items_query->bind_param("i", $return_id);
$items_query->execute();
$items = $items_query->get_result()->fetch_all(MYSQLI_ASSOC);
$items_query->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Return | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="wrapper">
    <?php include "layouts/sidebar.php"; ?>
    <div class="main">
        <?php include "layouts/header.php"; ?>
        <div class="content">
            <h2>Return: <?php echo htmlspecialchars($return['return_number']); ?></h2>
            <?php showMessage(); ?>
            
            <div class="card">
                <h3>Return Details</h3>
                <table>
                    <tr><td style="width:200px;"><strong>Return Number:</strong></td><td><?php echo htmlspecialchars($return['return_number']); ?></td></tr>
                    <tr><td><strong>Supplier:</strong></td><td><?php echo htmlspecialchars($return['supplier_name']); ?></td></tr>
                    <tr><td><strong>PO Number:</strong></td><td><?php echo htmlspecialchars($return['po_number'] ?? '-'); ?></td></tr>
                    <tr><td><strong>GRN Number:</strong></td><td><?php echo htmlspecialchars($return['grn_number'] ?? '-'); ?></td></tr>
                    <tr><td><strong>Return Date:</strong></td><td><?php echo formatDate($return['return_date']); ?></td></tr>
                    <tr><td><strong>Reason:</strong></td><td><?php echo nl2br(htmlspecialchars($return['reason'])); ?></td></tr>
                    <tr><td><strong>Total Amount:</strong></td><td>₱<?php echo number_format($return['total_amount'], 2); ?></td></tr>
                    <tr><td><strong>Status:</strong></td><td>
                        <span style="padding:4px 12px; border-radius:12px; font-size:12px; font-weight:600;
                            <?php 
                            if ($return['status'] === 'Returned') echo 'background:#d1fae5; color:#065f46;';
                            elseif ($return['status'] === 'Approved') echo 'background:#dbeafe; color:#1e40af;';
                            elseif ($return['status'] === 'Cancelled') echo 'background:#f3f4f6; color:#6b7280;';
                            else echo 'background:#fef3c7; color:#92400e;';
                            ?>">
                            <?php echo htmlspecialchars($return['status']); ?>
                        </span>
                    </td></tr>
                    <?php if ($return['notes']): ?>
                        <tr><td><strong>Notes:</strong></td><td><?php echo nl2br(htmlspecialchars($return['notes'])); ?></td></tr>
                    <?php endif; ?>
                </table>
                
                <h3 style="margin-top:30px;">Return Items</h3>
                <table style="width:100%; margin-top:15px;">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Quantity</th>
                            <th>Unit</th>
                            <th>Unit Price</th>
                            <th>Subtotal</th>
                            <th>Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                <td><?php echo number_format($item['quantity'], 2); ?></td>
                                <td><?php echo htmlspecialchars($item['unit']); ?></td>
                                <td>₱<?php echo number_format($item['unit_price'], 2); ?></td>
                                <td>₱<?php echo number_format($item['subtotal'], 2); ?></td>
                                <td><?php echo htmlspecialchars($item['reason'] ?? '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if ($return['status'] === 'Pending' && ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'procurement')): ?>
                    <div style="margin-top:30px; padding-top:20px; border-top:2px solid #e5e7eb;">
                        <h3>Approval Action</h3>
                        <form method="POST" action="api/approve_return.php" style="display:inline-block; margin-right:10px;">
                            <input type="hidden" name="return_id" value="<?php echo $return_id; ?>">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="btn" style="background:#10b981;">Approve</button>
                        </form>
                        <form method="POST" action="api/approve_return.php" style="display:inline-block;">
                            <input type="hidden" name="return_id" value="<?php echo $return_id; ?>">
                            <input type="hidden" name="action" value="cancel">
                            <button type="submit" class="btn" style="background:#dc2626;">Cancel</button>
                        </form>
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
