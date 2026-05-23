<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'warehouse')) {
    header("Location: login.php");
    exit;
}
include "includes/functions.php";
include "db_connect.php";

// Load sort parameters
$sort = getSortParams('material_name', ['material_code', 'material_name', 'category', 'quantity', 'unit', 'min_stock_level', 'expiry_date', 'warehouse_location']);

// Map display columns to database columns
$column_map = [
    'material_code' => 'material_code',
    'material_name' => 'material_name',
    'category' => 'category',
    'quantity' => 'quantity',
    'unit' => 'unit',
    'min_stock_level' => 'min_stock_level',
    'expiry_date' => 'expiry_date',
    'warehouse_location' => 'warehouse_location'
];

$order_by = isset($column_map[$sort['column']]) ? $column_map[$sort['column']] : 'material_name';

$pagination = function_exists('getPagination') ? getPagination($conn, "SELECT COUNT(*) as c FROM raw_materials") : ['offset' => 0, 'per_page' => 25];
$raw_materials = [];
$materials_query = $conn->query("
    SELECT material_id, material_name, category, quantity, unit, expiry_date, 
           warehouse_location, min_stock_level, material_code, created_at
    FROM raw_materials
    ORDER BY " . $order_by . " " . $sort['order'] . "
    LIMIT " . $pagination['offset'] . ", " . $pagination['per_page']
);
if ($materials_query) {
    while ($row = $materials_query->fetch_assoc()) {
        $raw_materials[] = $row;
    }
}

// Get unique categories for dropdown
$categories = [];
$cat_query = $conn->query("SELECT DISTINCT category FROM raw_materials WHERE category IS NOT NULL ORDER BY category");
if ($cat_query) {
    while ($row = $cat_query->fetch_assoc()) {
        $categories[] = $row['category'];
    }
}
// Add common categories if not present
$common_categories = ['Sugar', 'Salt', 'Soybeans', 'Vinegar Base', 'Spices', 'Packaging', 'Other'];
foreach ($common_categories as $cat) {
    if (!in_array($cat, $categories)) {
        $categories[] = $cat;
    }
}
sort($categories);

// Recently used raw materials (by production batch usage)
$recent_materials = [];

// Filtering by date range (optional)
$recent_start_date = $_GET['recent_start_date'] ?? '';
$recent_end_date = $_GET['recent_end_date'] ?? '';

$dateCondition = '';
if ($recent_start_date) {
    $dateCondition .= " AND bd.created_at >= '" . $conn->real_escape_string($recent_start_date) . " 00:00:00'";
}
if ($recent_end_date) {
    $dateCondition .= " AND bd.created_at <= '" . $conn->real_escape_string($recent_end_date) . " 23:59:59'";
}

$recent_query = $conn->query(
    "SELECT rm.material_id, rm.material_name, rm.category, rm.unit, " .
    "SUM(bd.quantity_used) AS total_used, " .
    "MAX(bd.created_at) AS last_used, " .
    "COUNT(DISTINCT bd.batch_id) AS batches_used " .
    "FROM batch_details bd " .
    "INNER JOIN raw_materials rm ON bd.material_id = rm.material_id " .
    "INNER JOIN production_batches pb ON bd.batch_id = pb.batch_id " .
    "WHERE 1=1 " . $dateCondition . " " .
    "GROUP BY rm.material_id " .
    "ORDER BY last_used DESC " .
    "LIMIT 25"
);
if ($recent_query) {
    while ($row = $recent_query->fetch_assoc()) {
        $recent_materials[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Raw Materials Management | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Form Styling */
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 25px;
            padding: 15px;
            transition: box-shadow 0.2s ease;
        }

        .card:hover {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.08);
        }

        .card h3 {
            margin-top: 0;
            margin-bottom: 12px;
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 8px;
        }

        #materialForm table {
            width: 100%;
        }

        #materialForm table tr {
            margin: 0;
        }

        #materialForm table td {
            padding: 8px 0;
            border-bottom: 1px solid #f3f4f6;
        }

        #materialForm table tr:last-child td {
            border-bottom: none;
            padding-top: 12px;
            border-top: 2px solid #f3f4f6;
        }

        #materialForm label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 4px;
            font-size: 13px;
        }

        #materialForm input[type="text"],
        #materialForm input[type="number"],
        #materialForm input[type="date"],
        #materialForm select {
            width: 100% !important;
            padding: 7px 10px !important;
            border: 1px solid #d1d5db !important;
            border-radius: 5px !important;
            font-size: 13px;
            font-family: inherit;
            background-color: #fff;
            transition: all 0.2s ease;
        }

        #materialForm input[type="text"]:focus,
        #materialForm input[type="number"]:focus,
        #materialForm input[type="date"]:focus,
        #materialForm select:focus {
            outline: none;
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
            background-color: #f0f9ff;
        }

        #materialForm small {
            display: block;
            margin-top: 2px;
            font-size: 11px;
            color: #6b7280;
        }

        /* Button Styling */
        .btn {
            padding: 7px 12px;
            border: none;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-block;
        }

        #materialForm .btn {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            margin-right: 8px;
            box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2);
        }

        #materialForm .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(59, 130, 246, 0.3);
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        }

        #materialForm .btn:active {
            transform: translateY(0);
        }

        /* Table Styling */
        .materials-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0;
            overflow: hidden;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            background: white;
        }

        .materials-table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .materials-table th {
            padding: 14px 12px;
            text-align: left;
            border-bottom: 2px solid #5568d3;
            font-weight: 700;
            color: white;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        .materials-table td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
            color: #374151;
        }

        .materials-table tbody tr {
            transition: all 0.2s ease;
        }

        .materials-table tbody tr:hover:not(.category-header) {
            background-color: #f8f9ff;
        }

        /* Category Header */
        .category-header {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            font-weight: 700;
            border-bottom: 2px solid #d1d5db;
        }

        .category-header td {
            padding: 14px 12px;
        }

        .category-header span {
            color: #374151;
            font-size: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .category-header .btn {
            padding: 6px 10px;
            font-size: 12px;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .category-header .btn:hover {
            background: #dc2626;
        }

        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-align: center;
            min-width: 80px;
            white-space: nowrap;
        }

        .status-low {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #dc2626;
        }

        .status-safe {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .status-warning {
            background: #fef3c7;
            color: #92400e;
            border-left: 4px solid #f59e0b;
        }

        .status-expired {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #dc2626;
            font-weight: 700;
        }

        /* Action Buttons */
        .edit-btn,
        .delete-btn {
            padding: 7px 14px;
            font-size: 12px;
            font-weight: 600;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 6px;
            transition: all 0.2s ease;
            display: inline-block;
            white-space: nowrap;
        }

        .edit-btn {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2);
        }

        .edit-btn:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(59, 130, 246, 0.3);
        }

        .edit-btn:active {
            transform: translateY(0);
        }

        .delete-btn {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            box-shadow: 0 2px 4px rgba(220, 38, 38, 0.2);
        }

        .delete-btn:hover {
            background: linear-gradient(135deg, #b91c1c 0%, #991b1b 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(220, 38, 38, 0.3);
        }

        .delete-btn:active {
            transform: translateY(0);
        }

        /* Row highlighting for status */
        .expired-row {
            background-color: #fef2f2;
            border-left: 4px solid #dc2626;
        }

        .expired-row:hover {
            background-color: #fee2e2;
        }

        .low-stock-row {
            background-color: #fffbeb;
            border-left: 4px solid #f59e0b;
        }

        .low-stock-row:hover {
            background-color: #fef3c7;
        }

        /* Empty state */
        .materials-table tbody tr:only-child td {
            text-align: center;
            color: #9ca3af;
            padding: 50px 20px;
            font-style: italic;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .materials-table {
                font-size: 12px;
            }

            .materials-table th,
            .materials-table td {
                padding: 10px 8px;
            }

            .edit-btn,
            .delete-btn {
                padding: 5px 10px;
                font-size: 11px;
                margin-right: 4px;
            }
        }

        @media (max-width: 768px) {
            #materialForm table,
            #materialForm table tr,
            #materialForm table td {
                display: block;
                width: 100%;
            }

            #materialForm table tr:first-child {
                margin-top: 0;
            }

            #materialForm table td {
                padding: 12px 0;
                border: none;
                border-bottom: 1px solid #e5e7eb;
            }

            #materialForm table td:first-child {
                display: none;
            }

            .materials-table {
                font-size: 12px;
                overflow-x: auto;
                display: block;
            }

            .materials-table tbody,
            .materials-table thead {
                display: block;
            }

            .materials-table th,
            .materials-table td {
                padding: 8px;
            }

            .category-header {
                flex-direction: column;
                gap: 10px;
            }

            .category-header .btn {
                width: 100%;
            }

            .edit-btn,
            .delete-btn {
                padding: 6px 10px;
                font-size: 11px;
                margin-right: 4px;
                margin-bottom: 4px;
            }
        }
    </style>
</head>
<body>
<div class="wrapper">
    <?php include "layouts/sidebar.php"; ?>
    <div class="main">
        <?php include "layouts/header.php"; ?>
        <div class="content">
            <h2 style="margin-bottom: 3px; color: #1f2937; font-size: 24px;">📦 Raw Materials Management</h2>
            <p style="color: #6b7280; font-size: 13px; margin-bottom: 15px;">
                Create and manage all raw materials used in production.
            </p>
            <?php showMessage(); ?>

            <!-- Add/Edit Raw Material Form -->
            <div class="card">
                <h3 id="form_title">Add New Raw Material</h3>
                <form method="POST" action="api/save_raw_material.php" id="materialForm">
                    <input type="hidden" name="material_id" id="material_id" value="">
                    <table>
                        <tr>
                            <td>
                                <label for="material_name">Material Name</label>
                                <input type="text" name="material_name" id="material_name" required 
                                       placeholder="e.g., Sugar, Salt, Soybeans">
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label for="category">Category</label>
                                <select name="category" id="category" required>
                                    <option value="">-- Select Category --</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat); ?>">
                                            <?php echo htmlspecialchars($cat); ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <option value="__new__">+ Add New Category</option>
                                </select>
                                <input type="text" name="new_category" id="new_category" 
                                       style="margin-top:8px; display:none;" 
                                       placeholder="Enter new category name">
                                <small style="color: var(--text-muted);">Create a new category if needed</small>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label for="quantity">Quantity</label>
                                <input type="number" name="quantity" id="quantity" 
                                       step="0.01" min="0" required placeholder="0.00">
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label for="unit">Unit of Measurement</label>
                                <select name="unit" id="unit" required>
                                    <option value="kg">kg (Kilogram)</option>
                                    <option value="g">g (Gram)</option>
                                    <option value="liters">liters (Liters)</option>
                                    <option value="ml">ml (Milliliters)</option>
                                    <option value="pcs">pcs (Pieces)</option>
                                    <option value="boxes">boxes (Boxes)</option>
                                    <option value="bags">bags (Bags)</option>
                                    <option value="sacks">sacks (Sacks)</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label for="min_stock_level">Minimum Stock Level</label>
                                <input type="number" name="min_stock_level" id="min_stock_level" 
                                       step="0.01" min="0" value="0" placeholder="0.00">
                                <small style="color: var(--text-muted);">⚠️ Alert when stock falls below this level</small>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label for="expiry_date">Expiry Date</label>
                                <input type="date" name="expiry_date" id="expiry_date">
                                <small style="color: var(--text-muted);">📅 Optional - only for perishable items</small>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label for="warehouse_location">Warehouse Location</label>
                                <input type="text" name="warehouse_location" id="warehouse_location" 
                                       placeholder="e.g., Lot 6720 Brgy San Joaquin Sto Tomas Batangas">
                                <small style="color: var(--text-muted);">📍 Where this material is stored</small>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="text-align:right; padding-top: 25px; border-top: 2px solid #f3f4f6;">
                                <button type="submit" class="btn" id="submit_btn">✓ Add Material</button>
                                <button type="button" class="btn" onclick="resetForm()" style="background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%); box-shadow: 0 2px 4px rgba(107, 114, 128, 0.2); margin-left:10px;">↻ Reset</button>
                                <button type="button" class="btn" onclick="cancelEdit()" id="cancel_btn" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); box-shadow: 0 2px 4px rgba(139, 92, 246, 0.2); margin-left:10px; display:none;">✕ Cancel</button>
                            </td>
                        </tr>
                    </table>
                </form>
            </div>

            <!-- Raw Materials Table -->
            <div class="card">
                <h3>All Raw Materials</h3>
                <?php if (function_exists('renderPerPageSelector')) echo '<div class="pagination-toolbar">' . renderPerPageSelector($conn, $pagination['per_page']) . '</div>'; ?>
                <table class="materials-table">
                    <thead>
                        <tr>
                            <th><?php echo sortHeader('material_code', 'Material Code', $sort); ?></th>
                            <th><?php echo sortHeader('material_name', 'Material Name', $sort); ?></th>
                            <th><?php echo sortHeader('category', 'Category', $sort); ?></th>
                            <th><?php echo sortHeader('quantity', 'Quantity', $sort); ?></th>
                            <th><?php echo sortHeader('unit', 'Unit', $sort); ?></th>
                            <th><?php echo sortHeader('min_stock_level', 'Min Level', $sort); ?></th>
                            <th><?php echo sortHeader('expiry_date', 'Expiry Date', $sort); ?></th>
                            <th><?php echo sortHeader('warehouse_location', 'Location', $sort); ?></th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($raw_materials) > 0): ?>
                            <?php 
                            $current_category = '';
                            foreach ($raw_materials as $m): 
                                if ($current_category !== ($m['category'] ?? 'Uncategorized')):
                                    $current_category = $m['category'] ?? 'Uncategorized';
                            ?>
                                <tr class="category-header">
                                    <td colspan="10" style="font-weight: 600; padding: 10px 12px; display: flex; justify-content: space-between; align-items: center;">
                                        <span>📦 <?php echo htmlspecialchars($current_category); ?></span>
                                        <?php if ($current_category !== 'Uncategorized'): ?>
                                            <button type="button" class="btn" style="padding: 4px 10px; font-size: 11px; background: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer;" onclick="deleteCategory('<?php echo htmlspecialchars($current_category, ENT_QUOTES); ?>')">
                                                Delete Category
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endif; 
                            
                            // Determine status
                            $is_expired = false;
                            $is_low = false;
                            if ($m['expiry_date']) {
                                $exp_date = new DateTime($m['expiry_date']);
                                $today = new DateTime();
                                if ($exp_date < $today) {
                                    $is_expired = true;
                                }
                            }
                            if ($m['min_stock_level'] > 0 && $m['quantity'] <= $m['min_stock_level']) {
                                $is_low = true;
                            }
                            
                            $row_class = '';
                            $status_text = 'Available';
                            $status_class = 'status-safe';
                            if ($is_expired) {
                                $row_class = 'expired-row';
                                $status_text = 'Expired';
                                $status_class = 'status-expired';
                            } elseif ($is_low) {
                                $row_class = 'low-stock-row';
                                $status_text = 'Low Stock';
                                $status_class = 'status-low';
                            } elseif ($m['min_stock_level'] > 0 && $m['quantity'] < ($m['min_stock_level'] * 2)) {
                                $status_text = 'Moderate';
                                $status_class = 'status-warning';
                            }
                            ?>
                            <tr class="<?php echo $row_class; ?>">
                                <td><?php echo htmlspecialchars($m['material_code'] ?? '-'); ?></td>
                                <td><strong><?php echo htmlspecialchars($m['material_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($m['category'] ?? 'Uncategorized'); ?></td>
                                <td><strong><?php echo number_format($m['quantity'], 2); ?></strong></td>
                                <td><?php echo htmlspecialchars($m['unit']); ?></td>
                                <td><?php echo number_format($m['min_stock_level'], 2); ?></td>
                                <td>
                                    <?php if ($m['expiry_date']): ?>
                                        <?php echo formatDate($m['expiry_date']); ?>
                                        <?php if ($is_expired): ?>
                                            <br><small style="color: #dc2626;">⚠️ Expired</small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted);">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars(formatLocation($m['warehouse_location'] ?? null)); ?></td>
                                <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                                <td>
                                    <button type="button" class="edit-btn" 
                                            onclick="editMaterial(<?php echo htmlspecialchars(json_encode($m)); ?>)">
                                        Edit
                                    </button>
                                    <button type="button" class="delete-btn" 
                                            onclick="deleteMaterial(<?php echo $m['material_id']; ?>, '<?php echo htmlspecialchars($m['material_name'], ENT_QUOTES); ?>')">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" style="text-align:center; padding:40px; color:var(--text-muted);">
                                    No raw materials found. Add your first raw material above.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <?php if (function_exists('renderPagination')) echo renderPagination($pagination); ?>
            </div>

            <!-- Recently Used Raw Materials -->
            <div class="card">
                <h3>Recently Used Raw Materials (Production)</h3>
                <p style="margin-top:0; margin-bottom:8px; color: var(--text-muted);">Latest raw materials used in production batches (sorted by most recent use). Use the date range filters to narrow results.</p>

                <form method="GET" style="display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end; margin-bottom:14px;">
                    <div style="display:flex; flex-direction:column;">
                        <label for="recent_start_date" style="font-weight: 600; color: #334155; font-size: 12px;">Start Date</label>
                        <input type="date" name="recent_start_date" id="recent_start_date" value="<?php echo htmlspecialchars($recent_start_date); ?>" style="padding:8px; border-radius:8px; border:1px solid #d1d5db;">
                    </div>
                    <div style="display:flex; flex-direction:column;">
                        <label for="recent_end_date" style="font-weight: 600; color: #334155; font-size: 12px;">End Date</label>
                        <input type="date" name="recent_end_date" id="recent_end_date" value="<?php echo htmlspecialchars($recent_end_date); ?>" style="padding:8px; border-radius:8px; border:1px solid #d1d5db;">
                    </div>
                    <div style="display:flex; gap:8px;">
                        <button type="submit" class="btn" style="padding:8px 14px;">Filter</button>
                        <button type="button" class="btn" style="padding:8px 14px; background:#94a3b8;" onclick="resetRecentFilter()">Reset</button>
                    </div>
                    <div style="margin-left:auto; display:flex; gap:8px; align-items:center;">
                        <span style="font-size:12px; color:#64748b;">Quick:</span>
                        <button type="button" class="btn" style="padding:6px 10px; font-size:12px;" onclick="setRecentRange(7)">Last 7d</button>
                        <button type="button" class="btn" style="padding:6px 10px; font-size:12px;" onclick="setRecentRange(30)">Last 30d</button>
                        <button type="button" class="btn" style="padding:6px 10px; font-size:12px;" onclick="setRecentRange(90)">Last 90d</button>
                    </div>
                </form>

                <table class="materials-table">
                    <thead>
                        <tr>
                            <th>Raw Material</th>
                            <th>Category</th>
                            <th>Total Used</th>
                            <th>Units</th>
                            <th>Last Used</th>
                            <th>Batches</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($recent_materials) > 0): ?>
                            <?php foreach ($recent_materials as $m): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($m['material_name']); ?></td>
                                    <td><?php echo htmlspecialchars($m['category'] ?: 'Uncategorized'); ?></td>
                                    <td><?php echo number_format($m['total_used'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($m['unit']); ?></td>
                                    <td><?php echo formatDate($m['last_used']); ?></td>
                                    <td><?php echo (int)$m['batches_used']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align:center; padding:40px; color:var(--text-muted);">
                                    No recent material usage found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <script>
            function resetRecentFilter() {
                document.getElementById('recent_start_date').value = '';
                document.getElementById('recent_end_date').value = '';
                window.location.href = window.location.pathname;
            }

            function setRecentRange(days) {
                var today = new Date();
                var end = today.toISOString().slice(0, 10);
                var start = new Date(today.getTime() - (days - 1) * 24 * 60 * 60 * 1000).toISOString().slice(0, 10);
                document.getElementById('recent_start_date').value = start;
                document.getElementById('recent_end_date').value = end;
                document.querySelector('form').submit();
            }
            </script>
        </div>

        <?php include "layouts/footer.php"; ?>
        </div>
    </div>
</div>

<script src="assets/js/sidebar.js"></script>
<script>
// Handle new category option
document.getElementById('category').addEventListener('change', function() {
    var newCategoryInput = document.getElementById('new_category');
    if (this.value === '__new__') {
        newCategoryInput.style.display = 'block';
        newCategoryInput.required = true;
    } else {
        newCategoryInput.style.display = 'none';
        newCategoryInput.required = false;
        newCategoryInput.value = '';
    }
});

// Edit material function
function editMaterial(material) {
    document.getElementById('material_id').value = material.material_id;
    document.getElementById('material_name').value = material.material_name;
    document.getElementById('category').value = material.category || '';
    document.getElementById('quantity').value = material.quantity;
    document.getElementById('unit').value = material.unit || 'kg';
    document.getElementById('min_stock_level').value = material.min_stock_level || 0;
    document.getElementById('expiry_date').value = material.expiry_date || '';
    document.getElementById('warehouse_location').value = material.warehouse_location || '';
    
    // Update form title and button
    document.getElementById('form_title').textContent = 'Edit Raw Material';
    document.getElementById('submit_btn').textContent = 'Update Material';
    document.getElementById('cancel_btn').style.display = 'inline-block';
    
    // Scroll to form
    document.querySelector('.card').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// Delete material function
function deleteMaterial(materialId, materialName) {
    if (!confirm('Are you sure you want to delete "' + materialName + '"?\n\nThis action cannot be undone and may affect production batches that use this material.')) {
        return;
    }
    
    // Create form and submit
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = 'api/delete_raw_material.php';
    
    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'material_id';
    input.value = materialId;
    form.appendChild(input);
    
    document.body.appendChild(form);
    form.submit();
}

// Reset form
function resetForm() {
    document.getElementById('materialForm').reset();
    document.getElementById('material_id').value = '';
    document.getElementById('new_category').style.display = 'none';
    document.getElementById('form_title').textContent = 'Add New Raw Material';
    document.getElementById('submit_btn').textContent = 'Add Material';
    document.getElementById('cancel_btn').style.display = 'none';
}

// Cancel edit
function cancelEdit() {
    resetForm();
}

// Delete category function
function deleteCategory(categoryName) {
    var count = document.querySelectorAll('td:contains("' + categoryName + '")').length;
    if (!confirm('Delete category "' + categoryName + '"?\n\n⚠️ WARNING: This will move all materials in this category to "Uncategorized". This action cannot be undone.')) {
        return;
    }
    
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = 'api/delete_raw_material_category.php';
    
    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'category';
    input.value = categoryName;
    form.appendChild(input);
    
    document.body.appendChild(form);
    form.submit();
}
</script>
</body>
</html>
