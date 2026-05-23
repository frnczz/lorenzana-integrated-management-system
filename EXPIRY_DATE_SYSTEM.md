# Production Batch Expiry Date Calculation System

## Overview
Automatic calculation of expiry dates based on product shelf life with accurate date arithmetic handling leap years.

---

## Database Schema

### Required Columns

#### `products` Table
```sql
ALTER TABLE products ADD COLUMN shelf_life_days INT NOT NULL DEFAULT 365 COMMENT 'Days product remains shelf-stable after production';

-- Example data:
UPDATE products SET shelf_life_days = 365 WHERE product_name LIKE '%Patis%';      -- Fish sauce: 365 days
UPDATE products SET shelf_life_days = 180 WHERE product_name LIKE '%Bagoong%';    -- Bagoong: 180 days
UPDATE products SET shelf_life_days = 90 WHERE product_name LIKE '%Crab Paste%';   -- Crab paste: 90 days
UPDATE products SET shelf_life_days = 365 WHERE product_name LIKE '%Soy Sauce%';  -- Soy sauce: 365 days
```

#### `production_batches` Table
```sql
ALTER TABLE production_batches ADD COLUMN expiry_date DATE COMMENT 'Computed as: production_date + product.shelf_life_days';

-- Optional: Add index for expiry queries
CREATE INDEX idx_expiry_date ON production_batches(expiry_date);
```

#### `raw_materials` Table (if shelf life for raw materials is needed)
```sql
ALTER TABLE raw_materials ADD COLUMN shelf_life_days INT DEFAULT 365 COMMENT 'Days raw material remains usable';
```

---

## PHP Implementation

### 1. Core Service: `includes/expiry_service.php`

**Functions:**

#### `calculateExpiryDate($production_date, $shelf_life_days)`
- **Purpose:** Calculate expiry date with accurate date arithmetic
- **Parameters:**
  - `$production_date`: String (YYYY-MM-DD) or DateTime object, or null for today
  - `$shelf_life_days`: Integer days
- **Returns:** String (YYYY-MM-DD)
- **Features:**
  - Handles leap years automatically via DateTime
  - Validates date format
  - Defaults to today if not provided

**Example:**
```php
$expiry = calculateExpiryDate('2026-03-02', 365);  // Returns: 2027-03-02
$expiry = calculateExpiryDate('2026-02-28', 1);    // Returns: 2026-03-01
```

#### `getProductShelfLife($conn, $product_id)`
- **Purpose:** Retrieve shelf life from product record
- **Parameters:**
  - `$conn`: MySQLi connection
  - `$product_id`: Product ID
- **Returns:** Integer days or null
- **Safety:** Prepared statement

#### `computeExpiryForBatch($conn, $product_id, $production_date)`
- **Purpose:** Compute expiry with full validation
- **Parameters:**
  - `$conn`: MySQLi connection
  - `$product_id`: Product ID
  - `$production_date`: Optional YYYY-MM-DD string
- **Returns:** Array with keys:
  - `success`: Boolean
  - `expiry_date`: String (YYYY-MM-DD) on success
  - `shelf_life_days`: Integer on success
  - `production_date`: Normalized date string
  - `error`: Error message on failure

**Example Response (Success):**
```json
{
  "success": true,
  "expiry_date": "2027-03-02",
  "production_date": "2026-03-02",
  "shelf_life_days": 365,
  "error": null
}
```

**Example Response (Error):**
```json
{
  "success": false,
  "expiry_date": null,
  "error": "Product not found or shelf_life_days not defined",
  "production_date": "2026-03-02"
}
```

---

## API Endpoint

### `api/calculate_expiry_date.php`

**Method:** POST

**Parameters:**
```php
$_POST['product_id']       // int, required
$_POST['production_date']  // string YYYY-MM-DD, optional (defaults to today)
```

**Usage from JavaScript:**
```javascript
fetch('/lorinims/api/calculate_expiry_date.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: 'product_id=4&production_date=2026-03-02'
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        document.getElementById('expiry_date').value = data.expiry_date;
        console.log(`Expiry: ${data.expiry_date} (${data.shelf_life_days} days)`);
    } else {
        console.error('Error:', data.error);
    }
});
```

---

## SQL Insert Statement

### Saving Production Batch with Expiry Date

```sql
INSERT INTO production_batches 
(batch_number, product_id, batch_date, production_date, quantity, 
 fermentation_status, packaging_status, status, expiry_date, created_by, updated_at)
VALUES 
(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW());
```

### MySQLi Prepared Statement (PHP)

```php
// Compute expiry date
$result = computeExpiryForBatch($conn, $product_id, $production_date);

if (!$result['success']) {
    throw new Exception("Cannot compute expiry date: " . $result['error']);
}

$computed_expiry = $result['expiry_date'];

// Insert batch with computed expiry_date
$stmt = $conn->prepare("
    INSERT INTO production_batches 
    (batch_number, product_id, batch_date, production_date, quantity, 
     fermentation_status, packaging_status, status, expiry_date, created_by)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "sisdssssi",
    $batch_number,
    $product_id,
    $production_date,
    $quantity,
    $fermentation_status,
    $packaging_status,
    $status,
    $computed_expiry,  // Computed expiry date
    $created_by
);

if (!$stmt->execute()) {
    throw new Exception("Error inserting batch: " . $stmt->error);
}

$batch_id = $conn->insert_id;
$stmt->close();
```

---

## Form Implementation

### HTML Form with Auto-Calculation

```html
<form method="POST" action="api/save_production_batch.php" id="productionForm">
    <!-- Production Date -->
    <div>
        <label>Production Date (required)</label>
        <input type="date" name="production_date" id="production_date" 
               value="<?php echo date('Y-m-d'); ?>" required 
               onchange="recalculateExpiry()">
    </div>

    <!-- Product Selection -->
    <div>
        <label>Product (required)</label>
        <select name="product_id" id="product_id" required 
                onchange="recalculateExpiry()">
            <option value="">-- Select Product --</option>
            <?php
            $products = $conn->query("SELECT product_id, product_name, shelf_life_days FROM products ORDER BY product_name");
            while ($row = $products->fetch_assoc()):
            ?>
                <option value="<?php echo $row['product_id']; ?>"
                        data-shelf-life="<?php echo $row['shelf_life_days']; ?>">
                    <?php echo htmlspecialchars($row['product_name']); ?>
                    (<?php echo $row['shelf_life_days']; ?> days)
                </option>
            <?php endwhile; ?>
        </select>
    </div>

    <!-- Expiry Date (Auto-Calculated, Read-Only) -->
    <div>
        <label>Expiry Date (Auto-Calculated)</label>
        <div style="display: flex; gap: 10px; align-items: center;">
            <input type="date" name="expiry_date" id="expiry_date" disabled
                   style="background: #f0f0f0; color: #666;">
            <span id="shelf_life_display" style="color: #666; font-size: 12px;">
                Select a product
            </span>
        </div>
        <small style="color: #999; display: block; margin-top: 4px;">
            Calculated automatically based on production date + product shelf life
        </small>
    </div>

    <!-- Other fields... -->
    <button type="submit" class="btn btn-primary">Save Batch</button>
</form>
```

### JavaScript Function for Real-Time Calculation

```html
<script>
function recalculateExpiry() {
    const productSelect = document.getElementById('product_id');
    const prodDateInput = document.getElementById('production_date');
    const expiryInput = document.getElementById('expiry_date');
    const shelfLifeDisplay = document.getElementById('shelf_life_display');
    
    const productId = productSelect.value;
    const productionDate = prodDateInput.value;
    
    if (!productId || !productionDate) {
        expiryInput.value = '';
        shelfLifeDisplay.textContent = 'Select a product and production date';
        return;
    }
    
    // Call API to compute expiry date
    fetch('/lorinims/api/calculate_expiry_date.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'product_id=' + productId + '&production_date=' + productionDate
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            expiryInput.value = data.expiry_date;
            const daysLabel = data.shelf_life_days === 1 ? 'day' : 'days';
            shelfLifeDisplay.textContent = `✓ ${data.shelf_life_days} ${daysLabel} shelf life`;
            shelfLifeDisplay.style.color = '#10b981';
        } else {
            expiryInput.value = '';
            shelfLifeDisplay.textContent = '⚠️ ' + data.error;
            shelfLifeDisplay.style.color = '#dc2626';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        shelfLifeDisplay.textContent = 'Error calculating expiry date';
        shelfLifeDisplay.style.color = '#dc2626';
    });
}

// Recalculate on page load in case of form restoration
document.addEventListener('DOMContentLoaded', recalculateExpiry);
</script>
```

---

## Testing Examples

### Test Case 1: Fish Sauce (365 days)
```
Production Date: 2026-03-02
Product Shelf Life: 365 days
Expected Expiry: 2027-03-02 ✓
```

### Test Case 2: Leap Year Handling
```
Production Date: 2026-02-28
Product Shelf Life: 1 day
Expected Expiry: 2026-03-01 ✓

Production Date: 2024-02-28
Product Shelf Life: 2 days
Expected Expiry: 2024-03-01 ✓ (leap year, Feb has 29 days)
```

### Test Case 3: Bagoong (180 days)
```
Production Date: 2026-03-02
Product Shelf Life: 180 days
Expected Expiry: 2026-09-08 ✓
```

---

## Validation Checklist

- ✅ `shelf_life_days` is NOT NULL
- ✅ `shelf_life_days` is a non-negative integer
- ✅ Production date is in YYYY-MM-DD format
- ✅ Production date is valid (not in future, handles leap years)
- ✅ Product exists in database
- ✅ Expiry date is computed server-side (can also show on client for preview)
- ✅ Prepared statements are used (no SQL injection)
- ✅ User permissions are validated

---

## Integration with Production Record

Update `production_record.php` to include the new form:

```php
<?php include "includes/expiry_service.php"; ?>

<!-- In the HTML form section... -->
<form method="POST" action="api/save_production_batch.php" id="productionForm">
    <!-- Include the form from above -->
</form>

<!-- Include the JavaScript -->
<script>
    // Include the recalculateExpiry() function from above
</script>
```

---

## Error Handling

All errors are returned as JSON with descriptive messages:

```json
{
  "success": false,
  "error": "Product not found or shelf_life_days not defined",
  "expiry_date": null
}
```

**Common Errors:**
1. Invalid product ID → "Invalid product ID"
2. Product not found → "Product not found or shelf_life_days not defined"
3. Invalid date format → "Production date must be in YYYY-MM-DD format"
4. Negative shelf life → "Shelf life days must be a non-negative number"

---

## Performance Considerations

- Expiry calculation uses PHP DateTime (no database call)
- Optional: Cache shelf_life_days in session after first lookup
- Index on `expiry_date` for reports/queries

---

## Notes

- Expiry date is **read-only** in the UI (computed server-side)
- Client-side preview helps users confirm before saving
- All dates use YYYY-MM-DD format consistently
- Handles leap years automatically via PHP DateTime
- No approximations (exact calendar days)

