# Expiry Date System - Implementation Checklist

## Phase 1: System Preparation ✓ COMPLETE

- [x] **expiry_service.php** - Core service functions created
  - Location: `/includes/expiry_service.php`
  - Functions: `calculateExpiryDate()`, `getProductShelfLife()`, `computeExpiryForBatch()`
  - Status: Ready for import

- [x] **API Endpoint** - REST API for real-time calculation created
  - Location: `/api/calculate_expiry_date.php`
  - Method: POST only
  - Status: Can be called from JavaScript

- [x] **Documentation** - Complete docs and guides created
  - `/EXPIRY_DATE_SYSTEM.md` - 2000+ line comprehensive guide
  - `/EXPIRY_DATE_INTEGRATION.php` - Code integration examples
  - `/EXPIRY_QUICK_START.php` - Quick start guide with examples

- [x] **Validation Tools** - Testing and validation files created
  - `/EXPIRY_VALIDATION.php` - Test runner (access via browser)
  - This checklist for tracking progress

---

## Phase 2: Database Setup - ACTION REQUIRED

### Step 1: Run Migration
- [ ] **Execute Migration Script**
  - File: `migrations/001_add_expiry_date_system.sql`
  - Method: Copy-paste into phpMyAdmin > SQL tab, or use command line
  - Expected: 2 new table columns + 1 index created

**Migration Contents:**
```sql
-- Add to products table
ALTER TABLE products ADD COLUMN shelf_life_days INT DEFAULT 365;

-- Add to production_batches table
ALTER TABLE production_batches ADD COLUMN expiry_date DATE;

-- Create index for performance
CREATE INDEX idx_production_batches_expiry_date 
ON production_batches(expiry_date);

-- Set defaults for core products
UPDATE products SET shelf_life_days=365 WHERE product_id=1;  -- Patis
UPDATE products SET shelf_life_days=180 WHERE product_id=2;  -- Bagoong
-- ... etc
```

### Step 2: Verify Schema
- [ ] Run validation tests: Access `http://localhost/lorinims/EXPIRY_VALIDATION.php`
  - Should show "✓ ALL TESTS PASSED"
  - Confirms columns exist and have correct names

### Step 3: Set Product Shelf Lives
- [ ] Review `products` table in database
- [ ] Verify `shelf_life_days` values are set for all active products
- [ ] Update any missing values using migration script or phpMyAdmin

---

## Phase 3: Code Integration - ACTION REQUIRED

### Step 1: Update save_production_batch.php
- [ ] **Add Include Statement** (near top of file, after db_connect.php)
  ```php
  require_once 'includes/expiry_service.php';
  ```

- [ ] **Add Computation Logic** (in the batch insertion loop)
  ```php
  $result = computeExpiryForBatch($conn, $product_id, $production_date);
  if (!$result['success']) {
      throw new Exception("Expiry calculation failed: " . $result['error']);
  }
  $expiry_date = $result['expiry_date'];
  ```

- [ ] **Update INSERT Statement** (add expiry_date to VALUES)
  ```php
  // Original: 
  // INSERT INTO production_batches (batch_number, batch_date, ...) VALUES (?, ?, ...)
  
  // Updated to include:
  // INSERT INTO production_batches (batch_number, batch_date, ..., expiry_date) 
  // VALUES (?, ?, ..., ?)
  ```

- [ ] **Update bind_param()** (add 's' for date string)
  ```php
  // Add $expiry_date to the bind_param call
  $stmt->bind_param('...s', ..., $expiry_date);  // s = string for DATE
  ```

**Reference Guide:**
- See: `EXPIRY_DATE_INTEGRATION.php` (lines showing exact SQL variations)
- Example code for different column combinations provided

---

## Phase 4: Form Integration - ACTION REQUIRED

### Step 1: Update production_record.php (or create new form)
- [ ] **Add Production Date Input** (if not already present)
  ```html
  <label for="production_date">Production Date:</label>
  <input type="date" id="production_date" name="production_date" required>
  ```

- [ ] **Add Product Selection** (if not already present)
  ```html
  <label for="product_id">Product:</label>
  <select id="product_id" name="product_id" required>
    <!-- Options from database -->
  </select>
  ```

- [ ] **Add Expiry Date Display Field** (read-only)
  ```html
  <label for="expiry_date">Expiry Date (Auto-calculated):</label>
  <input type="date" id="expiry_date" name="expiry_date" readonly 
         style="background: #f0f0f0;">
  ```

### Step 2: Add JavaScript for Real-Time Calculation
- [ ] **Add JavaScript Function** (in production_record.php)
  ```javascript
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
      } else {
          alert('Error calculating expiry: ' + data.error);
      }
  }
  ```

- [ ] **Add Event Listeners** (trigger calculation on changes)
  ```javascript
  document.getElementById('product_id').addEventListener('change', calculateExpiry);
  document.getElementById('production_date').addEventListener('change', calculateExpiry);
  document.getElementById('production_date').addEventListener('blur', calculateExpiry);
  ```

**Reference Guide:**
- Template HTML: See `EXPIRY_DATE_SYSTEM.md` (HTML Form Example section)
- JavaScript patterns: See `EXPIRY_QUICK_START.php` (Example 4)

---

## Phase 5: Testing - ACTION REQUIRED

### Functional Tests
- [ ] **Test API Endpoint Directly**
  - Via Postman or browser console:
  ```javascript
  fetch('/lorinims/api/calculate_expiry_date.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'product_id=1&production_date=2026-03-02'
  }).then(r => r.json()).then(d => console.log(d));
  ```
  - Expected: `{"success": true, "expiry_date": "2027-03-02", ...}`

- [ ] **Test Form Real-Time Calculation**
  - Select a product in production_record.php form
  - Enter a production date
  - Verify expiry_date calculates and displays automatically
  - Try different dates and confirm calculation is correct

- [ ] **Test Database Save**
  - Create a new production batch through the form
  - Check database: `SELECT batch_number, batch_date, expiry_date FROM production_batches`
  - Verify expiry_date matches the displayed value
  - Calculation: expiry_date = batch_date + shelf_life_days

### Edge Case Tests
Run these in `/EXPIRY_VALIDATION.php` dashboard:

- [ ] **Leap Year Handling**
  - Test: 2024-02-28 + 2 days = 2024-03-01 ✓
  - Test: 2024-02-29 + 1 day = 2024-03-01 ✓
  - Test: 2024-12-31 + 1 day = 2025-01-01 ✓

- [ ] **Various Shelf Lives**
  - Test: Product with 365 days shelf life
  - Test: Product with 180 days shelf life
  - Test: Product with 30 days shelf life
  - Test: Product with 0 days shelf life

- [ ] **Null/Invalid Handling**
  - Test: Product without shelf_life_days defined → Error message
  - Test: Invalid product ID → Error message
  - Test: Invalid date format → Error message
  - Test: Negative shelf_life_days → Error message

---

## Phase 6: Integration Verification - ACTION REQUIRED

### Approval Checklist
- [ ] All Phase 2 database setup tests pass (EXPIRY_VALIDATION.php shows all green)
- [ ] Code integrated into save_production_batch.php without syntax errors
- [ ] Form fields added to production_record.php
- [ ] JavaScript calculates expiry_date on form without errors (console clean)
- [ ] Creating a new batch correctly saves expiry_date to database
- [ ] Batch details page displays expiry_date (if shown)
- [ ] All edge case tests pass with expected results

### Sign-Off
- [ ] System is production-ready
- [ ] All tests document and pass
- [ ] Team trained on expiry date system
- [ ] Documentation reviewed and understood

---

## File Reference Guide

### Core Files (Created in Phase 1)
| File | Purpose | Status |
|------|---------|--------|
| `includes/expiry_service.php` | Core service functions | ✓ Created |
| `api/calculate_expiry_date.php` | REST API endpoint | ✓ Created |
| `migrations/001_add_expiry_date_system.sql` | Database migration | ✓ Created |

### Documentation Files (Created in Phase 1)
| File | Purpose | Status |
|------|---------|--------|
| `EXPIRY_DATE_SYSTEM.md` | Comprehensive documentation | ✓ Created |
| `EXPIRY_DATE_INTEGRATION.php` | Code integration guide | ✓ Created |
| `EXPIRY_QUICK_START.php` | Quick start examples | ✓ Created |
| `EXPIRY_VALIDATION.php` | Test/validation dashboard | ✓ Created |
| `EXPIRY_IMPLEMENTATION_CHECKLIST.md` | This file | ✓ Created |

### Files to Modify (Phases 3-4)
| File | Changes Needed |
|------|-----------------|
| `save_production_batch.php` | Add include, computation, and INSERT columns |
| `production_record.php` | Add form fields and JavaScript for calculation |

---

## Quick Command Reference

### Run Validation Tests
```
Navigate to: http://localhost/lorinims/EXPIRY_VALIDATION.php
Shows: All test results with pass/fail status
```

### View Quick Start
```
Navigate to: http://localhost/lorinims/EXPIRY_QUICK_START.php
Shows: Examples and quick setup steps
```

### Test API Endpoint
```
File: api/calculate_expiry_date.php
Method: POST
Parameters: product_id (required), production_date (optional)
Example: POST /api/calculate_expiry_date.php with body: product_id=1&production_date=2026-03-02
Response: JSON with success, expiry_date, shelf_life_days, error
```

---

## Troubleshooting Guide

### Issue: "Column shelf_life_days not found"
- **Cause:** Migration not executed
- **Solution:** Run `migrations/001_add_expiry_date_system.sql` in phpMyAdmin
- **Verify:** Run EXPIRY_VALIDATION.php

### Issue: "Product not found or shelf_life_days not defined"
- **Cause:** Product doesn't have shelf_life_days set
- **Solution:** Update product in database: `UPDATE products SET shelf_life_days=365 WHERE product_id=X`
- **Verify:** Check products table via phpMyAdmin

### Issue: "API returns error: Permission denied"
- **Cause:** User role is not admin or production
- **Solution:** Check user's role in database, or ensure you're logged in with correct role
- **Reference:** Look at permission checks in `api/calculate_expiry_date.php`

### Issue: "Expiry date not saving to database"
- **Cause:** bind_param() not updated with expiry_date parameter
- **Solution:** Review `EXPIRY_DATE_INTEGRATION.php` and add expiry_date to INSERT
- **Reference:** Check the exact SQL variations in integration guide

### Issue: "JavaScript console shows CORS errors"
- **Cause:** API path incorrect or fetch POST not properly formatted
- **Solution:** Check JavaScript console, verify path is `/lorinims/api/calculate_expiry_date.php`
- **Reference:** See EXPIRY_QUICK_START.php Example 4 for correct format

---

## Summary

**Total Setup Time:** ~30-45 minutes
- Database migration: 5 min
- Code integration: 15-20 min
- Form updates: 10-15 min
- Testing: 10 min

**Completion Criteria:** All checkboxes in Phases 1-6 are marked complete, and EXPIRY_VALIDATION.php shows all tests passing.

**Success Indicator:** When you create a new production batch, the `expiry_date` field in the database is automatically calculated and saved correctly based on the formula: `expiry_date = production_date + products.shelf_life_days`

---

**Document Status:** Complete ✓  
**Last Updated:** Generated at system implementation  
**Next Review:** After Phase 6 completion
