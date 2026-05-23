<?php
$cartCount = 0;
if (!empty($_SESSION['customer_cart']) && is_array($_SESSION['customer_cart'])) {
    foreach ($_SESSION['customer_cart'] as $row) {
        $cartCount += ((float)($row['quantity'] ?? 0) > 0 ? 1 : 0);
    }
}
$cn = htmlspecialchars($_SESSION['full_name'] ?? 'Customer', ENT_QUOTES, 'UTF-8');
?>
<header class="cust-top">
    <div class="cust-top-inner">
        <a href="customer_home.php" class="cust-brand">LORINIMS</a>
        <nav class="cust-nav">
            <a href="customer_home.php">Dashboard</a>
            <a href="customer_shop.php">Shop</a>
            <a href="customer_cart.php">Cart<?php if ($cartCount > 0): ?> <span class="cust-badge"><?php echo (int)$cartCount; ?></span><?php endif; ?></a>
            <span class="cust-name"><?php echo $cn; ?></span>
            <a href="logout.php">Log out</a>
        </nav>
    </div>
</header>
