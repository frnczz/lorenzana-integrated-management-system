# Sorting System Implementation Guide

## Overview
The sorting system provides a reusable way to add sortable column headers to any table in LORINIMS. Columns become clickable links that toggle between ascending/descending order with visual indicators.

## How It Works

### 1. **Sorting Functions** (in `includes/functions.php`)

Three main functions are available:

#### `getSortParams($default_column, $allowed_columns = [])`
Gets the current sort parameters from URL query string.

**Parameters:**
- `$default_column`: Column to sort by if none specified
- `$allowed_columns`: Array of allowed column names for validation

**Returns:** Array with keys:
- `column`: The current sort column
- `order`: Current sort order (ASC/DESC)
- `toggle`: The opposite order for toggling

**Example:**
```php
$sort = getSortParams('created_at', ['order_number', 'status', 'created_at']);
// Returns: ['column' => 'created_at', 'order' => 'DESC', 'toggle' => 'ASC']
```

#### `sortHeader($column, $label, $current_sort, $params = [])`
Generates a clickable table header with sort indicator.

**Parameters:**
- `$column`: Database column name
- `$label`: Display label on header
- `$current_sort`: Result from `getSortParams()`
- `$params`: Existing query parameters to preserve

**Returns:** HTML link with sort arrow (↑ for ASC, ↓ for DESC)

**Features:**
- Bold + blue if currently sorted
- Faded if not sorted
- Click to toggle between ASC/DESC
- Preserves other query parameters

---

## Implementation Steps

### Step 1: Get Sort Parameters
At the top of your query section, get the sort params:

```php
// Load sort parameters
$sort = getSortParams('order_date', ['order_number', 'customer_name', 'status', 'order_date']);

// Map display columns to database columns
$column_map = [
    'order_number' => 'so.order_number',
    'customer_name' => 'c.customer_name',
    'status' => 'so.status',
    'order_date' => 'so.order_date'
];

// Get the actual database column
$order_by = isset($column_map[$sort['column']]) ? $column_map[$sort['column']] : 'so.order_date';

// Execute query with sorting
$result = $conn->query("
    SELECT * FROM sales_orders so
    LEFT JOIN customers c ON so.customer_id = c.customer_id
    ORDER BY " . $order_by . " " . $sort['order']
);
```

### Step 2: Update Table Headers
Replace static headers with `sortHeader()` calls:

**Before:**
```php
<table>
    <tr>
        <th>Order No</th>
        <th>Customer</th>
        <th>Date</th>
    </tr>
```

**After:**
```php
<table>
    <tr>
        <th><?php echo sortHeader('order_number', 'Order No', $sort); ?></th>
        <th><?php echo sortHeader('customer_name', 'Customer', $sort); ?></th>
        <th><?php echo sortHeader('order_date', 'Date', $sort); ?></th>
    </tr>
```

### Step 3: Add Non-Sortable Columns (Optional)
For columns that shouldn't be sortable, just use plain text:

```php
<th>Actions</th>
<th>Product</th>
```

---

## Complete Example: Employee Table

```php
<?php
// Get sort parameters (default: sort by last name)
$sort = getSortParams('last_name', ['employee_number', 'first_name', 'last_name', 'position', 'department', 'salary']);

// Map columns
$column_map = [
    'employee_number' => 'e.employee_number',
    'first_name' => 'e.first_name',
    'last_name' => 'e.last_name',
    'position' => 'e.position',
    'department' => 'e.department',
    'salary' => 'e.salary'
];

$order_by = isset($column_map[$sort['column']]) ? $column_map[$sort['column']] : 'e.last_name';

// Query with sorting
$employees = $conn->query("
    SELECT e.* FROM employees e
    ORDER BY " . $order_by . " " . $sort['order']
);
?>

<table>
    <tr>
        <th><?php echo sortHeader('employee_number', 'Emp. No', $sort); ?></th>
        <th><?php echo sortHeader('last_name', 'Last Name', $sort); ?></th>
        <th><?php echo sortHeader('first_name', 'First Name', $sort); ?></th>
        <th><?php echo sortHeader('position', 'Position', $sort); ?></th>
        <th><?php echo sortHeader('department', 'Department', $sort); ?></th>
        <th><?php echo sortHeader('salary', 'Salary', $sort); ?></th>
        <th>Actions</th>
    </tr>
    <?php while ($emp = $employees->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($emp['employee_number']); ?></td>
            <td><?php echo htmlspecialchars($emp['last_name']); ?></td>
            <td><?php echo htmlspecialchars($emp['first_name']); ?></td>
            <td><?php echo htmlspecialchars($emp['position'] ?? '-'); ?></td>
            <td><?php echo htmlspecialchars($emp['department'] ?? '-'); ?></td>
            <td><?php echo formatCurrency($emp['salary']); ?></td>
            <td><button class="btn">Edit</button></td>
        </tr>
    <?php endwhile; ?>
</table>
```

---

## Visual Indicators

When a column header is sortable and interactive:

- **↑** = Currently sorted ascending
- **↓** = Currently sorted descending  
- **No arrow** = Not currently sorted
- **Orange highlight** = Currently sorted column (matches LORINIMS orange theme)
- **White/light text** = Other sortable columns on orange table headers
- **Cursor pointer** = Indicates clickable

---

## Preserving Other Query Parameters

If your page has filters, pagination, or other query parameters, they're automatically preserved:

```php
// Example: sorting employees by department with pagination
// URL: employees.php?page=2&role=production&sort_by=salary&sort_order=ASC
// Clicking a column header will preserve page=2 and role=production

$sort = getSortParams('last_name', ['last_name', 'salary']);
// Still keeps page=2&role=production in the links
```

---

## Database Column Mapping

Always map display column names to actual database columns. This is especially important with JOINs:

```php
$column_map = [
    'order_number' => 'so.order_number',      // Table alias required for JOINs
    'customer_name' => 'c.customer_name',
    'status' => 'so.status',
    'price' => 'oi.unit_price'
];
```

---

## Tables Currently with Sorting

✅ **Admin Dashboard** - Recent Orders & Deliveries  
✅ **payroll_employees.php** - Employee records  
✅ **sales.php** - Finished Orders, Pickup Orders, Pending Deliveries, Customers  
✅ **procurement_invoices.php** - Supplier invoices  
✅ **qc_inspection.php** - QC records  
✅ **production_records.php** - Production batches  
✅ **accounting_invoices.php** - Sales invoices  
✅ **customers_transactions.php** - Orders, Invoices, Payments  
✅ **inventory_items.php** - Raw materials, finished goods, QC batches  
✅ **inventory_summary.php** - Low stock & expiring materials  
✅ **inventory_raw_materials.php** - Raw materials  
✅ **sales_products.php** - Product list  
✅ **users.php** - System users  
✅ **accounting_expenses.php** - Expenses  
✅ **production_products.php** - Products  
✅ **production_requests.php** - Production requests  

## Table Styling (Orange Theme)

All table headers use the LORINIMS orange theme:
- Background: `linear-gradient(135deg, #FF6B35 0%, #FF8C5A 100%)`
- Sort links: Orange border/background when active; white text when inactive

---

## Best Practices

1. ✅ Always validate allowed columns
2. ✅ Use table aliases in column mappings
3. ✅ Default to a reasonable column
4. ✅ Apply DESC to dates/numbers, ASC to names
5. ✅ Preserve other query parameters automatically
6. ✅ Don't make all columns sortable (keep actions, IDs non-sortable)
7. ✅ Test with NULL values and edge cases

---

## Troubleshooting

**Column not sorting?**
- Check column is in `$allowed_columns`
- Verify database column name in mapping
- Ensure column exists in table

**Other parameters lost?**
- Parameters are auto-preserved, no additional code needed
- Check URL structure if manually building links

**Multiple sorting levels?**
- Current implementation: single column sort
- To add secondary sort: extend `getSortParams()` to handle multiple columns

---

## Future Enhancements

- [ ] Multi-column sorting (primary + secondary)
- [ ] Custom sort comparators for special data types
- [ ] Drag-to-reorder columns
- [ ] Save user's preferred sort preferences
- [ ] Export sorted data to CSV

