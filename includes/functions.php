<?php
// ================================
// GLOBAL PROJECT ROOT (SAFE ONCE)
// ================================
if (!defined('LORINIMS_ROOT')) {
    define('LORINIMS_ROOT', dirname(__DIR__));
}

// Load inventory service (event-driven stock management)
require_once __DIR__ . '/inventory_service.php';

// ================================
// INPUT SANITIZATION & SECURITY
// ================================
if (!function_exists('sanitizeString')) {
    function sanitizeString($input, $maxLength = 255) {
        if ($input === null || $input === '') return '';
        return mb_substr(trim(strip_tags((string)$input)), 0, $maxLength);
    }
}
if (!function_exists('sanitizeInt')) {
    function sanitizeInt($input, $min = null, $max = null) {
        $val = filter_var($input, FILTER_VALIDATE_INT);
        if ($val === false) return 0;
        if ($min !== null && $val < $min) return $min;
        if ($max !== null && $val > $max) return $max;
        return $val;
    }
}
if (!function_exists('sanitizeFloat')) {
    function sanitizeFloat($input, $min = null, $max = null) {
        $val = filter_var($input, FILTER_VALIDATE_FLOAT);
        if ($val === false) return 0.0;
        if ($min !== null && $val < $min) return (float)$min;
        if ($max !== null && $val > $max) return (float)$max;
        return (float)$val;
    }
}
if (!function_exists('sanitizeEmail')) {
    function sanitizeEmail($input) {
        return filter_var(trim((string)$input), FILTER_SANITIZE_EMAIL) ?: '';
    }
}
if (!function_exists('sanitizeEnum')) {
    function sanitizeEnum($input, array $allowed, $default = null) {
        $val = trim((string)$input);
        return in_array($val, $allowed, true) ? $val : ($default ?? ($allowed[0] ?? ''));
    }
}

if (!function_exists('formatLocation')) {
    /**
     * Normalize warehouse/location strings for display.
     * If the location is empty or null, falls back to the configured default.
     */
    function formatLocation($location) {
        $default = 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas';
        $loc = trim((string)($location ?? ''));
        return $loc === '' ? $default : $loc;
    }
}

// ================================
// PAGINATION
// ================================
if (!function_exists('ensureVehicleTypeColumn')) {
    function ensureVehicleTypeColumn($conn) {
        static $done = false;
        if ($done) return;
        $r = @$conn->query("SHOW COLUMNS FROM users LIKE 'vehicle_type'");
        if ($r && $r->num_rows === 0) {
            @$conn->query("ALTER TABLE users ADD COLUMN vehicle_type VARCHAR(50) DEFAULT NULL");
        }
        $done = true;
    }
}
if (!function_exists('ensurePaginationTable')) {
    function ensurePaginationTable($conn) {
        static $done = false;
        if ($done) return;
        $conn->query("CREATE TABLE IF NOT EXISTS pagination_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(50) NOT NULL UNIQUE,
            setting_value VARCHAR(50) NOT NULL,
            description VARCHAR(255) DEFAULT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        $conn->query("INSERT IGNORE INTO pagination_settings (setting_key, setting_value, description) VALUES ('items_per_page', '25', 'Default rows per page'), ('per_page_options', '10,25,50,100,200', 'Dropdown options')");
        $done = true;
    }
}
if (!function_exists('getPaginationPerPage')) {
    function getPaginationPerPage($conn, $default = 25) {
        static $cached = null;
        if ($cached !== null) return $cached;
        ensurePaginationTable($conn);
        $r = $conn->query("SELECT setting_value FROM pagination_settings WHERE setting_key = 'items_per_page'");
        $cached = ($r && ($row = $r->fetch_assoc())) ? max(5, min(500, (int)$row['setting_value'])) : $default;
        return $cached;
    }
}
if (!function_exists('getPaginationOptions')) {
    function getPaginationOptions($conn) {
        ensurePaginationTable($conn);
        $r = $conn->query("SELECT setting_value FROM pagination_settings WHERE setting_key = 'per_page_options'");
        if ($r && ($row = $r->fetch_assoc()) && !empty(trim($row['setting_value'] ?? ''))) {
            $opts = array_map('intval', array_filter(array_map('trim', explode(',', $row['setting_value']))));
            return !empty($opts) ? $opts : [10, 25, 50, 100, 200];
        }
        return [10, 25, 50, 100, 200];
    }
}
if (!function_exists('getPagination')) {
    function getPagination($conn, $count_sql, $per_page = null, $pageParam = 'page', $perPageParam = 'per_page') {
        // Determine per-page value (supports custom query parameter names)
        if ($per_page === null) {
            $per_page = isset($_GET[$perPageParam]) ? (int)$_GET[$perPageParam] : getPaginationPerPage($conn);
        }
        $per_page = max(1, min(500, (int)$per_page));
        $page = max(1, (int)($_GET[$pageParam] ?? 1));
        $res = @$conn->query($count_sql);
        $total = ($res && ($row = $res->fetch_assoc())) ? (int)$row['c'] : 0;
        $total_pages = $total > 0 ? (int)ceil($total / $per_page) : 1;
        $page = min($page, max(1, $total_pages));
        $offset = ($page - 1) * $per_page;
        return [
            'page' => $page,
            'per_page' => $per_page,
            'offset' => $offset,
            'total' => $total,
            'total_pages' => $total_pages,
            'prev_page' => $page > 1 ? $page - 1 : null,
            'next_page' => $page < $total_pages ? $page + 1 : null,
            // Preserve param names for helpers
            'page_param' => $pageParam,
            'per_page_param' => $perPageParam,
        ];
    }
}
if (!function_exists('renderPagination')) {
    function renderPagination($pagination, $pageParam = 'page', $perPageParam = 'per_page') {
        $p = $pagination;
        if ($p['total_pages'] <= 1) return '';
        $params = $_GET;
        unset($params[$pageParam], $params[$perPageParam]);
        $q = http_build_query($params);
        $base = $_SERVER['PHP_SELF'] ?? '';
        $prefix = $base . (strpos($base, '?') !== false ? '&' : '?') . $q . ($q ? '&' : '');
        $html = '<div class="pagination-wrapper"><div class="pagination-info">Showing ' . ($p['offset'] + 1) . '–' . min($p['offset'] + $p['per_page'], $p['total']) . ' of ' . $p['total'] . '</div>';
        $html .= '<nav class="pagination-nav" aria-label="Table pagination"><ul class="pagination">';
        if ($p['prev_page']) {
            $html .= '<li><a href="' . htmlspecialchars($prefix . $pageParam . '=' . $p['prev_page']) . '" class="pagination-btn" aria-label="Previous">‹</a></li>';
        } else {
            $html .= '<li><span class="pagination-btn disabled" aria-disabled="true">‹</span></li>';
        }
        $start = max(1, $p['page'] - 2);
        $end = min($p['total_pages'], $p['page'] + 2);
        if ($start > 1) {
            $html .= '<li><a href="' . htmlspecialchars($prefix . $pageParam . '=1') . '" class="pagination-btn">1</a></li>';
            if ($start > 2) $html .= '<li><span class="pagination-ellipsis">…</span></li>';
        }
        for ($i = $start; $i <= $end; $i++) {
            $cls = $i === $p['page'] ? 'pagination-btn active' : 'pagination-btn';
            $html .= '<li><a href="' . htmlspecialchars($prefix . $pageParam . '=' . $i) . '" class="' . $cls . '">' . $i . '</a></li>';
        }
        if ($end < $p['total_pages']) {
            if ($end < $p['total_pages'] - 1) $html .= '<li><span class="pagination-ellipsis">…</span></li>';
            $html .= '<li><a href="' . htmlspecialchars($prefix . $pageParam . '=' . $p['total_pages']) . '" class="pagination-btn">' . $p['total_pages'] . '</a></li>';
        }
        if ($p['next_page']) {
            $html .= '<li><a href="' . htmlspecialchars($prefix . $pageParam . '=' . $p['next_page']) . '" class="pagination-btn" aria-label="Next">›</a></li>';
        } else {
            $html .= '<li><span class="pagination-btn disabled" aria-disabled="true">›</span></li>';
        }
        $html .= '</ul></nav></div>';
        return $html;
    }
}
if (!function_exists('renderPerPageSelector')) {
    function renderPerPageSelector($conn, $current_per_page, $pageParam = 'page', $perPageParam = 'per_page') {
        // Support both (pageParam, perPageParam) and (perPageParam, pageParam) order conventions.
        // Compatibility: allow calling code to pass (perPageParam, pageParam) order.
        $isPerPageFirst = substr($pageParam, -9) === '_per_page' && substr($perPageParam, -5) === '_page';
        if ($isPerPageFirst) {
            list($pageParam, $perPageParam) = [$perPageParam, $pageParam];
        }

        $opts = getPaginationOptions($conn);
        $html = '<form method="get" class="per-page-form" style="display:inline;">';
        foreach ($_GET as $k => $v) {
            if ($k !== $perPageParam && $k !== $pageParam) {
                $html .= '<input type="hidden" name="' . htmlspecialchars($k) . '" value="' . htmlspecialchars((string)$v) . '">';
            }
        }
        $html .= '<label for="per_page_select">Per page:</label>';
        $html .= '<select name="' . htmlspecialchars($perPageParam) . '" id="per_page_select" class="per-page-select" onchange="this.form.submit()">';
        foreach ($opts as $o) {
            $sel = $o == $current_per_page ? ' selected' : '';
            $html .= '<option value="' . $o . '"' . $sel . '>' . $o . '</option>';
        }
        $html .= '</select></form>';
        return $html;
    }
}

// ================================
// COMMON HELPER FUNCTIONS
// ================================

/**
 * Generate a unique reference ID (uses transaction-safe version)
 * Format: PREFIX-YYYYMMDD-NNNN
 */
if (!function_exists('generateReferenceId')) {
    function generateReferenceId($conn, $prefix) {
        return function_exists('generateReferenceIdSafe') ? generateReferenceIdSafe($conn, $prefix) : generateReferenceIdLegacy($conn, $prefix);
    }
}

/**
 * Legacy reference ID (non-transaction-safe, fallback only)
 */
if (!function_exists('generateReferenceIdLegacy')) {
    function generateReferenceIdLegacy($conn, $prefix) {
        $prefix = strtoupper(preg_replace('/[^A-Z0-9]/', '', $prefix));
        if (strlen($prefix) < 2 || strlen($prefix) > 10) return null;
        $today = date('Y-m-d');
        $conn->query("
            INSERT INTO id_sequences (prefix, seq_date, last_seq)
            VALUES ('{$conn->real_escape_string($prefix)}', '{$conn->real_escape_string($today)}', 1)
            ON DUPLICATE KEY UPDATE last_seq = last_seq + 1
        ");
        if ($conn->error) return null;
        $stmt = $conn->prepare("SELECT last_seq FROM id_sequences WHERE prefix = ? AND seq_date = ?");
        $stmt->bind_param("ss", $prefix, $today);
        $stmt->execute();
        $res = $stmt->get_result();
        $stmt->close();
        if (!$res || !$row = $res->fetch_assoc()) return null;
        return $prefix . '-' . date('Ymd') . '-' . str_pad($row['last_seq'], 4, '0', STR_PAD_LEFT);
    }
}

/**
 * Get available stock
 */
if (!function_exists('getProductAvailableStock')) {
    function getProductAvailableStock($conn, $product_id) {
        $stmt = $conn->prepare("
            SELECT quantity, COALESCE(reserved_quantity, 0) AS reserved
            FROM finished_goods WHERE product_id = ?
        ");
        if (!$stmt) return 0;
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $r ? max(0, $r['quantity'] - $r['reserved']) : 0;
    }
}

/**
 * Reserve stock
 */
if (!function_exists('reserveStockForProduct')) {
    function reserveStockForProduct($conn, $product_id, $quantity) {
        $conn->query("
            UPDATE finished_goods
            SET reserved_quantity = COALESCE(reserved_quantity,0) + $quantity
            WHERE product_id = $product_id
            AND (quantity - COALESCE(reserved_quantity,0)) >= $quantity
        ");
        return $conn->affected_rows > 0;
    }
}

/**
 * Release reservation
 */
if (!function_exists('releaseReservationForProduct')) {
    function releaseReservationForProduct($conn, $product_id, $quantity) {
        $conn->query("
            UPDATE finished_goods
            SET reserved_quantity = GREATEST(0, COALESCE(reserved_quantity,0) - $quantity)
            WHERE product_id = $product_id
        ");
    }
}

/**
 * Fulfill stock
 */
if (!function_exists('fulfillStockForProduct')) {
    function fulfillStockForProduct($conn, $product_id, $quantity) {
        $conn->query("
            UPDATE finished_goods
            SET quantity = quantity - $quantity,
                reserved_quantity = GREATEST(0, COALESCE(reserved_quantity,0) - $quantity)
            WHERE product_id = $product_id
        ");
    }
}

/**
 * Activity log
 */
if (!function_exists('logActivity')) {
    function logActivity($conn, $user_id, $action, $entity_type, $entity_id = null, $details = null) {
        $exists = @$conn->query("SHOW TABLES LIKE 'activity_log'");
        if (!$exists || $exists->num_rows === 0) return;

        $stmt = $conn->prepare("
            INSERT INTO activity_log (user_id, action, entity_type, entity_id, details)
            VALUES (?, ?, ?, ?, ?)
        ");
        if (!$stmt) return;
        $stmt->bind_param("issis", $user_id, $action, $entity_type, $entity_id, $details);
        @$stmt->execute();
        $stmt->close();
    }
}

/**
 * Flash messages
 */
if (!function_exists('showMessage')) {
    function showMessage() {
        if (isset($_SESSION['success'])) {
            echo '<div data-notify="success" data-message="' . htmlspecialchars($_SESSION['success'], ENT_QUOTES) . '" style="display:none"></div>';
            unset($_SESSION['success']);
        }
        if (isset($_SESSION['error'])) {
            echo '<div data-notify="error" data-message="' . htmlspecialchars($_SESSION['error'], ENT_QUOTES) . '" style="display:none"></div>';
            unset($_SESSION['error']);
        }
    }
}

/**
 * Flash message setter
 */
if (!function_exists('setMessage')) {
    function setMessage(string $message, string $type = 'success') {
        if (!session_id()) session_start(); // make sure session started

        // Map type to session key used by showMessage()
        switch (strtolower($type)) {
            case 'error':
            case 'danger':
                $_SESSION['error'] = $message;
                break;
            case 'success':
            case 'info':
            case 'warning':
            default:
                $_SESSION['success'] = $message;
                break;
        }
    }
}

/**
 * Format helpers
 */
if (!function_exists('formatDate')) {
    function formatDate($date) {
        if (!$date || $date === '0000-00-00') return '-';
        return date('Y-m-d', strtotime($date));
    }
}

if (!function_exists('formatCurrency')) {
    function formatCurrency($amount) {
        return '₱' . number_format($amount, 2);
    }
}

/**
 * Record an expense from another module (Procurement, Production, etc.) for automatic accounting link.
 * @param mysqli $conn
 * @param string $category Raw Materials, Labor, Utilities, Transportation, Other
 * @param float $amount
 * @param string $description e.g. "Auto: Procurement - Supplier Invoice #SI-001 - Acme Corp"
 * @param string $expense_date Y-m-d
 * @param int $created_by User ID (0 for system)
 * @return bool Success
 */
if (!function_exists('recordExpenseFromModule')) {
    function recordExpenseFromModule($conn, $category, $amount, $description, $expense_date = null, $created_by = 0, $reference_type = null, $reference_id = null, $department = null) {
        if ($amount <= 0) return false;
        $expense_date = $expense_date ?: date('Y-m-d');
        $expense_ref = generateReferenceId($conn, 'EXP');
        if (!$expense_ref) return false;

        // Use extended columns if present
        $has_ref_cols = ($conn->query("SHOW COLUMNS FROM expenses LIKE 'reference_type'")->num_rows > 0);
        if ($has_ref_cols) {
            $stmt = $conn->prepare("INSERT INTO expenses (expense_ref, category, amount, description, expense_date, created_by, reference_type, reference_id, department) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if (!$stmt) return false;
            $stmt->bind_param("ssdssisss", $expense_ref, $category, $amount, $description, $expense_date, $created_by, $reference_type, $reference_id, $department);
        } else {
            $stmt = $conn->prepare("INSERT INTO expenses (expense_ref, category, amount, description, expense_date, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            if (!$stmt) return false;
            $stmt->bind_param("ssdssi", $expense_ref, $category, $amount, $description, $expense_date, $created_by);
        }

        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}

/**
 * Product image
 */
if (!function_exists('getProductImagePath')) {
    function getProductImagePath($product) {
        $imageDir = LORINIMS_ROOT . '/assets/images/products';
        if (is_array($product) && !empty($product['image_path'])) {
            $file = $imageDir . '/' . $product['image_path'];
            if (file_exists($file)) {
                return 'assets/images/products/' . $product['image_path'];
            }
        }
        return null;
    }
}

/**
 * Payroll settings
 */
if (!function_exists('getPayrollSetting')) {
    function getPayrollSetting($conn, $key, $default = 0) {
        $stmt = $conn->prepare("SELECT setting_value FROM payroll_settings WHERE setting_key = ?");
        if (!$stmt) return $default;
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $res = $stmt->get_result();
        $stmt->close();
        return ($res && $row = $res->fetch_assoc())
            ? floatval($row['setting_value'])
            : $default;
    }
}

/**
 * Get setting from a specific settings table
 */
if (!function_exists('getSetting')) {
    function getSetting($conn, $table, $key, $default = null, $is_numeric = false) {
        if (!in_array($table, ['qc_settings', 'sales_settings', 'warehouse_settings', 'production_settings', 'accounting_settings', 'pagination_settings'])) {
            return $default;
        }
        $stmt = $conn->prepare("SELECT setting_value FROM " . $table . " WHERE setting_key = ?");
        if (!$stmt) return $default;
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $res = $stmt->get_result();
        $stmt->close();
        if ($res && $row = $res->fetch_assoc()) {
            return $is_numeric ? floatval($row['setting_value']) : $row['setting_value'];
        }
        return $default;
    }
}

/**
 * QC Settings
 */
if (!function_exists('getQCSetting')) {
    function getQCSetting($conn, $key, $default = null, $is_numeric = false) {
        return getSetting($conn, 'qc_settings', $key, $default, $is_numeric);
    }
}

/**
 * Sales Settings
 */
if (!function_exists('getSalesSetting')) {
    function getSalesSetting($conn, $key, $default = null, $is_numeric = false) {
        return getSetting($conn, 'sales_settings', $key, $default, $is_numeric);
    }
}

/**
 * Warehouse Settings
 */
if (!function_exists('getWarehouseSetting')) {
    function getWarehouseSetting($conn, $key, $default = null, $is_numeric = false) {
        return getSetting($conn, 'warehouse_settings', $key, $default, $is_numeric);
    }
}

/**
 * Production Settings
 */
if (!function_exists('getProductionSetting')) {
    function getProductionSetting($conn, $key, $default = null, $is_numeric = false) {
        return getSetting($conn, 'production_settings', $key, $default, $is_numeric);
    }
}

/**
 * Accounting Settings
 */
if (!function_exists('getAccountingSetting')) {
    function getAccountingSetting($conn, $key, $default = null, $is_numeric = false) {
        return getSetting($conn, 'accounting_settings', $key, $default, $is_numeric);
    }
}

/**
 * Product icon fallback (returns emoji when image not available)
 */
if (!function_exists('getProductIcon')) {
    function getProductIcon($product_name) {
        if (empty($product_name)) return '📦';
        $name = strtolower($product_name);
        $map = [
            'patis' => '🧂',
            'vinegar' => '🍶',
            'soy' => '🫘',
            'alamang' => '🦀',
            'bagoong' => '🦐',
            'coconut' => '🥥',
            'crab' => '🦀',
            'sauce' => '🥫'
        ];
        foreach ($map as $k => $emoji) {
            if (strpos($name, $k) !== false) return $emoji;
        }
        return '📦';
    }
}

/**
 * Get sort parameters from URL
 */
if (!function_exists('getSortParams')) {
    function getSortParams($default_column = 'id', $allowed_columns = []) {
        $sort_by = $_GET['sort_by'] ?? $default_column;
        $sort_order = $_GET['sort_order'] ?? 'DESC';
        
        // Validate sort_by against allowed columns
        if (!empty($allowed_columns) && !in_array($sort_by, $allowed_columns)) {
            $sort_by = $default_column;
        }
        
        // Validate sort_order
        if (!in_array(strtoupper($sort_order), ['ASC', 'DESC'])) {
            $sort_order = 'DESC';
        }
        
        return [
            'column' => $sort_by,
            'order' => strtoupper($sort_order),
            'toggle' => strtoupper($sort_order) === 'ASC' ? 'DESC' : 'ASC'
        ];
    }
}

/**
 * Generate sort link with icon
 */
if (!function_exists('getSortLink')) {
    function getSortLink($column, $label, $current_sort, $params = []) {
        $is_active = $current_sort['column'] === $column;
        $next_order = $is_active ? $current_sort['toggle'] : 'DESC';
        
        // Build query string
        $query = 'sort_by=' . urlencode($column) . '&sort_order=' . urlencode($next_order);
        
        // Append other parameters
        foreach ($params as $key => $value) {
            if ($key !== 'sort_by' && $key !== 'sort_order') {
                $query .= '&' . urlencode($key) . '=' . urlencode($value);
            }
        }
        
        $indicator = '';
        if ($is_active) {
            $indicator = $current_sort['order'] === 'ASC' ? ' ↑' : ' ↓';
        }
        
        return '?' . $query . '|' . $indicator;
    }
}

/**
 * Create sortable table header
 */
if (!function_exists('sortHeader')) {
    function sortHeader($column, $label, $current_sort, $params = []) {
        $is_active = $current_sort['column'] === $column;
        $next_order = $is_active ? $current_sort['toggle'] : 'DESC';
        
        // Build query string - preserve common GET params
        $preserve = ['customer_id', 'status', 'page', 'employee_id', 'order_id'];
        foreach ($preserve as $k) {
            if (isset($_GET[$k]) && !isset($params[$k]) && (is_string($_GET[$k]) || is_numeric($_GET[$k]))) {
                $params[$k] = $_GET[$k];
            }
        }
        $query = 'sort_by=' . urlencode($column) . '&sort_order=' . urlencode($next_order);
        foreach ($params as $key => $value) {
            if ($key !== 'sort_by' && $key !== 'sort_order' && $value !== '' && $value !== null && (is_string($value) || is_numeric($value))) {
                $query .= '&' . urlencode($key) . '=' . urlencode($value);
            }
        }
        
        // Determine arrow and styling based on active state
        $arrow = '';
        $button_style = '';
        
        if ($is_active) {
            $arrow = $current_sort['order'] === 'ASC' ? '↑' : '↓';
            $button_style = 'background: rgba(255, 107, 53, 0.2); border-color: #FF6B35; color: #9A3412; font-weight: 700;';
        } else {
            // Keep sortable headers readable on light table heads.
            $button_style = 'background: #FFF1E9; border-color: rgba(255, 107, 53, 0.45); color: #1E293B; font-weight: 600;';
        }
        
        $html = '<a href="?' . htmlspecialchars($query) . '" style="' . $button_style . ' display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; border: 1.5px solid; border-radius: 6px; text-decoration: none; white-space: nowrap; font-size: 13px; transition: all 0.2s ease; box-shadow: 0 1px 2px rgba(0,0,0,0.04);">' 
            . htmlspecialchars($label);
        
        if ($arrow) {
            $html .= ' <span style="font-size: 12px; font-weight: bold;">' . $arrow . '</span>';
        }
        
        $html .= '</a>';
        
        return $html;
    }
}

/**
 * In-app notifications (fermentation / production alerts for admin, production, QC).
 */
if (!function_exists('ensureAppNotificationsTables')) {
    function ensureAppNotificationsTables($conn): void {
        static $done = false;
        if ($done) {
            return;
        }
        @$conn->query("CREATE TABLE IF NOT EXISTS app_notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            message VARCHAR(500) NOT NULL,
            link VARCHAR(255) DEFAULT NULL,
            target_roles VARCHAR(80) NOT NULL DEFAULT 'admin,production,qc',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        @$conn->query("CREATE TABLE IF NOT EXISTS app_notification_reads (
            notification_id INT NOT NULL,
            user_id INT NOT NULL,
            read_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (notification_id, user_id),
            KEY idx_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        $done = true;
    }
}

if (!function_exists('notifyFermentationCompletedStakeholders')) {
    function notifyFermentationCompletedStakeholders($conn, string $message, string $link = 'production_records.php'): void {
        ensureAppNotificationsTables($conn);
        $roles = 'admin,production,qc';
        $stmt = $conn->prepare('INSERT INTO app_notifications (message, link, target_roles) VALUES (?, ?, ?)');
        if (!$stmt) {
            return;
        }
        $stmt->bind_param('sss', $message, $link, $roles);
        @$stmt->execute();
        $stmt->close();
    }
}

if (!function_exists('getUnreadAppNotifications')) {
    /**
     * @return list<array{id:int,message:string,link:?string,created_at:string}>
     */
    function getUnreadAppNotifications($conn, int $userId, string $role): array {
        ensureAppNotificationsTables($conn);
        if ($userId <= 0) {
            return [];
        }
        $role = strtolower(trim($role));
        $sql = "SELECT n.id, n.message, n.link, n.created_at
                FROM app_notifications n
                LEFT JOIN app_notification_reads r ON r.notification_id = n.id AND r.user_id = ?
                WHERE r.notification_id IS NULL
                  AND n.created_at > DATE_SUB(NOW(), INTERVAL 14 DAY)
                  AND FIND_IN_SET(?, REPLACE(n.target_roles, ' ', '')) > 0
                ORDER BY n.id DESC
                LIMIT 20";
        $st = $conn->prepare($sql);
        if (!$st) {
            return [];
        }
        $st->bind_param('is', $userId, $role);
        $st->execute();
        $res = $st->get_result();
        $out = [];
        while ($row = $res->fetch_assoc()) {
            $out[] = $row;
        }
        $st->close();
        return $out;
    }
}

if (!function_exists('markAppNotificationRead')) {
    function markAppNotificationRead($conn, int $userId, int $notificationId): bool {
        ensureAppNotificationsTables($conn);
        if ($userId <= 0 || $notificationId <= 0) {
            return false;
        }
        $st = $conn->prepare('INSERT IGNORE INTO app_notification_reads (notification_id, user_id) VALUES (?, ?)');
        if (!$st) {
            return false;
        }
        $st->bind_param('ii', $notificationId, $userId);
        $ok = $st->execute();
        $st->close();
        return (bool)$ok;
    }
}
