<?php
require_once __DIR__ . '/includes/customer_auth.php';
customerRequireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['cart_action'] ?? '') === 'update') {
    $cart = $_SESSION['customer_cart'] ?? [];
    $qtys = $_POST['qty'] ?? [];
    if (is_array($qtys)) {
        $new = [];
        foreach ($cart as $line) {
            $pid = (int)($line['product_id'] ?? 0);
            if ($pid <= 0) {
                continue;
            }
            $q = isset($qtys[$pid]) ? (float)$qtys[$pid] : (float)($line['quantity'] ?? 0);
            if ($q > 0) {
                $new[] = ['product_id' => $pid, 'quantity' => $q];
            }
        }
        $_SESSION['customer_cart'] = $new;
    }
    header('Location: customer_cart.php');
    exit;
}

if (isset($_GET['remove'])) {
    $rid = (int)$_GET['remove'];
    if ($rid > 0) {
        $_SESSION['customer_cart'] = array_values(array_filter(
            $_SESSION['customer_cart'] ?? [],
            static function ($row) use ($rid) {
                return (int)($row['product_id'] ?? 0) !== $rid;
            }
        ));
    }
    header('Location: customer_cart.php');
    exit;
}

include __DIR__ . '/db_connect.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/inventory_service.php';

$cart = $_SESSION['customer_cart'] ?? [];
$lines = [];
$subtotal = 0.0;

foreach ($cart as $row) {
    $pid = (int)($row['product_id'] ?? 0);
    $qty = (float)($row['quantity'] ?? 0);
    if ($pid <= 0 || $qty <= 0) {
        continue;
    }
    $st = $conn->prepare('SELECT product_id, product_name, unit, COALESCE(unit_price, 0) AS unit_price FROM products WHERE product_id = ? LIMIT 1');
    $st->bind_param('i', $pid);
    $st->execute();
    $pr = $st->get_result()->fetch_assoc();
    $st->close();
    if (!$pr) {
        continue;
    }
    $up = (float)$pr['unit_price'];
    $lineSub = $up * $qty;
    $subtotal += $lineSub;
    $avail = getProductAvailableStock($conn, $pid);
    $lines[] = [
        'product_id' => $pid,
        'product_name' => $pr['product_name'],
        'unit' => $pr['unit'] ?? '',
        'unit_price' => $up,
        'quantity' => $qty,
        'line_subtotal' => $lineSub,
        'available' => $avail,
    ];
}

$cust = $conn->prepare('SELECT customer_name, address, contact_number FROM customers WHERE customer_id = ? LIMIT 1');
$cid = customerId();
$cust->bind_param('i', $cid);
$cust->execute();
$crow = $cust->get_result()->fetch_assoc();
$cust->close();
$default_address = trim((string)($crow['address'] ?? ''));
$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="customer-portal">
<?php include __DIR__ . '/includes/customer_nav.php'; ?>

<div class="cust-wrap">
    <h1 class="cust-page-title">Your cart</h1>
    <p class="cust-lead">All items are placed as <strong>one order</strong> at checkout—you can mix patis, soy sauce, vinegar, and more in a single order.</p>

    <?php if (count($lines) === 0): ?>
        <div class="cust-card" style="text-align:center;padding:40px 16px;">
            <p style="color:var(--text-muted);">Your cart is empty.</p>
            <p style="margin-top:14px;"><a class="cust-btn-primary" href="customer_shop.php">Browse the shop</a></p>
        </div>
    <?php else: ?>
        <form method="post" class="cust-card" style="margin-bottom:20px;">
            <input type="hidden" name="cart_action" value="update">
            <table class="cust-table cust-cart-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Qty</th>
                        <th>Subtotal</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lines as $L): ?>
                        <tr>
                            <td>
                                <?php echo htmlspecialchars($L['product_name'], ENT_QUOTES, 'UTF-8'); ?>
                                <?php if ($L['quantity'] > $L['available']): ?>
                                    <div class="warn">Only <?php echo htmlspecialchars((string)$L['available'], ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars($L['unit'], ENT_QUOTES, 'UTF-8'); ?> available—lower quantity or remove before checkout.</div>
                                <?php endif; ?>
                            </td>
                            <td>₱<?php echo number_format($L['unit_price'], 2); ?></td>
                            <td>
                                <input class="qty-inp" type="number" name="qty[<?php echo (int)$L['product_id']; ?>]" min="0.01" step="0.01" value="<?php echo htmlspecialchars((string)$L['quantity'], ENT_QUOTES, 'UTF-8'); ?>">
                                <span style="font-size:12px;color:var(--text-muted);"><?php echo htmlspecialchars($L['unit'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </td>
                            <td>₱<?php echo number_format($L['line_subtotal'], 2); ?></td>
                            <td><a class="rm" href="customer_cart.php?remove=<?php echo (int)$L['product_id']; ?>">Remove</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div style="margin-top:16px;display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
                <button type="submit" class="cust-btn-secondary">Update quantities</button>
                <strong style="margin-left:auto;color:var(--text-primary);">Estimated total: ₱<?php echo number_format($subtotal, 2); ?></strong>
            </div>
        </form>

        <div class="cust-card checkout">
            <h2 style="margin:0 0 12px;font-size:1.1rem;color:var(--text-primary);">Checkout</h2>
            <?php
            $block_checkout = false;
            foreach ($lines as $L) {
                if ($L['quantity'] > $L['available']) {
                    $block_checkout = true;
                    break;
                }
            }
            ?>
            <?php if ($block_checkout): ?>
                <p class="warn">Fix quantities above before placing your order.</p>
            <?php else: ?>
                <form method="post" action="api/customer_place_order.php">
                    <label for="fulfillment_type">Fulfillment</label>
                    <select id="fulfillment_type" name="fulfillment_type" required>
                        <option value="Delivery">Delivery</option>
                        <option value="Pickup">Customer pickup (warehouse)</option>
                    </select>

                    <div id="deliveryFields">
                        <label for="delivery_address">Delivery address</label>
                        <input type="text" id="delivery_address" name="delivery_address" value="<?php echo htmlspecialchars($default_address, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Street, barangay, city">

                        <label for="delivery_date">Preferred delivery date (optional)</label>
                        <input type="date" id="delivery_date" name="delivery_date" min="<?php echo htmlspecialchars($today, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>

                    <label for="order_date">Order date</label>
                    <input type="date" id="order_date" name="order_date" required value="<?php echo htmlspecialchars($today, ENT_QUOTES, 'UTF-8'); ?>">

                    <div style="margin-top:20px;">
                        <button type="submit" class="cust-btn-primary">Place order</button>
                    </div>
                </form>
                <script>
                (function() {
                    var sel = document.getElementById('fulfillment_type');
                    var box = document.getElementById('deliveryFields');
                    function sync() {
                        var d = sel.value === 'Delivery';
                        box.style.display = d ? 'block' : 'none';
                        document.getElementById('delivery_address').required = d;
                    }
                    sel.addEventListener('change', sync);
                    sync();
                })();
                </script>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
