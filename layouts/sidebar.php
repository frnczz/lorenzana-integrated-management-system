<?php
$role = $_SESSION['role'] ?? '';
$isAdmin = ($role == 'admin');
$hasProduction = ($role == 'admin' || $role == 'production');
$hasWarehouse = ($role == 'admin' || $role == 'warehouse');
$hasQC = ($role == 'admin' || $role == 'qc');
$hasAccounting = ($role == 'admin' || $role == 'accounting');
$hasSales = ($role == 'admin' || $role == 'sales');
$hasDelivery = ($role == 'delivery' || $role == 'driver');
$hasProcurement = ($role == 'admin' || $role == 'procurement');
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar">
    <?php include "layouts/logo.php"; ?>

    <div class="sidebar-menu-wrapper">
        <ul class="menu">
            <?php if ($isAdmin): ?>
            <li><a href="admin_dashboard.php"><span style="margin-right: 10px;">📊</span> Dashboard</a></li>
            <?php endif; ?>
            <?php if ($role == 'production'): ?>
            <li><a href="production_dashboard.php"><span style="margin-right: 10px;">📊</span> Dashboard</a></li>
            <?php endif; ?>
            <?php if ($role == 'warehouse'): ?>
            <li><a href="warehouse_dashboard.php"><span style="margin-right: 10px;">📊</span> Dashboard</a></li>
            <?php endif; ?>
            <?php if ($role == 'qc'): ?>
            <li><a href="quality_dashboard.php"><span style="margin-right: 10px;">📊</span> Dashboard</a></li>
            <?php endif; ?>
            <?php if ($role == 'sales'): ?>
            <li><a href="sales_dashboard.php"><span style="margin-right: 10px;">📊</span> Dashboard</a></li>
            <?php endif; ?>
            <?php if ($role == 'accounting'): ?>
            <li><a href="accounting_dashboard.php"><span style="margin-right: 10px;">📊</span> Dashboard</a></li>
            <?php endif; ?>


            <?php if ($hasProduction): ?>
            <li class="menu-group">
                <div class="menu-group-header">
                    <a href="production_record.php" class="menu-group-link"><span style="margin-right: 10px;">🏭</span> Production</a>
                    <button type="button" class="menu-group-expand" aria-label="Expand">▾</button>
                </div>
                <ul class="menu-sub" id="sub-production">
                    <li><a href="production_record.php">Production Batch</a></li>
                    <li><a href="production_records.php">Production Records</a></li>
                    <li><a href="production_requests.php">Production Requests</a></li>
                    <li><a href="production_products.php">Product Management</a></li>
                </ul>
            </li>
            <?php endif; ?>

            <?php if ($hasWarehouse): ?>
            <li class="menu-group">
                <div class="menu-group-header">
                    <a href="inventory_summary.php" class="menu-group-link"><span style="margin-right: 10px;">📦</span> Inventory</a>
                    <button type="button" class="menu-group-expand" aria-label="Expand">▾</button>
                </div>
                <ul class="menu-sub" id="sub-inventory">
                    <li><a href="inventory_summary.php">Summary</a></li>
                    <li><a href="inventory_items.php">Items & Records</a></li>
                    <li><a href="inventory_raw_materials.php">Raw Materials</a></li>
                    <li><a href="inventory_batches.php">Batch Tracking</a></li>
                    <li><a href="inventory_returns.php">Returns</a></li>
                </ul>
            </li>
            </li>
            <?php endif; ?>

            <?php if ($hasProcurement): ?>
            <li class="menu-group">
                <div class="menu-group-header">
                    <a href="procurement_dashboard.php" class="menu-group-link"><span style="margin-right: 10px;">🛒</span> Procurement</a>
                    <button type="button" class="menu-group-expand" aria-label="Expand">▾</button>
                </div>
                <ul class="menu-sub" id="sub-procurement">
                    <li><a href="procurement_suppliers.php">Suppliers</a></li>
                    <li><a href="procurement_requisitions.php">Purchase Requisitions</a></li>
                    <li><a href="procurement_orders.php">Purchase Orders</a></li>
                    <li><a href="procurement_receiving.php">Goods Receiving</a></li>
                    <li><a href="procurement_invoices.php">Supplier Invoices</a></li>
                    <li><a href="procurement_returns.php">Returns</a></li>
                </ul>
            </li>
            <?php endif; ?>

            <?php if ($hasQC): ?>
            <li class="menu-group">
                <div class="menu-group-header">
                    <a href="qc_inspection.php" class="menu-group-link"><span style="margin-right: 10px;">✅</span> Quality Control</a>
                    <button type="button" class="menu-group-expand" aria-label="Expand">▾</button>
                </div>
                <ul class="menu-sub" id="sub-qc">
                    <li><a href="qc_inspection.php">Production QC</a></li>
                    <li><a href="qc_raw_materials.php">Raw Materials QC</a></li>
                    <li><a href="qc_records.php">Inspection Records</a></li>
                </ul>
            </li>
            <?php endif; ?>

            <?php if ($hasSales): ?>
            <li class="menu-group">
                <div class="menu-group-header">
                    <a href="sales.php" class="menu-group-link"><span style="margin-right: 10px;">💼</span> Sales</a>
                    <button type="button" class="menu-group-expand" aria-label="Expand">▾</button>
                </div>
                <ul class="menu-sub" id="sub-sales">
                    <li><a href="sales.php">Customer Orders</a></li>
                    <li><a href="customers_transactions.php">Customer Transactions</a></li>
                    <li><a href="sales_request_production.php">Request Production</a></li>
                    <li><a href="sales_delivery.php">Delivery Scheduling</a></li>
                    <li><a href="gps.php">Live Tracking</a></li>
                    <li><a href="sales_products.php">Product Prices</a></li>
                </ul>
            </li>
            <?php endif; ?>

            <?php if ($hasAccounting): ?>
            <li class="menu-group">
                <div class="menu-group-header">
                    <a href="accounting_invoices.php" class="menu-group-link"><span style="margin-right: 10px;">💰</span> Accounting</a>
                    <button type="button" class="menu-group-expand" aria-label="Expand">▾</button>
                </div>
                <ul class="menu-sub" id="sub-accounting">
                    <li><a href="accounting_invoices.php">Invoices</a></li>
                    <li><a href="accounting_payments.php">Payments</a></li>
                    <li><a href="accounting_expenses.php">Expenses</a></li>
                </ul>
            </li>
            <li class="menu-group">
                <div class="menu-group-header">
                    <a href="payroll_employees.php" class="menu-group-link"><span style="margin-right: 10px;">💵</span> Payroll</a>
                    <button type="button" class="menu-group-expand" aria-label="Expand">▾</button>
                </div>
                <ul class="menu-sub" id="sub-payroll">
                    <li><a href="payroll_employees.php">Employees</a></li>
                    <li><a href="payroll_attendance.php">Attendance</a></li>
                    <li><a href="payroll_process.php">Process Payroll</a></li>
                </ul>
            </li>
            <?php endif; ?>

            <?php if ($isAdmin): ?>
            <li><a href="gps.php"><span style="margin-right: 10px;">📍</span> Live Delivery Map</a></li>
            <li>
                <a href="reports.php" class="<?= ($currentPage == 'reports.php') ? 'active' : '' ?>">
                        <span style="margin-right: 10px;">📊</span> Reports
                    </a>
                </li>
            <?php endif; ?>
            
            <?php if ($hasDelivery): ?>
            <li><a href="driver_gps.php"><span style="margin-right: 10px;">📍</span> My GPS / Deliveries</a></li>
            <?php endif; ?>

            <?php if ($isAdmin): ?>
            <li class="menu-group">
                <div class="menu-group-header">
                    <a href="settings_production.php" class="menu-group-link">
                        <span style="margin-right: 10px;">⚙️</span> Settings
                    </a>
                    <button type="button" class="menu-group-expand" aria-label="Expand">▾</button>
                </div>
                <ul class="menu-sub" id="sub-settings">
                    <li><a href="settings_production.php">Production Settings</a></li>
                    <li><a href="settings_warehouse.php">Warehouse Settings</a></li>
                    <li><a href="settings_pagination.php">Pagination Settings</a></li>
                    <li><a href="settings_qc.php">Quality Control Settings</a></li>
                    <li><a href="settings_sales.php">Sales Settings</a></li>
                    <li><a href="settings_accounting.php">Accounting Settings</a></li>
                    <li><a href="settings_Payroll.php">Payroll Settings</a></li>
                </ul>
            </li>
            <?php endif; ?>

            <li class="divider"></li>
            <li><a href="users.php"><span style="margin-right: 10px;">👥</span> User Management</a></li>
            <li><a href="logout.php"><span style="margin-right: 10px;">🚪</span> Logout</a></li>
        </ul>
        </br>
    </div>
</div>

<style>
.sidebar {
    display: flex;
    flex-direction: column;
    height: 100vh; /* full viewport height */
    overflow: hidden; /* prevent sidebar itself from scrolling */
}

.sidebar-menu-wrapper {
    flex: 1; /* take remaining height after logo */
    overflow-y: auto; /* enable vertical scrolling */
    padding-right: 5px; /* optional, avoid scrollbar overlap */
}

.sidebar-menu-wrapper::-webkit-scrollbar {
    width: 6px;
}

.sidebar-menu-wrapper::-webkit-scrollbar-thumb {
    background-color: rgba(0,0,0,0.2);
    border-radius: 3px;
}

.sidebar-menu-wrapper::-webkit-scrollbar-track {
    background: transparent;
}

.sidebar-menu-wrapper {
    scroll-behavior: smooth;
}
</style>

<script>
document.querySelectorAll(".menu-group-expand").forEach(function(btn){
    btn.addEventListener("click", function(e){
        e.preventDefault();
        e.stopPropagation();
        var group = this.closest(".menu-group");
        if(group) group.classList.toggle("open");
    });
});
</script>
