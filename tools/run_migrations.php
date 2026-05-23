<?php
// CLI migration runner
if (php_sapi_name() !== 'cli') {
    echo "This script must be run from the command line.\n";
    exit(1);
}
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../includes/migration_helper.php';

$migrations_dir = __DIR__ . '/../database_migrations';
ensure_migrations_table($conn);
$files = list_migration_files($migrations_dir);
$applied = get_applied_migrations($conn);

$pending = array_filter($files, function($f) use ($applied) { return !isset($applied[basename($f)]); });
if (empty($pending)) {
    echo "No pending migrations.\n";
    exit(0);
}

echo "Pending migrations:\n";
foreach ($pending as $p) echo " - " . basename($p) . "\n";

echo "\nRun pending migrations? (yes/no): ";
$handle = fopen('php://stdin', 'r');
$line = trim(fgets($handle));
if (strtolower($line) !== 'yes') { echo "Aborted.\n"; exit(1); }

foreach ($pending as $p) {
    echo "Applying " . basename($p) . "... ";
    list($ok, $msg) = apply_migration_file($conn, $p, null);
    if ($ok) echo "OK\n"; else { echo "FAIL: $msg\n"; exit(1); }
}

echo "All pending migrations applied.\n";
exit(0);