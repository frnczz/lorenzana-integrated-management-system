<?php
// This file is included by procurement_orders.php
// All variables ($po, $po_items, $pr, $pr_items, $suppliers, $raw_materials, $products, $po_id, $create_from_pr) are already loaded
// Unit list for raw materials
$units = [
    'kg', 'liters', 'pieces', 'boxes', 'bags', 'cans', 'bottles', 'packs', 'rolls', 'sheets', 'meters', 'feet', 'tons', 'gallons'
];
?>
<?php if ($pr): ?>
    <div class="pr-info" style="background: #f0f9ff; border: 2px solid #3b82f6; border-radius: 12px; padding: 15px; margin-bottom: 20px;">
        <h4 style="margin-top:0; color: #1e40af;">📋 Creating PO from PR: <?php echo htmlspecialchars($pr['pr_number']); ?></h4>
        <p><strong>Department:</strong> <?php echo htmlspecialchars($pr['department'] ?? '-'); ?></p>
        <p><strong>Required Date:</strong> <?php echo $pr['required_date'] ? formatDate($pr['required_date']) : '-'; ?></p>
    </div>
<?php endif; ?>
<?php showMessage(); ?>

<div class="card">
    <form method="POST" action="api/save_po.php">
        <input type="hidden" name="po_id" value="<?php echo $po_id; ?>">
        <input type="hidden" name="pr_id" value="<?php echo $create_from_pr; ?>">
        
        <h3>Purchase Order Information</h3>
        <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div>
                <label>PO Number</label>
                <div style="color:var(--text-secondary); padding:8px; background:#f9fafb; border-radius:4px;">
                    <?php echo $po ? htmlspecialchars($po['po_number']) : 'Auto-generated when saved'; ?>
                </div>
            </div>
            <div>
                <label>Supplier *</label>
                <select name="supplier_id" id="supplier_select" style="width:100%; padding:8px;" required>
                    <option value="">-- Select Supplier --</option>
                    <?php foreach ($suppliers as $supplier): ?>
                        <option 
                            value="<?php echo htmlspecialchars($supplier['supplier_id'] ?? ''); ?>"
                            data-contact="<?php echo htmlspecialchars($supplier['contact_number'] ?? $supplier['phone'] ?? ''); ?>"
                            data-terms="<?php echo htmlspecialchars($supplier['payment_terms'] ?? ''); ?>"
                            <?php echo ($po && isset($supplier['supplier_id']) && $po['supplier_id'] == $supplier['supplier_id']) ? 'selected' : ''; ?>
                        >
                            <?php echo htmlspecialchars($supplier['supplier_name'] ?? ''); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Order Date *</label>
                <input type="date" name="order_date" value="<?php echo $po ? $po['order_date'] : date('Y-m-d'); ?>" 
                       style="width:100%; padding:8px;" required>
            </div>
            <div>
                <label>Expected Delivery Date</label>
                <input type="date" name="expected_delivery_date" value="<?php echo $po['expected_delivery_date'] ?? ($pr['required_date'] ?? ''); ?>" 
                       style="width:100%; padding:8px;">
            </div>
            <div>
                <label>Payment Terms</label>
                <select name="payment_terms" id="payment_terms" style="width:100%; padding:8px;">
                    <option value="">-- Select Terms --</option>
                    <option value="Cash">Cash</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                    <option value="Net 30">Net 30</option>
                    <option value="Net 15">Net 15</option>
                    <option value="Check">Check</option>
                </select>
            </div>
            <div>
                <label>Contact Number</label>
                <input type="text" name="contact_number" id="supplier_contact" readonly value="<?php echo $po['contact_number'] ?? ''; ?>" 
                       style="width:100%; padding:8px; background:#f9fafb;">
            </div>
        </div>
        <div style="margin-top:15px;">
            <label>Delivery Address</label>
            <div style="padding:8px; background:#f3f4f6; border:1px solid #e5e7eb; border-radius:4px;">
                Lot 6720 Brgy San Joaquin Sto Tomas Batangas
            </div>
            <input type="hidden" name="delivery_address" value="Lot 6720 Brgy San Joaquin Sto Tomas Batangas">
        </div>
        <div style="margin-top:15px;">
            <label>Notes</label>
            <textarea name="notes" style="width:100%; padding:8px; min-height:60px;"><?php echo htmlspecialchars($po['notes'] ?? ''); ?></textarea>
        </div>
        
        <!-- Items Section -->
        <div class="items-section" style="background: #f9fafb; border: 2px solid #e5e7eb; border-radius: 12px; padding: 20px; margin: 20px 0;">
            <h3>Order Items</h3>
            <div id="items_list">
                <?php if (count($po_items) > 0): ?>
                    <?php foreach ($po_items as $item): ?>
                        <div class="item-row" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 1fr 1fr auto; gap: 10px; align-items: center; padding: 10px; margin-bottom: 8px; background: white; border-radius: 6px; border: 1px solid #e5e7eb;">
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
                                <input type="number" name="quantity[]" value="<?php echo $item['quantity_ordered']; ?>" 
                                       step="0.01" min="0.01" placeholder="Qty" style="width:100%; padding:6px;" required onchange="calculateTotal()">
                            </div>
                            <div>
                                <select name="unit[]" style="width:100%; padding:6px;" required>
                                    <?php foreach ($units as $unit): ?>
                                        <option value="<?php echo $unit; ?>" <?php echo $item['unit'] === $unit ? 'selected' : ''; ?>><?php echo $unit; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <input type="number" name="unit_price[]" value="<?php echo $item['unit_price']; ?>" 
                                       step="0.01" placeholder="Unit Price" style="width:100%; padding:6px;" required onchange="calculateTotal()">
                            </div>
                            <div>
                                <input type="number" name="subtotal[]" value="<?php echo $item['subtotal']; ?>" 
                                       step="0.01" readonly style="width:100%; padding:6px; background:#f3f4f6;">
                            </div>
                            <div>
                                <button type="button" class="btn" onclick="this.parentElement.parentElement.remove(); calculateTotal();">Remove</button>
                            </div>
                            <input type="hidden" name="material_id[]" value="<?php echo $item['material_id'] ?? ''; ?>">
                        </div>
                    <?php endforeach; ?>
                <?php elseif (count($pr_items) > 0): ?>
                    <?php foreach ($pr_items as $pr_item): ?>
                        <div class="item-row" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 1fr 1fr auto; gap: 10px; align-items: center; padding: 10px; margin-bottom: 8px; background: white; border-radius: 6px; border: 1px solid #e5e7eb;">
                            <div>
                                <select name="item_type[]" class="item-type-select" style="width:100%; padding:6px;" required>
                                    <option value="Raw Material" selected>Raw Material</option>
                                </select>
                            </div>
                            <div>
                                <select name="item_id[]" class="item-select" style="width:100%; padding:6px;">
                                    <option value="">-- Select Item --</option>
                                </select>
                                <input type="text" name="item_name[]" value="<?php echo htmlspecialchars($pr_item['item_name']); ?>" 
                                       placeholder="Item Name" style="width:100%; padding:6px; margin-top:5px;" required>
                            </div>
                            <div>
                                <input type="number" name="quantity[]" value="<?php echo $pr_item['quantity']; ?>" 
                                       step="0.01" min="0.01" placeholder="Qty" style="width:100%; padding:6px;" required onchange="calculateTotal()">
                            </div>
                            <div>
                                <select name="unit[]" style="width:100%; padding:6px;" required>
                                    <?php foreach ($units as $unit): ?>
                                        <option value="<?php echo $unit; ?>" <?php echo $pr_item['unit'] === $unit ? 'selected' : ''; ?>><?php echo $unit; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <input type="number" name="unit_price[]" value="<?php echo $pr_item['estimated_unit_price'] ?? 0; ?>" 
                                       step="0.01" placeholder="Unit Price" style="width:100%; padding:6px;" required onchange="calculateTotal()">
                            </div>
                            <div>
                                <input type="number" name="subtotal[]" value="0" 
                                       step="0.01" readonly style="width:100%; padding:6px; background:#f3f4f6;">
                            </div>
                            <div>
                                <button type="button" class="btn" onclick="this.parentElement.parentElement.remove(); calculateTotal();">Remove</button>
                            </div>
                            <input type="hidden" name="material_id[]" value="<?php echo $pr_item['material_id'] ?? ''; ?>">
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div style="margin-top:15px;">
                <button type="button" class="btn" onclick="addItemRow()">+ Add Item</button>
            </div>
            
            <div style="margin-top:15px; padding-top:15px; border-top:2px solid #d1d5db;">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                    <div>
                        <label>Subtotal</label>
                        <input type="number" name="subtotal" id="total_subtotal" value="0" step="0.01" readonly 
                               style="width:100%; padding:8px; background:#f3f4f6;">
                    </div>
                    <div>
                        <label>Tax Amount</label>
                        <input type="number" name="tax_amount" id="tax_amount" value="<?php echo $po['tax_amount'] ?? 0; ?>" 
                               step="0.01" style="width:100%; padding:8px;" onchange="calculateTotal()">
                    </div>
                </div>
                <div style="margin-top:15px;">
                    <label><strong>Total Amount</strong></label>
                    <input type="number" name="total_amount" id="total_amount" value="<?php echo $po['total_amount'] ?? 0; ?>" 
                           step="0.01" readonly style="width:100%; padding:8px; background:#f3f4f6; font-size:18px; font-weight:bold;">
                </div>
            </div>
        </div>
        
        <div style="text-align:right; margin-top:20px;">
            <button type="button" onclick="if(typeof hideForm === 'function') { hideForm(); } else { window.location.href='procurement_orders.php'; }" class="btn" style="margin-right:10px;">Cancel</button>
            <button type="submit" class="btn">Save Purchase Order</button>
        </div>
    </form>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
var rawMaterials = <?php echo json_encode($raw_materials); ?>;

document.getElementById("supplier_select").addEventListener("change", function () {

    var contact = this.options[this.selectedIndex].getAttribute("data-contact");

    document.getElementById("supplier_contact").value = contact ? contact : "";

});

function addItemRow() {
    var list = document.getElementById('items_list');
    var div = document.createElement('div');
    div.className = 'item-row';
    div.style.cssText = 'display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 1fr 1fr auto; gap: 10px; align-items: center; padding: 10px; margin-bottom: 8px; background: white; border-radius: 6px; border: 1px solid #e5e7eb;';
    div.innerHTML = '<div><select name="item_type[]" class="item-type-select" style="width:100%; padding:6px;" required><option value="Raw Material" selected>Raw Material</option></select></div>' +
        '<div><select name="item_id[]" class="item-select" style="width:100%; padding:6px;"><option value="">-- Select Item --</option></select><input type="text" name="item_name[]" placeholder="Item Name" style="width:100%; padding:6px; margin-top:5px;" required></div>' +
        '<div><input type="number" name="quantity[]" step="0.01" min="0.01" placeholder="Qty" style="width:100%; padding:6px;" required onchange="calculateTotal()"></div>' +
        '<div><select name="unit[]" style="width:100%; padding:6px;" required><?php foreach ($units as $unit) echo "<option value=\"$unit\">$unit</option>"; ?></select></div>' +
        '<div><input type="number" name="unit_price[]" step="0.01" placeholder="Unit Price" style="width:100%; padding:6px;" required onchange="calculateTotal()"></div>' +
        '<div><input type="number" name="subtotal[]" step="0.01" readonly style="width:100%; padding:6px; background:#f3f4f6;"></div>' +
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
    var qtyInput = row.querySelector('input[name="quantity[]"]');
    var priceInput = row.querySelector('input[name="unit_price[]"]');
    var subtotalInput = row.querySelector('input[name="subtotal[]"]');
    
    function updateSubtotal() {
        var qty = parseFloat(qtyInput.value) || 0;
        var price = parseFloat(priceInput.value) || 0;
        subtotalInput.value = (qty * price).toFixed(2);
        calculateTotal();
    }
    
    qtyInput.addEventListener('change', updateSubtotal);
    priceInput.addEventListener('change', updateSubtotal);
    
    // Always show raw materials
    rawMaterials.forEach(function(rm) {
        var opt = document.createElement('option');
        opt.value = rm.material_id;
        opt.textContent = rm.material_name;
        itemSelect.appendChild(opt);
    });
    
    itemSelect.addEventListener('change', function() {
        if (this.value) {
            materialInput.value = this.value;
            var rm = rawMaterials.find(m => m.material_id == this.value);
            if (rm) itemNameInput.value = rm.material_name;
        }
    });
    // pre-select if material_id exists
    if (materialInput.value) {
        itemSelect.value = materialInput.value;
        itemSelect.dispatchEvent(new Event('change'));
    }
}

function calculateTotal() {
    var subtotal = 0;
    document.querySelectorAll('input[name="subtotal[]"]').forEach(function(input) {
        subtotal += parseFloat(input.value) || 0;
    });
    
    var tax = parseFloat(document.getElementById('tax_amount')?.value) || 0;
    var total = subtotal + tax;
    
    var subtotalEl = document.getElementById('total_subtotal');
    var totalEl = document.getElementById('total_amount');
    if (subtotalEl) subtotalEl.value = subtotal.toFixed(2);
    if (totalEl) totalEl.value = total.toFixed(2);
}

// Initialize existing rows
document.querySelectorAll('.item-row').forEach(initItemRow);
calculateTotal();
</script>
