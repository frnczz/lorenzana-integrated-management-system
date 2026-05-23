<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'sales')) {
    header("Location: login.php");
    exit;
}
include "includes/functions.php";
include "db_connect.php";

// Load sales settings
$default_price = floatval(getSalesSetting($conn, 'default_price', 100, true));
$max_discount = floatval(getSalesSetting($conn, 'max_discount', 10, true));
$vat_rate = floatval(getSalesSetting($conn, 'vat_rate', 12, true));
$payment_terms = getSalesSetting($conn, 'payment_terms', 'Cash, 30 Days');

// Sort params for products
$sort_prod = getSortParams('product_name', ['product_name', 'category_name', 'unit_price', 'unit']);
$col_prod = ['product_name' => 'p.product_name', 'category_name' => 'pc.category_name', 'unit_price' => 'p.unit_price', 'unit' => 'p.unit'];
$order_by_prod = isset($col_prod[$sort_prod['column']]) ? $col_prod[$sort_prod['column']] : 'p.product_name';

// Fetch all products with categories
$products = [];
$products_query = $conn->query("
    SELECT p.product_id, p.product_name, p.description, p.unit_price, p.unit, p.image_path,
           pc.category_name
    FROM products p
    LEFT JOIN product_categories pc ON p.category_id = pc.category_id
    ORDER BY " . $order_by_prod . " " . $sort_prod['order'] . ", p.product_name ASC
");
if ($products_query) {
    while ($row = $products_query->fetch_assoc()) {
        $products[] = $row;
    }
}

// Fetch categories for dropdown
$categories = [];
$cat_query = $conn->query("SELECT category_id, category_name FROM product_categories ORDER BY category_name");
if ($cat_query) {
    while ($row = $cat_query->fetch_assoc()) {
        $categories[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Prices | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .price-form-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        .product-image-preview {
            width: 100px;
            height: 100px;
            object-fit: contain;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-top: 10px;
        }
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .products-table th,
        .products-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .products-table th {
            background: linear-gradient(135deg, #FF6B35 0%, #FF8C5A 100%);
            color: white;
            font-weight: 600;
        }
        .products-table tr:hover {
            background-color: #f8f9fa;
        }
        .product-img-cell {
            width: 80px;
        }
        .product-img-cell img {
            width: 60px;
            height: 60px;
            object-fit: contain;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .price-cell {
            font-weight: 600;
            color: #059669;
        }
        .edit-price-btn {
            padding: 4px 12px;
            font-size: 12px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .edit-price-btn:hover {
            background: #2563eb;
        }
        @media (max-width: 768px) {
            .price-form-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="wrapper">
    <?php include "layouts/sidebar.php"; ?>
    <div class="main">
        <?php include "layouts/header.php"; ?>
        <div class="content">
            <h2>Product Price Management</h2>
            <p>Add or update product prices. Prices will be used in sales orders and product dropdowns.</p>
            <p style="font-size:0.9em; color:#666; margin-top:-10px;"><strong>Current Settings:</strong> Default Price: <strong>₱<?php echo number_format($default_price, 2); ?></strong> | Max Discount: <strong><?php echo $max_discount; ?>%</strong> | VAT: <strong><?php echo $vat_rate; ?>%</strong> | Terms: <strong><?php echo htmlspecialchars($payment_terms); ?></strong> | <a href="settings_sales.php" style="color:#3b82f6;">Edit</a></p>
            <?php showMessage(); ?>

            <!-- Add/Update Price Form -->
            <div class="card">
                <h3>Add / Update Product Price</h3>
                <form method="POST" action="api/save_product_price.php" id="priceForm">
                    <table>
                        <tr>
                            <td style="width: 150px;">Product</td>
                            <td>
                                <select name="product_id" id="product_select" style="width:100%; padding:8px;" required>
                                    <option value="">-- Select Product --</option>
                                    <?php foreach ($products as $p): ?>
                                        <option value="<?php echo (int)$p['product_id']; ?>" 
                                            data-price="<?php echo number_format($p['unit_price'], 2); ?>"
                                            data-image="<?php echo htmlspecialchars($p['image_path'] ?? ''); ?>">
                                            <?php echo htmlspecialchars($p['product_name']); ?>
                                            <?php if ($p['unit_price'] > 0): ?>
                                                (Current: ₱<?php echo number_format($p['unit_price'], 2); ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div id="product_image_preview" style="margin-top: 10px;"></div>
                            </td>
                        </tr>
                        <tr>
                            <td>Unit Price (₱)</td>
                            <td>
                                <input type="number" name="unit_price" id="unit_price_input" 
                                       step="0.01" min="0" style="width:100%; padding:8px;" required>
                                <small style="color: var(--text-muted);">Enter price in Philippine Peso</small>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="text-align:right;">
                                <button type="submit" class="btn">Save Price</button>
                                <button type="button" class="btn" onclick="resetForm()" style="margin-left:10px;">Reset</button>
                            </td>
                        </tr>
                    </table>
                </form>
            </div>

            <!-- Products Table with Prices -->
            <div class="card">
                <h3>All Products & Prices</h3>
                <table class="products-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th><?php echo sortHeader('product_name', 'Product Name', $sort_prod); ?></th>
                            <th><?php echo sortHeader('category_name', 'Category', $sort_prod); ?></th>
                            <th><?php echo sortHeader('unit', 'Unit', $sort_prod); ?></th>
                            <th><?php echo sortHeader('unit_price', 'Unit Price', $sort_prod); ?></th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($products) > 0): ?>
                            <?php 
                            $current_category = '';
                            foreach ($products as $p): 
                                if ($current_category !== $p['category_name']):
                                    $current_category = $p['category_name'];
                            ?>
                                <tr style="background-color: #e5e7eb;">
                                    <td colspan="6" style="font-weight: 600; padding: 8px 12px;">
                                        <?php echo htmlspecialchars($current_category ?? 'Uncategorized'); ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <td class="product-img-cell">
                                    <?php if (!empty($p['image_path'])): ?>
                                        <img src="assets/images/products/<?php echo htmlspecialchars($p['image_path']); ?>" 
                                             alt="<?php echo htmlspecialchars($p['product_name']); ?>"
                                             onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'60\' height=\'60\'%3E%3Crect fill=\'%23ddd\' width=\'60\' height=\'60\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%23999\'%3ENo Image%3C/text%3E%3C/svg%3E';">
                                    <?php else: ?>
                                        <div style="width:60px; height:60px; background:#f3f4f6; border:1px solid #ddd; border-radius:4px; display:flex; align-items:center; justify-content:center; color:#9ca3af; font-size:10px;">No Image</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($p['product_name']); ?></strong>
                                    <?php if (!empty($p['description'])): ?>
                                        <br><small style="color: var(--text-muted);"><?php echo htmlspecialchars($p['description']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($p['category_name'] ?? 'Uncategorized'); ?></td>
                                <td><?php echo htmlspecialchars($p['unit']); ?></td>
                                <td class="price-cell">
                                    <?php if ($p['unit_price'] > 0): ?>
                                        ₱<?php echo number_format($p['unit_price'], 2); ?>
                                    <?php else: ?>
                                        <span style="color: #dc2626;">Not Set</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" class="edit-price-btn" 
                                            onclick="editPrice(<?php echo $p['product_id']; ?>, '<?php echo htmlspecialchars($p['product_name'], ENT_QUOTES); ?>', <?php echo $p['unit_price']; ?>)">
                                        Edit Price
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align:center; padding:40px; color:var(--text-muted);">
                                    No products found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php include "layouts/footer.php"; ?>
    </div>
</div>

<script src="assets/js/sidebar.js"></script>
<script>
// Auto-fill price when product is selected
document.getElementById('product_select').addEventListener('change', function() {
    var option = this.options[this.selectedIndex];
    var price = option.getAttribute('data-price');
    var image = option.getAttribute('data-image');
    
    if (price && price !== '0.00') {
        document.getElementById('unit_price_input').value = price;
    } else {
        document.getElementById('unit_price_input').value = '';
    }
    
    // Show image preview
    var previewDiv = document.getElementById('product_image_preview');
    if (image) {
        previewDiv.innerHTML = '<img src="assets/images/products/' + image + '" class="product-image-preview" onerror="this.style.display=\'none\'">';
    } else {
        previewDiv.innerHTML = '';
    }
});

// Edit price function
function editPrice(productId, productName, currentPrice) {
    document.getElementById('product_select').value = productId;
    document.getElementById('unit_price_input').value = currentPrice > 0 ? currentPrice.toFixed(2) : '';
    
    // Trigger change event to show image
    document.getElementById('product_select').dispatchEvent(new Event('change'));
    
    // Scroll to form
    document.querySelector('.card').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// Reset form
function resetForm() {
    document.getElementById('priceForm').reset();
    document.getElementById('product_image_preview').innerHTML = '';
}
</script>
</body>
</html>
