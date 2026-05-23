<?php
require_once __DIR__ . '/includes/customer_auth.php';
customerRequireLogin();
include __DIR__ . '/db_connect.php';

$cid = customerId();
$name = htmlspecialchars($_SESSION['full_name'] ?? 'Customer', ENT_QUOTES, 'UTF-8');

$ok = '';
$err = '';
if (!empty($_SESSION['customer_checkout_ok'])) {
    $ok = (string)$_SESSION['customer_checkout_ok'];
    unset($_SESSION['customer_checkout_ok']);
}
if (!empty($_SESSION['customer_checkout_error'])) {
    $err = (string)$_SESSION['customer_checkout_error'];
    unset($_SESSION['customer_checkout_error']);
}

$cust = $conn->prepare('SELECT customer_name, address, contact_number, email FROM customers WHERE customer_id = ? LIMIT 1');
$cust->bind_param('i', $cid);
$cust->execute();
$profile = $cust->get_result()->fetch_assoc();
$cust->close();

$orders = [];
$q = $conn->prepare('
    SELECT order_id, order_number, order_date, total_amount, status, fulfillment_type
    FROM sales_orders
    WHERE customer_id = ?
    ORDER BY order_date DESC, order_id DESC
    LIMIT 50
');
$q->bind_param('i', $cid);
$q->execute();
$r = $q->get_result();
while ($row = $r->fetch_assoc()) {
    $orders[] = $row;
}
$q->close();

$cartLines = 0;
if (!empty($_SESSION['customer_cart']) && is_array($_SESSION['customer_cart'])) {
    foreach ($_SESSION['customer_cart'] as $row) {
        $cartLines += ((float)($row['quantity'] ?? 0) > 0 ? 1 : 0);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="customer-portal">
<?php include __DIR__ . '/includes/customer_nav.php'; ?>

<div class="cust-main">
    <div class="cust-hero">
        <h1>Hello, <?php echo $name; ?></h1>
        <p>Order from the shop, build your cart, and check out once—several different products can go in a single order.</p>
    </div>

    <?php if ($ok !== ''): ?>
        <div class="cust-flash-ok"><?php echo htmlspecialchars($ok, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($err !== ''): ?>
        <div class="cust-flash-err"><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="cust-dashboard-grid">
        <div class="cust-tile">
            <h2>Shop</h2>
            <p>Browse products by category with photos, descriptions, and ingredient lists.</p>
            <a class="cust-btn-primary" href="customer_shop.php">Go to shop</a>
        </div>
        <div class="cust-tile">
            <h2>Cart</h2>
            <p><?php echo $cartLines > 0 ? 'You have items ready to review.' : 'Your cart is empty.'; ?></p>
            <a href="customer_cart.php" class="<?php echo $cartLines > 0 ? 'cust-btn-primary' : 'cust-btn-secondary'; ?>">View cart<?php if ($cartLines > 0): ?> (<?php echo (int)$cartLines; ?>)<?php endif; ?></a>
        </div>
    </div>

    <div class="cust-card" style="margin-bottom:20px;">
        <h2 class="cust-page-title" style="font-size:1.1rem;margin-bottom:8px;">Your profile</h2>
        <p class="cust-lead" style="margin-bottom:12px;">Details we have on file (sales can update your record if something is missing).</p>
        <?php if ($profile): ?>
            <dl class="prof">
                <dt>Name</dt>
                <dd><?php echo htmlspecialchars((string)($profile['customer_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></dd>
                <dt>Phone</dt>
                <dd><?php echo htmlspecialchars((string)($profile['contact_number'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></dd>
                <dt>Email</dt>
                <dd><?php echo htmlspecialchars((string)($profile['email'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></dd>
                <dt>Default address</dt>
                <dd><?php echo htmlspecialchars((string)($profile['address'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></dd>
            </dl>
        <?php else: ?>
            <p style="color:var(--text-muted);text-align:center;padding:24px;">No profile loaded.</p>
        <?php endif; ?>
    </div>

    <div class="cust-card">
        <h2 class="cust-page-title" style="font-size:1.1rem;margin-bottom:8px;">Your orders</h2>
        <p class="cust-lead" style="margin-bottom:12px;">Orders linked to your account—online and those entered by sales.</p>
        <?php if (count($orders) === 0): ?>
            <p style="color:var(--text-muted);text-align:center;padding:24px;">No orders yet. <a href="customer_shop.php" style="color:var(--primary);font-weight:600;">Start shopping</a></p>
        <?php else: ?>
            <table class="cust-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Fulfillment</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($o['order_number'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($o['order_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>₱<?php echo number_format((float)$o['total_amount'], 2); ?></td>
                            <td><?php echo htmlspecialchars($o['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php
                                $ft = (string)($o['fulfillment_type'] ?? '');
                                echo htmlspecialchars($ft === 'Pickup' ? 'Customer pickup' : $ft, ENT_QUOTES, 'UTF-8');
                            ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
