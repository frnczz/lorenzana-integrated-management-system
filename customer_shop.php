<?php
require_once __DIR__ . '/includes/customer_auth.php';
customerRequireLogin();
include __DIR__ . '/db_connect.php';

$products = [];
$pq = $conn->query("
    SELECT p.product_id, p.product_name, p.description, p.unit, p.unit_price, p.image_path, p.category_id,
           pc.category_name,
           COALESCE(p.category_id, 999999) AS cat_sort_key,
           (SELECT (fg.quantity - COALESCE(fg.reserved_quantity, 0)) FROM finished_goods fg WHERE fg.product_id = p.product_id LIMIT 1) AS available
    FROM products p
    LEFT JOIN product_categories pc ON pc.category_id = p.category_id
    ORDER BY cat_sort_key ASC, p.product_name ASC
");
if ($pq) {
    while ($r = $pq->fetch_assoc()) {
        $r['available'] = $r['available'] !== null ? (float)$r['available'] : 0;
        $r['unit_price'] = $r['unit_price'] !== null ? (float)$r['unit_price'] : 0;
        $products[] = $r;
    }
}

$recipe_by_product = [];
$rq = @$conn->query("
    SELECT pr.product_id, pr.quantity_required, rm.material_name, rm.unit
    FROM product_recipes pr
    INNER JOIN raw_materials rm ON rm.material_id = pr.material_id
    ORDER BY pr.product_id, rm.material_name
");
if ($rq) {
    while ($row = $rq->fetch_assoc()) {
        $pid = (int)$row['product_id'];
        if (!isset($recipe_by_product[$pid])) {
            $recipe_by_product[$pid] = [];
        }
        $recipe_by_product[$pid][] = [
            'material_name' => $row['material_name'],
            'quantity_required' => (float)$row['quantity_required'],
            'unit' => $row['unit'] ?? '',
        ];
    }
}

$sections_map = [];
foreach ($products as $p) {
    $pid = (int)$p['product_id'];
    $title = trim((string)($p['category_name'] ?? ''));
    if ($title === '') {
        $title = 'Other products';
    }
    $sort = isset($p['category_id']) && $p['category_id'] !== null ? (int)$p['category_id'] : 999999;
    if (!isset($sections_map[$title])) {
        $sections_map[$title] = ['sort' => $sort, 'title' => $title, 'products' => []];
    }
    $sections_map[$title]['products'][] = [
        'product_id' => $pid,
        'product_name' => $p['product_name'],
        'description' => $p['description'] ?? '',
        'unit' => $p['unit'] ?? 'pcs',
        'unit_price' => $p['unit_price'],
        'available' => $p['available'],
        'image_path' => $p['image_path'] ?? '',
        'recipe' => $recipe_by_product[$pid] ?? [],
    ];
}
uasort($sections_map, static function ($a, $b) {
    return $a['sort'] <=> $b['sort'];
});
$sections_json = array_values($sections_map);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="customer-portal">
<?php include __DIR__ . '/includes/customer_nav.php'; ?>

<div class="cust-main">
    <h1 class="cust-page-title">Shop</h1>
    <p class="cust-lead">Products are grouped by category (vinegar, soy sauce, patis, and more—based on your <strong>Production → Product categories</strong>). Tap a card for details, ingredients, and <strong>add to cart</strong>. Several products can be checked out in <strong>one order</strong>.</p>
    <div id="shopSections"></div>
</div>

<div class="cust-modal-overlay" id="overlay" aria-hidden="true">
    <div class="cust-modal" role="dialog" aria-modal="true" id="modal">
        <button type="button" class="cust-modal-close" id="modalClose" aria-label="Close">×</button>
        <img id="mImg" class="cust-modal-img" src="" alt="">
        <div class="cust-modal-body">
            <h2 id="mTitle"></h2>
            <p class="cust-modal-desc" id="mDesc"></p>
            <div class="cust-modal-recipe" id="mRecipeWrap">
                <h3>Ingredients (warehouse recipe / BOM)</h3>
                <ul id="mRecipe"></ul>
                <p class="hint" style="font-size:11px;color:var(--text-muted);margin-top:8px;">Maintained in Warehouse Settings → Product recipes. Descriptions are edited under Production → Product Management.</p>
            </div>
            <div class="cust-qty-row">
                <label for="mQty">Quantity</label>
                <input type="number" id="mQty" min="0.01" step="0.01" value="1">
                <span id="mStock" style="font-size:13px;color:var(--text-muted);"></span>
            </div>
            <button type="button" class="btn-add" id="mAdd">Add to cart</button>
        </div>
    </div>
</div>
<div class="cust-toast" id="toast"></div>

<script>
var sections = <?php echo json_encode($sections_json, JSON_UNESCAPED_UNICODE); ?>;
var catalog = [];
sections.forEach(function(sec) {
    sec.products.forEach(function(p) { catalog.push(p); });
});
var current = null;

function esc(s) {
    var d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

function renderShop() {
    var root = document.getElementById('shopSections');
    root.innerHTML = '';
    if (!sections.length) {
        root.innerHTML = '<p class="cust-lead">No products available yet.</p>';
        return;
    }
    sections.forEach(function(sec) {
        var block = document.createElement('section');
        block.className = 'shop-category';
        block.setAttribute('data-category', esc(sec.title));
        var head = document.createElement('div');
        head.className = 'shop-category-head';
        head.innerHTML = '<h2>' + esc(sec.title) + '</h2><span class="shop-category-count">' + sec.products.length + ' item(s)</span>';
        block.appendChild(head);
        var grid = document.createElement('div');
        grid.className = 'product-grid';
        sec.products.forEach(function(p) {
            var img = p.image_path ? 'assets/images/products/' + encodeURI(p.image_path) : '';
            var card = document.createElement('div');
            card.className = 'product-card';
            card.setAttribute('data-id', p.product_id);
            card.innerHTML =
                '<img src="' + (img || 'data:image/svg+xml,' + encodeURIComponent('<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"120\" height=\"120\"><rect fill=\"#f1f5f9\" width=\"120\" height=\"120\" rx=\"10\"/><text x=\"60\" y=\"68\" text-anchor=\"middle\" fill=\"#94a3b8\" font-size=\"11\">No image</text></svg>')) + '" alt="">' +
                '<div class="pn">' + esc(p.product_name) + '</div>' +
                '<div class="pp">' + (p.unit_price > 0 ? '₱' + p.unit_price.toFixed(2) : 'Price on request') + '</div>' +
                '<div class="st">Stock: ' + (p.available >= 0 ? p.available : 0) + ' ' + esc(p.unit || '') + '</div>';
            card.addEventListener('click', function() { openModal(p.product_id); });
            grid.appendChild(card);
        });
        block.appendChild(grid);
        root.appendChild(block);
    });
}

function openModal(id) {
    current = catalog.find(function(x) { return x.product_id === id; });
    if (!current) return;
    document.getElementById('overlay').classList.add('open');
    document.getElementById('overlay').setAttribute('aria-hidden', 'false');
    var src = current.image_path ? 'assets/images/products/' + current.image_path : '';
    var imgEl = document.getElementById('mImg');
    imgEl.src = src || '';
    imgEl.style.display = src ? 'block' : 'none';
    document.getElementById('mTitle').textContent = current.product_name;
    var desc = (current.description || '').trim();
    document.getElementById('mDesc').textContent = desc || 'No description yet. Staff can add one under Production → Product Management.';
    var ul = document.getElementById('mRecipe');
    ul.innerHTML = '';
    var rw = document.getElementById('mRecipeWrap');
    if (current.recipe && current.recipe.length) {
        rw.style.display = 'block';
        current.recipe.forEach(function(r) {
            var li = document.createElement('li');
            li.textContent = r.material_name + ': ' + r.quantity_required + ' ' + (r.unit || '').trim() + ' (per 1 ' + (current.unit || 'unit').trim() + ' of product)';
            ul.appendChild(li);
        });
    } else {
        rw.style.display = 'block';
        ul.innerHTML = '<li style="list-style:none;margin-left:-18px;color:var(--text-muted);">No recipe linked yet. Staff can add ingredients under Warehouse Settings.</li>';
    }
    document.getElementById('mQty').value = 1;
    document.getElementById('mStock').textContent = 'Available: ' + (current.available >= 0 ? current.available : 0) + ' ' + (current.unit || '');
    document.getElementById('mAdd').disabled = current.available <= 0;
}

function closeModal() {
    document.getElementById('overlay').classList.remove('open');
    document.getElementById('overlay').setAttribute('aria-hidden', 'true');
    current = null;
}

function toast(msg) {
    var t = document.getElementById('toast');
    t.textContent = msg;
    t.classList.add('show');
    setTimeout(function() { t.classList.remove('show'); }, 2800);
}

document.getElementById('modalClose').addEventListener('click', closeModal);
document.getElementById('overlay').addEventListener('click', function(e) {
    if (e.target.id === 'overlay') closeModal();
});

document.getElementById('mAdd').addEventListener('click', function() {
    if (!current) return;
    var q = parseFloat(document.getElementById('mQty').value);
    if (!q || q <= 0) {
        toast('Enter a valid quantity.');
        return;
    }
    if (q > current.available) {
        toast('Quantity exceeds available stock.');
        return;
    }
    var fd = new FormData();
    fd.append('product_id', current.product_id);
    fd.append('quantity', q);
    fetch('api/customer_cart_add.php', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                toast('Added to cart!');
                closeModal();
                location.reload();
            } else {
                toast(data.message || 'Could not add.');
            }
        })
        .catch(function() { toast('Network error.'); });
});

renderShop();
</script>
</body>
</html>
