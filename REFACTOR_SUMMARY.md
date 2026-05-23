# LORINIMS Refactor Summary

This document summarizes the system refactor for database normalization, Sales & Delivery, Production, Inventory, Procurement, GPS, UI, Accounting, Payroll, and Profile.

---

## 1. Database & Relationships

### Migration file: `database_refactor.sql`

**Run once on your existing database** (after `database_schema.sql` and optionally `database_id_sequences.sql`):

```bash
mysql -u root lorinims_db < database_refactor.sql
```

Or execute the file in phpMyAdmin.

- **order_items** – New table for multiple products per order.
  - `order_id`, `product_id`, `quantity`, `unit_price`, `subtotal`, `reserved`
  - FKs: `sales_orders(order_id)`, `products(product_id)`
- **sales_orders** – `total_amount` added; `product_id` and `quantity` made nullable (legacy support). New orders use only `order_items`.
- **Backfill** – Existing rows with `product_id`/`quantity` are copied into `order_items`.
- **finished_goods** – `reserved_quantity` added for stock reservation.
- **products** – `fermentation_eligible` (TINYINT), `unit_price` added.
- **activity_log** – User activity for profile “Recent activity”.
- **supplier_deliveries** & **supplier_delivery_items** – Procurement delivery tracking.

Relationships: **orders → order_items → products**; **production → inventory** (raw_materials, finished_goods) with FKs preserved.

---

## 2. Sales & Delivery Module

- **Order ID** – Still auto-generated (e.g. `ORD-YYYYMMDD-NNNN`) via `generateReferenceId('ORD')`.
- **Delivery dropdown** – Uses `order_number` and customer name (unchanged).
- **Multiple products per order** – Create order form has repeatable lines: product (with stock in dropdown), quantity, “Add product line”. Data is saved to `order_items`; one order can have many items.
- **Delete order** – “Delete” button on non-delivered/non-cancelled orders calls `api/delete_order.php`, sets status to Cancelled and releases reserved stock.
- **Stock reservation** – On order save, `reserved_quantity` in `finished_goods` is increased per product. On cancel, it is released; on delivery (status = Delivered in `update_gps.php`), stock is fulfilled (quantity and reserved_quantity decreased).
- **Product quantity in UI** – Orders list shows “Products / Qty” from `order_items` (or legacy product/qty). Sales form shows “Stock” per product in dropdown.

---

## 3. Production Module

- **Inventory update** – Recording a production batch still updates `finished_goods` and `inventory_transactions` (no change in flow).
- **Fermentation** – `products.fermentation_eligible` (default 1). In **Record Production Batch**:
  - Product dropdown is a simple select (no image picker) and includes fermentation flag.
  - “Fermentation (if applicable)” row: if product is not fermentation-eligible, the dropdown is hidden and “N/A — This product does not require fermentation” is shown; backend stores `Not Started` for those.

---

## 4. Inventory Module

- **Raw materials & finished goods** – Existing tables; `finished_goods` now has `reserved_quantity`.
- **Low stock alerts** – **Inventory Summary** shows counts and a **Low stock alerts** table (raw materials where `quantity <= min_stock_level`).
- **Near-expiry alerts** – **Near-expiry alerts (next 30 days)** table for raw materials with `expiry_date` in the next 30 days.
- **Finished goods low** – Summary includes count of finished goods with available (quantity − reserved) &lt; 10.

---

## 5. Procurement Module

- **Procurement Dashboard** – `procurement_dashboard.php`:
  - Overview: suppliers count, PR pending, PR approved/ordered.
  - **Record incoming delivery** – Form: supplier, delivery date, reference, items (one per line: name, qty, unit), notes. Saves to `supplier_deliveries` and `supplier_delivery_items`.
  - **Recent supplier deliveries** – List of deliveries with items.
- Sidebar: **Procurement → Dashboard** added.

---

## 6. Delivery & GPS Module

- **Live tracking map** – `gps.php` (Admin & Sales):
  - Leaflet map; markers for each active delivery (status Dispatched / On the Way / Arrived) using latest GPS from `gps_tracking`.
  - List of active deliveries below the map.
  - Polls `api/get_active_deliveries.php` every 10 seconds.
- **Admin**: Sidebar “Live Delivery Map” → `gps.php`.
- **Sales**: Sales submenu “Live Tracking” → `gps.php`.
- **Driver**: “My GPS / Deliveries” → `driver_gps.php` (unchanged).

---

## 7. UI / UX

- **Sidebar** – Consistent structure: Dashboard first per module (Procurement, Accounting), then sub-items. Sales: Customer Orders, Delivery Scheduling, Live Tracking. Admin: Live Delivery Map.
- **Responsive** – `.table-responsive` for horizontal scroll of wide tables; `.content` padding reduced on small screens.
- **Sales** – Multi-line order form, “Products / Qty” and “Total” in orders table, Delete with confirm.

---

## 8. Accounting & Payroll

- **Accounting Dashboard** – `accounting_dashboard.php`: revenue (paid), pending invoices, expenses, net; quick links to Invoices and Expenses. Sidebar: **Accounting → Dashboard**.
- **Payroll** – Gross Pay and Net Pay **removed from the Process Payroll form**. Only Basic Salary, Overtime Pay, Allowances, and Deductions are entered; backend computes and stores gross and net as before.

---

## 9. Profile & Activity

- **My Activity** – Stats (batches, orders, inspections, invoices, deliveries) remain **per user** (by `created_by` / `driver_id`).
- **Recent activity** – If `activity_log` exists, profile shows a “Recent activity” list (last 15 actions) for the current user (create order, cancel order, create batch, etc.).
- **Activity logging** – Used in: create order, cancel order, production batch, (and can be extended in save_supplier_delivery, etc.). Logging is no-op if `activity_log` table is missing.

---

## Files Added

- `database_refactor.sql` – Migration.
- `api/delete_order.php` – Cancel order and release reservation.
- `api/get_active_deliveries.php` – JSON for live map.
- `api/save_supplier_delivery.php` – Record supplier delivery.
- `procurement_dashboard.php` – Procurement dashboard.
- `accounting_dashboard.php` – Accounting dashboard.
- `gps.php` – Live delivery map (Admin/Sales).

## Files Modified

- `includes/functions.php` – `getProductAvailableStock`, `reserveStockForProduct`, `releaseReservationForProduct`, `fulfillStockForProduct`, `logActivity` (and safe no-op if no table).
- `api/save_order.php` – Multiple products, `order_items`, reservation, `total_amount`.
- `api/update_gps.php` – On Delivered, fulfill stock from `order_items`.
- `sales.php` – Multi-product form, order list with items and total, Delete button.
- `production_record.php` – Product select with fermentation eligibility; fermentation row toggled by product.
- `api/save_production_batch.php` – Fermentation N/A when product not eligible; activity log.
- `inventory_summary.php` – Low stock and near-expiry alert tables; FG low count.
- `layouts/sidebar.php` – Procurement Dashboard, Accounting Dashboard, Live Tracking / Live Delivery Map.
- `profile.php` – “Recent activity” from `activity_log`.
- `payroll_process.php` – Gross/Net Pay rows removed from form.
- `assets/css/style.css` – `.table-responsive`, content padding on small screens.

---

## Order of Setup

1. Apply `database_schema.sql` (or keep existing DB).
2. Apply `database_id_sequences.sql` if you use auto-generated IDs.
3. Apply **`database_refactor.sql`** once.
4. Use the system; new orders use `order_items` and stock reservation; old orders still display via backfilled `order_items` or legacy columns.

If any ALTER in `database_refactor.sql` fails (e.g. column already exists), you can comment out that line and re-run the rest.
