<?php
session_start();
include "../db_connect.php";
require_once __DIR__ . '/../includes/migration_helper.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin')) {
    header("HTTP/1.1 403 Forbidden");
    echo "Forbidden: admin only.";
    exit;
}

$migrations_dir = __DIR__ . '/../database_migrations';
ensure_migrations_table($conn);
$files = list_migration_files($migrations_dir);
$applied = get_applied_migrations($conn);

$results = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run'])) {
    foreach ($files as $f) {
        $name = basename($f);
        if (isset($applied[$name])) continue;
        list($ok, $msg) = apply_migration_file($conn, $f, $_SESSION['user_id']);
        $results[$name] = ['ok' => $ok, 'msg' => $msg];
        if (!$ok) break;
    }
    // refresh applied
    $applied = get_applied_migrations($conn);
}

?><!doctype html>
<html>
<head><meta charset="utf-8"><title>Run Migrations</title><link rel="stylesheet" href="/assets/css/style.css"></head>
<body>
<div class="wrapper"><div class="main"><div class="content">
<h2>Migration Runner</h2>
<p>Admin-only. Shows migration files and allows running pending migrations.</p>

<?php if (!empty($results)): ?>
    <div class="card">
        <h3>Results</h3>
        <ul>
        <?php foreach ($results as $file => $r): ?>
            <li><?php echo htmlspecialchars($file); ?>: <?php echo $r['ok'] ? 'OK' : 'FAIL'; ?> - <?php echo htmlspecialchars($r['msg']); ?></li>
        <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card">
    <h3>Pending Migrations</h3>
    <form method="POST">
    <ul>
    <?php foreach ($files as $f): $n = basename($f); ?>
        <li><?php echo htmlspecialchars($n); ?> <?php if (isset($applied[$n])) echo '<strong>(applied)</strong>'; ?></li>
    <?php endforeach; ?>
    </ul>
    <div style="text-align:right;"><button type="submit" name="run" class="btn">Run pending</button></div>
    </form>
</div>

</div></div></div>
</body>
</html>
