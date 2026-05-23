<?php
/**
 * Product dropdown helper - 7 main groups with cascading product selection
 * Lorins Patis, Lorins Vinegar, Lorins Soy Sauce, Lorins Alamang Guisado,
 * Lorins Bagoong, Sweet Products, Specialty Products (Coconut Milk, Crab Paste only)
 */

function getProductGroups($conn) {
    $groups = [
        'patis' => ['label' => 'Lorins Patis', 'category_ids' => [1, 8], 'product_filter' => null],
        'vinegar' => ['label' => 'Lorins Vinegar', 'category_ids' => [3], 'product_filter' => null],
        'soy_sauce' => ['label' => 'Lorins Soy Sauce', 'category_ids' => [2], 'product_filter' => null],
        'alamang' => ['label' => 'Lorins Alamang Guisado', 'category_ids' => [4], 'product_filter' => null],
        'bagoong' => ['label' => 'Lorins Bagoong', 'category_ids' => [5], 'product_filter' => null],
        'sweet' => ['label' => 'Sweet Products', 'category_ids' => [9], 'product_filter' => null],
        'specialty' => ['label' => 'Specialty Products', 'category_ids' => [6], 'product_filter' => ['Coconut Milk', 'Crab Paste']],
    ];
    return $groups;
}

function getProductsByGroup($conn, $groupKey) {
    $groups = getProductGroups($conn);
    if (!isset($groups[$groupKey])) return [];

    $group = $groups[$groupKey];
    $ids = $group['category_ids'];
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $sql = "
        SELECT
            fg.fg_id,
            fg.product_id,
            p.product_name,
            p.unit_price,
            (fg.quantity - fg.reserved_quantity) AS available_qty
        FROM finished_goods fg
        JOIN products p ON p.product_id = fg.product_id
        WHERE fg.qc_approved = 1
          AND (fg.quantity - fg.reserved_quantity) > 0
          AND p.category_id IN ($placeholders)
    ";

    $stmt = $conn->prepare($sql);
    $types = str_repeat('i', count($ids));
    $stmt->bind_param($types, ...$ids);
    $stmt->execute();
    $result = $stmt->get_result();

    $products = [];
    while ($row = $result->fetch_assoc()) {

        // Optional name filtering (kept from your original logic)
        if ($group['product_filter'] !== null) {
            foreach ($group['product_filter'] as $filter) {
                if (stripos($row['product_name'], $filter) !== false) {
                    $products[] = $row;
                    break;
                }
            }
        } elseif ($groupKey === 'patis') {
            if (
                preg_match('/Patis|Fish Sauce/i', $row['product_name']) &&
                !preg_match('/Coco Suka|Spicy-Sweet/i', $row['product_name'])
            ) {
                $products[] = $row;
            }
        } else {
            $products[] = $row;
        }
    }

    $stmt->close();
    return $products;
}

function renderProductDropdowns($conn, $formName = 'product_id', $required = true) {
    $groups = getProductGroups($conn);
    if (!function_exists('getProductImagePath')) require_once __DIR__ . '/functions.php';
    $productsJson = [];
    foreach (array_keys($groups) as $key) {
        $prods = getProductsByGroup($conn, $key);
        foreach ($prods as &$p) {
            $p['image_url'] = getProductImagePath($p);
            $p['icon'] = $p['image_url'] ? '' : getProductIcon($p['product_name']);
        }
        $productsJson[$key] = $prods;
    }
    ?>
    <div class="product-dropdown-wrap">
        <div class="form-row">
            <label for="product_category_select">Product Category</label>
            <select id="product_category_select" class="product-category-select">
                <option value="">-- Select Category --</option>
                <?php foreach ($groups as $key => $g): ?>
                    <option value="<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($g['label']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row product-table-row" id="product_size_row" style="display:none;">
            <label>Product / Size</label>
            <input type="hidden" name="<?php echo htmlspecialchars($formName); ?>" id="product_id_hidden" value="" <?php echo $required ? 'data-required="1"' : ''; ?>>
            <div class="product-select-panel">
                <div class="product-select-trigger" id="product_select_trigger">
                    <span class="product-select-placeholder">-- Select Product --</span>
                    <span class="product-select-arrow">▾</span>
                </div>
                <div class="product-select-dropdown" id="product_select_dropdown">
                    <table class="product-select-table">
                        <thead>
                            <tr><th>Image</th><th>Product</th><th>Price</th></tr>
                        </thead>
                        <tbody id="product_select_tbody">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script>
    (function() {
        var productsData = <?php echo json_encode($productsJson); ?>;
        var catSelect = document.getElementById('product_category_select');
        var trigger = document.getElementById('product_select_trigger');
        var dropdown = document.getElementById('product_select_dropdown');
        var tbody = document.getElementById('product_select_tbody');
        var hiddenInput = document.getElementById('product_id_hidden');
        var placeholder = document.querySelector('.product-select-placeholder');
        var sizeRow = document.getElementById('product_size_row');
        var selectedProduct = null;

        function renderProducts(key) {
            tbody.innerHTML = '';
            if (!key || !productsData[key]) return;

            productsData[key].forEach(function(p) {
                var tr = document.createElement('tr');
                tr.className = 'product-select-row';
                tr.dataset.productId = p.product_id;
                tr.dataset.productName = p.product_name;
                tr.dataset.unitPrice = p.unit_price || 0;

                var imgCell = '<td class="product-td-img">';
                if (p.image_url) {
                    imgCell += '<img src="' + p.image_url + '" alt="" class="product-row-img">';
                } else {
                    imgCell += '<span class="product-row-icon">' + (p.icon || '📦') + '</span>';
                }
                imgCell += '</td>';

                var price = parseFloat(p.unit_price || 0);
                var priceDisplay = price > 0 ? '₱' + price.toFixed(2) : 'N/A';

                tr.innerHTML =
                    imgCell +
                    '<td class="product-td-name">' +
                        '<div>' + (p.product_name || '') + '</div>' +
                        '<small style="color:#6b7280;">Available: ' + p.available_qty + '</small>' +
                    '</td>' +
                    '<td class="product-td-price" style="text-align:right; font-weight:600; color:#059669;">' + priceDisplay + '</td>';

                // ✅ SAFETY: disable zero / negative stock
                if (parseFloat(p.available_qty) <= 0) {
                    tr.style.opacity = '0.4';
                    tr.style.pointerEvents = 'none';
                } else {
                    tr.addEventListener('click', function() {
                        hiddenInput.value = p.product_id;
                        placeholder.textContent = p.product_name;
                        placeholder.classList.add('selected');

                        dropdown.classList.remove('open');
                        trigger.classList.remove('open');

                        tbody.querySelectorAll('.product-select-row')
                            .forEach(function(r) { r.classList.remove('selected'); });

                        tr.classList.add('selected');
                    });
                }

                tbody.appendChild(tr);
            });
        }

        if (catSelect) {
            catSelect.addEventListener('change', function() {
                var key = this.value;
                sizeRow.style.display = key ? 'block' : 'none';
                if (key && hiddenInput.dataset.required === '1') {
                    hiddenInput.setAttribute('required', 'required');
                } else {
                    hiddenInput.removeAttribute('required');
                }
                hiddenInput.value = '';
                placeholder.textContent = '-- Select Product --';
                placeholder.classList.remove('selected');
                selectedProduct = null;
                renderProducts(key);
                dropdown.classList.remove('open');
                trigger.classList.remove('open');
            });
        }

        if (trigger && dropdown) {
            trigger.addEventListener('click', function(e) {
                e.stopPropagation();
                var isOpen = dropdown.classList.toggle('open');
                trigger.classList.toggle('open', isOpen);
            });
            document.addEventListener('click', function() {
                dropdown.classList.remove('open');
                trigger.classList.remove('open');
            });
            dropdown.addEventListener('click', function(e) { e.stopPropagation(); });
        }
    })();
    </script>
    <?php
}
