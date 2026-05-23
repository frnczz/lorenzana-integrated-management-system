# LORINIMS Refactoring Architecture

## Event-Driven Architecture for Safe Automatic Generation

**Related Documents:** [THESIS_ALIGNMENT_ANALYSIS.md](THESIS_ALIGNMENT_ANALYSIS.md) (module alignment) · [REFACTOR_SUMMARY.md](REFACTOR_SUMMARY.md) (prior refactors) · [INVENTORY_FLOW_FIX.md](INVENTORY_FLOW_FIX.md) (current production→QC→FG flow)

**Document Purpose:** Defines architectural rules and refactoring patterns to make LORINIMS safe for automatic generation of dependent records (invoices, stock deductions, delivery receipts) without module-level race conditions or duplicate generation.

**Stack:** PHP 7+, MySQL  
**Approach:** Refactoring over rewrite; minimal, backward-compatible changes.

---

## 1. System Context

### 1.1 Overview

LORINIMS is a modular ERP system with interconnected domains:

| Domain       | Primary Tables                        | Integrations                            |
|--------------|----------------------------------------|-----------------------------------------|
| Procurement  | purchase_requisitions, purchase_orders, goods_receiving_notes | Inventory, QC                             |
| QC           | qc_records, raw_material_qc             | Raw materials, finished goods, production |
| Inventory    | raw_materials, finished_goods, inventory_transactions | All modules                               |
| Production   | production_batches, batch_details      | Raw materials, finished goods, QC        |
| Sales        | sales_orders, order_items              | Inventory (reservation, fulfillment)     |
| Delivery     | delivery_assignments, gps_tracking     | Sales, accounting                        |
| Accounting   | invoices, invoice_items, expenses      | Sales, delivery                          |

### 1.2 Current Issue

Multiple modules **directly mutate** inventory quantities, statuses, and accounting data. This makes:

- **Automatic generation unsafe** — risk of duplicate invoices, receipts, or stock changes.
- **Ownership unclear** — no single source of truth for stock or financial eligibility.
- **Page-based logic** — workflows depend on specific pages/endpoints running in a fixed order.

The goal is to refactor so modules are safe for **trigger-based automatic generation** using events and a centralized processing layer.

---

## 2. Core Rules

All refactoring must enforce the following rules:

| # | Rule | Rationale |
|---|------|-----------|
| R1 | Business modules MUST NOT directly change stock quantities (`quantity`, `reserved_quantity`) in `raw_materials` or `finished_goods`. | Single authority prevents race conditions and inconsistent stock. |
| R2 | Only the **Inventory Service** (or `inventory_transactions` via that service) may update stock. | Enforces R1 and provides an auditable transaction log. |
| R3 | Modules must **report events** (e.g., GRN_RECEIVED, QC_APPROVED_RAW, SALES_ORDER_DELIVERED), not infer or directly create dependent records. | Decouples operational actions from downstream effects. |
| R4 | Automatic generation (invoices, stock deduction, delivery receipts) must be **trigger-based** via a background processor, not tied to page load. | Eliminates page-order dependencies and duplicate generation. |
| R5 | Reference ID generation must be **centralized**, **transaction-safe**, and **never duplicated**. | Prevents duplicate IDs under concurrency. |
| R6 | Accounting modules must **react to events** (e.g., SALES_ORDER_DELIVERED), not query inventory or sales tables to infer eligibility. | Keeps accounting logic event-driven and auditable. |

---

## 3. Required Architectural Changes

### 3.1 Inventory Refactor (Event-Driven)

**Principle:** Treat inventory as event-driven. Stock changes occur only in response to inventory events.

#### 3.1.1 Inventory Service Layer

Introduce a simple **Inventory Service** that:

1. Accepts inventory events with types:
   - `GRN_RECEIVED` — goods received from supplier
   - `QC_APPROVED_RAW` — raw material QC approved
   - `PRODUCTION_CONSUME` — raw materials consumed in production
   - `PRODUCTION_OUTPUT` — finished goods produced (post-QC)
   - `SALES_RESERVE` — order reserved stock
   - `SALES_FULFILL` — order delivered, stock fulfilled
   - `SALES_RELEASE` — order cancelled, reservation released

2. Writes to `inventory_transactions` (and updates `raw_materials` / `finished_goods` via a single code path).

3. Derives available stock from transactions or maintains denormalized `quantity` / `reserved_quantity` only through this service.

#### 3.1.2 Modules That Must Stop Direct Stock Updates

| Module     | Current Behavior                          | Target Behavior                            |
|------------|-------------------------------------------|--------------------------------------------|
| QC         | Directly updates `raw_materials`, `finished_goods` | Emit `QC_APPROVED_RAW` or `QC_APPROVED_FG` only |
| Production | Directly updates `raw_materials`, `finished_goods` | Emit `PRODUCTION_CONSUME`, `PRODUCTION_OUTPUT` only |
| Sales      | Directly updates `reserved_quantity`, `quantity`  | Emit `SALES_RESERVE`, `SALES_FULFILL`, `SALES_RELEASE` |
| Delivery   | Calls `fulfillStockForProduct()` on delivery      | Emit `SALES_ORDER_DELIVERED`; processor fulfills stock |
| Procurement| GRN and returns directly update `raw_materials`   | Emit `GRN_RECEIVED`, `RETURN_PROCESSED`; service updates |

---

### 3.2 System Events (Auto-Generation Triggers)

#### 3.2.1 `system_events` Table

```sql
CREATE TABLE system_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type VARCHAR(50) NOT NULL,   -- e.g. 'sales_order', 'grn', 'qc_record'
    entity_id INT NOT NULL,
    event_type VARCHAR(50) NOT NULL,    -- e.g. 'SALES_ORDER_DELIVERED', 'GRN_RECEIVED'
    payload JSON NULL,                  -- optional context (order_items, amounts, etc.)
    processed TINYINT(1) NOT NULL DEFAULT 0,
    processed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_entity_event (entity_type, entity_id, event_type)
);
```

#### 3.2.2 Usage

- Modules **insert** rows into `system_events` instead of auto-generating invoices, receipts, or stock updates.
- A **background processor** (cron or CLI PHP script):
  1. Reads rows where `processed = 0`
  2. Generates dependent records (invoices, delivery receipts, inventory events)
  3. Sets `processed = 1`, `processed_at = NOW()`
  4. Uses `UNIQUE KEY` to prevent duplicate event insertion and idempotent processing.

---

### 3.3 QC Behavior

- QC modules must only **approve** or **reject**.
- QC must never directly modify `raw_materials.quantity` or `finished_goods.quantity`.
- **Approval** → emit an inventory event (e.g. `QC_APPROVED_RAW`, `QC_APPROVED_FG`).
- **Rejection** → blocks or triggers return flow; no stock increase.

---

### 3.4 Production Phases

Split production batches into clear stages:

| Stage             | Description                          | Inventory Impact                             |
|-------------------|--------------------------------------|----------------------------------------------|
| Planned           | Batch planned, not started           | None                                         |
| In Progress       | Production underway                   | `PRODUCTION_CONSUME` (raw materials deducted) |
| Output Pending QC | Output awaiting QC                   | None                                         |
| Completed         | QC approved; output available        | `PRODUCTION_OUTPUT` (finished goods added)    |

Inventory changes occur only at stage transitions, via the Inventory Service.

---

### 3.5 Sales Progress and Reservation Safety

- Reservations must be **time-bound**: add `reservation_expires_at` (e.g. 24–48 hours).
- Auto-release on:
  - Order cancellation
  - Expiration (background job)
- Stock is **deducted only** on delivery confirmation (event `SALES_ORDER_DELIVERED`); reservation alone does not deduct.

---

### 3.6 Accounting Rules

- Invoices are auto-generated **only** when a `SALES_ORDER_DELIVERED` event is processed.
- Accounting modules must not query `inventory` or `sales_orders` to infer invoice eligibility.
- `invoice_generated` remains a flag updated by the event processor, not by page logic.

---

## 4. Current Violations (Where Rules Are Broken)

The following locations in the codebase violate the core rules.

### 4.1 Direct Stock Mutations (Violates R1, R2)

| File                     | Line | Violation |
|--------------------------|------|-----------|
| `api/save_qc.php`        | 110–127 | Direct `UPDATE finished_goods SET quantity = ?` on QC approval |
| `api/save_production_batch.php` | 82–86 | Direct `UPDATE raw_materials SET quantity = quantity - ?` |
| `save_production.php`    | 32   | Direct `UPDATE raw_materials SET quantity = quantity - ?` |
| `api/save_grn.php`       | 177  | Direct `UPDATE raw_materials SET quantity = quantity + ?` |
| `api/save_return.php`   | 84   | Direct `UPDATE raw_materials SET quantity = GREATEST(0, quantity - ?)` |
| `api/approve_raw_material_qc.php` | 71 | Direct `UPDATE raw_materials SET quantity = quantity + ?` |
| `api/save_raw_material_qc.php` | 108 | Direct `UPDATE raw_materials SET quantity = quantity + ?` |
| `includes/functions.php` | 74, 89, 102 | `reserveStockForProduct`, `releaseReservationForProduct`, `fulfillStockForProduct` directly update `finished_goods` |
| `api/update_gps.php`    | 58–63 | Calls `fulfillStockForProduct()` directly on delivery status change |

### 4.2 Page-Based Auto-Generation (Violates R4, R6)

| File                       | Issue |
|----------------------------|-------|
| `api/auto_generate_invoice.php` | Invoice created when user clicks "Generate Invoice" on delivered order; not event-triggered. |
| `api/update_gps.php`       | Stock fulfillment tied to page/API call when status = Delivered; should be event-driven. |

### 4.3 Reference ID Generation (Potential R5 Violation)

| File                      | Issue |
|---------------------------|-------|
| `includes/functions.php`  | `generateReferenceId()` uses `INSERT ... ON DUPLICATE KEY UPDATE` and a subsequent `SELECT`. Under high concurrency, the non-atomic sequence of operations could theoretically allow duplicates. Use `SELECT ... FOR UPDATE` or a transaction with explicit locking for full safety. |

---

## 5. Proposed Minimal Code Changes Per Module

### 5.1 Procurement

- **GRN receipt:** After saving GRN, insert `system_events` row with `event_type = 'GRN_RECEIVED'`, `entity_type = 'grn'`, `entity_id = grn_id`.
- **Returns:** After saving return, insert `event_type = 'RETURN_PROCESSED'`.
- Remove direct `UPDATE raw_materials`; let the event processor (or Inventory Service) handle stock.

### 5.2 QC (Finished Goods)

- **On approval:** Insert `event_type = 'QC_APPROVED_FG'`, `entity_type = 'qc_record'`, `entity_id = qc_id`.
- Remove `UPDATE finished_goods` and `INSERT INTO finished_goods` from `api/save_qc.php`.
- Event processor (or Inventory Service) applies stock change.

### 5.3 QC (Raw Materials)

- **On approval:** Insert `event_type = 'QC_APPROVED_RAW'`.
- Remove `UPDATE raw_materials` from `api/save_raw_material_qc.php` and `api/approve_raw_material_qc.php`.

### 5.4 Production

- **On batch start:** Insert `event_type = 'PRODUCTION_CONSUME'` (for raw materials).
- **On batch completed (post-QC):** Insert `event_type = 'PRODUCTION_OUTPUT'`.
- Remove direct `UPDATE raw_materials` from `api/save_production_batch.php` and `save_production.php`.

### 5.5 Sales

- **On order save:** Insert `event_type = 'SALES_RESERVE'`; Inventory Service applies reservation.
- **On order cancel:** Insert `event_type = 'SALES_RELEASE'`.
- Add `reservation_expires_at` to `order_items` or `sales_orders`; background job releases expired reservations.

### 5.6 Delivery

- **On status = Delivered:** Insert `event_type = 'SALES_ORDER_DELIVERED'`, `entity_type = 'sales_order'`, `entity_id = order_id`.
- Remove `fulfillStockForProduct()` call from `api/update_gps.php`.

### 5.7 Accounting

- Remove manual "Generate Invoice" dependency on page load.
- Event processor, on `SALES_ORDER_DELIVERED`, creates invoice and delivery receipt.

---

## 6. Example PHP Implementations

### 6.1 Emitting System Events

```php
/**
 * Emit a system event. Idempotent via UNIQUE (entity_type, entity_id, event_type).
 */
function emitSystemEvent($conn, $entity_type, $entity_id, $event_type, array $payload = []) {
    $payload_json = !empty($payload) ? json_encode($payload) : null;
    $stmt = $conn->prepare("
        INSERT INTO system_events (entity_type, entity_id, event_type, payload)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)
    ");
    $stmt->bind_param("siss", $entity_type, $entity_id, $event_type, $payload_json);
    $stmt->execute();
    $event_id = $stmt->insert_id ?: $conn->insert_id;
    $stmt->close();
    return $event_id;
}

// Example: When delivery status becomes Delivered
emitSystemEvent($conn, 'sales_order', $order_id, 'SALES_ORDER_DELIVERED', [
    'order_items' => $order_items,
    'assignment_id' => $assignment_id
]);
```

### 6.2 Processing Inventory Events (Inventory Service)

```php
/**
 * Apply an inventory event and write inventory_transactions.
 * Single entry point for all stock changes.
 */
function processInventoryEvent($conn, $event_type, $event_data) {
    $conn->begin_transaction();
    try {
        switch ($event_type) {
            case 'SALES_FULFILL':
                foreach ($event_data['items'] as $item) {
                    $product_id = (int)$item['product_id'];
                    $qty = (float)$item['quantity'];
                    // Update finished_goods
                    $upd = $conn->prepare("
                        UPDATE finished_goods
                        SET quantity = quantity - ?,
                            reserved_quantity = GREATEST(0, COALESCE(reserved_quantity,0) - ?)
                        WHERE product_id = ?
                    ");
                    $upd->bind_param("ddi", $qty, $qty, $product_id);
                    $upd->execute();
                    $upd->close();
                    // Log transaction
                    logInventoryTransaction($conn, 'Finished Product', $product_id, 'Out', $qty, 'Sales', $event_data['order_id'], 'Order delivered');
                }
                break;
            case 'GRN_RECEIVED':
                // Similar: update raw_materials, log inventory_transaction
                break;
            // ... other event types
        }
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
}
```

### 6.3 Safe Reference ID Generation (Transaction-Safe)

```php
/**
 * Generate a unique reference ID using DB locking. Format: PREFIX-YYYYMMDD-NNNN
 */
function generateReferenceIdSafe($conn, $prefix) {
    $prefix = strtoupper(preg_replace('/[^A-Z0-9]/', '', $prefix));
    if (strlen($prefix) < 2 || strlen($prefix) > 10) return null;

    $today = date('Y-m-d');
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("
            INSERT INTO id_sequences (prefix, seq_date, last_seq)
            VALUES (?, ?, 1)
            ON DUPLICATE KEY UPDATE last_seq = last_seq + 1
        ");
        $stmt->bind_param("ss", $prefix, $today);
        $stmt->execute();
        $stmt->close();

        $sel = $conn->prepare("SELECT last_seq FROM id_sequences WHERE prefix = ? AND seq_date = ?");
        $sel->bind_param("ss", $prefix, $today);
        $sel->execute();
        $res = $sel->get_result()->fetch_assoc();
        $sel->close();

        $conn->commit();
        return $res ? $prefix . '-' . date('Ymd') . '-' . str_pad($res['last_seq'], 4, '0', STR_PAD_LEFT) : null;
    } catch (Exception $e) {
        $conn->rollBack();
        return null;
    }
}
```

---

## 7. Event Processor (Background Job)

```php
// process_system_events.php — run via cron every minute
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/includes/functions.php';

$events = $conn->query("
    SELECT id, entity_type, entity_id, event_type, payload
    FROM system_events
    WHERE processed = 0
    ORDER BY id ASC
    LIMIT 50
");

while ($row = $events->fetch_assoc()) {
    try {
        $payload = $row['payload'] ? json_decode($row['payload'], true) : [];
        if ($row['event_type'] === 'SALES_ORDER_DELIVERED') {
            processInventoryEvent($conn, 'SALES_FULFILL', $payload);
            autoGenerateInvoice($conn, $row['entity_id']);
        }
        // ... handle other event types

        $conn->prepare("UPDATE system_events SET processed = 1, processed_at = NOW() WHERE id = ?")
            ->execute([$row['id']]);
    } catch (Exception $e) {
        // Log and optionally retry later
    }
}
```

---

## 8. Backward Compatibility

- **Phase 1:** Add `system_events` table and `emitSystemEvent()`; modules begin emitting events in parallel with existing logic.
- **Phase 2:** Implement event processor; verify it produces same results as current page logic.
- **Phase 3:** Remove direct stock updates and page-based auto-generation from modules.
- **Phase 4:** Introduce Inventory Service; migrate all stock changes through it.

Existing `inventory_transactions` and `id_sequences` tables remain; new logic extends rather than replaces them.

---

## 9. Summary

| Area           | Before                               | After                                      |
|----------------|--------------------------------------|--------------------------------------------|
| Inventory      | Multiple modules update stock        | Single Inventory Service via events        |
| Invoices       | Page-triggered on delivered order    | Event-triggered by processor               |
| Stock on delivery | `update_gps.php` fulfills directly | `SALES_ORDER_DELIVERED` → processor        |
| QC             | Direct FG/RM updates                | Emit events only                           |
| Reference IDs  | Best-effort atomic                   | Transaction-safe with `generateReferenceIdSafe` |

This architecture supports deterministic, trigger-based automatic generation while keeping changes minimal and backward-compatible.
