<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'production')) {
    header("Location: login.php");
    exit;
}
include "includes/functions.php";
include "db_connect.php";

// Load production settings
$default_batch_size = floatval(getProductionSetting($conn, 'default_batch_size', 100, true));
$production_time_hours = floatval(getProductionSetting($conn, 'production_time_hours', 8, true));
$expected_yield = floatval(getProductionSetting($conn, 'expected_yield', 95, true));
$request_ids_param = $_GET['request_ids'] ?? '';
$request_ids_preload = array_filter(array_map('intval', preg_split('/[\s,]+/', $request_ids_param)));

$fermentation_duration_days_arr = [];
$fdq = @$conn->query("SELECT product_id, setting_value FROM production_settings WHERE setting_key = 'fermentation_duration_days'");
if ($fdq) {
    while ($fdr = $fdq->fetch_assoc()) {
        $fermentation_duration_days_arr[(string)(int)$fdr['product_id']] = max(0, (int)$fdr['setting_value']);
    }
}

// Product category -> material_ids (for filtering raw materials by product type)
$material_ids_by_category = [];
$map_query = @$conn->query("SELECT category_id, material_id FROM product_category_materials");
if ($map_query && $map_query->num_rows > 0) {
    while ($row = $map_query->fetch_assoc()) {
        $cid = (int)$row['category_id'];
        if (!isset($material_ids_by_category[$cid])) $material_ids_by_category[$cid] = [];
        $material_ids_by_category[$cid][] = (int)$row['material_id'];
    }
}

// Raw materials from inventory (raw_materials table) - with expiry date tracking
$raw_materials = [];
$materials_query = $conn->query("
    SELECT material_id, material_name, category, quantity, unit, min_stock_level, expiry_date
    FROM raw_materials
    ORDER BY category, material_name
");
if ($materials_query) {
    while ($row = $materials_query->fetch_assoc()) {
        // Check if material is expired
        $is_expired = false;
        if ($row['expiry_date'] && strtotime($row['expiry_date']) < time()) {
            $is_expired = true;
        }
        $row['is_expired'] = $is_expired;
        $raw_materials[] = $row;
    }
}
$materials_by_category = [];
foreach ($raw_materials as $mat) {
    $cat = $mat['category'] ?? 'Uncategorized';
    if (!isset($materials_by_category[$cat])) $materials_by_category[$cat] = [];
    $materials_by_category[$cat][] = $mat;
}

// Product recipes (BOM) from settings_warehouse - for auto-populating raw materials
$product_recipes = [];
$recipe_res = @$conn->query("SELECT product_id, material_id, quantity_required FROM product_recipes");
if ($recipe_res) {
    while ($r = $recipe_res->fetch_assoc()) {
        $pid = (int)$r['product_id'];
        if (!isset($product_recipes[$pid])) $product_recipes[$pid] = [];
        $product_recipes[$pid][] = [
            'material_id' => (int)$r['material_id'],
            'quantity_required' => (float)$r['quantity_required']
        ];
    }
}
$materials_map = [];
foreach ($raw_materials as $mat) {
    $materials_map[(int)$mat['material_id']] = [
        'material_name' => $mat['material_name'],
        'unit' => $mat['unit'],
        'quantity' => (float)$mat['quantity'],
        'min_stock_level' => (float)($mat['min_stock_level'] ?? 0),
        'is_expired' => !empty($mat['is_expired'])
    ];
}

// Products split: fermentation vs non-fermentation (for dropdowns in form)
$products_fermentation = [];
$products_no_fermentation = [];
$products_shelf_life_map = []; // Map product_id to shelf_life_days
$pfq = $conn->query("
    SELECT p.product_id, p.product_name, COALESCE(p.fermentation_eligible, 1) AS fermentation_eligible, p.image_path, p.category_id,
    COALESCE(p.shelf_life_days, 365) as shelf_life_days,
    (SELECT (fg.quantity - COALESCE(fg.reserved_quantity,0)) FROM finished_goods fg WHERE fg.product_id = p.product_id LIMIT 1) AS available
    FROM products p
    ORDER BY p.product_name
");
if ($pfq) {
    while ($r = $pfq->fetch_assoc()) {
        $r['available'] = $r['available'] !== null ? (float)$r['available'] : 0;
        $r['category_id'] = isset($r['category_id']) ? (int)$r['category_id'] : 0;
        $products_shelf_life_map[$r['product_id']] = (int)$r['shelf_life_days'];
        if (!empty($r['fermentation_eligible'])) {
            $products_fermentation[] = $r;
        } else {
            $products_no_fermentation[] = $r;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Record Production Batch | LORINIMS</title>
<link rel="stylesheet" href="assets/css/style.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
.select2-container .select2-selection--single { height: 38px !important; }
.select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 38px !important; display:flex; align-items:center; }
.select2-results__option img, .select2-container--default .select2-selection--single img { width:40px; height:40px; object-fit:contain; margin-right:8px; border:1px solid #e2e8f0; border-radius:8px; }
.page-header { margin-bottom: var(--spacing-lg); }
.page-header h2 { color: #0f172a; margin-bottom: 8px; font-weight: 700; letter-spacing: -0.02em; }
.page-header p { color: #64748b; }
.form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px; }
.request-select-card {
    background: linear-gradient(145deg, #f8fafc 0%, #eef2ff 100%);
    border: 1px solid #c7d2fe;
    border-radius: 12px;
    padding: var(--spacing-lg);
    margin-bottom: var(--spacing-lg);
    box-shadow: 0 4px 24px rgba(15, 23, 42, 0.06);
}
.request-select-card label { font-weight: 600; color: #312e81; display: block; margin-bottom: 8px; }
.request-select-card select {
    width: 100%; padding: 10px 12px; border-radius: 10px;
    border: 1px solid #a5b4fc; background: #fff;
}
.batch-lines-card {
    margin-bottom: var(--spacing-lg);
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 16px rgba(15, 23, 42, 0.04);
}
.batch-lines-card h3 { margin-bottom: 12px; color: #0f172a; font-weight: 600; }
.order-lines-table { width: 100%; border-collapse: collapse; }
.order-lines-table th, .order-lines-table td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #e2e8f0; }
.order-lines-table th { background: #f1f5f9; font-weight: 600; color: #475569; font-size: 13px; text-transform: uppercase; letter-spacing: 0.03em; }
.order-lines-table tbody tr:hover { background: #f8fafc; }
.materials-section {
    background: linear-gradient(180deg, #fafafa 0%, #f4f4f5 100%);
    border: 1px solid #e4e4e7;
    border-radius: 12px;
    padding: var(--spacing-lg);
    margin: var(--spacing-lg) 0;
}
.material-item {
    display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 10px; align-items: center;
    padding: 12px; margin-bottom: 8px; background: #fff; border-radius: 10px;
    border: 1px solid #e4e4e7; border-left: 4px solid #6366f1;
    transition: box-shadow 0.2s ease, border-color 0.2s ease;
}
.material-item:hover { border-color: #c4b5fd; box-shadow: 0 4px 14px rgba(99, 102, 241, 0.12); }
.material-item input[type="number"] { padding: 8px 10px; border: 1px solid #d4d4d8; border-radius: 8px; width: 100%; }
.material-item input[type="number"]:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15); outline: none; }
.remove-material-btn { background: #ef4444; color: #fff; border: none; border-radius: 8px; padding: 8px 12px; cursor: pointer; font-size: 12px; font-weight: 600; }
.remove-material-btn:hover { background: #dc2626; }
.add-material-section { margin-top: 15px; padding-top: 15px; border-top: 1px dashed #d4d4d8; }
.materials-summary {
    background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
    color: #fff; padding: 15px; border-radius: 10px; margin-top: 15px;
}
.fermentation-display { font-size: 12px; font-weight: 600; padding: 4px 10px; border-radius: 999px; display: inline-block; }
.fermentation-badge.yes { background: #e0e7ff; color: #3730a3; }
.fermentation-badge.no { background: #ecfdf5; color: #166534; }
.product-hover-preview img { width: 40px; height: 40px; object-fit: contain; border-radius: 8px; }
@media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; } .material-item { grid-template-columns: 1fr; } }
</style>
</head>
<body>
<div class="wrapper">
<?php include "layouts/sidebar.php"; ?>
<div class="main">
<?php include "layouts/header.php"; ?>
<div class="content">

<div class="page-header">
    <h2>Record Production Batch</h2>
    <p>Select a production request or add products manually. You can track multiple products per batch and the raw materials used.</p>
    <p style="margin-top:8px;">
        <a href="production_requests.php" class="btn" style="text-decoration:none;">← Production Requests</a>
    </p>
</div>
<?php showMessage(); ?>

<!-- Production Request selector -->
<div class="card request-select-card">
    <label>Production Request (customer order)</label>
    <select id="request_group_select" style="max-width: 100%;">
        <option value="">-- Select after clicking "Create Batch" in Production Requests --</option>
    </select>
    <p id="request_group_summary" style="margin-top:12px; color: var(--text-secondary); font-size: 14px; display: none;"></p>
</div>

<!-- Autofilled batch lines table -->
<div class="card batch-lines-card" id="autofillBatchCard" style="display:none;">
    <h3>Batch lines (from Production Request)</h3>
    <div style="margin-bottom: 15px;">
        <label style="font-weight: 600; display: block; margin-bottom: 8px;">Production Date</label>
        <input type="date" id="autofill_production_date_input" style="width: 100%; max-width: 250px; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color);" value="<?php echo date('Y-m-d'); ?>" onchange="calculateBatchExpiry('autofill')">
    </div>
    <div style="margin-bottom: 15px;">
        <label style="font-weight: 600; display: block; margin-bottom: 8px;">Warehouse Location</label>
        <input type="text" id="autofill_warehouse_location" style="width: 100%; max-width: 400px; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color);" value="Lot 6720 Brgy San Joaquin Sto Tomas Batangas" required>
    </div>
    <div style="margin-bottom:8px;">
        <button type="button" id="saveAutofillBatch" class="btn" style="padding:6px 12px; font-size:12px;">Save Batch</button>
    </div>
    <table class="order-lines-table">
        <thead>
            <tr>
                <th>Product</th>
                <th style="width:100px;">Qty Produced</th>
                <th style="width:170px;">Fermentation (Auto)</th>
                <th style="width:100px;">Line Status</th>
                <th style="width:60px;"></th>
            </tr>
        </thead>
        <tbody id="autofillBatchBody">
            <!-- JS fills this based on selected request -->
        </tbody>
    </table>

    <!-- Raw Materials for Autofill Batch -->
    <div class="materials-section" style="margin-top:20px;">
        <h3 style="margin-top:0;">Raw Materials Used <span style="color:#dc2626; font-size:14px;">*</span></h3>
        <p style="color: var(--text-muted); margin-bottom: 15px;">Auto-filled from recipes (Warehouse Settings). Quantities scale by product qty. You can edit or add more below.</p>
        <div id="autofill_selected_materials"></div>
        <div class="add-material-section">
            <label><strong>Add Raw Material</strong></label>
            <div style="display:grid; grid-template-columns: 2fr 1fr auto; gap:10px; align-items:end;">
                <select id="autofill_material_select" style="padding:10px; border-radius:8px;">
                    <option value="">-- Select material --</option>
                    <?php foreach ($materials_by_category as $category => $materials): ?>
                        <optgroup label="📦 <?php echo htmlspecialchars($category); ?>">
                            <?php foreach ($materials as $mat): ?>
                                <?php 
                                    $status_indicator = '✓';
                                    $is_low = $mat['min_stock_level'] > 0 && $mat['quantity'] <= $mat['min_stock_level'];
                                    if ($is_low) $status_indicator = '⚠️';
                                    if (@$mat['is_expired']) $status_indicator = '❌';
                                ?>
                                <option value="<?php echo $mat['material_id']; ?>"
                                        data-name="<?php echo htmlspecialchars($mat['material_name']); ?>"
                                        data-category="<?php echo htmlspecialchars($category); ?>"
                                        data-quantity="<?php echo $mat['quantity']; ?>"
                                        data-unit="<?php echo htmlspecialchars($mat['unit']); ?>"
                                        data-min="<?php echo $mat['min_stock_level']; ?>"
                                        data-expired="<?php echo (@$mat['is_expired'] ? '1' : '0'); ?>">
                                    <?php echo $status_indicator; ?> <?php echo htmlspecialchars($mat['material_name']); ?> (<?php echo number_format($mat['quantity'], 2); ?> <?php echo htmlspecialchars($mat['unit']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
                <input type="number" id="autofill_material_qty_input" step="0.01" min="0.01" placeholder="Qty" style="padding:10px; border-radius:8px;">
                <button type="button" id="autofill_add_material_btn" class="btn">Add</button>
            </div>
        </div>
        <div class="materials-summary" id="autofill_materials_summary" style="display:none;">
            <h4>Total Materials Selected</h4>
            <p id="autofill_total_materials_count">0 materials</p>
        </div>
    </div>
</div>

<!-- Manual batch lines table -->
<div class="card batch-lines-card" id="manualBatchCard">
    <h3>Product Entry</h3>
    <form method="POST" action="api/save_production_batch.php" id="productionForm">
        <input type="hidden" name="request_ids" id="hidden_request_ids" value="">
        <div class="form-grid">
            <div>
                <label>Production Date</label>
                <input type="date" id="production_date_input" name="production_date" style="width:100%; padding:10px; border-radius:8px; border:1px solid var(--border-color);" value="<?php echo date('Y-m-d'); ?>" required onchange="calculateBatchExpiry()">
            </div>
            <div>
                <label>Packaging Status</label>
                <select name="packaging_status" style="width:100%; padding:10px; border-radius:8px; border:1px solid var(--border-color);" required>
                    <option value="Pending">Pending</option>
                    <option value="In Progress">In Progress</option>
                    <option value="Finished">Finished</option>
                </select>
            </div>
            <div>
                <label>Warehouse Location</label>
                <input type="text" name="warehouse_location" style="width:100%; padding:10px; border-radius:8px; border:1px solid var(--border-color);" value="Lot 6720 Brgy San Joaquin Sto Tomas Batangas" required>
            </div>
        </div>
        <h4 style="margin:20px 0 10px 0; color: var(--text-primary);">Manual Products</h4>
        <table class="order-lines-table">
            <thead>
                <tr>
                    <th style="width:50px;">Image</th>
                    <th>Product</th>
                    <th style="width:100px;">Qty Produced</th>
                    <th style="width:170px;">Fermentation (Auto)</th>
                    <th style="width:100px;">Line Status</th>
                    <th style="width:60px;"></th>
                </tr>
            </thead>
            <tbody id="manualBatchBody">
                <!-- User can add product lines manually -->
            </tbody>
        </table>
        <p><button type="button" id="addManualLine" class="btn" style="margin-bottom:15px;">+ Add another product line</button></p>

        <!-- Raw Materials Section -->
        <div class="materials-section">
            <h3 style="margin-top:0;">Raw Materials Used <span style="color:#dc2626; font-size:14px;">*</span></h3>
            <p style="color: var(--text-muted); margin-bottom: 15px;">Auto-filled from recipes (Warehouse Settings). Quantities scale by product qty. You can edit or add more below.</p>
            <div id="selected_materials"></div>
            <div class="add-material-section">
                <label><strong>Add Raw Material</strong> <span id="material_filter_hint" style="color: var(--text-muted); font-size:12px;"></span></label>
                <div style="display:grid; grid-template-columns: 2fr 1fr auto; gap:10px; align-items:end;">
                    <select id="material_select" style="padding:10px; border-radius:8px; border:1px solid var(--border-color); background:white; cursor:pointer; font-size:14px;">
                        <option value="">-- Select material --</option>
                        <?php foreach ($materials_by_category as $category => $materials): ?>
                            <optgroup label="📦 <?php echo htmlspecialchars($category); ?>">
                                <?php foreach ($materials as $mat): ?>
                                    <?php 
                                        $status_indicator = '✓';
                                        $is_low = $mat['min_stock_level'] > 0 && $mat['quantity'] <= $mat['min_stock_level'];
                                        if ($is_low) $status_indicator = '⚠️';
                                        if (@$mat['is_expired']) $status_indicator = '❌';
                                    ?>
                                    <option value="<?php echo $mat['material_id']; ?>"
                                            data-name="<?php echo htmlspecialchars($mat['material_name']); ?>"
                                            data-category="<?php echo htmlspecialchars($category); ?>"
                                            data-quantity="<?php echo $mat['quantity']; ?>"
                                            data-unit="<?php echo htmlspecialchars($mat['unit']); ?>"
                                            data-min="<?php echo $mat['min_stock_level']; ?>"
                                            data-expired="<?php echo (@$mat['is_expired'] ? '1' : '0'); ?>">
                                        <?php echo $status_indicator; ?> <?php echo htmlspecialchars($mat['material_name']); ?> (<?php echo number_format($mat['quantity'], 2); ?> <?php echo htmlspecialchars($mat['unit']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" id="material_qty_input" step="0.01" min="0.01" placeholder="Qty" style="padding:10px; border-radius:8px; border:1px solid var(--border-color);">
                    <button type="button" id="add_material_btn" class="btn" style="white-space:nowrap;">Add Material</button>
                </div>
            </div>
            <div class="materials-summary" id="materials_summary" style="display:none;">
                <h4>Total Materials Selected</h4>
                <p id="total_materials_count">0 materials</p>
            </div>
        </div>
        <div style="text-align:right; margin-top:20px;">
            <button type="submit" class="btn btn-primary" id="submitProductionForm">Save Production Batch</button>
        </div>

    </form>
</div>

</div>
<?php include "layouts/footer.php"; ?>
</div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
var categoryMaterialIds = <?php echo json_encode($material_ids_by_category); ?>;
var requestIdsPreload = <?php echo json_encode($request_ids_preload); ?>;
var productRecipes = <?php echo json_encode($product_recipes); ?>;
var materialsMap = <?php echo json_encode($materials_map); ?>;
var productsShelfLifeMap = <?php echo json_encode($products_shelf_life_map); ?>;
var productsFermentation = <?php echo json_encode($products_fermentation); ?>;
var productsNoFermentation = <?php echo json_encode($products_no_fermentation); ?>;
var fermentationDurations = <?php echo json_encode($fermentation_duration_days_arr); ?>;
var baseProductOptions = {
    fermentation: productsFermentation.map(function(p) {
        return { id: p.product_id, name: p.product_name, category_id: p.category_id || 0, image_path: p.image_path || '', requested_qty: 0, fermentation_eligible: 1, shelf_life_days: p.shelf_life_days || 365 };
    }),
    noFermentation: productsNoFermentation.map(function(p) {
        return { id: p.product_id, name: p.product_name, category_id: p.category_id || 0, image_path: p.image_path || '', requested_qty: 0, fermentation_eligible: 0, shelf_life_days: p.shelf_life_days || 365 };
    })
};

// ============================================================================
// EXPIRY DATE CALCULATION FUNCTION (Editable/Autofill)
// ============================================================================
var expiryManualOverride = false;
var lastAutoExpiry = '';

function calculateBatchExpiry(section) {
    section = section || 'manual';
    var batchBody = document.getElementById(section === 'autofill' ? 'autofillBatchBody' : 'manualBatchBody');
    var expiryDisplays = document.querySelectorAll('#expiry_date_display');
    var shelfLifeDisplays = document.querySelectorAll('#shelf_life_display');
    var expiryDisplay = expiryDisplays[section === 'autofill' ? 0 : 1];
    var shelfLifeDisplay = shelfLifeDisplays[section === 'autofill' ? 0 : 1];
    var productionDateInput = section === 'autofill' ? document.getElementById('autofill_production_date_input') : document.getElementById('production_date_input');
    var prodDateValue = productionDateInput ? productionDateInput.value : null;
    if (!prodDateValue) {
        if (expiryDisplay) expiryDisplay.value = '';
        if (shelfLifeDisplay) shelfLifeDisplay.value = 'Set production date...';
        return;
    }
    var rows = batchBody.getElementsByTagName('tr');
    if (rows.length === 0) {
        if (expiryDisplay) expiryDisplay.value = '';
        if (shelfLifeDisplay) shelfLifeDisplay.value = 'Add a product to calculate...';
        return;
    }
    var firstRow = rows[0];
    // Look for product select (manual entry uses product_id[])
    var productSelect = firstRow.querySelector('select[name="product_id[]"]');
    if (!productSelect) {
        // Look for hidden product_id input (autofill batch)
        var hiddenInput = firstRow.querySelector('input[name="product_id[]"]');
        if (hiddenInput) {
            var productId = hiddenInput.value;
            if (!productId) {
                if (expiryDisplay) expiryDisplay.value = '';
                if (shelfLifeDisplay) shelfLifeDisplay.value = 'No product assigned...';
                return;
            }
            callExpiryAPI(productId, prodDateValue, expiryDisplay, shelfLifeDisplay, section);
            return;
        }
    }
    if (!productSelect || !productSelect.value) {
        if (expiryDisplay) expiryDisplay.value = '';
        if (shelfLifeDisplay) shelfLifeDisplay.value = 'Select a product...';
        return;
    }
    var productId = productSelect.value;
    callExpiryAPI(productId, prodDateValue, expiryDisplay, shelfLifeDisplay, section);
}

function callExpiryAPI(productId, prodDateValue, expiryDisplay, shelfLifeDisplay, section) {
    // Only update expiry if not manually overridden
    if (expiryManualOverride && section !== 'autofill') return;
    
    // Display shelf life from products table
    var shelfDays = productsShelfLifeMap[productId] || 365;
    if (shelfLifeDisplay) {
        shelfLifeDisplay.value = shelfDays + ' days';
    }
    
    fetch('api/calculate_expiry_date_v2.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'product_id=' + encodeURIComponent(productId) + '&production_date=' + encodeURIComponent(prodDateValue)
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.success) {
            lastAutoExpiry = data.expiry_date;
            if (expiryDisplay && !expiryManualOverride) expiryDisplay.value = data.expiry_date;
        } else {
            if (expiryDisplay && !expiryManualOverride) expiryDisplay.value = '';
            console.error('Expiry calculation error:', data.error);
        }
    })
    .catch(function(error) {
        console.error('Error calculating expiry date:', error);
    });
}

// Hook calculateBatchExpiry to production date changes and page load
document.addEventListener('DOMContentLoaded', function() {
    var prodDateInput = document.getElementById('production_date_input');
    if (prodDateInput) {
        prodDateInput.addEventListener('change', function() {
            expiryManualOverride = false;
            calculateBatchExpiry('manual');
        });
        calculateBatchExpiry('manual');
    }
    var autofillProdDateInput = document.getElementById('autofill_production_date_input');
    if (autofillProdDateInput) {
        autofillProdDateInput.addEventListener('change', function() {
            calculateBatchExpiry('autofill');
        });
    }
    var expiryInput = document.getElementById('expiry_date_display');
    if (expiryInput) {
        expiryInput.addEventListener('input', function() {
            if (expiryInput.value !== lastAutoExpiry) {
                expiryManualOverride = true;
            } else {
                expiryManualOverride = false;
            }
        });
    }
    var resetBtn = document.getElementById('reset_expiry_btn');
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            expiryManualOverride = false;
            if (lastAutoExpiry) document.getElementById('expiry_date_display').value = lastAutoExpiry;
        });
    }
});
</script>
<script src="assets/js/production_record.js"></script>
<script src="assets/js/sidebar.js"></script>
</body>
</html>