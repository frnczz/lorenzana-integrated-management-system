<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'procurement' && $_SESSION['role'] != 'warehouse')) {
    header("Location: login.php");
    exit;
}
include "includes/functions.php";
include "db_connect.php";

$pr_id = intval($_GET['id'] ?? 0);
if ($pr_id <= 0) {
    header("Location: procurement_requisitions.php");
    exit;
}

$pr_query = $conn->prepare("
    SELECT pr.*, u1.username as requested_by_name, u2.username as approved_by_name
    FROM purchase_requisitions pr
    LEFT JOIN users u1 ON pr.requested_by = u1.id
    LEFT JOIN users u2 ON pr.approved_by = u2.id
    WHERE pr.pr_id = ?
");
$pr_query->bind_param("i", $pr_id);
$pr_query->execute();
$pr = $pr_query->get_result()->fetch_assoc();
$pr_query->close();

if (!$pr) {
    header("Location: procurement_requisitions.php");
    exit;
}

$items_query = $conn->prepare("SELECT * FROM pr_items WHERE pr_id = ? ORDER BY pr_item_id");
$items_query->bind_param("i", $pr_id);
$items_query->execute();
$items = $items_query->get_result()->fetch_all(MYSQLI_ASSOC);
$items_query->close();

// Check if a PO has already been created from this PR
$po_exists = false;
$po_check_query = $conn->prepare("SELECT po_id FROM purchase_orders WHERE pr_id = ? LIMIT 1");
$po_check_query->bind_param("i", $pr_id);
$po_check_query->execute();
$po_check_result = $po_check_query->get_result();
if ($po_check_result->num_rows > 0) {
    $po_exists = true;
}
$po_check_query->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Purchase Requisition | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="wrapper">
    <?php include "layouts/sidebar.php"; ?>
    <div class="main">
        <?php include "layouts/header.php"; ?>
        <div class="content">
            <h2>Purchase Requisition: <?php echo htmlspecialchars($pr['pr_number']); ?></h2>
            <?php showMessage(); ?>
            
            <div class="card">
                <h3>Requisition Details</h3>
                <table>
                    <tr><td style="width:200px;"><strong>PR Number:</strong></td><td><?php echo htmlspecialchars($pr['pr_number']); ?></td></tr>
                    <tr><td><strong>Department:</strong></td><td><?php echo htmlspecialchars($pr['department'] ?? '-'); ?></td></tr>
                    <tr><td><strong>Requested By:</strong></td><td><?php echo htmlspecialchars($pr['requested_by_name'] ?? 'N/A'); ?></td></tr>
                    <tr><td><strong>Request Date:</strong></td><td><?php echo formatDate($pr['request_date']); ?></td></tr>
                    <tr><td><strong>Required Date:</strong></td><td><?php echo $pr['required_date'] ? formatDate($pr['required_date']) : '-'; ?></td></tr>
                    <tr><td><strong>Status:</strong></td><td>
                        <span style="padding:4px 12px; border-radius:12px; font-size:12px; font-weight:600;
                            <?php 
                            if ($pr['status'] === 'Approved') echo 'background:#d1fae5; color:#065f46;';
                            elseif ($pr['status'] === 'Rejected') echo 'background:#fee2e2; color:#991b1b;';
                            elseif ($pr['status'] === 'Submitted') echo 'background:#dbeafe; color:#1e40af;';
                            else echo 'background:#f3f4f6; color:#374151;';
                            ?>">
                            <?php echo htmlspecialchars($pr['status']); ?>
                        </span>
                    </td></tr>
                    <tr><td><strong>Total Estimated Cost:</strong></td><td>₱<?php echo number_format($pr['total_estimated_cost'], 2); ?></td></tr>
                    <tr><td><strong>Justification:</strong></td><td><?php echo nl2br(htmlspecialchars($pr['justification'])); ?></td></tr>
                </table>
                
                <h3 style="margin-top:30px;">Requested Items</h3>
                <table style="width:100%; margin-top:15px;">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Type</th>
                            <th>Quantity</th>
                            <th>Unit</th>
                            <th>Est. Unit Price</th>
                            <th>Est. Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['item_type']); ?></td>
                                <td><?php echo number_format($item['quantity'], 2); ?></td>
                                <td><?php echo htmlspecialchars($item['unit']); ?></td>
                                <td>₱<?php echo number_format($item['estimated_unit_price'], 2); ?></td>
                                <td>₱<?php echo number_format($item['estimated_total'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if ($pr['status'] === 'Submitted' && $_SESSION['role'] == 'admin'): ?>
                    <div style="margin-top:30px; padding-top:20px; border-top:2px solid #e5e7eb;">
                        <h3>Approval Action</h3>
                        <form method="POST" action="api/approve_pr.php" style="display:inline-block; margin-right:10px;">
                            <input type="hidden" name="pr_id" value="<?php echo $pr_id; ?>">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="btn" style="background:#10b981;">Approve</button>
                        </form>
                        <form method="POST" action="api/approve_pr.php" style="display:inline-block;">
                            <input type="hidden" name="pr_id" value="<?php echo $pr_id; ?>">
                            <input type="hidden" name="action" value="reject">
                            <input type="text" name="rejection_reason" placeholder="Rejection reason" required style="padding:8px; margin-right:10px;">
                            <button type="submit" class="btn" style="background:#dc2626;">Reject</button>
                        </form>
                    </div>
                <?php endif; ?>
                
                <?php if ($pr['status'] === 'Approved'): ?>
                    <div style="margin-top:30px; text-align:center;">
                        <?php if ($po_exists): ?>
                            <button type="button" class="btn" disabled style="opacity:0.6; cursor:not-allowed;">Purchase Order Already Created</button>
                        <?php else: ?>
                            <button type="button" class="btn" id="createPoBtn" onclick="createPurchaseOrder(this, <?php echo $pr_id; ?>)">Create Purchase Order</button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php include "layouts/footer.php"; ?>
    </div>
</div>
<script src="assets/js/sidebar.js"></script>
<script>
function createPurchaseOrder(btn, prId) {
    // Check if already in progress to prevent duplicate submissions
    var key = 'po_creating_' + prId;
    if (localStorage.getItem(key)) {
        return; // Already creating, prevent duplicate
    }
    
    // Set flag to prevent duplicate submissions
    localStorage.setItem(key, 'true');
    
    btn.disabled = true;
    btn.textContent = 'Creating...';
    btn.style.opacity = '0.6';
    btn.style.cursor = 'not-allowed';
    
    // Navigate to the procurement orders page with the PR id
    window.location.href = 'procurement_orders.php?create_from_pr=' + prId;
}
</script>
</body>
</html>
