<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include "includes/functions.php";
include "db_connect.php";
ensureVehicleTypeColumn($conn);

// Get current user information
$user_id = $_SESSION['user_id'];
$user_query = "SELECT u.*, e.employee_number, e.position, e.department, e.hire_date, e.status as employee_status
               FROM users u
               LEFT JOIN employees e ON u.id = e.user_id
               WHERE u.id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();
$stmt->close();

// Get user activity statistics (role-specific)
$stats = ['batches_created' => 0, 'orders_created' => 0, 'inspections_done' => 0, 'raw_qc_done' => 0,
    'invoices_created' => 0, 'expenses_created' => 0, 'payments_created' => 0, 'deliveries_assigned' => 0,
    'grn_received' => 0, 'inventory_transactions' => 0, 'purchase_orders' => 0, 'supplier_invoices' => 0];
$role = $_SESSION['role'] ?? '';
$tables = [
    'production_batches' => ['col' => 'created_by', 'key' => 'batches_created'],
    'sales_orders' => ['col' => 'created_by', 'key' => 'orders_created'],
    'qc_records' => ['col' => 'inspected_by', 'key' => 'inspections_done'],
    'invoices' => ['col' => 'created_by', 'key' => 'invoices_created'],
    'delivery_assignments' => ['col' => 'driver_id', 'key' => 'deliveries_assigned'],
    'expenses' => ['col' => 'created_by', 'key' => 'expenses_created'],
    'payments' => ['col' => 'created_by', 'key' => 'payments_created'],
    'goods_receiving_notes' => ['col' => 'received_by', 'key' => 'grn_received'],
    'inventory_transactions' => ['col' => 'created_by', 'key' => 'inventory_transactions'],
    'purchase_orders' => ['col' => 'created_by', 'key' => 'purchase_orders'],
    'supplier_invoices' => ['col' => 'created_by', 'key' => 'supplier_invoices'],
];
$raw_material_qc_exists = @$conn->query("SHOW TABLES LIKE 'raw_material_qc'")->num_rows > 0;
foreach ($tables as $tbl => $cfg) {
    if (@$conn->query("SHOW TABLES LIKE '$tbl'")->num_rows > 0) {
        $c = @$conn->query("SHOW COLUMNS FROM $tbl LIKE '{$cfg['col']}'")->num_rows;
        if ($c > 0) {
            $r = $conn->query("SELECT COUNT(*) as c FROM $tbl WHERE {$cfg['col']} = " . (int)$user_id);
            $stats[$cfg['key']] = ($r && $row = $r->fetch_assoc()) ? (int)$row['c'] : 0;
        }
    }
}
if ($raw_material_qc_exists) {
    $r = $conn->query("SELECT COUNT(*) as c FROM raw_material_qc WHERE inspected_by = " . (int)$user_id);
    $stats['raw_qc_done'] = ($r && $row = $r->fetch_assoc()) ? (int)$row['c'] : 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | LORINIMS</title>
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

            <h2>My Profile</h2>
            <p>Manage your account information and view your activity.</p>

            <?php showMessage(); ?>

            <!-- Profile Overview -->
            <div class="card">
                <h3>Profile Information</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
                    <div style="text-align: center; padding: 20px; background: linear-gradient(135deg, rgba(255, 107, 53, 0.1) 0%, rgba(255, 140, 90, 0.1) 100%); border-radius: var(--border-radius); border: 2px solid rgba(255, 107, 53, 0.3);">
                        <div style="width: 100px; height: 100px; margin: 0 auto 15px; border-radius: 50%; background: linear-gradient(135deg, #FF6B35 0%, #FF8C5A 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 36px; box-shadow: var(--shadow-md);">
                            <?php echo strtoupper(substr($user['full_name'] ?? $user['username'] ?? 'U', 0, 1)); ?>
                        </div>
                        <h4 style="margin: 0 0 5px 0; color: var(--text-primary); font-size: 18px;">
                            <?php echo htmlspecialchars($user['full_name'] ?? 'Not set'); ?>
                        </h4>
                        <p style="margin: 0; color: var(--text-muted); font-size: 14px; text-transform: capitalize;">
                            <?php echo htmlspecialchars($user['role'] ?? 'User'); ?>
                        </p>
                        <?php if (!empty($user['employee_number'])): ?>
                            <p style="margin: 5px 0 0 0; color: var(--text-secondary); font-size: 12px;">
                                ID: <?php echo htmlspecialchars($user['employee_number']); ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <div style="padding: 20px;">
                        <h4 style="margin: 0 0 15px 0; color: var(--text-primary); border-bottom: 2px solid #FF6B35; padding-bottom: 8px;">Account Details</h4>
                        <div style="display: grid; gap: 12px;">
                            <div>
                                <strong style="color: var(--text-muted); font-size: 12px; text-transform: uppercase;">Username</strong>
                                <p style="margin: 5px 0; font-size: 16px; font-weight: 600;"><?php echo htmlspecialchars($user['username']); ?></p>
                            </div>
                            <?php if (!empty($user['email'])): ?>
                            <div>
                                <strong style="color: var(--text-muted); font-size: 12px; text-transform: uppercase;">Email</strong>
                                <p style="margin: 5px 0; font-size: 16px; font-weight: 600;"><?php echo htmlspecialchars($user['email']); ?></p>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($user['phone_number'])): ?>
                            <div>
                                <strong style="color: var(--text-muted); font-size: 12px; text-transform: uppercase;">Phone</strong>
                                <p style="margin: 5px 0; font-size: 16px; font-weight: 600;"><?php echo htmlspecialchars($user['phone_number']); ?></p>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($user['address'])): ?>
                            <div>
                                <strong style="color: var(--text-muted); font-size: 12px; text-transform: uppercase;">Address</strong>
                                <p style="margin: 5px 0; font-size: 14px; font-weight: 600; line-height: 1.5;"><?php echo nl2br(htmlspecialchars($user['address'])); ?></p>
                            </div>
                            <?php endif; ?>
                            <div>
                                <strong style="color: var(--text-muted); font-size: 12px; text-transform: uppercase;">Role</strong>
                                <p style="margin: 5px 0; font-size: 16px; font-weight: 600; text-transform: capitalize;">
                                    <span style="padding: 4px 10px; background: rgba(255, 107, 53, 0.1); color: #FF6B35; border-radius: 4px; font-size: 14px;">
                                        <?php echo htmlspecialchars($user['role']); ?>
                                    </span>
                                </p>
                            </div>
                            <?php if (!empty($user['position'])): ?>
                            <div>
                                <strong style="color: var(--text-muted); font-size: 12px; text-transform: uppercase;">Position</strong>
                                <p style="margin: 5px 0; font-size: 16px; font-weight: 600;"><?php echo htmlspecialchars($user['position']); ?></p>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($user['department'])): ?>
                            <div>
                                <strong style="color: var(--text-muted); font-size: 12px; text-transform: uppercase;">Department</strong>
                                <p style="margin: 5px 0; font-size: 16px; font-weight: 600;"><?php echo htmlspecialchars($user['department']); ?></p>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($user['hire_date'])): ?>
                            <div>
                                <strong style="color: var(--text-muted); font-size: 12px; text-transform: uppercase;">Hire Date</strong>
                                <p style="margin: 5px 0; font-size: 16px; font-weight: 600;"><?php echo date('F d, Y', strtotime($user['hire_date'])); ?></p>
                            </div>
                            <?php endif; ?>
                            <?php if ((($_SESSION['role'] ?? '') == 'delivery' || ($_SESSION['role'] ?? '') == 'driver') && !empty($user['vehicle_type'])): ?>
                            <div>
                                <strong style="color: var(--text-muted); font-size: 12px; text-transform: uppercase;">Vehicle Type</strong>
                                <p style="margin: 5px 0; font-size: 16px; font-weight: 600;"><?php echo htmlspecialchars($user['vehicle_type']); ?></p>
                            </div>
                            <?php endif; ?>
                            <div>
                                <strong style="color: var(--text-muted); font-size: 12px; text-transform: uppercase;">Member Since</strong>
                                <p style="margin: 5px 0; font-size: 16px; font-weight: 600;"><?php echo date('F d, Y', strtotime($user['created_at'])); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity Statistics (role-specific) -->
            <div class="card">
                <h3>My Activity Statistics</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-top: 20px;">
                    <?php
                    $stat_cards = [];
                    if ($role == 'production') {
                        $stat_cards[] = ['Production Batches', $stats['batches_created'], '#6366f1'];
                    } elseif ($role == 'warehouse') {
                        $stat_cards[] = ['GRNs Received', $stats['grn_received'], '#059669'];
                        $stat_cards[] = ['Inventory Transactions', $stats['inventory_transactions'], '#0d9488'];
                    } elseif ($role == 'qc') {
                        $stat_cards[] = ['QC Inspections (FG)', $stats['inspections_done'], '#8b5cf6'];
                        if ($raw_material_qc_exists) $stat_cards[] = ['QC Inspections (Raw)', $stats['raw_qc_done'], '#7c3aed'];
                    } elseif ($role == 'accounting') {
                        $stat_cards[] = ['Invoices Created', $stats['invoices_created'], '#f59e0b'];
                        $stat_cards[] = ['Expenses Recorded', $stats['expenses_created'], '#d97706'];
                        $stat_cards[] = ['Payments Processed', $stats['payments_created'], '#b45309'];
                    } elseif ($role == 'sales') {
                        $stat_cards[] = ['Sales Orders', $stats['orders_created'], '#3b82f6'];
                    } elseif ($role == 'delivery' || $role == 'driver') {
                        $stat_cards[] = ['Deliveries Assigned', $stats['deliveries_assigned'], '#06b6d4'];
                    } elseif ($role == 'procurement') {
                        $stat_cards[] = ['Purchase Orders', $stats['purchase_orders'], '#ec4899'];
                        $stat_cards[] = ['Supplier Invoices', $stats['supplier_invoices'], '#db2777'];
                    } else {
                        // admin or unknown: show relevant stats
                        $stat_cards[] = ['Production Batches', $stats['batches_created'], '#6366f1'];
                        $stat_cards[] = ['Sales Orders', $stats['orders_created'], '#3b82f6'];
                        $stat_cards[] = ['QC Inspections', ($stats['inspections_done'] + $stats['raw_qc_done']), '#8b5cf6'];
                        $stat_cards[] = ['Invoices', $stats['invoices_created'], '#f59e0b'];
                        $stat_cards[] = ['Deliveries', $stats['deliveries_assigned'], '#06b6d4'];
                        $stat_cards[] = ['Purchase Orders', $stats['purchase_orders'], '#ec4899'];
                    }
                    foreach ($stat_cards as $sc): ?>
                    <div style="padding: 15px; background: linear-gradient(135deg, <?php echo $sc[2]; ?>22 0%, <?php echo $sc[2]; ?>11 100%); border-radius: 8px; border: 1px solid <?php echo $sc[2]; ?>44;">
                        <h4 style="margin: 0 0 8px 0; font-size: 14px; color: var(--text-muted); text-transform: uppercase;"><?php echo htmlspecialchars($sc[0]); ?></h4>
                        <p style="margin: 0; font-size: 28px; font-weight: bold; color: <?php echo $sc[2]; ?>;"><?php echo number_format($sc[1]); ?></p>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($stat_cards)): ?>
                    <p style="color: var(--text-muted);">No activity statistics available for your role.</p>
                    <?php endif; ?>
                </div>
            </div>

            <?php
            $activity_log_exists = @$conn->query("SHOW TABLES LIKE 'activity_log'");
            if ($activity_log_exists && $activity_log_exists->num_rows > 0) {
                $recent = $conn->prepare("SELECT action, entity_type, entity_id, details, created_at FROM activity_log WHERE user_id = ? ORDER BY created_at DESC LIMIT 15");
                $recent->bind_param("i", $user_id);
                $recent->execute();
                $recent_result = $recent->get_result();
            } else {
                $recent_result = false;
            }
            ?>
            <?php if ($recent_result && $recent_result->num_rows > 0): ?>
            <div class="card">
                <h3>Recent activity</h3>
                <ul style="list-style: none; padding: 0; margin: 0;">
                    <?php while ($a = $recent_result->fetch_assoc()): ?>
                        <li style="padding: 10px 0; border-bottom: 1px solid var(--border-color); font-size: 14px;">
                            <strong><?php echo htmlspecialchars(ucfirst($a['action'])); ?></strong> <?php echo htmlspecialchars($a['entity_type']); ?>
                            <?php if (!empty($a['details'])): ?> — <span style="color: var(--text-muted);"><?php echo htmlspecialchars($a['details']); ?></span><?php endif; ?>
                            <br><small style="color: var(--text-muted);"><?php echo date('M j, Y g:i A', strtotime($a['created_at'])); ?></small>
                        </li>
                    <?php endwhile; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- Update Profile Form -->
            <div class="card">
                <h3>Update Profile Information</h3>
                <form method="POST" action="api/update_profile.php" data-loading-message="Updating profile..." data-loading-subtext="Saving profile information.">
                    <table>
                        <tr>
                            <td>Full Name</td>
                            <td><input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" style="width:100%; padding:8px;" required></td>
                        </tr>
                        <tr>
                            <td>Email Address</td>
                            <td><input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" style="width:100%; padding:8px;"></td>
                        </tr>
                        <tr>
                            <td>Phone Number</td>
                            <td><input type="tel" name="phone_number" value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>" style="width:100%; padding:8px;" placeholder="+63 XXX XXX XXXX"></td>
                        </tr>
                        <tr>
                            <td>Address</td>
                            <td><textarea name="address" style="width:100%; padding:8px; min-height: 80px;" placeholder="Enter your address"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea></td>
                        </tr>
                        <tr>
                            <td>Birth Date</td>
                            <td><input type="date" name="birth_date" value="<?php echo !empty($user['birth_date']) ? $user['birth_date'] : ''; ?>" style="width:100%; padding:8px;"></td>
                        </tr>
                        <?php if ($_SESSION['role'] == 'delivery' || $_SESSION['role'] == 'driver'): ?>
                        <tr>
                            <td>Vehicle Type</td>
                            <td>
                                <select name="vehicle_type" style="width:100%; padding:8px;">
                                    <option value="">-- Select Vehicle --</option>
                                    <option value="Motorcycle" <?php echo (($user['vehicle_type'] ?? '') === 'Motorcycle') ? 'selected' : ''; ?>>Motorcycle / Bike</option>
                                    <option value="Car" <?php echo (($user['vehicle_type'] ?? '') === 'Car') ? 'selected' : ''; ?>>Car / Sedan / Van</option>
                                    <option value="Truck" <?php echo (($user['vehicle_type'] ?? '') === 'Truck') ? 'selected' : ''; ?>>Truck / Lorry</option>
                                    <option value="Other" <?php echo (($user['vehicle_type'] ?? '') === 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                                <small style="color: var(--text-muted); font-size: 12px;">Used for GPS map markers on the Live Delivery Tracking page.</small>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td>Username</td>
                            <td>
                                <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" style="width:100%; padding:8px; background: var(--bg-tertiary); border: 1px solid var(--border-color);" readonly>
                                <small style="color: var(--text-muted); font-size: 12px;">Username cannot be changed. Contact administrator if needed.</small>
                            </td>
                        </tr>
                        <tr>
                            <td>Role</td>
                            <td>
                                <input type="text" value="<?php echo htmlspecialchars(ucfirst($user['role'])); ?>" style="width:100%; padding:8px; background: var(--bg-tertiary); border: 1px solid var(--border-color);" disabled>
                                <small style="color: var(--text-muted); font-size: 12px;">Role cannot be changed. Contact administrator if needed.</small>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="text-align:right;">
                                <button type="submit" class="btn">Save Changes</button>
                            </td>
                        </tr>
                    </table>
                </form>
            </div>

            <!-- Change Password -->
            <div class="card">
                <h3>Change Password</h3>
                <form method="POST" action="api/change_password.php" data-loading-message="Changing password..." data-loading-subtext="Updating your password.">
                    <table>
                        <tr>
                            <td>Current Password</td>
                            <td><input type="password" name="current_password" style="width:100%; padding:8px;" required></td>
                        </tr>
                        <tr>
                            <td>New Password</td>
                            <td><input type="password" name="new_password" id="new_password" style="width:100%; padding:8px;" required minlength="6"></td>
                        </tr>
                        <tr>
                            <td>Confirm New Password</td>
                            <td><input type="password" name="confirm_password" style="width:100%; padding:8px;" required minlength="6"></td>
                        </tr>
                        <tr>
                            <td colspan="2" style="text-align:right;">
                                <button type="submit" class="btn">Change Password</button>
                            </td>
                        </tr>
                    </table>
                </form>
            </div>

        </div>

        <!-- Footer -->
        <?php include "layouts/footer.php"; ?>

    </div>

</div>

<script src="assets/js/sidebar.js"></script>
<script>
// Validate password confirmation
document.querySelector('form[action="api/change_password.php"]').addEventListener('submit', function(e) {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.querySelector('input[name="confirm_password"]').value;
    
    if (newPassword !== confirmPassword) {
        e.preventDefault();
        alert('New password and confirm password do not match!');
        return false;
    }
    
    if (newPassword.length < 6) {
        e.preventDefault();
        alert('Password must be at least 6 characters long!');
        return false;
    }
});
</script>

</body>
</html>
