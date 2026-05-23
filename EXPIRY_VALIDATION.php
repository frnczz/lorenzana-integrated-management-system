<?php
/**
 * EXPIRY DATE SYSTEM - VALIDATION & TEST RUNBOOK
 * 
 * Use this file to verify the expiry date system is working correctly
 * after database migration and integration.
 * 
 * Access: http://localhost/lorinims/EXPIRY_VALIDATION.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_connect.php';
require_once 'includes/expiry_service.php';
require_once 'includes/functions.php';

$results = [];
$all_passed = true;

// ============================================================================
// TEST 1: Database Columns Exist
// ============================================================================

$test_name = "Database Schema Verification";
$results[$test_name] = [];

// Check products table has shelf_life_days
$query = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
          WHERE TABLE_NAME='products' AND COLUMN_NAME='shelf_life_days'";
$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    $results[$test_name][] = [
        'check' => 'products.shelf_life_days column exists',
        'status' => 'PASS',
        'details' => 'Column found in products table'
    ];
} else {
    $results[$test_name][] = [
        'check' => 'products.shelf_life_days column exists',
        'status' => 'FAIL',
        'details' => 'Column NOT found - run migration first'
    ];
    $all_passed = false;
}

// Check production_batches table has expiry_date
$query = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
          WHERE TABLE_NAME='production_batches' AND COLUMN_NAME='expiry_date'";
$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    $results[$test_name][] = [
        'check' => 'production_batches.expiry_date column exists',
        'status' => 'PASS',
        'details' => 'Column found in production_batches table'
    ];
} else {
    $results[$test_name][] = [
        'check' => 'production_batches.expiry_date column exists',
        'status' => 'FAIL',
        'details' => 'Column NOT found - run migration first'
    ];
    $all_passed = false;
}

// ============================================================================
// TEST 2: Service Function Tests
// ============================================================================

$test_name = "Service Function Tests";
$results[$test_name] = [];

// Test 1: calculateExpiryDate - Basic addition
$expiry = calculateExpiryDate('2026-03-02', 365);
if ($expiry === '2027-03-02') {
    $results[$test_name][] = [
        'check' => 'calculateExpiryDate: Basic 365-day addition',
        'status' => 'PASS',
        'details' => "2026-03-02 + 365 days = $expiry"
    ];
} else {
    $results[$test_name][] = [
        'check' => 'calculateExpiryDate: Basic 365-day addition',
        'status' => 'FAIL',
        'details' => "Expected 2027-03-02, got $expiry"
    ];
    $all_passed = false;
}

// Test 2: calculateExpiryDate - Leap year
$expiry = calculateExpiryDate('2024-02-28', 2);
if ($expiry === '2024-03-01') {
    $results[$test_name][] = [
        'check' => 'calculateExpiryDate: Leap year handling',
        'status' => 'PASS',
        'details' => "2024-02-28 + 2 days = $expiry (correct leap year)"
    ];
} else {
    $results[$test_name][] = [
        'check' => 'calculateExpiryDate: Leap year handling',
        'status' => 'FAIL',
        'details' => "Expected 2024-03-01, got $expiry"
    ];
    $all_passed = false;
}

// Test 3: calculateExpiryDate - Default to today
$today = date('Y-m-d');
$expiry = calculateExpiryDate(null, 0);
if ($expiry === $today) {
    $results[$test_name][] = [
        'check' => 'calculateExpiryDate: Default to today',
        'status' => 'PASS',
        'details' => "When date is null, uses today: $today"
    ];
} else {
    $results[$test_name][] = [
        'check' => 'calculateExpiryDate: Default to today',
        'status' => 'FAIL',
        'details' => "Expected $today, got $expiry"
    ];
    $all_passed = false;
}

// Test 4: calculateExpiryDate - Year boundary
$expiry = calculateExpiryDate('2026-12-31', 1);
if ($expiry === '2027-01-01') {
    $results[$test_name][] = [
        'check' => 'calculateExpiryDate: Year boundary',
        'status' => 'PASS',
        'details' => "2026-12-31 + 1 day = $expiry (year transition)"
    ];
} else {
    $results[$test_name][] = [
        'check' => 'calculateExpiryDate: Year boundary',
        'status' => 'FAIL',
        'details' => "Expected 2027-01-01, got $expiry"
    ];
    $all_passed = false;
}

// Test 5: getProductShelfLife - Valid product
$shelf_life = getProductShelfLife($conn, 1);
if ($shelf_life !== null && is_numeric($shelf_life) && $shelf_life > 0) {
    $results[$test_name][] = [
        'check' => 'getProductShelfLife: Retrieve valid product',
        'status' => 'PASS',
        'details' => "Product ID 1 has shelf life: $shelf_life days"
    ];
} else {
    $results[$test_name][] = [
        'check' => 'getProductShelfLife: Retrieve valid product',
        'status' => 'FAIL',
        'details' => "Could not retrieve shelf life or invalid value"
    ];
    $all_passed = false;
}

// Test 6: getProductShelfLife - Invalid product
$shelf_life = getProductShelfLife($conn, 99999);
if ($shelf_life === null) {
    $results[$test_name][] = [
        'check' => 'getProductShelfLife: Invalid product returns null',
        'status' => 'PASS',
        'details' => "Invalid product ID correctly returns null"
    ];
} else {
    $results[$test_name][] = [
        'check' => 'getProductShelfLife: Invalid product returns null',
        'status' => 'FAIL',
        'details' => "Expected null for invalid product, got: $shelf_life"
    ];
    $all_passed = false;
}

// ============================================================================
// TEST 3: Database Data Verification
// ============================================================================

$test_name = "Database Data Verification";
$results[$test_name] = [];

// Check if products have shelf_life_days set
$query = "SELECT COUNT(*) as count FROM products WHERE shelf_life_days IS NOT NULL AND shelf_life_days > 0";
$result = $conn->query($query);
$row = $result->fetch_assoc();
$count = $row['count'];

if ($count > 0) {
    $results[$test_name][] = [
        'check' => 'Products with shelf_life_days set',
        'status' => 'PASS',
        'details' => "$count product(s) have shelf life defined"
    ];
} else {
    $results[$test_name][] = [
        'check' => 'Products with shelf_life_days set',
        'status' => 'FAIL',
        'details' => 'No products have shelf_life_days set - check migration'
    ];
    $all_passed = false;
}

// Check specific products
$specific_products = [
    ['id' => 1, 'name' => 'Patis/Fish Sauce', 'expected' => 365],
    ['id' => 2, 'name' => 'Bagoong/Shrimp Paste', 'expected' => 180]
];

foreach ($specific_products as $prod) {
    $query = "SELECT shelf_life_days FROM products WHERE product_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $prod['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $shelf_life = $row['shelf_life_days'];
        
        if ($shelf_life == $prod['expected']) {
            $results[$test_name][] = [
                'check' => "Product {$prod['id']}: {$prod['name']}",
                'status' => 'PASS',
                'details' => "Shelf life correctly set to $shelf_life days"
            ];
        } else {
            $results[$test_name][] = [
                'check' => "Product {$prod['id']}: {$prod['name']}",
                'status' => 'WARN',
                'details' => "Shelf life is $shelf_life (expected {$prod['expected']})"
            ];
        }
    } else {
        $results[$test_name][] = [
            'check' => "Product {$prod['id']}: {$prod['name']}",
            'status' => 'FAIL',
            'details' => 'Product not found'
        ];
    }
    $stmt->close();
}

// ============================================================================
// TEST 4: API Endpoint Test
// ============================================================================

$test_name = "API Endpoint Verification";
$results[$test_name] = [];

if (file_exists('api/calculate_expiry_date.php')) {
    $results[$test_name][] = [
        'check' => 'API endpoint file exists',
        'status' => 'PASS',
        'details' => 'api/calculate_expiry_date.php found'
    ];
} else {
    $results[$test_name][] = [
        'check' => 'API endpoint file exists',
        'status' => 'FAIL',
        'details' => 'api/calculate_expiry_date.php not found'
    ];
    $all_passed = false;
}

// ============================================================================
// RENDER RESULTS
// ============================================================================
?>
<!DOCTYPE html>
<html>
<head>
    <title>Expiry Date System - Validation Report</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        .status-badge {
            font-size: 16px;
            font-weight: bold;
            padding: 8px 16px;
            border-radius: 4px;
        }
        .status-badge.pass {
            background: #d4edda;
            color: #155724;
        }
        .status-badge.fail {
            background: #f8d7da;
            color: #721c24;
        }
        .section {
            margin: 25px 0;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            overflow: hidden;
        }
        .section-header {
            background: #f8f9fa;
            padding: 12px 15px;
            font-weight: bold;
            border-bottom: 1px solid #dee2e6;
            font-size: 14px;
            text-transform: uppercase;
            color: #495057;
        }
        .test-item {
            display: flex;
            padding: 12px 15px;
            border-bottom: 1px solid #e9ecef;
            align-items: flex-start;
            gap: 15px;
        }
        .test-item:last-child {
            border-bottom: none;
        }
        .test-status {
            min-width: 60px;
            font-weight: bold;
            padding: 4px 8px;
            border-radius: 3px;
            text-align: center;
            font-size: 12px;
        }
        .test-status.pass {
            background: #d4edda;
            color: #155724;
        }
        .test-status.fail {
            background: #f8d7da;
            color: #721c24;
        }
        .test-status.warn {
            background: #fff3cd;
            color: #856404;
        }
        .test-content {
            flex: 1;
        }
        .test-check {
            font-weight: 500;
            margin-bottom: 3px;
        }
        .test-details {
            font-size: 13px;
            color: #6c757d;
        }
        .quick-reference {
            background: #e7f3ff;
            border-left: 4px solid #0066cc;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .quick-reference h3 {
            margin-top: 0;
            color: #004499;
        }
        .quick-reference ol {
            margin: 10px 0;
        }
        .quick-reference li {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Expiry Date System Validation</h1>
            <div class="status-badge <?php echo $all_passed ? 'pass' : 'fail'; ?>">
                <?php echo $all_passed ? '✓ ALL TESTS PASSED' : '✗ SOME TESTS FAILED'; ?>
            </div>
        </div>

        <?php if (!$all_passed && isset($_SERVER['REQUEST_METHOD'])): ?>
            <div class="quick-reference">
                <h3>⚠️ Setup Required</h3>
                <p><strong>Before validation passes, you need to:</strong></p>
                <ol>
                    <li><strong>Run the migration:</strong> Execute SQL from <code>migrations/001_add_expiry_date_system.sql</code> in phpMyAdmin</li>
                    <li><strong>Verify columns:</strong> Check that <code>products.shelf_life_days</code> and <code>production_batches.expiry_date</code> exist</li>
                    <li><strong>Integration:</strong> Add code to <code>save_production_batch.php</code> (see EXPIRY_DATE_INTEGRATION.php)</li>
                    <li><strong>Refresh:</strong> Reload this page to re-run validation</li>
                </ol>
            </div>
        <?php endif; ?>

        <?php foreach ($results as $section_name => $tests): ?>
            <div class="section">
                <div class="section-header"><?php echo htmlspecialchars($section_name); ?></div>
                <?php foreach ($tests as $test): ?>
                    <div class="test-item">
                        <div class="test-status <?php echo strtolower($test['status']); ?>">
                            <?php 
                            echo match($test['status']) {
                                'PASS' => '✓ PASS',
                                'FAIL' => '✗ FAIL',
                                'WARN' => '⚠ WARN',
                                default => $test['status']
                            };
                            ?>
                        </div>
                        <div class="test-content">
                            <div class="test-check"><?php echo htmlspecialchars($test['check']); ?></div>
                            <div class="test-details"><?php echo htmlspecialchars($test['details']); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>

        <div class="quick-reference">
            <h3>📋 Next Steps</h3>
            <ol>
                <li>Review all test results above</li>
                <li>If any fail, follow the setup required section</li>
                <li>Once all tests pass, integrate into <code>save_production_batch.php</code></li>
                <li>Add HTML form fields to <code>production_record.php</code> (see EXPIRY_DATE_SYSTEM.md)</li>
                <li>Test by creating a production batch and verifying expiry_date is calculated</li>
            </ol>
        </div>

        <div class="quick-reference" style="background: #f0f8ff; border-left-color: #0066cc;">
            <h3>🔗 Reference Files</h3>
            <ul style="margin: 10px 0;">
                <li><strong>Service Layer:</strong> <code>includes/expiry_service.php</code> - Core functions</li>
                <li><strong>API Endpoint:</strong> <code>api/calculate_expiry_date.php</code> - REST endpoint</li>
                <li><strong>Database Migration:</strong> <code>migrations/001_add_expiry_date_system.sql</code></li>
                <li><strong>Full Documentation:</strong> <code>EXPIRY_DATE_SYSTEM.md</code></li>
                <li><strong>Integration Guide:</strong> <code>EXPIRY_DATE_INTEGRATION.php</code></li>
                <li><strong>Quick Start:</strong> <code>EXPIRY_QUICK_START.php</code></li>
            </ul>
        </div>

    </div>
</body>
</html>
<?php $conn->close(); ?>
