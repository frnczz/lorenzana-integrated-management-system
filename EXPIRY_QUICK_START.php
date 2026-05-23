<?php
/**
 * Quick Start Guide: Expiry Date System
 * 
 * This file demonstrates how to use the expiry date calculation system
 * with complete working examples
 */

// ============================================================================
// EXAMPLE 1: Basic Date Calculation
// ============================================================================

echo "=== EXAMPLE 1: Basic Date Calculation ===\n";

require_once 'includes/expiry_service.php';

// Calculate expiry for a product with 365 day shelf life
$expiry = calculateExpiryDate('2026-03-02', 365);
echo "Production: 2026-03-02 + 365 days = " . $expiry . "\n";
// Output: Production: 2026-03-02 + 365 days = 2027-03-02

// Calculate with leap year
$expiry = calculateExpiryDate('2024-02-28', 2);
echo "Production: 2024-02-28 + 2 days = " . $expiry . "\n";
// Output: Production: 2024-02-28 + 2 days = 2024-03-01

// Default to today
$expiry = calculateExpiryDate(null, 180);
echo "Production: Today + 180 days = " . $expiry . "\n";

echo "\n";

// ============================================================================
// EXAMPLE 2: Using with Database Connection
// ============================================================================

echo "=== EXAMPLE 2: Get Product Shelf Life from Database ===\n";

// Assuming you have a database connection
// $conn = new mysqli(...);
// require_once 'includes/expiry_service.php';
// 
// $shelf_life = getProductShelfLife($conn, 4);  // Product ID 4
// echo "Product 4 shelf life: $shelf_life days\n";

echo "(Requires active database connection)\n";
echo "\n";

// ============================================================================
// EXAMPLE 3: Complete Batch Expiry Computation
// ============================================================================

echo "=== EXAMPLE 3: Complete Batch Computation ===\n";

// In your save_production_batch.php:
/*
require_once 'includes/expiry_service.php';

$product_id = 4;  // Fish sauce
$production_date = '2026-03-02';

$result = computeExpiryForBatch($conn, $product_id, $production_date);

if ($result['success']) {
    echo "✓ Batch created\n";
    echo "  Production: " . $result['production_date'] . "\n";
    echo "  Shelf Life: " . $result['shelf_life_days'] . " days\n";
    echo "  Expiry: " . $result['expiry_date'] . "\n";
    
    // Save to database using $result['expiry_date']
} else {
    echo "✗ Error: " . $result['error'] . "\n";
}
*/

echo "(See EXPIRY_DATE_SYSTEM.md for database integration)\n";
echo "\n";

// ============================================================================
// EXAMPLE 4: API Usage from JavaScript
// ============================================================================

echo "=== EXAMPLE 4: JavaScript Integration ===\n";

$javascript_example = <<<'JS'
// In your HTML form:
<script>
async function calculateExpiry() {
    const productId = document.getElementById('product_id').value;
    const productionDate = document.getElementById('production_date').value;
    
    if (!productId || !productionDate) return;
    
    const response = await fetch('/lorinims/api/calculate_expiry_date.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `product_id=${productId}&production_date=${productionDate}`
    });
    
    const data = await response.json();
    
    if (data.success) {
        document.getElementById('expiry_date').value = data.expiry_date;
        console.log(`✓ Expiry: ${data.expiry_date} (${data.shelf_life_days} days)`);
    } else {
        console.error('✗ Error:', data.error);
    }
}

// Call on product select and date change
document.getElementById('product_id').addEventListener('change', calculateExpiry);
document.getElementById('production_date').addEventListener('change', calculateExpiry);
</script>
JS;

echo $javascript_example;
echo "\n";

// ============================================================================
// EXAMPLE 5: Validation Test Cases
// ============================================================================

echo "=== EXAMPLE 5: Test Cases ===\n";

$test_cases = [
    [
        'name' => 'Fish Sauce (365 days)',
        'production' => '2026-03-02',
        'shelf_life' => 365,
        'expected' => '2027-03-02'
    ],
    [
        'name' => 'Leap Year Test',
        'production' => '2024-02-28',
        'shelf_life' => 2,
        'expected' => '2024-03-01'
    ],
    [
        'name' => 'Bagoong (180 days)',
        'production' => '2026-03-02',
        'shelf_life' => 180,
        'expected' => '2026-09-08'
    ],
    [
        'name' => 'Single Day',
        'production' => '2026-12-31',
        'shelf_life' => 1,
        'expected' => '2027-01-01'
    ]
];

foreach ($test_cases as $test) {
    $result = calculateExpiryDate($test['production'], $test['shelf_life']);
    $pass = ($result === $test['expected']) ? '✓' : '✗';
    echo sprintf(
        "%s %s: %s + %d days = %s (expected: %s)\n",
        $pass,
        $test['name'],
        $test['production'],
        $test['shelf_life'],
        $result,
        $test['expected']
    );
}

echo "\n";

// ============================================================================
// QUICK SETUP STEPS
// ============================================================================

echo "=== QUICK SETUP ===\n";

$setup = <<<'SETUP'
1. Run the migration:
   - Open phpMyAdmin and run: migrations/001_add_expiry_date_system.sql
   
2. Include the service in your PHP:
   - Add to save_production_batch.php:
     require_once 'includes/expiry_service.php';
   
3. Compute expiry before insert:
   - $result = computeExpiryForBatch($conn, $product_id, $production_date);
   - if (!$result['success']) throw new Exception($result['error']);
   - $expiry_date = $result['expiry_date'];
   
4. Add to INSERT statement:
   - Include expiry_date in the VALUES clause
   - Bind it with $stmt->bind_param(..., $expiry_date, ...)
   
5. Add to production_record.php form:
   - Use the HTML/JavaScript from EXPIRY_DATE_SYSTEM.md
   - Or copy from EXPIRY_DATE_INTEGRATION.php
   
6. Test the API:
   - POST to /api/calculate_expiry_date.php
   - Parameters: product_id=4&production_date=2026-03-02
   - Check response for success and computed date

7. Verify in database:
   - SELECT batch_number, batch_date, expiry_date FROM production_batches
   - Expiry dates should be calculated correctly
SETUP;

echo $setup;

?>
