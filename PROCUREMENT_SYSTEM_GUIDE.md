# Complete Procurement System - Implementation Guide

## Overview
This document outlines the complete procurement system implementation for Lorenzana Food Corporation, connecting raw materials to the procurement workflow.

## Database Schema

Run `database_procurement_system.sql` to create all necessary tables:
- `suppliers` - Supplier master data
- `supplier_products` - Products/items each supplier provides
- `purchase_requisitions` - PR requests with approval workflow
- `pr_items` - Items in each PR
- `purchase_orders` - PO documents
- `po_items` - Items in each PO
- `goods_receiving_notes` - GRN for receiving goods
- `grn_items` - Items received with QC, expiry, lot tracking
- `supplier_invoices` - Supplier invoice records
- `supplier_returns` - Returns to supplier
- `return_items` - Items being returned

## Core Features Implemented

### 1️⃣ Supplier Management ✅
**Files:**
- `procurement_suppliers.php` - Supplier list
- `procurement_supplier_form.php` - Add/Edit supplier
- `api/save_supplier.php` - Save supplier API

**Features:**
- Supplier master data (code, name, contact, payment terms)
- Supplier status (Active/Inactive)
- Product catalog per supplier
- Supplier performance tracking

### 2️⃣ Purchase Requisition (PR) ✅
**Files:**
- `procurement_requisitions.php` - PR list with filters
- `procurement_requisition_form.php` - Create/Edit PR
- `procurement_requisition_view.php` - View PR details
- `api/save_pr.php` - Save PR API
- `api/approve_pr.php` - Approve/Reject PR API

**Features:**
- Department requests items
- Quantity & required date
- Justification field
- PR status: Draft → Submitted → Approved/Rejected
- Approval workflow with remarks
- Total cost calculation

### 3️⃣ Purchase Order (PO) - TO BE CREATED
**Required Files:**
- `procurement_orders.php` - PO list
- `procurement_order_form.php` - Create PO (from PR or manual)
- `procurement_order_view.php` - View PO
- `api/save_po.php` - Save PO API

**Features:**
- Auto-generated PO number
- Link to PR (optional)
- Supplier selection
- Items, quantities, prices
- Delivery date & payment terms
- PO status: Open → Partially Received → Received → Closed

### 4️⃣ Goods Receiving (GRN) - TO BE CREATED
**Required Files:**
- `procurement_receiving.php` - GRN list
- `procurement_receiving_form.php` - Create GRN from PO
- `api/save_grn.php` - Save GRN API
- `api/qc_grn.php` - QC approval API

**Features:**
- Receive items vs PO
- Partial deliveries support
- Actual quantity received
- Rejected quantity tracking
- QC status (Pending/Passed/Failed)
- Expiry date & lot number tracking
- Warehouse location tagging
- **Auto stock-in to raw_materials after QC approval**

### 5️⃣ Supplier Invoices - TO BE CREATED
**Required Files:**
- `procurement_invoices.php` - Invoice list
- `procurement_invoice_form.php` - Record invoice
- `api/save_supplier_invoice.php` - Save invoice API

**Features:**
- Supplier invoice number
- Invoice date & amount
- Link to PO
- Payment status tracking

### 6️⃣ Returns to Supplier - TO BE CREATED
**Required Files:**
- `procurement_returns.php` - Returns list
- `procurement_return_form.php` - Create return
- `api/save_return.php` - Save return API

**Features:**
- Return damaged/rejected goods
- Link to PO/GRN
- Adjust inventory automatically
- Return approval workflow

### 7️⃣ Procurement Dashboard - TO BE CREATED
**Required Files:**
- `procurement_dashboard.php` - Dashboard with reports

**Features:**
- Purchase summary
- Open POs
- Supplier purchase history
- Pending approvals
- Low stock alerts

## Workflow Integration

### Complete Procurement Flow:
```
1. Department creates PR (Draft)
   ↓
2. PR Submitted for Approval
   ↓
3. Admin/Procurement Approves PR
   ↓
4. Create PO from Approved PR
   ↓
5. PO sent to Supplier
   ↓
6. Goods Received - Create GRN
   ↓
7. QC Inspection (Pass/Fail)
   ↓
8. If Passed: Auto Stock-In to raw_materials
   ↓
9. Record Supplier Invoice
   ↓
10. Payment Processing
```

### Raw Materials Connection:
- **Stock-In**: When GRN is QC approved, automatically:
  - Insert/Update `raw_materials` table
  - Create `inventory_transactions` record
  - Update quantity, expiry, lot number, location

- **Stock-Out**: When materials used in production:
  - Deducted in `api/save_production_batch.php`
  - Tracked in `batch_details` table

## Key Integration Points

1. **Raw Materials → Procurement**
   - Preferred supplier can be set in `raw_materials.preferred_supplier_id`
   - When creating PR, can select from raw materials list
   - When receiving, auto-updates raw materials inventory

2. **Production → Procurement**
   - Production batch uses raw materials
   - Low stock alerts can trigger PR creation
   - Material usage tracked in batch_details

3. **Inventory → Procurement**
   - GRN items automatically post to inventory
   - Expiry tracking for FEFO (First Expired First Out)
   - Lot tracking for traceability

## Next Steps to Complete

1. Create Purchase Order system (procurement_orders.php, etc.)
2. Create Goods Receiving system (procurement_receiving.php, etc.)
3. Create Supplier Invoice system (procurement_invoices.php, etc.)
4. Create Returns system (procurement_returns.php, etc.)
5. Create Procurement Dashboard (procurement_dashboard.php)
6. Add auto stock-in logic in GRN save API
7. Add low stock alerts
8. Add procurement reports

## Files Created So Far

✅ `database_procurement_system.sql` - Complete database schema
✅ `procurement_suppliers.php` - Supplier management
✅ `procurement_supplier_form.php` - Supplier form
✅ `api/save_supplier.php` - Supplier save API
✅ `procurement_requisitions.php` - PR list
✅ `procurement_requisition_form.php` - PR form
✅ `procurement_requisition_view.php` - PR view
✅ `api/save_pr.php` - PR save API
✅ `api/approve_pr.php` - PR approval API

## Files Still Needed

⏳ `procurement_orders.php` - PO list
⏳ `procurement_order_form.php` - PO form
⏳ `procurement_order_view.php` - PO view
⏳ `api/save_po.php` - PO save API
⏳ `procurement_receiving.php` - GRN list
⏳ `procurement_receiving_form.php` - GRN form
⏳ `api/save_grn.php` - GRN save API (with auto stock-in)
⏳ `api/qc_grn.php` - GRN QC API
⏳ `procurement_invoices.php` - Invoice list
⏳ `procurement_invoice_form.php` - Invoice form
⏳ `api/save_supplier_invoice.php` - Invoice save API
⏳ `procurement_returns.php` - Returns list
⏳ `procurement_return_form.php` - Return form
⏳ `api/save_return.php` - Return save API
⏳ `procurement_dashboard.php` - Dashboard

## Important Notes

1. **Auto Stock-In**: When GRN is QC approved, the system must:
   - Check if material exists in `raw_materials`
   - If exists: Update quantity, expiry, lot
   - If not: Create new raw_materials record
   - Create inventory_transaction record
   - Update PO status to "Partially Received" or "Received"

2. **Expiry & Lot Tracking**: Critical for food safety
   - Expiry date must be captured during receiving
   - Lot number for traceability
   - FEFO (First Expired First Out) in inventory management

3. **QC Integration**: 
   - QC must approve before stock-in
   - Rejected items tracked separately
   - Can trigger returns to supplier

4. **Payment Terms**: 
   - Tracked per supplier
   - Used in PO and invoice due dates
   - Important for cash flow management
