<?php
// This file is included by procurement_receiving.php
// Variables ($po, $po_items, $open_pos) are already loaded
?>
<div class="card">
    <form method="POST" action="api/save_grn.php">
        <h3>Receiving Information</h3>
        <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div>
                <label>Purchase Order *</label>
                <?php if ($po): ?>
                    <div style="padding:8px; background:#f0f9ff; border-radius:4px;">
                        <strong><?php echo htmlspecialchars($po['po_number']); ?></strong> - <?php echo htmlspecialchars($po['supplier_name']); ?>
                    </div>
                    <input type="hidden" name="po_id" value="<?php echo $po_id; ?>">
                <?php else: ?>
                    <select name="po_id" id="po_select" style="width:100%; padding:8px;" required onchange="loadPOItems()">
                        <option value="">-- Select PO --</option>
                        <?php foreach ($open_pos as $open_po): ?>
                            <option value="<?php echo $open_po['po_id']; ?>">
                                <?php echo htmlspecialchars($open_po['po_number']); ?> - <?php echo htmlspecialchars($open_po['supplier_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>
            <div>
                <label>Received Date *</label>
                <input type="date" name="received_date" value="<?php echo date('Y-m-d'); ?>" 
                       style="width:100%; padding:8px;" required>
            </div>
        </div>
        
        <!-- Items Section -->
        <div class="items-section" style="background: #f9fafb; border: 2px solid #e5e7eb; border-radius: 12px; padding: 20px; margin: 20px 0;">
            <h3>Received Items</h3>
            <div id="items_list">
                <?php if (count($po_items) > 0): ?>
                    <?php foreach ($po_items as $item): ?>
                        <div class="item-row" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 1fr 1fr 1fr 1fr; gap: 8px; align-items: center; padding: 10px; margin-bottom: 8px; background: white; border-radius: 6px; border: 1px solid #e5e7eb; font-size: 12px;">
                            <div>
                                <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
                                <br><small>Ordered: <?php echo number_format($item['quantity_ordered'], 2); ?> | 
                                Received: <?php echo number_format($item['quantity_received'], 2); ?> | 
                                Remaining: <?php echo number_format($item['remaining_qty'], 2); ?></small>
                            </div>
                            <div>
                                <label>Qty Received</label>
                                <input type="number" name="quantity_received[]" 
                                       value="<?php echo $item['remaining_qty']; ?>" 
                                       max="<?php echo $item['remaining_qty']; ?>"
                                       step="0.01" min="0" style="width:100%; padding:6px; font-size:12px;" required onchange="updateAccepted(this)">
                            </div>
                            <div>
                                <label>Qty Accepted</label>
                                <input type="number" name="quantity_accepted[]" 
                                       value="<?php echo $item['remaining_qty']; ?>" 
                                       step="0.01" min="0" style="width:100%; padding:6px; font-size:12px;" required>
                            </div>
                            <div>
                                <label>Qty Rejected</label>
                                <input type="number" name="quantity_rejected[]" value="0" 
                                       step="0.01" min="0" style="width:100%; padding:6px; font-size:12px;" required>
                            </div>
                            <div>
                                <label>Lot Number</label>
                                <input type="text" name="lot_number[]" placeholder="Lot #" style="width:100%; padding:6px; font-size:12px;">
                            </div>
                            <div>
                                <label>Expiry Date</label>
                                <input type="date" name="expiry_date[]" style="width:100%; padding:6px; font-size:12px;">
                            </div>
                            <div>
                                <label>Location</label>
                                <input type="text" name="warehouse_location[]" placeholder="Warehouse" style="width:100%; padding:6px; font-size:12px;">
                            </div>
                            <input type="hidden" name="item_qc_status[]" value="Pending">
                            <input type="hidden" name="item_qc_remarks[]" value="">
                            <input type="hidden" name="po_item_id[]" value="<?php echo $item['po_item_id']; ?>">
                            <input type="hidden" name="item_name[]" value="<?php echo htmlspecialchars($item['item_name']); ?>">
                            <input type="hidden" name="unit[]" value="<?php echo htmlspecialchars($item['unit']); ?>">
                            <input type="hidden" name="unit_price[]" value="<?php echo $item['unit_price']; ?>">
                            <input type="hidden" name="material_id[]" value="<?php echo $item['material_id'] ?? ''; ?>">
                            <input type="hidden" name="product_id[]" value="<?php echo $item['product_id'] ?? ''; ?>">
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align:center; padding:30px; color:var(--text-muted);">
                        <?php if ($po_id > 0): ?>
                            All items from this PO have been received.
                        <?php else: ?>
                            Select a Purchase Order to receive items.
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- QC Section - Read Only Info from QC Module -->
        <div class="qc-section" style="background: #dbeafe; border: 2px solid #3b82f6; border-radius: 8px; padding: 15px; margin: 15px 0;">
            <h3>QC Information (Read-Only)</h3>
            <p style="font-size:12px; color:#1e40af; margin-bottom:15px;">
                <strong>ℹ️ Note:</strong> QC inspection is handled separately in the Quality Control module. Items will automatically appear there for inspection after this GRN is created. QC status updates will not be recorded here.
            </p>
            <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <label>Initial QC Status</label>
                    <div style="padding:8px; background:white; border-radius:4px; border: 1px solid #3b82f6;">
                        <strong style="color:#3b82f6;">Pending</strong>
                        <input type="hidden" name="qc_status" value="Pending">
                    </div>
                    <small style="color:#1e40af; margin-top:5px; display:block;">All items will be sent to QC module for inspection</small>
                </div>
                <div>
                    <label>Auto-Assigned To</label>
                    <div style="padding:8px; background:#f0f9ff; border-radius:4px;">
                        QC Department
                    </div>
                </div>
            </div>
        </div>
        
        <div style="margin-top:15px;">
            <label>Notes</label>
            <textarea name="notes" style="width:100%; padding:8px; min-height:60px;" 
                      placeholder="Additional notes..."></textarea>
        </div>
        
        <div style="text-align:right; margin-top:20px;">
            <button type="button" onclick="if(typeof hideForm === 'function') { hideForm(); } else { window.location.href='procurement_receiving.php'; }" class="btn" style="margin-right:10px;">Cancel</button>
            <button type="submit" class="btn">Save GRN</button>
        </div>
    </form>
</div>
<script>
function updateAccepted(receivedInput) {
    var row = receivedInput.closest('.item-row');
    var received = parseFloat(receivedInput.value) || 0;
    var acceptedInput = row.querySelector('input[name="quantity_accepted[]"]');
    acceptedInput.value = received;
}

function loadPOItems() {
    var poId = document.getElementById('po_select')?.value;
    if (poId) {
        window.location.href = 'procurement_receiving.php?po_id=' + poId;
    }
}
</script>
