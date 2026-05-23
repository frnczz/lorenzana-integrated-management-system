# LORINIMS Backend Implementation Guide

## Overview
All backend handlers have been created in the `api/` directory. This guide explains how to connect forms to the backend.

## Backend Handlers Created

### 1. Quality Control
- **File**: `api/save_qc.php`
- **Form Action**: `api/save_qc.php`
- **Method**: POST
- **Fields**: batch_number, inspector_name, inspection_date, test_result, non_conformance, corrective_action, approval_status

### 2. Production
- **File**: `api/save_production_batch.php`
- **Form Action**: `api/save_production_batch.php`
- **Method**: POST
- **Fields**: batch_number, product_id, production_date, quantity, fermentation_status, packaging_status, status

### 3. Inventory
- **File**: `api/save_inventory.php`
- **Form Action**: `api/save_inventory.php`
- **Method**: POST
- **Fields**: item_name, category, quantity, unit, expiry_date, warehouse_location

### 4. Procurement
- **Suppliers**: `api/save_supplier.php`
  - Fields: supplier_name, contact_person, contact_number, email, address
- **Purchase Requests**: `api/save_purchase_request.php`
  - Fields: supplier_id, item_name, quantity, unit, expected_delivery_date

### 5. Sales
- **Orders**: `api/save_order.php`
  - Fields: customer_name, product_id, quantity, order_date, delivery_address, delivery_date, status
- **Delivery Assignment**: `api/save_delivery.php`
  - Fields: order_id, driver_id, vehicle_info, dispatch_time

### 6. Accounting
- **Invoices**: `api/save_invoice.php`
  - Fields: customer_id, order_id, amount, invoice_date, due_date, status
- **Expenses**: `api/save_expense.php`
  - Fields: category, amount, description, expense_date

## How to Update Forms

### Step 1: Add form method and action
```html
<form method="POST" action="api/save_[module].php">
```

### Step 2: Add name attributes to inputs
```html
<input type="text" name="field_name" required>
```

### Step 3: Add success/error message display
```php
<?php include "includes/functions.php"; showMessage(); ?>
```

### Step 4: Update submit button
```html
<button type="submit" class="btn">Save</button>
```

## Database Schema
Run `database_schema.sql` to create all necessary tables.

## Session Messages
All handlers use `$_SESSION['success']` and `$_SESSION['error']` for user feedback.

## Next Steps
1. Update all forms to connect to backend handlers
2. Add database queries to display records in tables
3. Add edit/delete functionality
4. Add search and filter capabilities
5. Add pagination for large datasets
