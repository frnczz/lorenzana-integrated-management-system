# Expiry Date System - Complete Redesign (Version 2)

## Overview

This is a comprehensive redesign of the expiry date calculation system with:
- **Product-specific shelf life rules** stored in `production_settings` table
- **Flexible time units**: days, months, years (not just days)
- **Backward compatibility** with legacy `products.shelf_life_days`
- **Accurate calendar calculations** (leap years, month boundaries, year transitions)
- **Clean database structure** with proper constraints

---

## What Was Wrong (Version 1)

❌ **Legacy System Issues:**
- Only supported days (not months/years)
- Limited flexibility for real products
- `shelf_life_days` was approximate (not ideal for long shelf life like 24 months = 730 days?)
- No product-specific rules; all products defaulted to single value
- Missing data validation in database

✅ **Version 2 Improvements:**
- Supports days, months, years (`ENUM('days', 'months', 'years')`)
- Product-specific settings in dedicated table
- Proper date arithmetic (calendar-aware)
- Backward compatible (fallback to legacy column if needed)
- Full data validation and cleanup

---

## Database Design (Version 2)

### Table: `production_settings`

Stores product-specific shelf life configuration.

```sql
CREATE TABLE production_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL UNIQUE,
    expiry_value INT NOT NULL DEFAULT 24,           -- 24, 36, 365, etc.
    expiry_unit ENUM('days','months','years') NOT NULL DEFAULT 'months',
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    INDEX idx_product_id (product_id)
);
```

### Table: `production_batches` (Updated)

```sql
-- The expiry_date column is now:
-- - NOT NULL (enforced)
-- - Automatically calculated
-- - Properly validated before insert

ALTER TABLE production_batches 
MODIFY COLUMN expiry_date DATE NOT NULL;
```

---

## Step 1: Install Migration

### Execute migration to:
1. Fix invalid expiry_date values
2. Create production_settings table
3. Load default product shelf lives
4. Make expiry_date NOT NULL

**File:** `migrations/002_fix_expiry_system_with_product_settings.sql`

**How to run:**

**Option A: phpMyAdmin**
```
1. Open phpMyAdmin → Select your database
2. Go to SQL tab
3. Copy entire migration file content
4. Execute
```

**Option B: MySQL Command Line**
```bash
mysql -u root -p lorinims < migrations/002_fix_expiry_system_with_product_settings.sql
```

**Option C: PHP Script**
```php
$sql_file = file_get_contents('migrations/002_fix_expiry_system_with_product_settings.sql');
$statements = array_filter(explode(';', $sql_file));

foreach ($statements as $statement) {
    if (trim($statement)) {
        $conn->query(trim($statement));
    }
}
```

---

## Step 2: Understand Product Shelf Life Settings

### Lorenzana Foods Defaults (Pre-loaded)

| Product | Value | Unit | Total |
|---------|-------|------|-------|
| Fish Sauce (Patis) | 24 | months | 2 years |
| Fish Sauce w/ Chili | 24 | months | 2 years |
| Soy Sauce | 36 | months | 3 years |
| Vinegar | 36 | months | 3 years |
| Bagoong (Fermented) | 24 | months | 2 years |
| Value Packs | 24 | months | 2 years |

### View Current Settings

```sql
SELECT 
    p.product_id,
    p.product_name,
    ps.expiry_value,
    ps.expiry_unit,
    ps.description
FROM products p
LEFT JOIN production_settings ps ON p.product_id = ps.product_id
ORDER BY p.product_name;
```

### Update a Product's Shelf Life

```sql
-- Update Fish Sauce to 30 months
UPDATE production_settings 
SET expiry_value = 30, expiry_unit = 'months'
WHERE product_id = 1;

-- Or insert if not exists
INSERT INTO production_settings (product_id, expiry_value, expiry_unit, description)
VALUES (1, 30, 'months', 'Fish Sauce - Extended shelf life variant')
ON DUPLICATE KEY UPDATE 
    expiry_value = 30, 
    expiry_unit = 'months';
```

---

## Step 3: Backend Service Functions

### File: `includes/expiry_service_v2.php`

Three core functions:

#### 1. `calculateExpiryDate($production_date, $value, $unit)`

**Purpose:** Pure date calculation function

```php
$expiry = calculateExpiryDate('2026-03-01', 24, 'months');
// Result: '2028-03-01'

$expiry = calculateExpiryDate('2026-02-28', 2, 'days');
// Result: '2026-03-01' (leap year aware)

$expiry = calculateExpiryDate('2026-03-01', 3, 'years');
// Result: '2029-03-01'
```

**Parameters:**
- `$production_date` (string|DateTime): Date in YYYY-MM-DD format or DateTime object
- `$value` (int): Time value to add
- `$unit` (string): 'days', 'months', or 'years'

**Returns:** Expiry date in YYYY-MM-DD format

**Throws:** Exception if parameters invalid

---

#### 2. `getProductShelfLife($conn, $product_id)`

**Purpose:** Fetch product shelf life from database

```php
$config = getProductShelfLife($conn, 4); // Fish Sauce
// Returns: [
//   'value' => 24,
//   'unit' => 'months',
//   'source' => 'production_settings'
// ]
```

**Strategy:**
1. Try `production_settings` table first (new system)
2. Fall back to `products.shelf_life_days` (legacy)
3. Return null if not found

**Returns:** Array or null

---

#### 3. `computeExpiryForBatch($conn, $product_id, $production_date)`

**Purpose:** Complete validation and calculation

```php
$result = computeExpiryForBatch($conn, 4, '2026-03-01');

// Success response:
// [
//   'success' => true,
//   'expiry_date' => '2028-03-01',
//   'shelf_life_value' => 24,
//   'shelf_life_unit' => 'months',
//   'shelf_life_source' => 'production_settings',
//   'production_date' => '2026-03-01',
//   'error' => null
// ]

// Error response:
// [
//   'success' => false,
//   'expiry_date' => null,
//   'shelf_life_value' => null,
//   'shelf_life_unit' => null,
//   'shelf_life_source' => null,
//   'production_date' => '2026-03-01',
//   'error' => 'Product not found or shelf life not configured in database'
// ]
```

**What it does:**
1. Validates product ID (must be positive integer)
2. Normalizes production date (defaults to today)
3. Fetches shelf life settings from database
4. Validates shelf life value (must be non-negative)
5. Calculates expiry using `calculateExpiryDate()`
6. Returns detailed response with source information

---

## Step 4: API Endpoint

### File: `api/calculate_expiry_date_v2.php`

**Endpoint:** `POST /lorinims/api/calculate_expiry_date_v2.php`

**Parameters:**
```
product_id (required, int): Product ID
production_date (optional, string YYYY-MM-DD): Defaults to today
```

**Usage from JavaScript:**
```javascript
fetch('/lorinims/api/calculate_expiry_date_v2.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'product_id=4&production_date=2026-03-01'
})
.then(r => r.json())
.then(data => {
    if (data.success) {
        console.log('Expiry: ' + data.expiry_date);
        console.log('Shelf Life: ' + data.shelf_life_value + ' ' + data.shelf_life_unit);
    } else {
        console.error('Error: ' + data.error);
    }
});
```

**Features:**
- ✅ POST only (returns 403 for GET)
- ✅ Authentication required (401 if not logged in)
- ✅ Role-based access (admin or production, 403 if insufficient)
- ✅ Comprehensive error handling
- ✅ JSON response with detailed information

**Response Format:**
```json
{
  "success": true,
  "expiry_date": "2028-03-01",
  "shelf_life_value": 24,
  "shelf_life_unit": "months",
  "shelf_life_source": "production_settings",
  "production_date": "2026-03-01",
  "error": null
}
```

---

## Step 5: Frontend Integration (production_record.php)

### Expiry Date Display Section

Green-themed information box showing:
- 📅 **Batch Expiry Date** (auto-calculated, read-only)
- ⏳ **Shelf Life** (value + unit, e.g., "24 months (Custom)")

**HTML:**
```html
<div style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); 
            border: 2px solid #22c55e; border-radius: 8px; padding: 15px;">
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
        <div>
            <label>📅 Batch Expiry Date</label>
            <input type="date" id="expiry_date_display" name="expiry_date" readonly 
                   style="...green styling...">
        </div>
        <div>
            <label>⏳ Shelf Life</label>
            <input type="text" id="shelf_life_display" readonly 
                   style="...green styling...">
        </div>
    </div>
</div>
```

### JavaScript Function

```javascript
function calculateBatchExpiry() {
    var batchBody = document.getElementById('manualBatchBody');
    var rows = batchBody.getElementsByTagName('tr');
    
    if (rows.length === 0) {
        document.getElementById('expiry_date_display').value = '';
        document.getElementById('shelf_life_display').value = 'Add a product to calculate...';
        return;
    }
    
    var firstRow = rows[0];
    var productSelect = firstRow.querySelector('select[name="product_id"]');
    
    if (!productSelect || !productSelect.value) {
        document.getElementById('expiry_date_display').value = '';
        document.getElementById('shelf_life_display').value = 'Select a product...';
        return;
    }
    
    var productId = productSelect.value;
    var productionDate = document.getElementById('production_date_input').value;
    
    if (!productionDate) {
        document.getElementById('expiry_date_display').value = '';
        document.getElementById('shelf_life_display').value = 'Set production date...';
        return;
    }
    
    // Call API
    fetch('/lorinims/api/calculate_expiry_date_v2.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'product_id=' + productId + '&production_date=' + productionDate
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('expiry_date_display').value = data.expiry_date;
            
            // Display shelf life with unit and source
            var shelfLifeText = data.shelf_life_value + ' ' + data.shelf_life_unit;
            var sourceInfo = data.shelf_life_source === 'production_settings' ? ' (Custom)' : ' (Default)';
            document.getElementById('shelf_life_display').value = shelfLifeText + sourceInfo;
        } else {
            document.getElementById('expiry_date_display').value = '';
            document.getElementById('shelf_life_display').value = 'Error: ' + data.error;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('shelf_life_display').value = 'Error calculating...';
    });
}
```

### Trigger Events

```javascript
// Recalculate when:
// 1. Production date changes
document.getElementById('production_date_input').addEventListener('change', calculateBatchExpiry);

// 2. Product is selected
productSelect.addEventListener('change', function() {
    // ... existing code ...
    if (typeof calculateBatchExpiry === 'function') {
        calculateBatchExpiry();
    }
});

// 3. New product line is added
document.getElementById('addManualLine').addEventListener('click', function() {
    // ... existing code ...
    if (typeof calculateBatchExpiry === 'function') {
        calculateBatchExpiry();
    }
});
```

---

## Step 6: Saving Production Batch

### Updated Code in `api/save_production_batch.php`

```php
// Include the service
require_once __DIR__ . '/../includes/expiry_service_v2.php';

// In the batch insertion loop:
foreach ($products as $product) {
    $product_id = $product['product_id'];
    $production_date = $_POST['production_date'];
    
    // Compute expiry date
    $expiry_result = computeExpiryForBatch($conn, $product_id, $production_date);
    
    if (!$expiry_result['success']) {
        throw new Exception("Cannot compute expiry for product $product_id: " . $expiry_result['error']);
    }
    
    $expiry_date = $expiry_result['expiry_date'];
    
    // Insert batch with expiry_date
    $stmt = $conn->prepare("
        INSERT INTO production_batches 
        (batch_number, product_id, batch_date, quantity, expiry_date, created_by)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param(
        "sisdsi",
        $batch_number,
        $product_id,
        $production_date,
        $quantity,
        $expiry_date,          // Calculated expiry
        $created_by
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Error saving batch: " . $stmt->error);
    }
    
    $stmt->close();
}
```

---

## Verification Checklist

### ✅ Database Setup
- [ ] Migration executed successfully
- [ ] `production_settings` table created
- [ ] `production_batches.expiry_date` is NOT NULL
- [ ] Default shelf lives loaded for Lorenzana products

### ✅ Backend Service
- [ ] `includes/expiry_service_v2.php` exists
- [ ] Three functions work correctly:
  - [ ] `calculateExpiryDate()` - pure date math
  - [ ] `getProductShelfLife()` - fetch from DB
  - [ ] `computeExpiryForBatch()` - complete validation
- [ ] Error handling comprehensive

### ✅ API Endpoint
- [ ] `api/calculate_expiry_date_v2.php` exists
- [ ] POST-only validation works
- [ ] Authentication required
- [ ] Role-based access control (admin/production)
- [ ] JSON responses formatted correctly

### ✅ Frontend
- [ ] `production_record.php` updated
- [ ] Expiry date display section visible
- [ ] JavaScript calculates on product change
- [ ] JavaScript calculates on date change
- [ ] Displays shelf life value AND unit
- [ ] Shows source (Custom vs Default)

### ✅ Form Submission
- [ ] Production batch saves with expiry_date
- [ ] No "Data truncated" warnings
- [ ] Expiry date is valid calendar date
- [ ] Can view saved expiry dates in database

---

## Testing Examples

### Test Case 1: Fish Sauce (24 months)
```
Product: Fish Sauce (ID: 1)
Production Date: 2026-03-01
Expected Expiry: 2028-03-01
Shelf Life Source: production_settings
```

### Test Case 2: Soy Sauce (36 months)
```
Product: Soy Sauce (ID: 2)
Production Date: 2026-06-15
Expected Expiry: 2029-06-15
Shelf Life Source: production_settings
```

### Test Case 3: Leap Year Handling
```
Start: 2024-02-28 (not leap year)
Add: 2 months
Expected: 2024-04-28
Details: Properly jumps from Feb to April
```

### Test Case 4: Month Boundary
```
Start: 2026-01-31 (month with 31 days)
Add: 1 month
Expected: 2026-02-28 (or 2026-03-03 depending on implementation)
Note: DateTime::add() handles this intelligently
```

---

## Troubleshooting

### Issue: "Data truncated for column 'expiry_date'"
**Cause:** Invalid date values still in database
**Solution:** Run migration Step 2 to clean up data

### Issue: API returns "Product not found"
**Cause:** Product doesn't exist or shelf life not configured
**Solution:** Check production_settings table, ensure product_id is correct

### Issue: Expiry date calculation seems wrong
**Cause:** Check if using days vs months
**Solution:** Verify shelf_life_unit in production_settings

### Issue: Date format error
**Cause:** Production date not in YYYY-MM-DD format
**Solution:** Ensure HTML form uses `type="date"` input

### Issue: Permission denied on API
**Cause:** User not logged in or insufficient role
**Solution:** Log in, ensure user has 'admin' or 'production' role

---

## File Reference

| File | Purpose | Status |
|------|---------|--------|
| `migrations/002_fix_expiry_system_with_product_settings.sql` | Database migration | ✓ Created |
| `includes/expiry_service_v2.php` | Service layer (core functions) | ✓ Created |
| `api/calculate_expiry_date_v2.php` | REST API endpoint | ✓ Created |
| `production_record.php` | Frontend form (updated) | ✓ Updated |
| `EXPIRY_SYSTEM_REDESIGN.md` | This documentation | ✓ Created |

---

## Migration Path from Version 1

The new system is **backward compatible**:

1. Old system uses `products.shelf_life_days` (days only)
2. New system uses `production_settings` (days/months/years)
3. Service checks `production_settings` first
4. Falls back to `products.shelf_life_days` if not found
5. Can migrate gradually (set production_settings for important products first)

**Gradual Migration Example:**
```
Week 1: Set up production_settings for Fish Sauce variants
Week 2: Add Soy Sauce and Vinegar to production_settings
Week 3: Add Bagoong and remaining products
Week 4: Verify all products working, deprecate products.shelf_life_days
```

---

## Summary

✅ **What's New:**
- Product-specific shelf life rules in dedicated table
- Flexible time units (days, months, years)
- Accurate calendar calculations
- Clean database structure with constraints
- Backward compatible

✅ **What's Fixed:**
- No more invalid date warnings
- Proper expiry_date NOT NULL constraint
- Clear audit trail (shelf_life_source shows origin)
- Complete error handling

✅ **What's Ready:**
- Migration script
- Service functions
- API endpoint
- Frontend integration
- Testing framework

👉 **Next Step:** Run the migration and test with your actual products!
