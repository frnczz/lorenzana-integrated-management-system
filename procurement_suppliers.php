<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'procurement' && $_SESSION['role'] != 'warehouse')) {
    header("Location: login.php");
    exit;
}
include "includes/functions.php";
include "db_connect.php";

$show_form = isset($_GET['new']) || isset($_GET['id']);
$supplier_id = intval($_GET['id'] ?? 0);
$supplier = null;
$supplier_products = [];

// Load supplier data if editing
if ($supplier_id > 0) {
    $supplier_query = $conn->prepare("SELECT * FROM suppliers WHERE supplier_id = ?");
    $supplier_query->bind_param("i", $supplier_id);
    $supplier_query->execute();
    $supplier = $supplier_query->get_result()->fetch_assoc();
    $supplier_query->close();
    
    if ($supplier) {
        $products_query = $conn->prepare("
            SELECT sp.*, rm.material_name, p.product_name
            FROM supplier_products sp
            LEFT JOIN raw_materials rm ON sp.material_id = rm.material_id
            LEFT JOIN products p ON sp.product_id = p.product_id
            WHERE sp.supplier_id = ?
            ORDER BY sp.item_type, sp.item_name
        ");
        $products_query->bind_param("i", $supplier_id);
        $products_query->execute();
        $products_result = $products_query->get_result();
        while ($row = $products_result->fetch_assoc()) {
            $supplier_products[] = $row;
        }
        $products_query->close();
    }
}

// Fetch raw materials and products for dropdowns
$raw_materials = [];
$materials_query = $conn->query("SELECT material_id, material_name, category, unit FROM raw_materials ORDER BY material_name");
if ($materials_query) {
    while ($row = $materials_query->fetch_assoc()) {
        $raw_materials[] = $row;
    }
}

$products = [];
$products_query = $conn->query("SELECT product_id, product_name, unit FROM products ORDER BY product_name");
if ($products_query) {
    while ($row = $products_query->fetch_assoc()) {
        $products[] = $row;
    }
}

// Fetch suppliers with pagination
$pagination = function_exists('getPagination') ? getPagination($conn, "SELECT COUNT(*) as c FROM suppliers") : ['offset' => 0, 'per_page' => 25, 'total' => 0];
$suppliers = [];
$suppliers_query = $conn->query("
    SELECT s.*, 
           COUNT(DISTINCT sp.sp_id) as product_count,
           COUNT(DISTINCT po.po_id) as order_count
    FROM suppliers s
    LEFT JOIN supplier_products sp ON s.supplier_id = sp.supplier_id
    LEFT JOIN purchase_orders po ON s.supplier_id = po.supplier_id
    GROUP BY s.supplier_id
    ORDER BY s.supplier_name
    LIMIT " . $pagination['offset'] . ", " . $pagination['per_page']
);
if ($suppliers_query) {
    while ($row = $suppliers_query->fetch_assoc()) {
        $suppliers[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Management | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .summary-card {
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            color: white;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .summary-card h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            opacity: 0.9;
        }
        .summary-card p {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
        }
        .summary-card:nth-child(1) { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .summary-card:nth-child(2) { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .summary-card:nth-child(3) { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-active { background: #d1fae5; color: #065f46; }
        .status-inactive { background: #fee2e2; color: #991b1b; }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        table th, table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        table th {
            background-color: #f9fafb;
            font-weight: 600;
            color: #374151;
            text-transform: uppercase;
            font-size: 12px;
        }
        table tr:hover {
            background-color: #f9fafb;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        .btn-small {
            padding: 4px 12px;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .products-section {
            background: #f9fafb;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
        }
        .product-item {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr auto;
            gap: 10px;
            align-items: center;
            padding: 10px;
            margin-bottom: 8px;
            background: white;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
        }
        #supplierForm { display: <?php echo $show_form ? 'block' : 'none'; ?>; }
        #suppliersList { display: <?php echo $show_form ? 'none' : 'block'; ?>; }
    </style>
</head>
<body>
<div class="wrapper">
    <?php include "layouts/sidebar.php"; ?>
    <div class="main">
        <?php include "layouts/header.php"; ?>
        <div class="content">
            <h2>Supplier Management</h2>
            <p>Manage supplier master data, contacts, and product catalogs.</p>
            <?php showMessage(); ?>
            
            <!-- Summary Cards -->
            <div class="summary-cards">
                <div class="summary-card">
                    <h3>Total Suppliers</h3>
                    <p><?php echo $pagination['total']; ?></p>
                </div>
                <div class="summary-card">
                    <h3>Active Suppliers</h3>
                    <p><?php echo count(array_filter($suppliers, function($s) { return $s['status'] === 'Active'; })); ?></p>
                </div>
                <div class="summary-card">
                    <h3>Total Products</h3>
                    <p><?php echo array_sum(array_column($suppliers, 'product_count')); ?></p>
                </div>
            </div>
            
            <!-- Suppliers List -->
            <div id="suppliersList">
                <!-- Add Supplier Button -->
                <div style="text-align:right; margin-bottom:15px;">
                    <a href="procurement_suppliers.php?new=1" class="btn">+ Add New Supplier</a>
                </div>
                
                <!-- Suppliers Table -->
                <div class="card">
                <h3>All Suppliers</h3>
                <?php if (function_exists('renderPerPageSelector')) echo '<div class="pagination-toolbar">' . renderPerPageSelector($conn, $pagination['per_page']) . '</div>'; ?>
                <table>
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Supplier Name</th>
                            <th>Contact Person</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th>Payment Terms</th>
                            <th>Products</th>
                            <th>Orders</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($suppliers) > 0): ?>
                            <?php foreach ($suppliers as $s): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($s['supplier_code']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($s['supplier_name']); ?></td>
                                    <td><?php echo htmlspecialchars($s['contact_person'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($s['contact_number'] ?? $s['phone'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($s['email'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($s['payment_terms']); ?></td>
                                    <td><?php echo $s['product_count']; ?></td>
                                    <td><?php echo $s['order_count']; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($s['status']); ?>">
                                            <?php echo htmlspecialchars($s['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="?id=<?php echo $s['supplier_id']; ?>" class="btn btn-small">Edit</a>
                                            <a href="procurement_supplier_view.php?id=<?php echo $s['supplier_id']; ?>" class="btn btn-small">View</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="10" style="text-align:center;padding:30px;color:var(--text-muted);">No suppliers found. <a href="?new=1" class="btn" style="margin-top:10px;">Add your first supplier</a></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <?php if (function_exists('renderPagination')) echo renderPagination($pagination); ?>
            </div>
            </div>
            
            <!-- Supplier Form -->
            <div id="supplierForm" class="card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <h3><?php echo $supplier ? 'Edit Supplier' : 'Add New Supplier'; ?></h3>
                    <button onclick="hideForm()" class="btn">Cancel</button>
                </div>
                <form method="POST" action="api/save_supplier.php" autocomplete="off">
                    <input type="hidden" name="supplier_id" value="<?php echo $supplier_id; ?>">
                    
                    <h3>Supplier Information</h3>
                    <div class="form-grid">
                        <div>
                            <label>Supplier Code</label>
                            <div style="color:var(--text-secondary); padding:8px; background:#f9fafb; border-radius:4px;">
                                <?php echo $supplier ? htmlspecialchars($supplier['supplier_code']) : 'Auto-generated when saved'; ?>
                            </div>
                        </div>
                        <div>
                            <label>Supplier Name *</label>
                            <input type="text" name="supplier_name" value="<?php echo htmlspecialchars($supplier['supplier_name'] ?? ''); ?>" 
                                   style="width:100%; padding:8px;" required>
                        </div>
                        <div>
                            <label>Contact Person</label>
                            <input type="text" name="contact_person" value="<?php echo htmlspecialchars($supplier['contact_person'] ?? ''); ?>" 
                                   style="width:100%; padding:8px;">
                        </div>
                        <div>
                            <label>Contact Number</label>
                            <input type="text" name="contact_number" value="<?php echo htmlspecialchars($supplier['contact_number'] ?? $supplier['phone'] ?? ''); ?>" 
                                   style="width:100%; padding:8px;">
                        </div>
                        <div>
                            <label>Email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($supplier['email'] ?? ''); ?>" 
                                   style="width:100%; padding:8px;">
                        </div>
                        <div>
                            <label>Payment Terms</label>
                            <select name="payment_terms" style="width:100%; padding:8px;">
                                <option value="Net 15" <?php echo ($supplier['payment_terms'] ?? 'Net 30') === 'Net 15' ? 'selected' : ''; ?>>Net 15</option>
                                <option value="Net 30" <?php echo ($supplier['payment_terms'] ?? 'Net 30') === 'Net 30' ? 'selected' : ''; ?>>Net 30</option>
                                <option value="Net 45" <?php echo ($supplier['payment_terms'] ?? 'Net 30') === 'Net 45' ? 'selected' : ''; ?>>Net 45</option>
                                <option value="Net 60" <?php echo ($supplier['payment_terms'] ?? 'Net 30') === 'Net 60' ? 'selected' : ''; ?>>Net 60</option>
                                <option value="Cash on Delivery" <?php echo ($supplier['payment_terms'] ?? 'Net 30') === 'Cash on Delivery' ? 'selected' : ''; ?>>Cash on Delivery</option>
                            </select>
                        </div>
                        <div>
                            <label>Status</label>
                            <select name="status" style="width:100%; padding:8px;">
                                <option value="Active" <?php echo ($supplier['status'] ?? 'Active') === 'Active' ? 'selected' : ''; ?>>Active</option>
                                <option value="Inactive" <?php echo ($supplier['status'] ?? 'Active') === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div style="margin-top:15px;">
                        <label>Address</label>
                        <textarea name="address" style="width:100%; padding:8px; min-height:80px;"><?php echo htmlspecialchars($supplier['address'] ?? ''); ?></textarea>
                    </div>
                    <div style="margin-top:15px;">
                        <label>Notes</label>
                        <textarea name="notes" style="width:100%; padding:8px; min-height:80px;"><?php echo htmlspecialchars($supplier['notes'] ?? ''); ?></textarea>
                    </div>
                    
                    <!-- Supplier Products Section -->
                    <div class="products-section">
                        <h3>Products/Items Supplied</h3>
                        <div id="supplier_products_list">
                            <?php if (count($supplier_products) > 0): ?>
                                <?php foreach ($supplier_products as $sp): ?>
                                    <div class="product-item" data-sp-id="<?php echo $sp['sp_id']; ?>">
                                        <div>
                                            <strong><?php echo htmlspecialchars($sp['item_name']); ?></strong>
                                            <br><small style="color:var(--text-muted);"><?php echo htmlspecialchars($sp['item_type']); ?></small>
                                        </div>
                                        <div>
                                            <input type="number" name="product_price[<?php echo $sp['sp_id']; ?>]" 
                                                   value="<?php echo $sp['unit_price']; ?>" step="0.01" 
                                                   placeholder="Unit Price" style="width:100%; padding:6px;">
                                        </div>
                                        <div>
                                            <input type="text" name="product_unit[<?php echo $sp['sp_id']; ?>]" 
                                                   value="<?php echo htmlspecialchars($sp['unit']); ?>" 
                                                   placeholder="Unit" style="width:100%; padding:6px;">
                                        </div>
                                        <div>
                                            <button type="button" class="btn" onclick="removeProduct(<?php echo $sp['sp_id']; ?>)">Remove</button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <div style="margin-top:15px; padding-top:15px; border-top:2px dashed #d1d5db;">
                            <h4>Add Product/Item</h4>
                            <div style="display:grid; grid-template-columns: 2fr 1fr 1fr auto; gap:10px; align-items:end;">
                                <select id="product_type_select" style="padding:8px;">
                                    <option value="">-- Select Type --</option>
                                    <option value="Raw Material">Raw Material</option>
                                    <option value="Product">Product</option>
                                    <option value="Other">Other</option>
                                </select>
                                <select id="item_select" style="padding:8px;">
                                    <option value="">-- Select Item --</option>
                                </select>
                                <input type="text" id="item_name_input" placeholder="Or enter name" style="padding:8px;">
                                <button type="button" class="btn" onclick="addProduct()">Add</button>
                            </div>
                        </div>
                    </div>
                    
                    <div style="text-align:right; margin-top:20px;">
                        <button type="button" onclick="hideForm()" class="btn" style="margin-right:10px;">Cancel</button>
                        <button type="submit" class="btn">Save Supplier</button>
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
    document.getElementById('suppliersList').style.display = 'none';
    document.getElementById('supplierForm').style.display = 'block';

    // Clear form inputs
    var form = document.querySelector('#supplierForm form');
    form.reset();

    // Clear supplier products
    document.getElementById('supplier_products_list').innerHTML = '';

    window.scrollTo(0, 0);
}

function hideForm() {
    document.getElementById('supplierForm').style.display = 'none';
    document.getElementById('suppliersList').style.display = 'block';
    window.location.href = 'procurement_suppliers.php';
}

var rawMaterials = <?php echo json_encode($raw_materials); ?>;
var products = <?php echo json_encode($products); ?>;

function updateItemSelect() {
    var type = document.getElementById('product_type_select').value;
    var itemSelect = document.getElementById('item_select');
    itemSelect.innerHTML = '<option value="">-- Select Item --</option>';
    
    if (type === 'Raw Material') {
        rawMaterials.forEach(function(rm) {
            var opt = document.createElement('option');
            opt.value = rm.material_id;
            opt.textContent = rm.material_name + ' (' + rm.category + ')';
            opt.setAttribute('data-name', rm.material_name);
            itemSelect.appendChild(opt);
        });
    } else if (type === 'Product') {
        products.forEach(function(p) {
            var opt = document.createElement('option');
            opt.value = p.product_id;
            opt.textContent = p.product_name;
            opt.setAttribute('data-name', p.product_name);
            itemSelect.appendChild(opt);
        });
    }
}

document.getElementById('product_type_select')?.addEventListener('change', updateItemSelect);

function addProduct() {
    var type = document.getElementById('product_type_select').value;
    var itemSelect = document.getElementById('item_select');
    var itemNameInput = document.getElementById('item_name_input');
    
    if (!type) {
        alert('Please select item type');
        return;
    }
    
    var itemName = '';
    var itemId = null;
    
    if (itemSelect.value) {
        var selectedOption = itemSelect.options[itemSelect.selectedIndex];
        itemName = selectedOption.getAttribute('data-name') || selectedOption.textContent;
        itemId = itemSelect.value;
    } else if (itemNameInput.value.trim()) {
        itemName = itemNameInput.value.trim();
    } else {
        alert('Please select an item or enter item name');
        return;
    }
    
    var list = document.getElementById('supplier_products_list');
    var div = document.createElement('div');
    div.className = 'product-item';
    div.innerHTML = '<div><strong>' + itemName + '</strong><br><small style="color:var(--text-muted);">' + type + '</small></div>' +
        '<div><input type="number" name="new_product_price[]" step="0.01" placeholder="Unit Price" style="width:100%; padding:6px;"></div>' +
        '<div><input type="text" name="new_product_unit[]" placeholder="Unit" style="width:100%; padding:6px;"></div>' +
        '<div><button type="button" class="btn" onclick="this.parentElement.parentElement.remove()">Remove</button></div>' +
        '<input type="hidden" name="new_product_type[]" value="' + type + '">' +
        '<input type="hidden" name="new_product_name[]" value="' + itemName + '">' +
        (itemId ? '<input type="hidden" name="new_product_' + (type === 'Raw Material' ? 'material' : 'product') + '_id[]" value="' + itemId + '">' : '');
    
    list.appendChild(div);
    
    itemSelect.value = '';
    itemNameInput.value = '';
}

function removeProduct(spId) {
    if (confirm('Remove this product from supplier catalog?')) {
        var form = document.querySelector('form');
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'delete_product[]';
        input.value = spId;
        form.appendChild(input);
        
        document.querySelector('[data-sp-id="' + spId + '"]').remove();
    }
}
</script>
</body>
</html>
