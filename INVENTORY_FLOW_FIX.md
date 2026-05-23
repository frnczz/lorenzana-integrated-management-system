# Inventory Flow Fix - Production → QC → Finished Goods → Product Dropdown

## Problem Fixed
Previously, production batches were directly adding items to `finished_goods` inventory, bypassing the QC (Quality Control) process. This meant products could appear in product dropdowns without being QC-approved.

## Correct Flow (Now Implemented)

### 1. **Production** → Creates Production Batch
- **File**: `api/save_production_batch.php` or `save_production.php`
- **Action**: Creates a production batch record
- **Status**: Batch status = "Processing" or "Ready"
- **Important**: Does NOT add to `finished_goods` table

### 2. **Quality Control** → Inspects Batch
- **File**: `api/save_qc.php`
- **Action**: QC inspector reviews the batch
- **Possible Outcomes**:
  - **Approved**: Batch moves to finished goods inventory
  - **Rejected**: Batch marked as rejected (does not enter inventory)
  - **For Re-inspection**: Batch needs re-inspection

### 3. **If QC Approved** → Added to Finished Goods Inventory
- **File**: `api/save_qc.php` (lines 88-128)
- **Action**: 
  - Adds quantity to `finished_goods` table
  - Sets `qc_approved = 1` flag
  - Marks production batch as "Completed"

### 4. **Finished Goods Inventory** → Shows QC-Approved Items
- **File**: `inventory_items.php`
- **Query**: Only shows items where `qc_approved = 1`
- **Display**: Shows available quantity, total stock, and reserved quantities

### 5. **Product Dropdown** → Shows Only QC-Approved Items
- **File**: `includes/product_dropdowns.php`
- **Query**: Filters by `fg.qc_approved = 1`
- **Result**: Only QC-approved products appear in sales/product selection dropdowns

## Files Modified

### 1. `api/save_qc.php`
- ✅ Now sets `qc_approved = 1` when adding to finished_goods
- ✅ Only adds to finished_goods when approval_status = "Approved"

### 2. `api/save_production_batch.php`
- ✅ Removed direct finished_goods insertion
- ✅ Production now only creates batches (awaiting QC)

### 3. `save_production.php`
- ✅ Removed direct finished_goods insertion
- ✅ Production now only creates batches (awaiting QC)

### 4. `inventory_items.php`
- ✅ Updated Finished Goods query to use `qc_approved = 1` flag
- ✅ Shows available quantity, total stock, and reserved quantities
- ✅ Enhanced Pending QC section with better status display

## Database Schema

The `finished_goods` table has a `qc_approved` column:
```sql
qc_approved TINYINT(1) NOT NULL DEFAULT 0
```

- `qc_approved = 0`: Not approved (should not appear in dropdowns)
- `qc_approved = 1`: QC approved (appears in dropdowns and inventory)

## Verification

To verify the flow is working:

1. **Create a production batch** → Check `production_batches` table
2. **Perform QC inspection** → Check `qc_records` table
3. **If approved** → Check `finished_goods` table has `qc_approved = 1`
4. **Check inventory** → `inventory_items.php` should show the item
5. **Check dropdown** → Product should appear in `product_dropdowns.php`

## Important Notes

- **Old Data**: Existing `finished_goods` records may have `qc_approved = 0`. You may need to manually update them or run a migration script.
- **Pending Batches**: Batches waiting for QC appear in "Pending QC / Production Batches" section
- **Rejected Batches**: Rejected batches do not enter finished goods inventory
