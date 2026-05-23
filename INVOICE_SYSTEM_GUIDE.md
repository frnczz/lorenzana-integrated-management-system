# Complete Invoice & Transaction System

## Overview
This system implements a complete invoicing workflow from customer order to payment collection, following standard business practices.

## Complete Transaction Flow

### 1️⃣ Customer Order
- **Location**: `sales.php`
- Customer places order (walk-in, phone, email, or system)
- **Details Captured**:
  - Customer name
  - Products with quantities
  - Prices (from product database)
  - Discounts (if any)
- **Status**: `Pending` or `Confirmed`

### 2️⃣ Order Verification
- **Location**: `sales.php` → Order Review
- Sales checks:
  - Product availability (from finished goods inventory)
  - Correct pricing (from products table)
  - Customer terms (cash/credit)
- Warehouse confirms stock availability
- **Status**: `Confirmed` or `Dispatched`

### 3️⃣ Delivery / Release of Goods
- **Location**: `sales_delivery.php` or GPS tracking
- Products are:
  - Released from warehouse OR
  - Delivered to customer
- **Supporting Documents**:
  - Delivery Receipt (DR) - Auto-generated
  - Picking List - Available in order details
- **Status**: `Delivered`
- **Action**: Stock is deducted from finished goods inventory

### 4️⃣ Invoice Creation (Automatic)
- **Location**: `accounting_invoices.php`
- **Trigger**: When order status = `Delivered` and `invoice_generated = 0`
- **Automatic Generation**:
  - Invoice number (unique, auto-generated: INV-YYYYMMDD-####)
  - Invoice date (current date)
  - Customer details (from order)
  - Product list with:
    - Unit price
    - Quantity
    - Subtotal per item
  - Subtotal
  - Discount amount (if any)
  - VAT calculation (default 12%, configurable)
  - Total amount due
  - Payment terms (Cash/Credit)
  - Due date (auto-calculated for credit terms)
- **Delivery Receipt**: Auto-generated with DR number
- **Status**: `Pending` (awaiting approval)

### 5️⃣ Invoice Approval
- **Location**: `accounting_invoices.php`
- **Workflow**:
  - Supervisor or Accounting reviews invoice
  - Verifies:
    - Prices
    - Totals
    - Tax computation
  - Approves or Rejects
- **Status**: `Approved` or `Rejected`
- **Note**: Only approved invoices can receive payments

### 6️⃣ Invoice Issuance
- **Location**: `accounting_invoices.php` → View Invoice
- Invoice is:
  - Generated as PDF
  - Printed and signed OR
  - Sent via email/system
- Customer receives:
  - Invoice (PDF)
  - Copy of delivery receipt

### 7️⃣ Payment Collection
- **Location**: `accounting_payments.php`
- **Cash Sale**:
  - Customer pays immediately
  - Issue Official Receipt (OR) - Payment record
- **Credit Sale**:
  - Invoice recorded as Accounts Receivable
  - Payment due based on agreed terms (e.g., 15 or 30 days)

### 8️⃣ Payment Recording
- **Location**: `accounting_payments.php`
- Accounting records payment:
  - Payment method (Cash/Bank Transfer/Check/Credit Card)
  - Amount
  - Reference number
  - Notes
- **Invoice Status Updated**:
  - `Unpaid` → `Partially Paid` → `Paid`
  - `Overdue` (if due date passed)
- **Payment Number**: Auto-generated (PAY-YYYYMMDD-####)

### 9️⃣ Posting to Accounting & Reports
- Sales posted to:
  - Sales ledger (invoices table)
  - Inventory deduction (already done on delivery)
  - Financial reports
- **Used for**:
  - Daily sales report
  - VAT report
  - Monthly revenue tracking
  - Customer transaction history

## Database Tables

### `invoices`
- Invoice header information
- Includes: subtotal, discount, VAT, total
- Approval workflow tracking
- Payment terms and due dates

### `invoice_items`
- Line items for each invoice
- Product details, quantities, prices
- Links to order items

### `payments`
- Payment records
- Links to invoices
- Payment methods and references
- Auto-updates invoice status

### `delivery_receipts`
- Delivery receipt records
- Links to orders and invoices
- Driver and vehicle information

### `sales_orders`
- Updated with `invoice_id` and `invoice_generated` flag
- Tracks which orders have invoices

## Key Pages

### 1. Customer Transactions (`customers_transactions.php`)
- **Purpose**: View all customer activity
- **Features**:
  - Customer selector
  - Summary cards (orders, invoiced, paid, outstanding)
  - Tabs for:
    - Orders (with invoice generation button)
    - Invoices (with payment status)
    - Payments (complete history)
- **Access**: Sales, Accounting, Admin

### 2. Invoice Management (`accounting_invoices.php`)
- **Purpose**: Create, approve, and manage invoices
- **Features**:
  - Auto-generate from delivered orders
  - Manual invoice creation
  - Approval workflow
  - Payment recording links
  - PDF generation
- **Access**: Accounting, Admin

### 3. Payment Recording (`accounting_payments.php`)
- **Purpose**: Record customer payments
- **Features**:
  - Link to invoice
  - Multiple payment methods
  - Reference number tracking
  - Auto-update invoice status
- **Access**: Accounting, Admin

## Automatic Features

1. **Invoice Generation**: Triggered when order is delivered
2. **Delivery Receipt**: Auto-generated with invoice
3. **Payment Status**: Auto-updated based on payments
4. **Due Date Tracking**: Auto-calculates overdue status
5. **Stock Deduction**: Happens on delivery (before invoice)

## Workflow Summary

```
Order Created → Confirmed → Dispatched → Delivered
                                              ↓
                                    Invoice Auto-Generated
                                              ↓
                                    Invoice Approval
                                              ↓
                                    Invoice Issued (PDF)
                                              ↓
                                    Payment Recorded
                                              ↓
                                    Invoice Status: Paid
```

## Important Notes

- **Invoice Generation**: Only for delivered orders
- **Approval Required**: Payments can only be recorded for approved invoices
- **Stock Management**: Stock is deducted on delivery, not on invoice
- **VAT**: Default 12%, configurable per invoice
- **Payment Terms**: Cash (immediate) or Credit (with due days)
- **Multiple Payments**: Invoices can receive partial payments

## Setup Instructions

1. Run `database_invoice_system.sql` to update database schema
2. Access Invoice Management from Accounting menu
3. Access Customer Transactions from Sales menu
4. System will automatically show delivered orders ready for invoicing
