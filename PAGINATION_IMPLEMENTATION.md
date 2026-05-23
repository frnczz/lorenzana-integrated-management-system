# Pagination Implementation Guide

This project already includes a reusable pagination helper set in `includes/functions.php`. The goal is to make *any table listing page* paginated with minimal effort.

---
## ✅ Core Helpers (Already Available)

These functions are defined in `includes/functions.php`:

- **`getPagination($conn, $count_sql, $per_page = null)`**
  - Returns an array: `['page', 'per_page', 'offset', 'total', 'total_pages', 'prev_page', 'next_page']`
  - Reads `$_GET['page']` and `$_GET['per_page']` and clamps reasonable values.
  - Requires a SQL `SELECT COUNT(*) as c FROM ...` query.

- **`renderPerPageSelector($conn, $current_per_page)`**
  - Renders a dropdown selector that updates `per_page` via the current query string.
  - Uses the pagination options stored in the database (default: 10,25,50,100,200).

- **`renderPagination($pagination)`**
  - Renders links for page navigation using `page` in the current query string.

---
## ✅ How to Add Pagination to a Table (Example)
Use `production_products.php` as the reference implementation.

### 1) Add Pagination Setup (PHP)
Place this near the top, where you load data:

```php
$pagination = function_exists('getPagination')
    ? getPagination($conn, "SELECT COUNT(*) as c FROM products")
    : ['offset' => 0, 'per_page' => 25, 'total' => 0, 'total_pages' => 1];

$products_query = $conn->query("SELECT ... FROM products ... ORDER BY ... LIMIT {$pagination['offset']}, {$pagination['per_page']}");
```

### 2) Render the Per-Page Selector (Above the Table)
Use this near the top of the table so users can choose how many rows to see:

```php
<?php if (function_exists('renderPerPageSelector')) echo '<div class="pagination-toolbar">' . renderPerPageSelector($conn, $pagination['per_page']) . '</div>'; ?>
```

### 3) Render Pagination Links (Below the Table)
Place this after the table closing tag:

```php
<?php if (function_exists('renderPagination')) echo renderPagination($pagination); ?>
```

---
## 💡 Notes & Best Practices

- **Always include `includes/functions.php`** on the page (most pages already do).
- **Count query should match the filter criteria** used in your table query.
- **Sorting & pagination should work together:** apply `ORDER BY ... LIMIT {offset}, {per_page}`.
- If a page has multiple tables, you can implement per-table pagination by using unique query parameters (e.g., `raw_page`, `fg_page`) and extending `getPagination` slightly. (The helper currently assumes `page` and `per_page`.)

---
## ✅ Recent Example: `procurement_invoices.php`
That page now uses `getPagination`:
- It counts invoices with `SELECT COUNT(*) ... FROM supplier_invoices`.
- Handles `page` and `per_page` automatically.
- Adds the per-page selector and pagination controls around the invoice table.

---
## Next Steps
To add pagination for another table:
1. Add a count query for that table.
2. Use the helper to get `$pagination`.
3. Apply `LIMIT {$pagination['offset']}, {$pagination['per_page']}`.
4. Add the selector and pagination UI using the helper render functions.

If you want, send me one specific page you want paginated next and I can apply the exact changes for you.