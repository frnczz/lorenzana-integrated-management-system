# LORINIMS Event-Driven Refactor – Implementation Guide

This document describes the refactoring applied to make LORINIMS safe for automatic generation through event-driven architecture.

## 1. What Was Done

### A. New Files Created

| File | Purpose |
|------|---------|
| `database_refactor_event_driven.sql` | Migration: `system_events` table, `reservation_expires_at`, `phase` column |
| `includes/inventory_service.php` | Central inventory service: `emitSystemEvent`, `processInventoryEvent`, `generateReferenceIdSafe` |
| `includes/accounting_events.php` | `autoGenerateInvoiceFromEvent()` for event-triggered invoice generation |
| `cron/process_system_events.php` | Background processor for system events |
| `cron/release_expired_reservations.php` | Releases expired sales reservations |

### B. Core Changes

1. **Inventory**  
   - Only `processInventoryEvent()` updates `raw_materials` and `finished_goods`.  
   - Business modules emit events instead of changing stock.

2. **System events**  
   - `emitSystemEvent($conn, $entity_type, $entity_id, $event_type, $payload)`  
   - Idempotent via `UNIQUE(entity_type, entity_id, event_type)`.  
   - Background processor handles stock, invoices, delivery receipts.

3. **Reference IDs**  
   - `generateReferenceIdSafe()` uses a DB transaction to avoid duplicates.  
   - `generateReferenceId()` in `functions.php` now uses it by default.

4. **QC**  
   - QC modules only approve or reject.  
   - On approval they emit `QC_APPROVED_RAW` or `QC_APPROVED_FG`; stock is updated via the service.

5. **Production phases**  
   - `production_batches.phase`: Planned, In Progress, Output Pending QC, Completed, Rejected.

6. **Sales reservations**  
   - `sales_orders.reservation_expires_at` for time limits (default 48 hours).  
   - Release on cancel or expiration via cron.

7. **Delivery**  
   - On “Delivered”: emit `SALES_ORDER_DELIVERED`.  
   - Processor performs stock fulfillment and invoice generation.

## 2. Setup

### Step 1: Run database migration

```bash
mysql -u root lorinims_db < database_refactor_event_driven.sql
```

If `reservation_expires_at` or `phase` already exist, comment out or remove the corresponding `ALTER TABLE` statements.

### Step 2: Configure cron

Add to your crontab:

```
# Process system events (every minute)
* * * * * php /path/to/lorinims/cron/process_system_events.php

# Release expired reservations (every hour)
0 * * * * php /path/to/lorinims/cron/release_expired_reservations.php
```

### Step 3: Test manually

```bash
php cron/process_system_events.php
php cron/release_expired_reservations.php
```

## 3. Event Types

| Event | Emitted By | Processor Action |
|-------|------------|------------------|
| `QC_APPROVED_RAW` | GRN save, Raw material QC approve | Add to `raw_materials` |
| `QC_APPROVED_FG` | Finished goods QC approve | Add to `finished_goods` |
| `PRODUCTION_CONSUME` | Production batch save | Deduct from `raw_materials` |
| `PRODUCTION_OUTPUT` | QC approve (FG) | Add to `finished_goods` |
| `SALES_RESERVE` | Order save | Reserve in `finished_goods` |
| `SALES_RELEASE` | Order cancel | Release reservation |
| `SALES_ORDER_DELIVERED` | GPS update (Delivered) | Fulfill stock + generate invoice |
| `RETURN_PROCESSED` | Supplier return save | Deduct from `raw_materials` |

## 4. Backward Compatibility

- If `reservation_expires_at` is missing, order save works without it.  
- If `phase` is missing, production uses status only.  
- If `invoices` has only base columns, accounting uses minimal insert.  
- If `invoice_items` or `delivery_receipts` are missing, they are skipped.

## 5. Modules Touched

- `api/save_grn.php` – emit events instead of direct stock updates  
- `api/save_return.php` – emit `RETURN_PROCESSED`  
- `api/approve_raw_material_qc.php` – emit `QC_APPROVED_RAW`  
- `api/save_raw_material_qc.php` – emit `QC_APPROVED_RAW`  
- `api/save_qc.php` – emit `QC_APPROVED_FG`  
- `api/save_production_batch.php` – emit `PRODUCTION_CONSUME`  
- `save_production.php` – emit `PRODUCTION_CONSUME`  
- `api/update_gps.php` – emit `SALES_ORDER_DELIVERED`, no direct stock fulfill  
- `api/save_order.php` – use `processInventoryEvent` for `SALES_RESERVE`, add `reservation_expires_at`  
- `api/delete_order.php` – use `processInventoryEvent` for `SALES_RELEASE`  
- `api/update_batch_status.php` – supports new phases  
- `includes/functions.php` – uses `generateReferenceIdSafe`

## 6. Removed Direct Mutations

- `UPDATE raw_materials SET quantity` removed from QC, GRN, returns, production.  
- `UPDATE finished_goods SET quantity|reserved_quantity` removed from QC, delivery, sales.  
- `fulfillStockForProduct()` no longer called from delivery; fulfillment is via events.  
- `reserveStockForProduct` / `releaseReservationForProduct` replaced with `processInventoryEvent`.

## 7. Troubleshooting

- **Duplicate IDs** – Ensure `id_sequences` has `PRIMARY KEY (prefix, seq_date)` and use `generateReferenceIdSafe`.  
- **Stock not updating** – Ensure cron is running `process_system_events.php`.  
- **Invoice not generated** – Check `system_events` for `SALES_ORDER_DELIVERED` and that the row is processed.  
- **Reservation not releasing** – Ensure `release_expired_reservations.php` runs and `reservation_expires_at` is set.
