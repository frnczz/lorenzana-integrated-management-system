<?php
session_start();
if (!isset($_SESSION['user_id']) || (!in_array($_SESSION['role'], ['admin', 'warehouse', 'production']))) {
    header("Location: login.php");
    exit;
}
include "includes/functions.php";
include "db_connect.php";

// Load settings
$near_expiry_days = intval(getSetting($conn, 'warehouse_settings', 'expiry_warning_days', 30, true));
$stock_method = getSetting($conn, 'warehouse_settings', 'stock_method', 'FIFO', true);

// Pagination for raw materials batches
$rmb_pagination = function_exists('getPagination')
    ? getPagination($conn, "SELECT COUNT(*) as c FROM raw_material_batches", null, 'rmb_page', 'rmb_per_page')
    : ['offset' => 0, 'per_page' => 25, 'total' => 0, 'total_pages' => 1, 'page' => 1, 'prev_page' => null, 'next_page' => null];

// Pagination for finished goods batches
$fgb_pagination = function_exists('getPagination')
    ? getPagination($conn, "SELECT COUNT(*) as c FROM finished_goods_batches", null, 'fgb_page', 'fgb_per_page')
    : ['offset' => 0, 'per_page' => 25, 'total' => 0, 'total_pages' => 1, 'page' => 1, 'prev_page' => null, 'next_page' => null];

// Load sort parameters
$sort_rmb = getSortParams('received_date', ['batch_number', 'material_name', 'quantity_remaining', 'expiry_date', 'received_date']);
$sort_fgb = getSortParams('production_date', ['batch_number', 'product_name', 'quantity_remaining', 'expiry_date', 'production_date']);

// Raw materials batches query
$rmb_query = "
    SELECT rmb.*, rm.material_name, rm.category, rm.unit,
           s.supplier_name,
           DATEDIFF(rmb.expiry_date, CURDATE()) as days_to_expiry
    FROM raw_material_batches rmb
    LEFT JOIN raw_materials rm ON rmb.material_id = rm.material_id
    LEFT JOIN suppliers s ON rmb.supplier_id = s.supplier_id
    ORDER BY " . ($sort_rmb['column'] == 'material_name' ? 'rm.material_name' : 'rmb.' . $sort_rmb['column']) . " " . $sort_rmb['order'] . "
    LIMIT " . $rmb_pagination['offset'] . ", " . $rmb_pagination['per_page'];

$rmb_result = $conn->query($rmb_query);

// Finished goods batches query
$fgb_query = "
    SELECT fgb.*, p.product_name, p.category_id,
           DATEDIFF(fgb.expiry_date, CURDATE()) as days_to_expiry
    FROM finished_goods_batches fgb
    LEFT JOIN products p ON fgb.product_id = p.product_id
    ORDER BY " . ($sort_fgb['column'] == 'product_name' ? 'p.product_name' : 'fgb.' . $sort_fgb['column']) . " " . $sort_fgb['order'] . "
    LIMIT " . $fgb_pagination['offset'] . ", " . $fgb_pagination['per_page'];

$fgb_result = $conn->query($fgb_query);

// Near expiry batches (both raw and finished)
$near_expiry_batches = $conn->query("
    SELECT 'raw' as type, rmb.batch_number, rm.material_name as item_name, rmb.quantity_remaining, rmb.expiry_date, rmb.warehouse_location,
           DATEDIFF(rmb.expiry_date, CURDATE()) as days_to_expiry
    FROM raw_material_batches rmb
    LEFT JOIN raw_materials rm ON rmb.material_id = rm.material_id
    WHERE rmb.expiry_date IS NOT NULL AND rmb.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL $near_expiry_days DAY)
    UNION ALL
    SELECT 'finished' as type, fgb.batch_number, p.product_name as item_name, fgb.quantity_remaining, fgb.expiry_date, fgb.warehouse_location,
           DATEDIFF(fgb.expiry_date, CURDATE()) as days_to_expiry
    FROM finished_goods_batches fgb
    LEFT JOIN products p ON fgb.product_id = p.product_id
    WHERE fgb.expiry_date IS NOT NULL AND fgb.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL $near_expiry_days DAY)
    ORDER BY expiry_date ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Batch Level Inventory | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .batch-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .batch-card h3 {
            margin: 0 0 10px 0;
            font-size: 16px;
            opacity: 0.9;
        }
        .batch-card p {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
        }
        .expiry-warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .table-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            overflow: hidden;
            margin-bottom: 25px;
        }
        .table-container h3 {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            margin: 0;
            padding: 15px 20px;
            font-size: 18px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #5568d3;
        }
        table td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }
        table tbody tr:hover {
            background-color: #f8f9ff;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-safe { background: #d1fae5; color: #065f46; }
        .status-warning { background: #fef3c7; color: #92400e; }
        .status-expired { background: #fee2e2; color: #991b1b; }
        .batch-number {
            font-weight: 600;
            color: #667eea;
        }
        .expiry-info {
            font-size: 12px;
            color: #6b7280;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <?php include "layouts/sidebar.php"; ?>
    <div class="main">
        <?php include "layouts/header.php"; ?>
        <div class="content">
            <h2>Batch Level Inventory Tracking</h2>
            <p>Track inventory by individual batches for proper FEFO (First Expired First Out) management.</p>
            <?php showMessage(); ?>

            <!-- Summary Cards -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <?php
                $total_rmb = $conn->query("SELECT COUNT(*) as c FROM raw_material_batches WHERE quantity_remaining > 0")->fetch_assoc()['c'] ?? 0;
                $total_fgb = $conn->query("SELECT COUNT(*) as c FROM finished_goods_batches WHERE quantity_remaining > 0")->fetch_assoc()['c'] ?? 0;
                $near_expiry_count = $near_expiry_batches ? $near_expiry_batches->num_rows : 0;
                ?>
                <div class="batch-card">
                    <h3>Active Raw Material Batches</h3>
                    <p><?php echo number_format($total_rmb); ?></p>
                </div>
                <div class="batch-card">
                    <h3>Active Finished Goods Batches</h3>
                    <p><?php echo number_format($total_fgb); ?></p>
                </div>
                <div class="batch-card expiry-warning">
                    <h3>Near Expiry Batches</h3>
                    <p><?php echo number_format($near_expiry_count); ?></p>
                    <small>Within <?php echo $near_expiry_days; ?> days</small>
                </div>
                <div class="batch-card">
                    <h3>Stock Method</h3>
                    <p><?php echo htmlspecialchars($stock_method); ?></p>
                    <small><?php echo $stock_method == 'FEFO' ? 'Expiry-based' : 'Time-based'; ?></small>
                </div>
            </div>

            <!-- Raw Materials Batches -->
            <div class="table-container">
                <h3>📦 Raw Materials Batches</h3>
                <?php if (function_exists('renderPerPageSelector')) echo '<div class="pagination-toolbar" style="margin: 15px 20px;">' . renderPerPageSelector($conn, $rmb_pagination['per_page'], 'rmb_per_page', 'rmb_page') . '</div>'; ?>

                <table>
                    <thead>
                        <tr>
                            <th><?php echo sortHeader('batch_number', 'Batch #', $sort_rmb); ?></th>
                            <th><?php echo sortHeader('material_name', 'Material', $sort_rmb); ?></th>
                            <th><?php echo sortHeader('quantity_remaining', 'Remaining Qty', $sort_rmb); ?></th>
                            <th>Unit</th>
                            <th><?php echo sortHeader('expiry_date', 'Expiry Date', $sort_rmb); ?></th>
                            <th><?php echo sortHeader('received_date', 'Received', $sort_rmb); ?></th>
                            <th>Supplier</th>
                            <th>Location</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($rmb_result && $rmb_result->num_rows > 0): ?>
                            <?php while ($batch = $rmb_result->fetch_assoc()): ?>
                                <tr>
                                    <td><span class="batch-number"><?php echo htmlspecialchars($batch['batch_number']); ?></span></td>
                                    <td><?php echo htmlspecialchars($batch['material_name']); ?> <small style="color:#6b7280;">(<?php echo htmlspecialchars($batch['category']); ?>)</small></td>
                                    <td><strong><?php echo number_format($batch['quantity_remaining'], 2); ?></strong></td>
                                    <td><?php echo htmlspecialchars($batch['unit']); ?></td>
                                    <td>
                                        <?php echo $batch['expiry_date'] ? formatDate($batch['expiry_date']) : '-'; ?>
                                        <?php if ($batch['days_to_expiry'] !== null): ?>
                                            <div class="expiry-info">
                                                <?php if ($batch['days_to_expiry'] < 0): ?>
                                                    <span class="status-expired">Expired <?php echo abs($batch['days_to_expiry']); ?> days ago</span>
                                                <?php elseif ($batch['days_to_expiry'] <= $near_expiry_days): ?>
                                                    <span class="status-warning"><?php echo $batch['days_to_expiry']; ?> days left</span>
                                                <?php else: ?>
                                                    <span class="status-safe"><?php echo $batch['days_to_expiry']; ?> days left</span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo formatDate($batch['received_date']); ?></td>
                                    <td><?php echo htmlspecialchars($batch['supplier_name'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($batch['warehouse_location'] ?? '-'); ?></td>
                                    <td>
                                        <?php if ($batch['quantity_remaining'] <= 0): ?>
                                            <span class="status-badge status-expired">Depleted</span>
                                        <?php elseif ($batch['days_to_expiry'] < 0): ?>
                                            <span class="status-badge status-expired">Expired</span>
                                        <?php elseif ($batch['days_to_expiry'] <= $near_expiry_days): ?>
                                            <span class="status-badge status-warning">Near Expiry</span>
                                        <?php else: ?>
                                            <span class="status-badge status-safe">Active</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                    No raw material batches found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <?php if (function_exists('renderPagination')) echo renderPagination($rmb_pagination, 'rmb_page'); ?>
            </div>

            <!-- Finished Goods Batches -->
            <div class="table-container">
                <h3>🏭 Finished Goods Batches</h3>
                <?php if (function_exists('renderPerPageSelector')) echo '<div class="pagination-toolbar" style="margin: 15px 20px;">' . renderPerPageSelector($conn, $fgb_pagination['per_page'], 'fgb_per_page', 'fgb_page') . '</div>'; ?>

                <table>
                    <thead>
                        <tr>
                            <th><?php echo sortHeader('batch_number', 'Batch #', $sort_fgb); ?></th>
                            <th><?php echo sortHeader('product_name', 'Product', $sort_fgb); ?></th>
                            <th><?php echo sortHeader('quantity_remaining', 'Remaining Qty', $sort_fgb); ?></th>
                            <th><?php echo sortHeader('expiry_date', 'Expiry Date', $sort_fgb); ?></th>
                            <th><?php echo sortHeader('production_date', 'Produced', $sort_fgb); ?></th>
                            <th>Location</th>
                            <th>QC Status</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($fgb_result && $fgb_result->num_rows > 0): ?>
                            <?php while ($batch = $fgb_result->fetch_assoc()): ?>
                                <tr>
                                    <td><span class="batch-number"><?php echo htmlspecialchars($batch['batch_number']); ?></span></td>
                                    <td><?php echo htmlspecialchars($batch['product_name']); ?></td>
                                    <td><strong><?php echo number_format($batch['quantity_remaining'], 2); ?></strong></td>
                                    <td>
                                        <?php echo $batch['expiry_date'] ? formatDate($batch['expiry_date']) : '-'; ?>
                                        <?php if ($batch['days_to_expiry'] !== null): ?>
                                            <div class="expiry-info">
                                                <?php if ($batch['days_to_expiry'] < 0): ?>
                                                    <span class="status-expired">Expired <?php echo abs($batch['days_to_expiry']); ?> days ago</span>
                                                <?php elseif ($batch['days_to_expiry'] <= $near_expiry_days): ?>
                                                    <span class="status-warning"><?php echo $batch['days_to_expiry']; ?> days left</span>
                                                <?php else: ?>
                                                    <span class="status-safe"><?php echo $batch['days_to_expiry']; ?> days left</span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo formatDate($batch['production_date']); ?></td>
                                    <td><?php echo htmlspecialchars($batch['warehouse_location'] ?? '-'); ?></td>
                                    <td>
                                        <?php if ($batch['qc_approved']): ?>
                                            <span class="status-badge status-safe">Approved</span>
                                        <?php else: ?>
                                            <span class="status-badge status-warning">Pending QC</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($batch['quantity_remaining'] <= 0): ?>
                                            <span class="status-badge status-expired">Depleted</span>
                                        <?php elseif (!$batch['qc_approved']): ?>
                                            <span class="status-badge status-warning">Pending QC</span>
                                        <?php elseif ($batch['days_to_expiry'] < 0): ?>
                                            <span class="status-badge status-expired">Expired</span>
                                        <?php elseif ($batch['days_to_expiry'] <= $near_expiry_days): ?>
                                            <span class="status-badge status-warning">Near Expiry</span>
                                        <?php else: ?>
                                            <span class="status-badge status-safe">Active</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                    No finished goods batches found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <?php if (function_exists('renderPagination')) echo renderPagination($fgb_pagination, 'fgb_page'); ?>
            </div>

            <!-- Near Expiry Batches Alert -->
            <?php if ($near_expiry_batches && $near_expiry_batches->num_rows > 0): ?>
            <div class="table-container" style="border-left: 4px solid #f59e0b;">
                <h3 style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">⏰ Near Expiry Batches (Next <?php echo $near_expiry_days; ?> Days)</h3>
                <p style="margin: 15px 20px; color: #92400e;">These batches are approaching expiry and should be prioritized for use according to <?php echo $stock_method; ?> policy.</p>

                <table>
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Batch #</th>
                            <th>Item</th>
                            <th>Remaining Qty</th>
                            <th>Expiry Date</th>
                            <th>Days Left</th>
                            <th>Location</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($batch = $near_expiry_batches->fetch_assoc()): ?>
                            <tr class="<?php echo $batch['days_to_expiry'] <= 7 ? 'table-warning' : ''; ?>">
                                <td>
                                    <span class="status-badge <?php echo $batch['type'] == 'raw' ? 'status-safe' : 'status-warning'; ?>">
                                        <?php echo $batch['type'] == 'raw' ? 'Raw Material' : 'Finished Good'; ?>
                                    </span>
                                </td>
                                <td><span class="batch-number"><?php echo htmlspecialchars($batch['batch_number']); ?></span></td>
                                <td><?php echo htmlspecialchars($batch['item_name']); ?></td>
                                <td><strong><?php echo number_format($batch['quantity_remaining'], 2); ?></strong></td>
                                <td><?php echo formatDate($batch['expiry_date']); ?></td>
                                <td>
                                    <?php if ($batch['days_to_expiry'] <= 0): ?>
                                        <span class="status-badge status-expired">Expired</span>
                                    <?php elseif ($batch['days_to_expiry'] <= 7): ?>
                                        <span class="status-badge status-expired"><?php echo $batch['days_to_expiry']; ?> days</span>
                                    <?php else: ?>
                                        <span class="status-badge status-warning"><?php echo $batch['days_to_expiry']; ?> days</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($batch['warehouse_location'] ?? '-'); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

        </div>
        <?php include "layouts/footer.php"; ?>
    </div>
</div>

<!-- Expiry Alert Modal -->
<!-- <div id="expiryModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3 style="margin: 0; color: #dc2626;">⚠️ Expired Inventory Alert</h3>
            <span class="modal-close" onclick="closeExpiryModal()">&times;</span>
        </div>
        <div class="modal-body" style="padding: 20px;">
            <p id="expiryMessage" style="margin-bottom: 20px; line-height: 1.5;"></p>
            <div id="expiredBatchesList" style="max-height: 200px; overflow-y: auto; margin-bottom: 20px;"></div>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button onclick="closeExpiryModal()" class="btn" style="background: #6b7280;">Keep Inventory</button>
                <button onclick="removeExpiredInventory()" class="btn" style="background: #dc2626; color: white;">Remove Expired</button>
            </div>
        </div>
    </div>
</div> -->

<script src="assets/js/sidebar.js"></script>
<script>
// Check for expired batches on page load
document.addEventListener('DOMContentLoaded', function() {
    checkExpiredBatches();
});

function checkExpiredBatches() {
    fetch('api/get_expired_batches.php')
        .then(response => response.json())
        .then(data => {
            if (data.expired_batches && data.expired_batches.length > 0) {
                showExpiryModal(data.expired_batches);
            }
        })
        .catch(error => console.error('Error checking expired batches:', error));
}

function showExpiryModal(expiredBatches) {
    const modal = document.getElementById('expiryModal');
    const messageEl = document.getElementById('expiryMessage');
    const listEl = document.getElementById('expiredBatchesList');

    let totalExpired = expiredBatches.length;
    let rawCount = expiredBatches.filter(b => b.type === 'raw').length;
    let finishedCount = expiredBatches.filter(b => b.type === 'finished').length;

    messageEl.innerHTML = `You have <strong>${totalExpired}</strong> expired batch${totalExpired > 1 ? 'es' : ''} in your inventory ` +
                         `(${rawCount} raw material${rawCount > 1 ? 's' : ''}, ${finishedCount} finished good${finishedCount > 1 ? 's' : ''}). ` +
                         `These items should be removed from inventory according to food safety regulations.`;

    listEl.innerHTML = expiredBatches.map(batch => `
        <div style="padding: 8px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between;">
            <div>
                <strong>${batch.batch_number}</strong> - ${batch.item_name}
                <br><small style="color: #6b7280;">${batch.type === 'raw' ? 'Raw Material' : 'Finished Good'} • ${batch.quantity_remaining} remaining</small>
            </div>
            <div style="color: #dc2626; font-weight: 600;">
                Expired ${Math.abs(batch.days_to_expiry)} days ago
            </div>
        </div>
    `).join('');

    modal.style.display = 'block';
}

function closeExpiryModal() {
    document.getElementById('expiryModal').style.display = 'none';
}

function removeExpiredInventory() {
    if (!confirm('Are you sure you want to remove all expired inventory? This action cannot be undone.')) {
        return;
    }

    fetch('api/remove_expired_inventory.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`Successfully removed ${data.removed_count} expired batches from inventory.`);
            closeExpiryModal();
            location.reload(); // Refresh the page to show updated inventory
        } else {
            alert('Error removing expired inventory: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error removing expired inventory. Please try again.');
    });
}
</script>

<style>
.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-close {
    font-size: 24px;
    cursor: pointer;
    color: #6b7280;
}

.modal-close:hover {
    color: #374151;
}

.modal-body {
    padding: 20px;
}
</style>

</body>
</html>