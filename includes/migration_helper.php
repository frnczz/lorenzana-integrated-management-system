<?php
// Migration helper functions
if (!defined('LORINIMS_ROOT')) define('LORINIMS_ROOT', dirname(__DIR__));

function ensure_migrations_table($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(255) NOT NULL,
        checksum VARCHAR(64) NOT NULL,
        applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        applied_by INT NULL,
        success TINYINT(1) DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    return $conn->query($sql);
}

function list_migration_files($dir) {
    $files = glob(rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.sql');
    sort($files, SORT_STRING);
    return $files;
}

function get_applied_migrations($conn) {
    $res = $conn->query("SELECT filename, checksum, applied_at FROM migrations ORDER BY applied_at");
    $out = [];
    if ($res) {
        while ($r = $res->fetch_assoc()) $out[$r['filename']] = $r;
    }
    return $out;
}

function apply_migration_file($conn, $filePath, $applied_by = null) {
    $filename = basename($filePath);
    $sql = file_get_contents($filePath);
    if ($sql === false) return [false, 'Could not read file'];
    $checksum = md5($sql);

    // Run inside transaction using multi_query
    if (!$conn->begin_transaction()) return [false, 'Could not start transaction: ' . $conn->error];
    if (!$conn->multi_query($sql)) {
        $err = $conn->error;
        $conn->rollback();
        return [false, 'Multi-query failed: ' . $err];
    }
    // consume results
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->more_results() && $conn->next_result());

    // If error after processing
    if ($conn->errno) {
        $err = $conn->error;
        $conn->rollback();
        return [false, 'Error during execution: ' . $err];
    }

    // record migration
    $stmt = $conn->prepare("INSERT INTO migrations (filename, checksum, applied_by, success) VALUES (?, ?, ?, 1)");
    if ($stmt) {
        $stmt->bind_param('ssi', $filename, $checksum, $applied_by);
        $ok = $stmt->execute();
        $stmt->close();
        if (!$ok) {
            $conn->rollback();
            return [false, 'Could not record migration: ' . $conn->error];
        }
    }

    if (!$conn->commit()) {
        $conn->rollback();
        return [false, 'Could not commit transaction: ' . $conn->error];
    }

    return [true, 'Applied'];
}

?>
