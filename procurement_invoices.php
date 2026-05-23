<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'procurement' && $_SESSION['role'] != 'accounting')) {
    header("Location: login.php");
    exit;
}
include "includes/functions.php";
include "db_connect.php";

$show_form = isset($_GET['new']);

// Pagination helper
$pagination = function_exists('getPagination')
    ? getPagination($conn, "SELECT COUNT(*) as c FROM supplier_invoices")
    : ['offset' => 0, 'per_page' => 25, 'total' => 0, 'total_pages' => 1];

// Fetch supplier invoices
$invoices = [];
$invoices_query = $conn->query("
    SELECT si.*, s.supplier_name, po.po_number,
           u.username as created_by_name
    FROM supplier_invoices si
    LEFT JOIN suppliers s ON si.supplier_id = s.supplier_id
    LEFT JOIN purchase_orders po ON si.po_id = po.po_id
    LEFT JOIN users u ON si.created_by = u.id
    ORDER BY si.created_at DESC
    LIMIT {$pagination['offset']}, {$pagination['per_page']}
");
if ($invoices_query) {
    while ($row = $invoices_query->fetch_assoc()) {
        $invoices[] = $row;
    }
}

// Fetch suppliers and POs for form
$suppliers = [];
$pos = [];

// Always preload suppliers and recent POs to support client-side show/hide of the form
$suppliers_query = $conn->query("SELECT supplier_id, supplier_name FROM suppliers WHERE status = 'Active' ORDER BY supplier_name");
if ($suppliers_query) {
    while ($row = $suppliers_query->fetch_assoc()) {
        $suppliers[] = $row;
    }
}

$pos_query = $conn->query(
    "SELECT po.po_id, po.po_number, po.total_amount, s.supplier_name
        FROM purchase_orders po
        LEFT JOIN suppliers s ON po.supplier_id = s.supplier_id
        WHERE po.status IN ('Received', 'Partially Received')
        ORDER BY po.order_date DESC
        LIMIT 50"
);
if ($pos_query) {
    while ($row = $pos_query->fetch_assoc()) {
        $pos[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Invoices | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-unpaid { background: #fee2e2; color: #991b1b; }
        .status-partial { background: #fef3c7; color: #92400e; }
        .status-paid { background: #d1fae5; color: #065f46; }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        #invoiceForm { display: <?php echo $show_form ? 'block' : 'none'; ?>; }
        #invoicesList { display: <?php echo $show_form ? 'none' : 'block'; ?>; }
    </style>
</head>
<body>
<div class="wrapper">
    <?php include "layouts/sidebar.php"; ?>
    <div class="main">
        <?php include "layouts/header.php"; ?>
        <div class="content">
            <h2>Supplier Invoices</h2>
            <p>Record and track supplier invoices for payment processing.</p>
            <?php showMessage(); ?>
            
            <!-- Invoices List -->
            <div id="invoicesList">
                <div style="text-align:right; margin-bottom:15px;">
                    <button onclick="showForm()" class="btn">+ Record Invoice</button>
                </div>
                
                <div class="card">
                    <h3>Supplier Invoices</h3>
                    <table style="width:100%;">
                        <thead>
                            <tr>
                                <th>Invoice Number</th>
                                <th>Supplier</th>
                                <th>PO Number</th>
                                <th>Invoice Date</th>
                                <th>Due Date</th>
                                <th>Total Amount</th>
                                <th>Paid Amount</th>
                                <th>Outstanding</th>
                                <th>Payment Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($invoices) > 0): ?>
                                <?php foreach ($invoices as $inv): ?>
                                    <?php 
                                    $outstanding = $inv['total_amount'] - $inv['paid_amount'];
                                    ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($inv['invoice_number']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($inv['supplier_name']); ?></td>
                                        <td><?php echo htmlspecialchars($inv['po_number'] ?? '-'); ?></td>
                                        <td><?php echo formatDate($inv['invoice_date']); ?></td>
                                        <td><?php echo $inv['due_date'] ? formatDate($inv['due_date']) : '-'; ?></td>
                                        <td>₱<?php echo number_format($inv['total_amount'], 2); ?></td>
                                        <td>₱<?php echo number_format($inv['paid_amount'], 2); ?></td>
                                        <td><strong>₱<?php echo number_format($outstanding, 2); ?></strong></td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $inv['payment_status'])); ?>">
                                                <?php echo htmlspecialchars($inv['payment_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="procurement_invoice_view.php?id=<?php echo $inv['invoice_id']; ?>" class="btn" style="padding:4px 12px; font-size:12px;">View</a>
                                            <a href="procurement_pay_invoice.php?invoice_id=<?php echo $inv['invoice_id']; ?>" class="btn" style="padding:4px 12px; font-size:12px; background:#10b981; margin-left:6px;">Record Payment</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="10" style="text-align:center;padding:30px;color:var(--text-muted);">No supplier invoices found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <?php if (function_exists('renderPerPageSelector')) echo '<div class="pagination-toolbar">' . renderPerPageSelector($conn, $pagination['per_page']) . '</div>'; ?>
                    <?php if (function_exists('renderPagination')) echo renderPagination($pagination); ?>
                </div>
            </div>
            
            <!-- Invoice Form -->
            <div id="invoiceForm" class="card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <h3>Record Supplier Invoice</h3>
                    <button onclick="hideForm()" class="btn">Cancel</button>
                </div>
                <form method="POST" action="api/save_supplier_invoice.php">
                    <div class="form-grid">
                        <div>
                            <label>Supplier *</label>
                            <select name="supplier_id" id="supplier_select" style="width:100%; padding:8px;" required onchange="loadPOs()">
                                <option value="">-- Select Supplier --</option>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <option value="<?php echo $supplier['supplier_id']; ?>">
                                        <?php echo htmlspecialchars($supplier['supplier_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label>Link to PO (Optional)</label>
                            <select name="po_id" id="po_select" style="width:100%; padding:8px;">
                                <option value="">-- Select PO (Optional) --</option>
                                <?php foreach ($pos as $po): ?>
                                    <option value="<?php echo $po['po_id']; ?>" data-amount="<?php echo $po['total_amount']; ?>">
                                        <?php echo htmlspecialchars($po['po_number']); ?> - ₱<?php echo number_format($po['total_amount'], 2); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label>Invoice Number *</label>
                            <input type="text" name="invoice_number" style="width:100%; padding:8px;" required>
                        </div>
                        <div>
                            <label>Invoice Date *</label>
                            <input type="date" name="invoice_date" value="<?php echo date('Y-m-d'); ?>" 
                                   style="width:100%; padding:8px;" required>
                        </div>
                        <div>
                            <label>Due Date</label>
                            <input type="date" name="due_date" style="width:100%; padding:8px;">
                        </div>
                    </div>
                    <div style="margin-top:15px;">
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                            <div>
                                <label>Subtotal</label>
                                <input type="number" name="subtotal" id="subtotal" step="0.01" min="0" 
                                       value="0" style="width:100%; padding:8px;" onchange="calculateTotal()">
                            </div>
                            <div>
                                <label>Tax Amount</label>
                                <input type="number" name="tax_amount" id="tax_amount" step="0.01" min="0" 
                                       value="0" style="width:100%; padding:8px;" onchange="calculateTotal()">
                            </div>
                        </div>
                        <div style="margin-top:15px;">
                            <label><strong>Total Amount *</strong></label>
                            <input type="number" name="total_amount" id="total_amount" step="0.01" min="0.01" 
                                   required style="width:100%; padding:8px; font-size:18px; font-weight:bold;">
                        </div>
                    </div>
                    <div style="margin-top:15px;">
                        <label>Notes</label>
                        <textarea name="notes" style="width:100%; padding:8px; min-height:60px;"></textarea>
                    </div>
                    
                    <div style="text-align:right; margin-top:20px;">
                        <button type="button" onclick="hideForm()" class="btn" style="margin-right:10px;">Cancel</button>
                        <button type="submit" class="btn">Save Invoice</button>
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
    document.getElementById('invoicesList').style.display = 'none';
    document.getElementById('invoiceForm').style.display = 'block';
    window.scrollTo(0, 0);
}

function hideForm() {
    document.getElementById('invoiceForm').style.display = 'none';
    document.getElementById('invoicesList').style.display = 'block';
}

function loadPOs() {
    var supplierId = document.getElementById('supplier_select').value;
    var poSelect = document.getElementById('po_select');
    poSelect.innerHTML = '<option value="">-- Select PO (Optional) --</option>';
    
    if (supplierId) {
        fetch('api/get_pos_by_supplier.php?supplier_id=' + supplierId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    data.pos.forEach(function(po) {
                        var option = document.createElement('option');
                        option.value = po.po_id;
                        option.textContent = po.po_number + ' - ₱' + parseFloat(po.total_amount).toFixed(2);
                        option.setAttribute('data-amount', po.total_amount);
                        poSelect.appendChild(option);
                    });
                }
            });
    }
}

// When a PO is selected, prefill subtotal and a provisional invoice number
document.getElementById('po_select')?.addEventListener('change', function() {
    var option = this.options[this.selectedIndex];
    if (option && option.value) {
        var amount = parseFloat(option.getAttribute('data-amount')) || 0;
        document.getElementById('subtotal').value = amount.toFixed(2);
        calculateTotal();
        // Provisional invoice number: SI-<PO_NUMBER>-<timestamp>
        var poText = option.textContent.split(' - ')[0] || option.value;
        var provisional = 'SI-' + poText + '-' + Date.now().toString().slice(-6);
        var invInput = document.querySelector('input[name="invoice_number"]');
        if (invInput && (!invInput.value || invInput.value.trim() === '')) invInput.value = provisional;
    }
});

document.getElementById('po_select')?.addEventListener('change', function() {
    var option = this.options[this.selectedIndex];
    if (option.value) {
        var amount = parseFloat(option.getAttribute('data-amount')) || 0;
        document.getElementById('subtotal').value = amount.toFixed(2);
        calculateTotal();
    }
});

function calculateTotal() {
    var subtotal = parseFloat(document.getElementById('subtotal').value) || 0;
    var tax = parseFloat(document.getElementById('tax_amount').value) || 0;
    document.getElementById('total_amount').value = (subtotal + tax).toFixed(2);
}
// prefill logic when redirected with supplier/po params
(function(){
    var params = new URLSearchParams(window.location.search);
    var supplierId = params.get('supplier_id');
    var poId = params.get('po_id');
    if (supplierId) {
        var supplierSelect = document.getElementById('supplier_select');
        if (supplierSelect) {
            supplierSelect.value = supplierId;
            loadPOs();
            if (poId) {
                // give time for PO options to load
                setTimeout(function(){
                    var poSelect = document.getElementById('po_select');
                    if (poSelect) {
                        poSelect.value = poId;
                        poSelect.dispatchEvent(new Event('change'));
                    }
                }, 500);
            }
        }
        // open form
        showForm();
    }
})();
</script>
</body>
</html>
