<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'warehouse')) {
    header("Location: login.php");
    exit;
}
include "includes/functions.php";
include "db_connect.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purchase Requests | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="wrapper">
    <?php include "layouts/sidebar.php"; ?>
    <div class="main">
        <?php include "layouts/header.php"; ?>
        <div class="content">
            <h2>Purchase Requests</h2>
            <p>Create and view purchase requests.</p>
            <?php showMessage(); ?>

            <!-- CREATE PURCHASE REQUEST FORM -->
            <div class="card">
                <h3>Create Purchase Request</h3>
                <form method="POST" action="api/save_purchase_request.php" data-loading-message="Saving purchase request..." data-loading-subtext="Submitting purchase request.">
                    <table>
                        <tr>
                            <td>Supplier</td>
                            <td>
                                <?php
                                // Only active suppliers for dropdown
                                $activeSuppliers = $conn->query("
                                    SELECT supplier_id, supplier_name
                                    FROM suppliers
                                    WHERE status = 'active'
                                    ORDER BY supplier_name
                                ");
                                ?>
                                <select name="supplier_id" style="width:100%; padding:8px;" required>
                                    <option value="">-- Select Supplier --</option>
                                    <?php if ($activeSuppliers): while ($supp = $activeSuppliers->fetch_assoc()): ?>
                                        <option value="<?= $supp['supplier_id']; ?>"><?= htmlspecialchars($supp['supplier_name']); ?></option>
                                    <?php endwhile; endif; ?>
                                </select>
                            </td>
                        </tr>
                        <tr><td>Item Name</td><td><input type="text" name="item_name" style="width:100%; padding:8px;" required></td></tr>
                        <tr><td>Quantity</td><td><input type="number" name="quantity" step="0.01" style="width:100%; padding:8px;" required></td></tr>
                        <tr>
                            <td>Unit</td>
                            <td>
                                <select name="unit" style="width:100%; padding:8px;" required>
                                    <option value="kg">kg</option>
                                    <option value="liters">liters</option>
                                    <option value="pcs">pcs</option>
                                </select>
                            </td>
                        </tr>
                        <tr><td>Expected Delivery Date</td><td><input type="date" name="expected_delivery_date" style="width:100%; padding:8px;"></td></tr>
                        <tr><td colspan="2" style="text-align:right;"><button type="submit" class="btn">Submit Purchase Request</button></td></tr>
                    </table>
                </form>
            </div>

            <!-- PURCHASE REQUESTS TABLE -->
            <div class="card" style="margin-top:20px;">
                <h3>Purchase Requests / Orders</h3>
                <?php
                $pr_result = $conn->query("
                    SELECT pr.*, s.supplier_name, s.status AS supplier_status
                    FROM purchase_requests pr
                    LEFT JOIN suppliers s ON pr.supplier_id = s.supplier_id
                    ORDER BY pr.created_at DESC
                    LIMIT 50
                ");
                ?>
                <table width="100%" border="1" cellpadding="8" cellspacing="0">
                    <tr>
                        <th>PR No</th>
                        <th>Supplier</th>
                        <th>Item</th>
                        <th>Quantity</th>
                        <th>Status</th>
                        <th>Request Date</th>
                        <th>Expected Delivery</th>
                        <th>Actions</th>
                    </tr>
                    <?php if ($pr_result && $pr_result->num_rows > 0): ?>
                        <?php while ($pr = $pr_result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($pr['pr_number']); ?></td>
                                <td>
                                    <?php
                                    if (isset($pr['supplier_name'])) {
                                        echo htmlspecialchars($pr['supplier_name']);
                                        if ($pr['supplier_status'] !== 'active') {
                                            echo " <span style='color:red;'>(inactive)</span>";
                                        }
                                    } else {
                                        echo "N/A";
                                    }
                                    ?>
                                </td>
                                <td><?= htmlspecialchars($pr['item_name']); ?></td>
                                <td><?= number_format($pr['quantity'], 2) . ' ' . htmlspecialchars($pr['unit']); ?></td>
                                <td><?= htmlspecialchars($pr['status']); ?></td>
                                <td><?= formatDate($pr['created_at']); ?></td>
                                <td><?= formatDate($pr['expected_delivery_date']); ?></td>
                                <td>
                                    <a href="api/generate_pdf.php?type=purchase_order&id=<?= $pr['pr_id']; ?>" target="_blank" class="btn" style="padding:6px 12px; font-size:12px; text-decoration:none; display:inline-block;">📄 PO</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" style="text-align:center;padding:20px;color:var(--text-muted);">No purchase requests found.</td></tr>
                    <?php endif; ?>
                </table>
            </div>

        </div>
        <?php include "layouts/footer.php"; ?>
    </div>
</div>
<script src="assets/js/sidebar.js"></script>
</body>
</html>
