<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'procurement' && $_SESSION['role'] != 'warehouse')) {
    header("Location: login.php");
    exit;
}
include "includes/functions.php";
include "db_connect.php";

$show_form = isset($_GET['new']) || isset($_GET['id']);
$pr_id = intval($_GET['id'] ?? 0);
$pr = null;
$pr_items = [];

// Department list
$departments = [
    'Production / Operations',
    'Inventory / Warehouse',
];

// Unit list for raw materials
$units = [
    'kg', 'liters', 'pieces', 'boxes', 'bags', 'cans', 'bottles', 'packs', 'rolls', 'sheets', 'meters', 'feet', 'tons', 'gallons'
];

// Load PR data if editing
if ($pr_id > 0) {
    $pr_query = $conn->prepare("SELECT * FROM purchase_requisitions WHERE pr_id = ?");
    $pr_query->bind_param("i", $pr_id);
    $pr_query->execute();
    $pr = $pr_query->get_result()->fetch_assoc();
    $pr_query->close();
    
    if ($pr) {
        $items_query = $conn->prepare("SELECT * FROM pr_items WHERE pr_id = ? ORDER BY pr_item_id");
        $items_query->bind_param("i", $pr_id);
        $items_query->execute();
        $items_result = $items_query->get_result();
        while ($row = $items_result->fetch_assoc()) {
            $pr_items[] = $row;
        }
        $items_query->close();
    }
}

// Fetch raw materials
$raw_materials = [];
$materials_query = $conn->query("SELECT material_id, material_name, category, unit FROM raw_materials ORDER BY material_name");
if ($materials_query) {
    while ($row = $materials_query->fetch_assoc()) {
        $raw_materials[] = $row;
    }
}

// Fetch purchase requisitions
$requisitions = [];
$status_filter = $_GET['status'] ?? '';
$where_clause = $status_filter ? "WHERE pr.status = '" . $conn->real_escape_string($status_filter) . "'" : "";

$req_query = $conn->query("
    SELECT pr.*, 
           u1.username as requested_by_name,
           u2.username as approved_by_name,
           COUNT(pri.pr_item_id) as item_count
    FROM purchase_requisitions pr
    LEFT JOIN users u1 ON pr.requested_by = u1.id
    LEFT JOIN users u2 ON pr.approved_by = u2.id
    LEFT JOIN pr_items pri ON pr.pr_id = pri.pr_id
    $where_clause
    GROUP BY pr.pr_id
    ORDER BY pr.created_at DESC
    LIMIT 100
");
if ($req_query) {
    while ($row = $req_query->fetch_assoc()) {
        $requisitions[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Requisitions | LORINIMS</title>
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
        .status-submitted { background: #dbeafe; color: #1e40af; }
        .status-approved { background: #d1fae5; color: #065f46; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
        .status-cancelled { background: #f3f4f6; color: #6b7280; }
        
        .filter-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .items-section {
            background: #f9fafb;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
        }
        .item-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr 1fr auto;
            gap: 10px;
            align-items: center;
            padding: 10px;
            margin-bottom: 8px;
            background: white;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
        }
        #prForm { display: <?php echo $show_form ? 'block' : 'none'; ?>; }
        #prList { display: <?php echo $show_form ? 'none' : 'block'; ?>; }
    </style>
</head>
<body>
<div class="wrapper">
    <?php include "layouts/sidebar.php"; ?>
    <div class="main">
        <?php include "layouts/header.php"; ?>
        <div class="content">
            <h2>Purchase Requisitions</h2>
            <p>Manage purchase requisitions and approval workflow.</p>
            <?php showMessage(); ?>
            
            <!-- Requisitions List -->
            <div id="prList">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <div class="filter-bar">
                        <a href="procurement_requisitions.php" class="btn <?php echo !$status_filter ? 'btn-primary' : ''; ?>">All</a>
                        <a href="?status=Draft" class="btn <?php echo $status_filter === 'Draft' ? 'btn-primary' : ''; ?>">Draft</a>
                        <a href="?status=Submitted" class="btn <?php echo $status_filter === 'Submitted' ? 'btn-primary' : ''; ?>">Submitted</a>
                        <a href="?status=Approved" class="btn <?php echo $status_filter === 'Approved' ? 'btn-primary' : ''; ?>">Approved</a>
                        <a href="?status=Rejected" class="btn <?php echo $status_filter === 'Rejected' ? 'btn-primary' : ''; ?>">Rejected</a>
                    </div>
                    <button onclick="showForm()" class="btn">+ New Requisition</button>
                </div>
                
                <div class="card">
                <h3>Purchase Requisitions</h3>
                <table style="width:100%;">
                    <thead>
                        <tr>
                            <th>PR Number</th>
                            <th>Department</th>
                            <th>Requested By</th>
                            <th>Request Date</th>
                            <th>Required Date</th>
                            <th>Items</th>
                            <th>Est. Cost</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($requisitions) > 0): ?>
                            <?php foreach ($requisitions as $req): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($req['pr_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($req['department'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($req['requested_by_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo formatDate($req['request_date']); ?></td>
                                    <td><?php echo $req['required_date'] ? formatDate($req['required_date']) : '-'; ?></td>
                                    <td><?php echo $req['item_count']; ?></td>
                                    <td>₱<?php echo number_format($req['total_estimated_cost'], 2); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($req['status']); ?>">
                                            <?php echo htmlspecialchars($req['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display:flex; gap:5px;">
                                            <a href="procurement_requisition_view.php?id=<?php echo $req['pr_id']; ?>" class="btn" style="padding:4px 12px; font-size:12px;">View</a>
                                            <?php if ($req['status'] === 'Draft'): ?>
                                                <a href="?id=<?php echo $req['pr_id']; ?>" class="btn" style="padding:4px 12px; font-size:12px;">Edit</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="9" style="text-align:center;padding:30px;color:var(--text-muted);">No purchase requisitions found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            </div>
            
            <!-- PR Form -->
            <div id="prForm" class="card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <h3><?php echo $pr ? 'Edit Purchase Requisition' : 'New Purchase Requisition'; ?></h3>
                    <button onclick="hideForm()" class="btn">Cancel</button>
                </div>
                <form method="POST" action="api/save_pr.php">
                    <input type="hidden" name="pr_id" value="<?php echo $pr_id; ?>">
                    
                    <h3>Requisition Information</h3>
                    <div class="form-grid">
                        <div>
                            <label>PR Number</label>
                            <div style="color:var(--text-secondary); padding:8px; background:#f9fafb; border-radius:4px;">
                                <?php echo $pr ? htmlspecialchars($pr['pr_number']) : 'Auto-generated when saved'; ?>
                            </div>
                        </div>
                        <div>
                            <label>Department *</label>
                            <select name="department" style="width:100%; padding:8px;" required>
                                <option value="">-- Select Department --</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo $pr && $pr['department'] == $dept ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label>Request Date *</label>
                            <input type="date" name="request_date" value="<?php echo $pr ? $pr['request_date'] : date('Y-m-d'); ?>" 
                                   style="width:100%; padding:8px;" required>
                        </div>
                        <div>
                            <label>Required Date</label>
                            <input type="date" name="required_date" value="<?php echo $pr['required_date'] ?? ''; ?>" 
                                   style="width:100%; padding:8px;">
                        </div>
                    </div>
                    <div style="margin-top:15px;">
                        <label>Justification *</label>
                        <textarea name="justification" style="width:100%; padding:8px; min-height:80px;" required><?php echo htmlspecialchars($pr['justification'] ?? ''); ?></textarea>
                    </div>
                    
                    <!-- Items Section -->
                    <div class="items-section">
                        <h3>Requested Items</h3>
                        <div id="items_list">
                            <?php if (count($pr_items) > 0): ?>
                                <?php foreach ($pr_items as $idx => $item): ?>
                                    <div class="item-row">
                                        <div>
                                            <select name="item_type[]" class="item-type-select" style="width:100%; padding:6px;" required>
                                                <option value="Raw Material" selected>Raw Material</option>
                                            </select>
                                        </div>
                                        <div>
                                            <select name="item_id[]" class="item-select" style="width:100%; padding:6px;">
                                                <option value="">-- Select Item --</option>
                                            </select>
                                            <input type="text" name="item_name[]" value="<?php echo htmlspecialchars($item['item_name']); ?>" 
                                                   placeholder="Item Name" style="width:100%; padding:6px; margin-top:5px;" required>
                                        </div>
                                        <div>
                                            <input type="number" name="quantity[]" value="<?php echo $item['quantity']; ?>" 
                                                   step="0.01" min="0.01" placeholder="Qty" style="width:100%; padding:6px;" required>
                                        </div>
                                        <div>
                                            <select name="unit[]" style="width:100%; padding:6px;" required>
                                                <?php foreach ($units as $unit): ?>
                                                    <option value="<?php echo $unit; ?>" <?php echo $item['unit'] === $unit ? 'selected' : ''; ?>><?php echo $unit; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div>
                                            <input type="number" name="estimated_price[]" value="<?php echo $item['estimated_unit_price']; ?>" 
                                                   step="0.01" placeholder="Est. Price" style="width:100%; padding:6px;">
                                        </div>
                                        <div>
                                            <button type="button" class="btn" onclick="this.parentElement.parentElement.remove(); calculateTotal();">Remove</button>
                                        </div>
                                        <input type="hidden" name="material_id[]" value="<?php echo $item['material_id'] ?? ''; ?>">
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <div style="margin-top:15px;">
                            <button type="button" class="btn" onclick="addItemRow()">+ Add Item</button>
                        </div>
                        
                        <div style="margin-top:15px; padding-top:15px; border-top:2px solid #d1d5db; text-align:right;">
                            <strong>Total Estimated Cost: ₱<span id="total_cost">0.00</span></strong>
                        </div>
                    </div>
                    
                    <div style="text-align:right; margin-top:20px;">
                        <button type="button" onclick="hideForm()" class="btn" style="margin-right:10px;">Cancel</button>
                        <?php if (!$pr || $pr['status'] === 'Draft'): ?>
                            <button type="submit" name="action" value="save_draft" class="btn" style="margin-right:10px;">Save as Draft</button>
                            <button type="submit" name="action" value="submit" class="btn">Submit for Approval</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
        <?php include "layouts/footer.php"; ?>
    </div>
</div>
<script src="assets/js/sidebar.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function showForm() {
    document.getElementById('prList').style.display = 'none';
    document.getElementById('prForm').style.display = 'block';
    window.scrollTo(0, 0);
}

function hideForm() {
    document.getElementById('prForm').style.display = 'none';
    document.getElementById('prList').style.display = 'block';
    window.location.href = 'procurement_requisitions.php';
}

var rawMaterials = <?php echo json_encode($raw_materials); ?>;

function addItemRow() {
    var list = document.getElementById('items_list');
    var div = document.createElement('div');
    div.className = 'item-row';
    div.innerHTML = '<div><select name="item_type[]" class="item-type-select" style="width:100%; padding:6px;" required><option value="Raw Material" selected>Raw Material</option></select></div>' +
        '<div><select name="item_id[]" class="item-select" style="width:100%; padding:6px;"><option value="">-- Select Item --</option></select><input type="text" name="item_name[]" placeholder="Item Name" style="width:100%; padding:6px; margin-top:5px;" required></div>' +
        '<div><input type="number" name="quantity[]" step="0.01" min="0.01" placeholder="Qty" style="width:100%; padding:6px;" required onchange="calculateTotal()"></div>' +
        '<div><select name="unit[]" style="width:100%; padding:6px;" required><?php foreach ($units as $unit) echo "<option value=\"$unit\">$unit</option>"; ?></select></div>' +
        '<div><input type="number" name="estimated_price[]" step="0.01" placeholder="Est. Price" style="width:100%; padding:6px;" onchange="calculateTotal()"></div>' +
        '<div><button type="button" class="btn" onclick="this.parentElement.parentElement.remove(); calculateTotal();">Remove</button></div>' +
        '<input type="hidden" name="material_id[]" value="">';
    
    list.appendChild(div);
    initItemRow(div);
}

function initItemRow(row) {
    var typeSelect = row.querySelector('.item-type-select');
    var itemSelect = row.querySelector('.item-select');
    var itemNameInput = row.querySelector('input[name="item_name[]"]');
    var materialInput = row.querySelector('input[name="material_id[]"]');
    
    // Always show raw materials
    rawMaterials.forEach(function(rm) {
        var opt = document.createElement('option');
        opt.value = rm.material_id;
        opt.textContent = rm.material_name;
        itemSelect.appendChild(opt);
    });
    
    itemSelect.addEventListener('change', function() {
        if (this.value) {
            var rm = rawMaterials.find(m => m.material_id == this.value);
            if (rm) {
                itemNameInput.value = rm.material_name;
                materialInput.value = this.value;
            }
        }
    });
    // pre-select if material_id exists (editing existing requisition)
    if (materialInput.value) {
        itemSelect.value = materialInput.value;
        itemSelect.dispatchEvent(new Event('change'));
    }
}

function calculateTotal() {
    var total = 0;
    document.querySelectorAll('.item-row').forEach(function(row) {
        var qty = parseFloat(row.querySelector('input[name="quantity[]"]').value) || 0;
        var price = parseFloat(row.querySelector('input[name="estimated_price[]"]').value) || 0;
        total += qty * price;
    });
    document.getElementById('total_cost').textContent = total.toFixed(2);
}

// Initialize existing rows
document.querySelectorAll('.item-row').forEach(initItemRow);
calculateTotal();
</script>
</body>
</html>
