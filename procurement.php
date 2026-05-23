<?php
session_start();

// Allow admin and procurement roles (you can add 'procurement' later as a role)
if (!isset($_SESSION['user_id']) || 
   ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'warehouse')) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Procurement & Supplier Management | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="wrapper">

    <!-- Sidebar -->
    <?php include "layouts/sidebar.php"; ?>

    <!-- Main Area -->
    <div class="main">

        <!-- Header -->
        <?php include "layouts/header.php"; ?>

        <!-- Content -->
        <div class="content">

            <h2>Procurement & Supplier Management Module</h2>
            <p>Create purchase requests, manage suppliers, and monitor deliveries.</p>

            <?php include "includes/functions.php"; showMessage(); ?>
            <?php include "db_connect.php"; ?>

            <!-- Supplier Registration -->
            <div class="card">
                <h3>Add New Supplier</h3>
                <form method="POST" action="api/save_supplier.php" data-loading-message="Saving supplier..." data-loading-subtext="Adding or updating supplier.">
                    <table>
                        <tr>
                            <td>Supplier Name</td>
                            <td><input type="text" name="supplier_name" style="width:100%; padding:8px;" required></td>
                        </tr>
                        <tr>
                            <td>Contact Person</td>
                            <td><input type="text" name="contact_person" style="width:100%; padding:8px;"></td>
                        </tr>
                        <tr>
                            <td>Contact Number</td>
                            <td><input type="text" name="contact_number" style="width:100%; padding:8px;"></td>
                        </tr>
                        <tr>
                            <td>Email Address</td>
                            <td><input type="email" name="email" style="width:100%; padding:8px;"></td>
                        </tr>
                        <tr>
                            <td>Address</td>
                            <td><textarea name="address" style="width:100%; padding:8px;" rows="3"></textarea></td>
                        </tr>
                        <tr>
                            <td colspan="2" style="text-align:right;">
                                <button type="submit" class="btn">Save Supplier</button>
                            </td>
                        </tr>
                    </table>
                </form>
            </div>

            <!-- Purchase Request -->
            <div class="card">
                <h3>Create Purchase Request</h3>
                <form method="POST" action="api/save_purchase_request.php" data-loading-message="Saving purchase request..." data-loading-subtext="Submitting purchase request.">
                    <table>
                        <tr>
                            <td>Supplier</td>
                            <td>
                                <?php
                                $suppliers_query = "SELECT supplier_id, supplier_name FROM suppliers ORDER BY supplier_name";
                                $suppliers_result = $conn->query($suppliers_query);
                                ?>
                                <select name="supplier_id" style="width:100%; padding:8px;" required>
                                    <option value="">-- Select Supplier --</option>
                                    <?php if ($suppliers_result && $suppliers_result->num_rows > 0): ?>
                                        <?php while ($supp = $suppliers_result->fetch_assoc()): ?>
                                            <option value="<?php echo $supp['supplier_id']; ?>"><?php echo htmlspecialchars($supp['supplier_name']); ?></option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>Item Name</td>
                            <td><input type="text" name="item_name" style="width:100%; padding:8px;" required></td>
                        </tr>
                        <tr>
                            <td>Quantity</td>
                            <td><input type="number" name="quantity" step="0.01" style="width:100%; padding:8px;" required></td>
                        </tr>
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
                        <tr>
                            <td>Expected Delivery Date</td>
                            <td><input type="date" name="expected_delivery_date" style="width:100%; padding:8px;"></td>
                        </tr>
                        <tr>
                            <td colspan="2" style="text-align:right;">
                                <button type="submit" class="btn">Submit Purchase Request</button>
                            </td>
                        </tr>
                    </table>
                </form>
            </div>

            <!-- Purchase Orders Table -->
            <div class="card">
                <h3>Purchase Requests / Orders</h3>
                <?php
                $pr_query = "SELECT pr.*, s.supplier_name FROM purchase_requests pr 
                            LEFT JOIN suppliers s ON pr.supplier_id = s.supplier_id 
                            ORDER BY pr.created_at DESC LIMIT 50";
                $pr_result = $conn->query($pr_query);
                ?>
                <table>
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
                                <td><?php echo htmlspecialchars($pr['pr_number']); ?></td>
                                <td><?php echo htmlspecialchars($pr['supplier_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($pr['item_name']); ?></td>
                                <td><?php echo number_format($pr['quantity'], 2) . ' ' . htmlspecialchars($pr['unit']); ?></td>
                                <td><?php echo htmlspecialchars($pr['status']); ?></td>
                                <td><?php echo formatDate($pr['created_at']); ?></td>
                                <td><?php echo formatDate($pr['expected_delivery_date']); ?></td>
                                <td>
                                    <a href="api/generate_pdf.php?type=purchase_order&id=<?php echo $pr['pr_id']; ?>" target="_blank" class="btn" style="padding: 6px 12px; font-size: 12px; text-decoration: none; display: inline-block;">📄 PO</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 20px; color: var(--text-muted);">No purchase requests found.</td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>

        </div>

        <!-- Footer -->
        <?php include "layouts/footer.php"; ?>

    </div>

</div>

<script src="assets/js/sidebar.js"></script>

</body>
</html>
