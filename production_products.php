<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'production')) {
    header("Location: login.php");
    exit;
}
include "includes/functions.php";
include "db_connect.php";

// Fetch all products with categories (with sorting)
$products = [];

// Sorting: default by category then product name
$sort = getSortParams('product_name', [
    'product_name',
    'category_name',
    'unit',
    'fermentation_eligible'
]);

$column_map = [
    'product_name'         => 'p.product_name',
    'category_name'        => 'pc.category_name',
    'unit'                 => 'p.unit',
    'fermentation_eligible'=> 'p.fermentation_eligible'
];

$order_by = isset($column_map[$sort['column']]) ? $column_map[$sort['column']] : 'pc.category_name, p.product_name';

$pagination = function_exists('getPagination') ? getPagination($conn, "SELECT COUNT(*) as c FROM products p") : ['offset' => 0, 'per_page' => 25, 'total' => 0, 'total_pages' => 1];
$products_query = $conn->query("
    SELECT p.product_id, p.product_name, p.description, p.unit, p.image_path, p.fermentation_eligible,
           pc.category_id, pc.category_name
    FROM products p
    LEFT JOIN product_categories pc ON p.category_id = pc.category_id
    ORDER BY {$order_by} {$sort['order']}
    LIMIT {$pagination['offset']}, {$pagination['per_page']}
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
    <title>Product Management | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .product-form-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        .product-image-preview {
            width: 150px;
            height: 150px;
            object-fit: contain;
            border: 2px solid #ddd;
            border-radius: 8px;
            margin-top: 10px;
            background: #f9fafb;
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
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .products-table tr:hover {
            background-color: #f8f9fa;
        }
        .product-img-cell {
            width: 100px;
        }
        .product-img-cell img {
            width: 80px;
            height: 80px;
            object-fit: contain;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: #f9fafb;
        }
        .delete-btn {
            padding: 4px 12px;
            font-size: 12px;
            background: #dc2626;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .delete-btn:hover {
            background: #b91c1c;
        }
        .edit-btn {
            padding: 4px 12px;
            font-size: 12px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 5px;
        }
        .edit-btn:hover {
            background: #2563eb;
        }
        .file-upload-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }
        .file-upload-wrapper input[type=file] {
            width: 100%;
            padding: 8px;
        }
        @media (max-width: 768px) {
            .product-form-container {
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
            <h2>Product Management</h2>
            <p>Add, edit, or delete products. Products are used in production batches and inventory.</p>
            <?php showMessage(); ?>

            <!-- Add/Edit Product Form -->
            <div class="card">
                <h3 id="form_title">Add New Product</h3>
                <form method="POST" action="api/save_product.php" id="productForm" enctype="multipart/form-data">
                    <input type="hidden" name="product_id" id="product_id" value="">
                    <table>
                        <tr>
                            <td style="width: 150px;">Product Name</td>
                            <td>
                                <input type="text" name="product_name" id="product_name" 
                                       style="width:100%; padding:8px;" required>
                            </td>
                        </tr>
                        <tr>
                            <td>Description</td>
                            <td>
                                <textarea name="description" id="description" 
                                          style="width:100%; padding:8px; min-height:80px;"></textarea>
                            </td>
                        </tr>
                        <tr>
                            <td>Category</td>
                            <td>
                                <select name="category_id" id="category_id" style="width:100%; padding:8px;" required>
                                    <option value="">-- Select Category --</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo (int)$cat['category_id']; ?>">
                                            <?php echo htmlspecialchars($cat['category_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>Unit</td>
                            <td>
                                <select name="unit" id="unit" style="width:100%; padding:8px;" required>
                                    <option value="pcs">pcs</option>
                                    <option value="kg">kg</option>
                                    <option value="liters">liters</option>
                                    <option value="boxes">boxes</option>
                                    <option value="bottles">bottles</option>
                                    <option value="packs">packs</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>Fermentation Eligible</td>
                            <td>
                                <select name="fermentation_eligible" id="fermentation_eligible" style="width:100%; padding:8px;">
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>Product Image</td>
                            <td>
                                <div class="file-upload-wrapper">
                                    <input type="file" name="product_image" id="product_image" 
                                           accept="image/*" onchange="previewImage(this)">
                                    <small style="color: var(--text-muted);">Upload product image (JPG, PNG, WEBP)</small>
                                </div>
                                <div id="image_preview_container" style="margin-top: 10px;">
                                    <img id="image_preview" class="product-image-preview" style="display:none;">
                                    <div id="current_image_info" style="margin-top: 5px;"></div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="text-align:right;">
                                <button type="submit" class="btn" id="submit_btn">Add Product</button>
                                <button type="button" class="btn" onclick="resetForm()" style="margin-left:10px;">Reset</button>
                                <button type="button" class="btn" onclick="cancelEdit()" id="cancel_btn" style="margin-left:10px; display:none;">Cancel Edit</button>
                            </td>
                        </tr>
                    </table>
                </form>
            </div>

            <!-- Products Table -->
            <div class="card">
                <h3>All Products</h3>
                <?php if (function_exists('renderPerPageSelector')) echo '<div class="pagination-toolbar">' . renderPerPageSelector($conn, $pagination['per_page']) . '</div>'; ?>
                <table class="products-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th><?php echo sortHeader('product_name', 'Product Name', $sort); ?></th>
                            <th><?php echo sortHeader('category_name', 'Category', $sort); ?></th>
                            <th><?php echo sortHeader('unit', 'Unit', $sort); ?></th>
                            <th><?php echo sortHeader('fermentation_eligible', 'Fermentation', $sort); ?></th>
                            <th>Actions</th>
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
                                             onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'80\' height=\'80\'%3E%3Crect fill=\'%23f3f4f6\' width=\'80\' height=\'80\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%239ca3af\'%3ENo Image%3C/text%3E%3C/svg%3E';">
                                    <?php else: ?>
                                        <div style="width:80px; height:80px; background:#f3f4f6; border:1px solid #ddd; border-radius:4px; display:flex; align-items:center; justify-content:center; color:#9ca3af; font-size:10px;">No Image</div>
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
                                <td><?php echo $p['fermentation_eligible'] ? 'Yes' : 'No'; ?></td>
                                <td>
                                    <button type="button" class="edit-btn" 
                                            onclick="editProduct(<?php echo htmlspecialchars(json_encode($p)); ?>)">
                                        Edit
                                    </button>
                                    <button type="button" class="delete-btn" 
                                            onclick="deleteProduct(<?php echo $p['product_id']; ?>, '<?php echo htmlspecialchars($p['product_name'], ENT_QUOTES); ?>')">
                                        Delete
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
                <?php if (function_exists('renderPagination')) echo renderPagination($pagination); ?>
            </div>
        </div>
        <?php include "layouts/footer.php"; ?>
    </div>
</div>

<script src="assets/js/sidebar.js"></script>
<script>
// Preview image before upload
function previewImage(input) {
    var preview = document.getElementById('image_preview');
    var container = document.getElementById('image_preview_container');
    
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.style.display = 'none';
    }
}

// Edit product function
function editProduct(product) {
    document.getElementById('product_id').value = product.product_id;
    document.getElementById('product_name').value = product.product_name;
    document.getElementById('description').value = product.description || '';
    document.getElementById('category_id').value = product.category_id || '';
    document.getElementById('unit').value = product.unit || 'pcs';
    document.getElementById('fermentation_eligible').value = product.fermentation_eligible || '1';
    
    // Show current image if exists
    var currentImageInfo = document.getElementById('current_image_info');
    var imagePreview = document.getElementById('image_preview');
    if (product.image_path) {
        currentImageInfo.innerHTML = '<small style="color: var(--text-muted);">Current: ' + product.image_path + '</small>';
        imagePreview.src = 'assets/images/products/' + product.image_path;
        imagePreview.style.display = 'block';
    } else {
        currentImageInfo.innerHTML = '';
        imagePreview.style.display = 'none';
    }
    
    // Update form title and button
    document.getElementById('form_title').textContent = 'Edit Product';
    document.getElementById('submit_btn').textContent = 'Update Product';
    document.getElementById('cancel_btn').style.display = 'inline-block';
    
    // Scroll to form
    document.querySelector('.card').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// Delete product function
function deleteProduct(productId, productName) {
    if (!confirm('Are you sure you want to delete "' + productName + '"?\n\nThis action cannot be undone and may affect production batches and inventory records.')) {
        return;
    }
    
    // Create form and submit
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = 'api/delete_product.php';
    
    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'product_id';
    input.value = productId;
    form.appendChild(input);
    
    document.body.appendChild(form);
    form.submit();
}

// Reset form
function resetForm() {
    document.getElementById('productForm').reset();
    document.getElementById('product_id').value = '';
    document.getElementById('image_preview').style.display = 'none';
    document.getElementById('current_image_info').innerHTML = '';
    document.getElementById('form_title').textContent = 'Add New Product';
    document.getElementById('submit_btn').textContent = 'Add Product';
    document.getElementById('cancel_btn').style.display = 'none';
}

// Cancel edit
function cancelEdit() {
    resetForm();
}
</script>
</body>
</html>
