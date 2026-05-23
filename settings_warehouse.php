<?php
session_start();

// =========================
// ACCESS CONTROL
// =========================
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'warehouse'])) {
    header("Location: login.php");
    exit;
}

include "db_connect.php";
include "includes/functions.php";

// =========================
// SAVE SETTINGS
// =========================
$allowed_keys = ['low_stock_threshold', 'expiry_warning_days', 'default_location', 'stock_method'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['settings']) && is_array($_POST['settings'])) {
    foreach ($_POST['settings'] as $key => $value) {
        if (!in_array($key, $allowed_keys, true)) continue;
        $value = trim((string)$value);
        if ($key === 'low_stock_threshold' || $key === 'expiry_warning_days') $value = (string)max(1, (int)$value);
        elseif ($key === 'stock_method') $value = in_array($value, ['FIFO','FEFO'], true) ? $value : 'FIFO';
        else $value = mb_substr(strip_tags($value), 0, 255);
        $stmt = $conn->prepare("UPDATE warehouse_settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->bind_param("ss", $value, $key);
        $stmt->execute();
        $stmt->close();
    }
    setMessage('Settings saved successfully.', 'success');
}

// =========================
// LOAD SETTINGS
// =========================
$settings = [];
$result = $conn->query("SELECT setting_key, setting_value FROM warehouse_settings");
while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// =========================
// RECIPE (BOM) EDITOR
// =========================
// Load products and raw materials to allow recipe configuration
$products = [];
$prodRes = $conn->query("SELECT product_id, product_name FROM products ORDER BY product_name");
if ($prodRes) {
    while ($r = $prodRes->fetch_assoc()) {
        $products[] = $r;
    }
}

$rawMaterials = [];
$matRes = $conn->query("SELECT material_id, material_name, unit FROM raw_materials ORDER BY material_name");
if ($matRes) {
    while ($r = $matRes->fetch_assoc()) {
        $rawMaterials[] = $r;
    }
}

// Load existing recipes for all products
$product_recipes = [];
$recipeRes = @$conn->query("SELECT product_id, material_id, quantity_required FROM product_recipes");
if ($recipeRes) {
    while ($r = $recipeRes->fetch_assoc()) {
        $pid = (int)$r['product_id'];
        if (!isset($product_recipes[$pid])) {
            $product_recipes[$pid] = [];
        }
        $product_recipes[$pid][] = $r;
    }
}

// Prepare current product selected for editing
$selectedRecipeProductId = isset($_GET['recipe_product_id']) ? (int)$_GET['recipe_product_id'] : 0;
if ($selectedRecipeProductId === 0 && !empty($products)) {
    $selectedRecipeProductId = (int)$products[0]['product_id'];
}

// If this page submitted a recipe edit, apply it
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recipe_product_id'])) {
    $productId = (int)$_POST['recipe_product_id'];
    $selectedRecipeProductId = $productId; // keep selection after save

    // Delete existing recipe entries for this product
    $delStmt = $conn->prepare("DELETE FROM product_recipes WHERE product_id = ?");
    $delStmt->bind_param("i", $productId);
    $delStmt->execute();
    $delStmt->close();

    // Insert new recipe entries
    $materialIds = $_POST['recipe_material_id'] ?? [];
    $quantities = $_POST['recipe_quantity'] ?? [];

    $insStmt = $conn->prepare("INSERT INTO product_recipes (product_id, material_id, quantity_required) VALUES (?, ?, ?)");
    foreach ($materialIds as $index => $matId) {
        $matId = (int)$matId;
        if ($matId <= 0) continue;
        $qty = isset($quantities[$index]) ? (float)$quantities[$index] : 0;
        if ($qty <= 0) continue;
        $insStmt->bind_param("iid", $productId, $matId, $qty);
        $insStmt->execute();
    }
    $insStmt->close();

    // Reload recipes (so UI can show updated).
    $product_recipes = [];
    $recipeRes = @$conn->query("SELECT product_id, material_id, quantity_required FROM product_recipes");
    if ($recipeRes) {
        while ($r = $recipeRes->fetch_assoc()) {
            $pid = (int)$r['product_id'];
            if (!isset($product_recipes[$pid])) {
                $product_recipes[$pid] = [];
            }
            $product_recipes[$pid][] = $r;
        }
    }

    setMessage('Recipe saved successfully.', 'success');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Warehouse Settings | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="wrapper">
<?php include "layouts/sidebar.php"; ?>
<div class="main">
<?php include "layouts/header.php"; ?>

<div class="content">
<h2>Warehouse Settings</h2>
<p>Configure inventory control and stock handling.</p>
<?php showMessage(); ?>

<div class="card">
    <form method="POST">
        <h3>Stock Management</h3>
        <table>
            <tr>
                <td>
                    <label><strong>Alert when stock below (units)</strong></label>
                    <small style="color:#666; display:block; margin-top:3px;">Trigger low stock warning when inventory drops below this quantity. Helps prevent stockouts and plan reorders.</small>
                </td>
                <td>
                    <input type="number" min="1" name="settings[low_stock_threshold]" style="width:100%; padding:6px;"
                           value="<?= htmlspecialchars($settings['low_stock_threshold'] ?? 50) ?>">
                </td>
            </tr>
            <tr>
                <td>
                    <label><strong>Expiry warning (days before)</strong></label>
                    <small style="color:#666; display:block; margin-top:3px;">Show alerts for items expiring within this many days. Allows time to use or sell expiring inventory.</small>
                </td>
                <td>
                    <input type="number" min="1" name="settings[expiry_warning_days]" style="width:100%; padding:6px;"
                           value="<?= htmlspecialchars($settings['expiry_warning_days'] ?? 30) ?>">
                </td>
            </tr>
            <tr>
                <td>
                    <label><strong>Default Storage Location</strong></label>
                    <small style="color:#666; display:block; margin-top:3px;">Primary warehouse location for new inventory items. Can be overridden for specific batch placements.</small>
                </td>
                <td>
                    <input type="text" name="settings[default_location]" style="width:100%; padding:6px;"
                           value="<?= htmlspecialchars($settings['default_location'] ?? 'Main Warehouse') ?>">
                </td>
            </tr>
            <tr>
                <td>
                    <label><strong>Stock Handling Method</strong></label>
                    <small style="color:#666; display:block; margin-top:3px;"><strong>FIFO:</strong> Oldest items used first. <strong>FEFO:</strong> Items closest to expiry used first (better for perishables).</small>
                </td>
                <td>
                    <select name="settings[stock_method]" style="width:100%; padding:6px;">
                        <option value="FIFO" <?= ($settings['stock_method'] ?? 'FIFO') == 'FIFO' ? 'selected' : '' ?>>FIFO (First In, First Out)</option>
                        <option value="FEFO" <?= ($settings['stock_method'] ?? 'FIFO') == 'FEFO' ? 'selected' : '' ?>>FEFO (First Expiry, First Out)</option>
                    </select>
                </td>
            </tr>
        </table>

        <div style="text-align:right;margin-top:15px;">
            <button type="submit" class="btn">Save Settings</button>
        </div>
    </form>
</div>

<div class="card">
    <h3>Product Recipe (BOM) Editor</h3>
    <p>Define which raw materials are needed for each product. This is used in production batching to auto-populate materials.</p>
    <p style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:10px 12px;color:#0c4a6e;font-size:13px;line-height:1.5;margin-bottom:12px;">
        <strong>Customer shop:</strong> The same recipe lines and quantities appear on the customer portal product detail (shop), so shoppers see which raw materials go into each product.
        Short marketing text for each product is edited under <strong>Production → Product Management</strong> (product description).
    </p>

    <form method="POST" id="recipeForm">
        <input type="hidden" name="recipe_product_id" id="recipe_product_id" value="<?= htmlspecialchars($selectedRecipeProductId) ?>">

        <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:center; margin-bottom:12px;">
            <div style="flex:1; min-width:240px;">
                <label><strong>Product</strong></label>
                <select id="recipeProductSelect" style="width:100%; padding:6px;" onchange="onRecipeProductChange(this.value)">
                    <?php foreach ($products as $p): ?>
                        <option value="<?= $p['product_id'] ?>" <?= $p['product_id'] == $selectedRecipeProductId ? 'selected' : '' ?>><?= htmlspecialchars($p['product_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="flex:1; min-width:240px;">
                <label><strong>Add Material</strong></label>
                <div style="display:flex; gap:8px;">
                    <select id="recipeMaterialSelect" style="flex:2; padding:6px;">
                        <option value="">-- Select material --</option>
                        <?php foreach ($rawMaterials as $m): ?>
                            <option value="<?= $m['material_id'] ?>" data-unit="<?= htmlspecialchars($m['unit']) ?>"><?= htmlspecialchars($m['material_name']) ?> (<?= htmlspecialchars($m['unit']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" id="recipeMaterialQty" step="0.01" min="0.01" placeholder="Qty" style="flex:1; padding:6px;" />
                    <button type="button" class="btn" id="addRecipeLine">Add</button>
                </div>
            </div>
        </div>

        <div style="overflow-x:auto;" id="recipeTableWrapper">
            <table class="table" id="recipeTable" style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr>
                        <th style="text-align:left; padding:8px; border-bottom:1px solid #ddd;">Material</th>
                        <th style="text-align:left; padding:8px; border-bottom:1px solid #ddd;">Qty</th>
                        <th style="text-align:left; padding:8px; border-bottom:1px solid #ddd;">Unit</th>
                        <th style="text-align:left; padding:8px; border-bottom:1px solid #ddd;">Actions</th>
                    </tr>
                </thead>
                <tbody id="recipeLines">
                    <!-- JS will populate -->
                </tbody>
            </table>
        </div>

        <div style="text-align:right; margin-top:15px;">
            <button type="submit" class="btn">Save Recipe</button>
        </div>
    </form>
</div>

</div>

<?php include "layouts/footer.php"; ?>
</div>
</div>
<script src="assets/js/sidebar.js"></script>

<script>
(function(){
    var productRecipes = <?= json_encode($product_recipes) ?>;
    var rawMaterials = <?= json_encode(array_column($rawMaterials, null, 'material_id')) ?>;
    var currentProductId = <?= (int)$selectedRecipeProductId ?>;

    function createRow(materialId, quantity) {
        var material = rawMaterials[materialId];
        if (!material) return null;
        var unit = material.unit || '';
        var row = document.createElement('tr');
        row.innerHTML = `
            <td style="padding:8px; border-bottom:1px solid #eee;">
                <select name="recipe_material_id[]" class="recipe-material-select" style="width:100%; padding:6px;">
                    ${Object.values(rawMaterials).map(function(m){
                        var sel = m.material_id == materialId ? 'selected' : '';
                        return `<option value="${m.material_id}" ${sel}>${m.material_name} (${m.unit})</option>`;
                    }).join('')}
                </select>
            </td>
            <td style="padding:8px; border-bottom:1px solid #eee;">
                <input type="number" name="recipe_quantity[]" step="0.01" min="0.01" value="${quantity}" style="width:100%; padding:6px;" required>
            </td>
            <td style="padding:8px; border-bottom:1px solid #eee;">${unit}</td>
            <td style="padding:8px; border-bottom:1px solid #eee;"><button type="button" class="btn" data-action="remove">Remove</button></td>
        `;
        return row;
    }

    function renderRecipeLines(productId) {
        currentProductId = parseInt(productId, 10) || 0;
        document.getElementById('recipe_product_id').value = currentProductId;
        var lines = productRecipes[currentProductId] || [];
        var tbody = document.getElementById('recipeLines');
        tbody.innerHTML = '';
        if (!lines.length) {
            var tr = document.createElement('tr');
            tr.innerHTML = '<td colspan="4" style="padding:12px; color:#666;">No recipe defined yet. Add materials below and save.</td>';
            tbody.appendChild(tr);
            return;
        }
        lines.forEach(function(row){
            var tr = createRow(row.material_id, row.quantity_required);
            if (tr) tbody.appendChild(tr);
        });
    }

    function addRecipeLine() {
        var select = document.getElementById('recipeMaterialSelect');
        var qtyInput = document.getElementById('recipeMaterialQty');
        var materialId = parseInt(select.value, 10);
        var qty = parseFloat(qtyInput.value);
        if (!materialId || !qty || qty <= 0) {
            alert('Select a material and enter a valid quantity.');
            return;
        }

        // Prevent duplicates: if already present, just update quantity
        var existing = document.querySelector('#recipeLines tr');
        var found = false;
        document.querySelectorAll('#recipeLines tr').forEach(function(tr){
            var matSel = tr.querySelector('select[name="recipe_material_id[]"]');
            var qtyInp = tr.querySelector('input[name="recipe_quantity[]"]');
            if (matSel && parseInt(matSel.value, 10) === materialId) {
                qtyInp.value = parseFloat(qtyInp.value || 0) + qty;
                found = true;
            }
        });

        if (!found) {
            var tr = createRow(materialId, qty);
            if (tr) {
                document.getElementById('recipeLines').appendChild(tr);
            }
        }

        select.value = '';
        qtyInput.value = '';
    }

    document.getElementById('addRecipeLine').addEventListener('click', addRecipeLine);

    document.getElementById('recipeLines').addEventListener('click', function(e) {
        if (e.target && e.target.dataset.action === 'remove') {
            var tr = e.target.closest('tr');
            if (tr) tr.remove();
        }
    });

    document.getElementById('recipeLines').addEventListener('change', function(e) {
        if (e.target && e.target.name === 'recipe_material_id[]') {
            var sel = e.target;
            var tr = sel.closest('tr');
            if (!tr) return;
            var material = rawMaterials[sel.value];
            var unitCell = tr.children[2];
            if (material && unitCell) unitCell.textContent = material.unit || '';
        }
    });

    window.onRecipeProductChange = function(productId) {
        renderRecipeLines(productId);
    };

    // Initial render
    renderRecipeLines(currentProductId);
})();
</script>
</body>
</html>
