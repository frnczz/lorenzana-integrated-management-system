<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'warehouse' && $_SESSION['role'] != 'procurement')) {
    header("Location: login.php");
    exit;
}
include "includes/functions.php";
include "db_connect.php";

$show_form = isset($_GET['new']) || isset($_GET['edit']) || isset($_GET['supplier_id']);

// Fetch returns
$returns = [];
$returns_query = $conn->query("
    SELECT sr.*, s.supplier_name, po.po_number, grn.grn_number,
           u1.username as created_by_name, u2.username as approved_by_name
    FROM supplier_returns sr
    LEFT JOIN suppliers s ON sr.supplier_id = s.supplier_id
    LEFT JOIN purchase_orders po ON sr.po_id = po.po_id
    LEFT JOIN goods_receiving_notes grn ON sr.grn_id = grn.grn_id
    LEFT JOIN users u1 ON sr.created_by = u1.id
    LEFT JOIN users u2 ON sr.approved_by = u2.id
    ORDER BY sr.created_at DESC
    LIMIT 100
");
if ($returns_query) {
    while ($row = $returns_query->fetch_assoc()) {
        $returns[] = $row;
    }
}

// Fetch suppliers and materials for form
$suppliers = [];
$raw_materials = [];
if ($show_form) {
    $suppliers_query = $conn->query("SELECT supplier_id, supplier_name FROM suppliers WHERE status = 'Active' ORDER BY supplier_name");
    if ($suppliers_query) {
        while ($row = $suppliers_query->fetch_assoc()) {
            $suppliers[] = $row;
        }
    }
    
    // prefill helpers from query string (needed before materials query)
    $prefill_supplier = isset($_GET['supplier_id']) ? intval($_GET['supplier_id']) : 0;
    $prefill_grn_id = isset($_GET['grn_id']) ? intval($_GET['grn_id']) : 0;
    $prefill_material_id = isset($_GET['material_id']) ? intval($_GET['material_id']) : 0;
    $prefill_qty = isset($_GET['qty']) ? floatval($_GET['qty']) : '';
    $prefill_unit = $_GET['unit'] ?? '';
    $prefill_item_name = $_GET['item_name'] ?? '';

    // load materials only if a GRN has been specified (avoids SQL placeholder error)
    if ($prefill_grn_id > 0) {
        $stmt = $conn->prepare(
            "SELECT 
                gi.grn_item_id,
                gi.material_id,
                rm.material_name,
                gi.quantity_received AS quantity,
                gi.unit_price,
                rm.unit
            FROM grn_items gi
            JOIN raw_materials rm ON gi.material_id = rm.material_id
            WHERE gi.grn_id = ?
            ORDER BY rm.material_name"
        );
        $stmt->bind_param("i", $prefill_grn_id);
        $stmt->execute();
        $materials_result = $stmt->get_result();
        while ($row = $materials_result->fetch_assoc()) {
            $raw_materials[] = $row;
        }
        $stmt->close();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Returns to Supplier | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-approved { background: #dbeafe; color: #1e40af; }
        .status-returned { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #f3f4f6; color: #6b7280; }
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
        #returnForm { display: <?php echo $show_form ? 'block' : 'none'; ?>; }
        #returnsList { display: <?php echo $show_form ? 'none' : 'block'; ?>; }
    </style>
</head>
<body>
<div class="wrapper">
    <?php include "layouts/sidebar.php"; ?>
    <div class="main">
        <?php include "layouts/header.php"; ?>
        <div class="content">
            <h2>Returns to Supplier</h2>
            <p>Manage returns of damaged or rejected goods to suppliers.</p>
            <?php showMessage(); ?>
            
            <!-- Returns List -->
            <div id="returnsList">
                <div style="text-align:right; margin-bottom:15px;">
                    <button onclick="showForm()" class="btn">+ New Return</button>
                </div>
                
                <div class="card">
                    <h3>Returns</h3>
                    <table style="width:100%;">
                        <thead>
                            <tr>
                                <th>Return Number</th>
                                <th>Supplier</th>
                                <th>PO Number</th>
                                <th>GRN Number</th>
                                <th>Return Date</th>
                                <th>Reason</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($returns) > 0): ?>
                                <?php foreach ($returns as $return): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($return['return_number']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($return['supplier_name']); ?></td>
                                        <td><?php echo htmlspecialchars($return['po_number'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($return['grn_number'] ?? '-'); ?></td>
                                        <td><?php echo formatDate($return['return_date']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($return['reason'], 0, 50)) . (strlen($return['reason']) > 50 ? '...' : ''); ?></td>
                                        <td>₱<?php echo number_format($return['total_amount'], 2); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($return['status']); ?>">
                                                <?php echo htmlspecialchars($return['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="procurement_return_view.php?id=<?php echo $return['return_id']; ?>" class="btn" style="padding:4px 12px; font-size:12px;">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="9" style="text-align:center;padding:30px;color:var(--text-muted);">No returns found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Return Form -->
            <div id="returnForm" class="card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <h3>New Return to Supplier</h3>
                    <?php if (!empty($prefill_grn_id) || !empty($prefill_item_name)): ?>
                        <div style="margin-bottom:10px; font-style:italic; color:#555;">
                            <?php if (!empty($prefill_grn_id)): ?>
                                Linked to GRN <?php echo intval($prefill_grn_id); ?>.
                            <?php endif; ?>
                            <?php if (!empty($prefill_item_name)): ?>
                                Returning <strong><?php echo htmlspecialchars($prefill_item_name); ?></strong>
                                (<?php echo htmlspecialchars($prefill_qty . ' ' . $prefill_unit); ?>)
                                <?php if (!empty($prefill_grn_id)): ?>from GRN <?php echo intval($prefill_grn_id); ?><?php endif; ?>.
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <button onclick="hideForm()" class="btn">Cancel</button>
                </div>
                <form method="POST" action="api/save_return.php">
                    <input type="hidden" name="grn_id" value="<?php echo isset($prefill_grn_id) ? $prefill_grn_id : ''; ?>">
                    <div class="form-grid">
                        <div>
                            <label>Return Number</label>
                            <div style="color:var(--text-secondary); padding:8px; background:#f9fafb; border-radius:4px;">
                                Auto-generated when saved
                            </div>
                        </div>
                        <div>
                            <label>Supplier *</label>
                            <select name="supplier_id" style="width:100%; padding:8px;" required>
                                <option value="">-- Select Supplier --</option>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <option value="<?php echo $supplier['supplier_id']; ?>" <?php if (!empty($prefill_supplier) && $supplier['supplier_id'] == $prefill_supplier) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($supplier['supplier_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label>Return Date *</label>
                            <input type="date" name="return_date" value="<?php echo date('Y-m-d'); ?>" 
                                   style="width:100%; padding:8px;" required>
                        </div>
                        <div>
                            <label>Link to PO (Optional)</label>
                            <select name="po_id" style="width:100%; padding:8px;">
                                <option value="">-- Select PO (Optional) --</option>
                            </select>
                        </div>
                    </div>
                    <div style="margin-top:15px;">
                        <label>Reason for Return *</label>
                        <textarea name="reason" style="width:100%; padding:8px; min-height:80px;" required 
                                  placeholder="e.g., Damaged goods, Quality issues, Wrong items..."></textarea>
                    </div>
                    
                    <div class="items-section">
                        <h3>Items to Return</h3>
                        <div id="items_list"></div>
                        
                        <div style="margin-top:15px;">
                            <label>Add Item</label>
                            <div style="display:grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap:10px; align-items:end;">
                                <select id="material_select" style="padding:8px;">
                                    <option value="">-- Select Material --</option>
                                    <?php foreach ($raw_materials as $mat): ?>
                                        <option value="<?php echo $mat['material_id']; ?>"
                                                data-name="<?php echo htmlspecialchars($mat['material_name']); ?>"
                                                data-quantity="<?php echo $mat['quantity']; ?>"
                                                data-unit="<?php echo htmlspecialchars($mat['unit']); ?>">
                                            <?php echo htmlspecialchars($mat['material_name']); ?> 
                                            (Available: <?php echo number_format($mat['quantity'], 2); ?> <?php echo htmlspecialchars($mat['unit']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="number" id="return_qty" step="0.01" min="0.01" placeholder="Qty" style="padding:8px;">
                                <input type="number" id="return_price" step="0.01" min="0" placeholder="Unit Price" style="padding:8px;">
                                <input type="text" id="return_reason" placeholder="Reason" style="padding:8px;">
                                <button type="button" class="btn" onclick="addReturnItem()">Add</button>
                            </div>
                        </div>
                        
                        <div style="margin-top:15px; padding-top:15px; border-top:2px solid #d1d5db; text-align:right;">
                            <strong>Total Amount: ₱<span id="total_amount">0.00</span></strong>
                        </div>
                    </div>
                    
                    <div style="margin-top:15px;">
                        <label>Notes</label>
                        <textarea name="notes" style="width:100%; padding:8px; min-height:60px;"></textarea>
                    </div>
                    
                    <div style="text-align:right; margin-top:20px;">
                        <button type="button" onclick="hideForm()" class="btn" style="margin-right:10px;">Cancel</button>
                        <button type="submit" class="btn">Submit Return</button>
                    </div>
                </form>
            </div>
        </div>
        <?php include "layouts/footer.php"; ?>
    </div>
</div>
<script src="assets/js/sidebar.js"></script>
<script>
function showForm() {
    document.getElementById('returnsList').style.display = 'none';
    document.getElementById('returnForm').style.display = 'block';
    window.scrollTo(0, 0);
}

function hideForm() {
    document.getElementById('returnForm').style.display = 'none';
    document.getElementById('returnsList').style.display = 'block';
}

function addReturnItem() {
    var select = document.getElementById('material_select');
    var option = select.options[select.selectedIndex];
    if (!option.value) {
        alert('Please select a material');
        return;
    }
    
    var qty = parseFloat(document.getElementById('return_qty').value) || 0;
    if (qty <= 0) {
        alert('Please enter a valid quantity');
        return;
    }
    
    var availableAttr = option.getAttribute('data-quantity');
    var grnItemIdAttr = option.getAttribute('data-grn_item_id');
    if (availableAttr !== null && availableAttr !== '') {
        var available = parseFloat(availableAttr) || 0;
        if (qty > available) {
            alert('Quantity exceeds available stock');
            return;
        }
    } // if no data-quantity provided (prefill), skip stock check
    
    var price = parseFloat(document.getElementById('return_price').value) || 0;
    var reason = document.getElementById('return_reason').value || '';
    var materialName = option.getAttribute('data-name');
    var unit = option.getAttribute('data-unit');
    var materialId = option.value;
    var subtotal = qty * price;
    
    var list = document.getElementById('items_list');
    var div = document.createElement('div');
    div.className = 'item-row';
    div.innerHTML = '<div><strong>' + materialName + '</strong><br><small>' + unit + '</small></div>' +
        '<div><input type="number" name="quantity[]" value="' + qty + '" step="0.01" readonly style="width:100%; padding:6px; background:#f3f4f6;"></div>' +
        '<div><input type="text" name="unit[]" value="' + unit + '" readonly style="width:100%; padding:6px; background:#f3f4f6;"></div>' +
        '<div><input type="number" name="unit_price[]" value="' + price + '" step="0.01" style="width:100%; padding:6px;" onchange="calculateTotal()"></div>' +
        '<div><input type="text" name="item_reason[]" value="' + reason + '" placeholder="Reason" style="width:100%; padding:6px;"></div>' +
        '<div><button type="button" class="btn" onclick="this.parentElement.parentElement.remove(); calculateTotal();">Remove</button></div>' +
        '<input type="hidden" name="material_id[]" value="' + materialId + '">' +
        '<input type="hidden" name="item_name[]" value="' + materialName + '">' +
        '<input type="hidden" name="subtotal[]" value="' + subtotal + '">' +
        (grnItemIdAttr ? '<input type="hidden" name="grn_item_id[]" value="' + grnItemIdAttr + '">' : '');
    
    list.appendChild(div);
    calculateTotal();
    
    select.value = '';
    document.getElementById('return_qty').value = '';
    document.getElementById('return_price').value = '';
    document.getElementById('return_reason').value = '';
}

function calculateTotal() {
    var total = 0;
    document.querySelectorAll('input[name="subtotal[]"]').forEach(function(input) {
        var qty = parseFloat(input.closest('.item-row').querySelector('input[name="quantity[]"]').value) || 0;
        var price = parseFloat(input.closest('.item-row').querySelector('input[name="unit_price[]"]').value) || 0;
        var subtotal = qty * price;
        input.value = subtotal.toFixed(2);
        total += subtotal;
    });
}

// on load, check for prefill parameters and auto-add item
document.addEventListener('DOMContentLoaded', function() {
    var params = new URLSearchParams(window.location.search);
    var matId = params.get('material_id');
    if (matId) {
        var select = document.getElementById('material_select');
        var itemName = params.get('item_name') || '';
        // if the dropdown doesn't already contain this material, add temporary option
        if (select && !select.querySelector('option[value="' + matId + '"]')) {
            var opt = document.createElement('option');
            opt.value = matId;
            opt.text = itemName || matId;
            // add necessary data attributes for prefill
            opt.setAttribute('data-name', itemName || '');
            opt.setAttribute('data-unit', params.get('unit') || '');
            opt.setAttribute('data-quantity', params.get('qty') || ''); // allows validation to pass or skip
            opt.setAttribute('data-grn_item_id', params.get('grn_item_id') || '');
            select.appendChild(opt);
        }
        if (select) select.value = matId;
        var qtyInput = document.getElementById('return_qty');
        if (qtyInput) qtyInput.value = params.get('qty') || '';
        var priceInput = document.getElementById('return_price');
        if (priceInput) priceInput.value = params.get('price') || '';
        var reasonInput = document.getElementById('return_reason');
        if (reasonInput) reasonInput.value = params.get('item_reason') || '';
        // default overall return reason
        var overallReason = document.querySelector('textarea[name="reason"]');
        if (overallReason && !overallReason.value) {
            overallReason.value = 'Returning ' + itemName + ' due to QC rejection';
        }
        // finally add the item row
        addReturnItem();
    }
});
</script>
</body>
</html>
