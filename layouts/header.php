<?php
$role = $_SESSION['role'] ?? '';
$isAdmin = ($role == 'admin');
$hasProduction = ($role == 'admin' || $role == 'production');
$hasWarehouse = ($role == 'admin' || $role == 'warehouse');
$hasQC = ($role == 'admin' || $role == 'qc');
$hasAccounting = ($role == 'admin' || $role == 'accounting');
$hasSales = ($role == 'admin' || $role == 'sales');
$hasDelivery = ($role == 'delivery' || $role == 'driver');
$currentPage = basename($_SERVER['PHP_SELF']);

if (!isset($conn)) {
    $lorinimsDb = __DIR__ . '/../db_connect.php';
    if (is_file($lorinimsDb)) {
        @include_once $lorinimsDb;
    }
}
if (!function_exists('getUnreadAppNotifications') && is_file(__DIR__ . '/../includes/functions.php')) {
    require_once __DIR__ . '/../includes/functions.php';
}

$lorinims_notif_items = [];
$lorinims_notif_count = 0;
$admin_view_only_mode = (int)($_SESSION['admin_view_only_mode'] ?? 0);
if (isset($conn) && is_object($conn) && !empty($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0 && in_array($role, ['admin', 'production', 'qc'], true) && function_exists('getUnreadAppNotifications')) {
    $lorinims_notif_items = getUnreadAppNotifications($conn, (int)$_SESSION['user_id'], $role);
    $lorinims_notif_count = count($lorinims_notif_items);
}

// Mobile nav menu: built in PHP so <script> contains valid JavaScript (no <?php inside array literals).
$navMenuItems = [];
if ($isAdmin) {
    $navMenuItems[] = ['group' => '📊 DASHBOARDS', 'label' => 'Admin Dashboard', 'value' => 'admin_dashboard.php'];
}
if ($role === 'production') {
    $navMenuItems[] = ['group' => '📊 DASHBOARDS', 'label' => 'Production Dashboard', 'value' => 'production_dashboard.php'];
}
if ($role === 'warehouse') {
    $navMenuItems[] = ['group' => '📊 DASHBOARDS', 'label' => 'Warehouse Dashboard', 'value' => 'warehouse_dashboard.php'];
}
if ($role === 'qc') {
    $navMenuItems[] = ['group' => '📊 DASHBOARDS', 'label' => 'Quality Dashboard', 'value' => 'quality_dashboard.php'];
}
if ($role === 'sales') {
    $navMenuItems[] = ['group' => '📊 DASHBOARDS', 'label' => 'Sales Dashboard', 'value' => 'sales_dashboard.php'];
}
if ($role === 'accounting') {
    $navMenuItems[] = ['group' => '📊 DASHBOARDS', 'label' => 'Accounting Dashboard', 'value' => 'accounting_dashboard.php'];
}
if ($role === 'procurement') {
    $navMenuItems[] = ['group' => '📊 DASHBOARDS', 'label' => 'Procurement Dashboard', 'value' => 'procurement_dashboard.php'];
}
if ($hasProduction) {
    $navMenuItems[] = ['group' => '⚙️ PRODUCTION', 'label' => 'Production Home', 'value' => 'production.php'];
    $navMenuItems[] = ['group' => '⚙️ PRODUCTION', 'label' => 'Record Batch', 'value' => 'production_record.php'];
    $navMenuItems[] = ['group' => '⚙️ PRODUCTION', 'label' => 'Batch Records', 'value' => 'production_records.php'];
    $navMenuItems[] = ['group' => '⚙️ PRODUCTION', 'label' => 'Production Requests', 'value' => 'production_requests.php'];
    $navMenuItems[] = ['group' => '⚙️ PRODUCTION', 'label' => 'Products', 'value' => 'production_products.php'];
}
if ($hasWarehouse) {
    $navMenuItems[] = ['group' => '📦 WAREHOUSE & INVENTORY', 'label' => 'Inventory Home', 'value' => 'inventory.php'];
    $navMenuItems[] = ['group' => '📦 WAREHOUSE & INVENTORY', 'label' => 'Inventory Summary', 'value' => 'inventory_summary.php'];
    $navMenuItems[] = ['group' => '📦 WAREHOUSE & INVENTORY', 'label' => 'Inventory Items', 'value' => 'inventory_items.php'];
    $navMenuItems[] = ['group' => '📦 WAREHOUSE & INVENTORY', 'label' => 'Raw Materials', 'value' => 'inventory_raw_materials.php'];
}
if ($hasWarehouse || $isAdmin) {
    $navMenuItems[] = ['group' => '🛒 PROCUREMENT', 'label' => 'Procurement Home', 'value' => 'procurement.php'];
    $navMenuItems[] = ['group' => '🛒 PROCUREMENT', 'label' => 'Suppliers', 'value' => 'procurement_suppliers.php'];
    $navMenuItems[] = ['group' => '🛒 PROCUREMENT', 'label' => 'Purchase Requests', 'value' => 'procurement_requests.php'];
    $navMenuItems[] = ['group' => '🛒 PROCUREMENT', 'label' => 'Requisitions', 'value' => 'procurement_requisitions.php'];
    $navMenuItems[] = ['group' => '🛒 PROCUREMENT', 'label' => 'Purchase Orders', 'value' => 'procurement_orders.php'];
    $navMenuItems[] = ['group' => '🛒 PROCUREMENT', 'label' => 'Supplier Invoices', 'value' => 'procurement_invoices.php'];
    $navMenuItems[] = ['group' => '🛒 PROCUREMENT', 'label' => 'Goods Receipt', 'value' => 'procurement_receiving.php'];
    $navMenuItems[] = ['group' => '🛒 PROCUREMENT', 'label' => 'Returns', 'value' => 'procurement_returns.php'];
}
if ($hasQC) {
    $navMenuItems[] = ['group' => '✓ QUALITY CONTROL', 'label' => 'QC Home', 'value' => 'qc.php'];
    $navMenuItems[] = ['group' => '✓ QUALITY CONTROL', 'label' => 'Record Inspection', 'value' => 'qc_inspection.php'];
    $navMenuItems[] = ['group' => '✓ QUALITY CONTROL', 'label' => 'QC Records', 'value' => 'qc_records.php'];
    $navMenuItems[] = ['group' => '✓ QUALITY CONTROL', 'label' => 'Raw Material QC', 'value' => 'qc_raw_materials.php'];
}
if ($hasSales) {
    $navMenuItems[] = ['group' => '🚚 SALES & DELIVERY', 'label' => 'Sales Home', 'value' => 'sales.php'];
    $navMenuItems[] = ['group' => '🚚 SALES & DELIVERY', 'label' => 'Sales Orders', 'value' => 'sales_dashboard.php'];
    $navMenuItems[] = ['group' => '🚚 SALES & DELIVERY', 'label' => 'Products', 'value' => 'sales_products.php'];
    $navMenuItems[] = ['group' => '🚚 SALES & DELIVERY', 'label' => 'Delivery Tracking', 'value' => 'sales_delivery.php'];
    $navMenuItems[] = ['group' => '🚚 SALES & DELIVERY', 'label' => 'Customer Transactions', 'value' => 'customers_transactions.php'];
}
if ($hasAccounting) {
    $navMenuItems[] = ['group' => '💰 ACCOUNTING & PAYROLL', 'label' => 'Accounting Home', 'value' => 'accounting.php'];
    $navMenuItems[] = ['group' => '💰 ACCOUNTING & PAYROLL', 'label' => 'Accounting Dashboard', 'value' => 'accounting_dashboard.php'];
    $navMenuItems[] = ['group' => '💰 ACCOUNTING & PAYROLL', 'label' => 'Invoices', 'value' => 'accounting_invoices.php'];
    $navMenuItems[] = ['group' => '💰 ACCOUNTING & PAYROLL', 'label' => 'Expenses', 'value' => 'accounting_expenses.php'];
    $navMenuItems[] = ['group' => '💰 ACCOUNTING & PAYROLL', 'label' => 'Payments', 'value' => 'accounting_payments.php'];
    $navMenuItems[] = ['group' => '💰 ACCOUNTING & PAYROLL', 'label' => 'Payroll Home', 'value' => 'payroll.php'];
    $navMenuItems[] = ['group' => '💰 ACCOUNTING & PAYROLL', 'label' => 'Employees', 'value' => 'payroll_employees.php'];
    $navMenuItems[] = ['group' => '💰 ACCOUNTING & PAYROLL', 'label' => 'Attendance', 'value' => 'payroll_attendance.php'];
    $navMenuItems[] = ['group' => '💰 ACCOUNTING & PAYROLL', 'label' => 'Process Payroll', 'value' => 'payroll_process.php'];
}
if ($hasDelivery) {
    $navMenuItems[] = ['group' => '📍 DELIVERY & GPS', 'label' => 'GPS Tracking', 'value' => 'driver_gps.php'];
    $navMenuItems[] = ['group' => '📍 DELIVERY & GPS', 'label' => 'Map View', 'value' => 'gps.php'];
}
if ($isAdmin) {
    $navMenuItems[] = ['group' => '⚙️ ADMINISTRATION', 'label' => 'User Management', 'value' => 'users.php'];
    $navMenuItems[] = ['group' => '⚙️ ADMINISTRATION', 'label' => 'Warehouse Settings', 'value' => 'settings_warehouse.php'];
    $navMenuItems[] = ['group' => '⚙️ ADMINISTRATION', 'label' => 'Production Settings', 'value' => 'settings_production.php'];
    $navMenuItems[] = ['group' => '⚙️ ADMINISTRATION', 'label' => 'QC Settings', 'value' => 'settings_qc.php'];
    $navMenuItems[] = ['group' => '⚙️ ADMINISTRATION', 'label' => 'Sales Settings', 'value' => 'settings_sales.php'];
    $navMenuItems[] = ['group' => '⚙️ ADMINISTRATION', 'label' => 'Accounting Settings', 'value' => 'settings_accounting.php'];
    $navMenuItems[] = ['group' => '⚙️ ADMINISTRATION', 'label' => 'Payroll Settings', 'value' => 'settings_payroll.php'];
    $navMenuItems[] = ['group' => '⚙️ ADMINISTRATION', 'label' => 'Reports', 'value' => 'reports.php'];
}
$navMenuItems[] = ['group' => '👤 ACCOUNT', 'label' => 'My Profile', 'value' => 'profile.php'];
$navMenuItems[] = ['group' => '👤 ACCOUNT', 'label' => 'Logout', 'value' => 'logout.php'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lorenzana Integrated Management System</title>
    
    <!-- Favicon -->
    <link rel="icon" href="<?php echo $basePath; ?>/assets/images/1653274078_SITE-LOGO.png" type="image/png">
    
    <!-- Main CSS -->
    <link rel="stylesheet" href="<?php echo $basePath; ?>/assets/css/style.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Georgia:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">

    <!-- Header Inline Styles -->
    <style>
        /* Header Base Styles */
        .app-header {
            height: 70px;
            background: linear-gradient(135deg, rgba(255, 107, 53, 0.98) 0%, rgba(255, 140, 90, 0.98) 100%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 12px rgba(255, 107, 53, 0.15);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 16px;
            flex: 1;
            min-width: 0;
        }

        .header-center {
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 0.8;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 0 0 auto;
        }

        .header-logo {
            flex-shrink: 0;
            display: none;
            align-items: center;
            justify-content: center;
        }

        .header-logo img {
            max-height: 45px;
            width: auto;
            object-fit: contain;
        }

        .header-logo-mobile {
            display: none;
        }

        .header-btn {
            background: rgba(255, 255, 255, 0.15);
            border: 2px solid rgba(255, 255, 255, 0.25);
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 150ms cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 44px;
            min-height: 44px;
            flex-shrink: 0;
        }

        .header-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.4);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .header-btn:active {
            transform: translateY(0);
        }

        .toggle-icon {
            display: block;
            width: 24px;
            height: 24px;
            line-height: 24px;
            text-align: center;
        }

        .header-title {
            display: flex;
            flex-direction: column;
            gap: 2px;
            flex: 1;
            min-width: 0;
        }

        .header-title h1 {
            font-family: 'Georgia', 'Times New Roman', serif;
            font-size: 24px;
            font-weight: 700;
            margin: 0;
            color: white;
            letter-spacing: 0.5px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .header-title p {
            font-family: 'Georgia', 'Times New Roman', serif;
            font-size: 11px;
            font-weight: 500;
            margin: 0;
            color: rgba(255, 255, 255, 0.9);
            letter-spacing: 1px;
            text-transform: uppercase;
            font-style: italic;
        }

        .header-nav-select {
            display: none;
        }

        .header-nav-select optgroup {
            font-weight: 600;
            background: #f5f5f5;
            color: #1a0f0a;
        }

        .header-nav-select option {
            padding: 8px 12px;
            background: white;
            color: #1a0f0a;
        }

        /* Modern Dropdown Styles */
        .dropdown-wrapper {
            position: relative;
            width: 100%;
        }

        .dropdown-trigger {
            width: 100%;
            padding: 8px 12px;
            border: 2px solid rgba(255, 255, 255, 0.25);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.95);
            color: #1a0f0a;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 150ms cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .dropdown-trigger:hover {
            background-color: white;
            border-color: rgba(255, 255, 255, 0.4);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .dropdown-trigger.active {
            background-color: white;
            border-color: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .dropdown-icon {
            font-size: 12px;
            transition: transform 150ms ease;
        }

        .dropdown-trigger.active .dropdown-icon {
            transform: rotate(180deg);
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            top: calc(100% + 6px);
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            min-width: 100%;
            max-height: 400px;
            overflow-y: auto;
            animation: dropdownSlide 200ms ease-out;
        }

        .dropdown-menu.active {
            display: block;
        }

        @keyframes dropdownSlide {
            from {
                opacity: 0;
                transform: translateY(-8px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .dropdown-search {
            padding: 10px 12px;
            border-bottom: 1px solid #e0e0e0;
            position: sticky;
            top: 0;
            background: white;
        }

        .dropdown-search input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            font-size: 13px;
            transition: all 150ms ease;
            box-sizing: border-box;
        }

        .dropdown-search input:focus {
            outline: none;
            border-color: #FF6B35;
            box-shadow: 0 0 0 2px rgba(255, 107, 53, 0.1);
        }

        .dropdown-search input::placeholder {
            color: #999;
        }

        .dropdown-items {
            padding: 4px 0;
            max-height: 320px;
            overflow-y: auto;
        }

        .dropdown-item {
            padding: 10px 14px;
            color: #1a0f0a;
            cursor: pointer;
            transition: all 100ms ease;
            font-size: 13px;
            border-left: 3px solid transparent;
        }

        .dropdown-item:hover {
            background: #f8f8f8;
            border-left-color: #FF6B35;
            color: #FF6B35;
        }

        .dropdown-item.selected {
            background: rgba(255, 107, 53, 0.1);
            border-left-color: #FF6B35;
            color: #FF6B35;
            font-weight: 600;
        }

        .dropdown-group {
            padding: 8px 0;
        }

        .dropdown-group-label {
            padding: 8px 14px 4px 14px;
            font-size: 11px;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .dropdown-item.hidden {
            display: none;
        }

        .dropdown-no-results {
            padding: 20px 14px;
            text-align: center;
            color: #999;
            font-size: 13px;
        }

        .user-profile-dropdown {
            position: relative;
        }

        .user-profile-trigger {
            gap: 10px;
            padding: 8px 14px;
        }

        .profile-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #FFF9E6 0%, #FFE6CC 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #FF6B35;
            font-weight: 700;
            font-size: 14px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            flex-shrink: 0;
        }

        .profile-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
            min-width: 0;
        }

        .profile-name {
            font-size: 13px;
            font-weight: 600;
            color: white;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .profile-role {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.85);
            text-transform: capitalize;
        }

        .profile-chevron {
            font-size: 10px;
            color: rgba(255, 255, 255, 0.7);
            transition: transform 150ms cubic-bezier(0.4, 0, 0.2, 1);
        }

        .user-profile-menu {
            display: none;
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.16);
            min-width: 280px;
            z-index: 2000;
            border: 1px solid #e2e8f0;
            animation: headerSlideDown 0.2s ease-out;
            overflow: hidden;
        }

        .menu-header {
            padding: 16px;
            border-bottom: 1px solid #e2e8f0;
        }

        .menu-user-name {
            font-weight: 600;
            color: #0f172a;
            font-size: 14px;
            margin-bottom: 4px;
        }

        .menu-user-role {
            font-size: 12px;
            color: #94a3b8;
            text-transform: capitalize;
        }

        .menu-user-email {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 6px;
            word-break: break-word;
        }

        .menu-divider {
            height: 1px;
            background: #e2e8f0;
        }

        .menu-items {
            padding: 8px;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            color: #0f172a;
            text-decoration: none;
            border-radius: 8px;
            transition: all 150ms cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 14px;
        }

        .menu-item:hover {
            background: #f1f5f9;
            color: #FF6B35;
        }

        .menu-item-logout:hover {
            background: #fee2e2;
            color: #dc2626;
        }

        @keyframes headerSlideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Mobile Responsiveness */
        @media (max-width: 770px) {
            .app-header {
                height: 60px;
                padding: 0 12px;
                gap: 8px;
            }

            .header-left {
                gap: 8px;
                flex: 1;
                min-width: 0;
                order: 1;
            }

            .header-center {
                display: flex !important;
                flex: 1;
                min-width: 120px;
                order: 0;
            }

            .header-right {
                order: 2;
            }

            .header-nav-select {
                max-width: 100%;
                font-size: 12px;
                padding: 6px 8px;
                padding-right: 24px;
                height: 36px;
            }

            .header-title {
                display: none;
            }

            #toggleSidebar {
                display: none;
            }

            .header-logo {
                display: none;
            }

            .header-logo-mobile {
                display: flex !important;
                order: -1;
                margin-right: 8px;
            }

            .header-logo-mobile img {
                max-height: 48px;
                width: auto;
            }

            .dropdown-wrapper {
                flex: 1;
                min-width: 0;
            }

            .dropdown-trigger {
                padding: 6px 10px;
                font-size: 12px;
            }

            .user-profile-trigger {
                padding: 6px 10px;
                gap: 8px;
            }

            .profile-avatar {
                width: 32px;
                height: 32px;
                font-size: 12px;
            }

            .profile-info {
                display: none;
            }

            .profile-chevron {
                display: none;
            }

            .user-profile-menu {
                right: -10px;
                width: calc(100vw - 20px);
                max-width: 320px;
            }
        }

        @media (max-width: 480px) {
            .app-header {
                height: 56px;
                padding: 0 10px;
            }

            .header-logo-mobile img {
                max-height: 42px;
            }

            .dropdown-trigger {
                padding: 6px 8px;
                font-size: 11px;
            }

            .dropdown-menu {
                max-height: 350px;
            }

            .dropdown-items {
                max-height: 280px;
            }

            .header-btn {
                min-width: 40px;
                min-height: 40px;
                padding: 6px 10px;
            }

            .toggle-icon {
                font-size: 18px;
            }
        }

        /* Desktop View - Hide Dropdown */
        @media (min-width: 771px) {
            .header-center {
                display: none !important;
            }

            #toggleSidebar {
                display: block !important;
            }
        }
    </style>

    <!-- Mobile Navigation JS -->
    <script src="<?php echo $basePath; ?>/assets/js/mobile_nav.js" defer></script>
</head>
<body>

<header class="app-header">
    <!-- Left Section: Logo, Toggle, Title -->
    <div class="header-left">
        <!-- Desktop Logo -->
        <div class="header-logo desktop-logo">
            <?php include "layouts/logo.php"; ?>
        </div>
        
        <!-- Mobile Logo -->
        <div class="header-logo-mobile mobile-logo">
            <img src="<?php echo dirname($_SERVER['PHP_SELF']); ?>/assets/images/1653274078_SITE-LOGO.png" alt="Lorenzana">
        </div>
        
        <!-- Sidebar Toggle Button -->
        <button id="toggleSidebar" class="header-btn header-toggle-btn" title="Toggle Sidebar" aria-label="Toggle Sidebar">
            <span class="toggle-icon">≡</span>
        </button>
        
        <!-- Company Title (Desktop Only) -->
        <div class="header-title">
            <h1>LORENZANA FOOD CORPORATION</h1>
            <p>Integrated Management System</p>
        </div>
    </div>

    <!-- Center Section: Navigation Dropdown (Mobile) -->
    <div class="header-center">
        <div class="dropdown-wrapper">
            <button type="button" class="dropdown-trigger" id="dropdownTrigger" aria-label="Navigate to">
                <span id="dropdownText">📍 Navigate</span>
                <span class="dropdown-icon">▼</span>
            </button>
            <div class="dropdown-menu" id="dropdownMenu">
                <div class="dropdown-search">
                    <input type="text" id="dropdownSearch" placeholder="🔍 Search pages..." autocomplete="off">
                </div>
                <div class="dropdown-items" id="dropdownItems">
                    <!-- Items will be populated by JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <!-- Right Section: Notifications + User Profile -->
    <div class="header-right">
        <?php if ($lorinims_notif_count > 0): ?>
        <div class="header-notif-wrap" style="position:relative;">
            <button type="button" class="header-btn" id="headerNotifBtn" title="Notifications" aria-label="Notifications" style="position:relative;">
                🔔
                <span style="position:absolute;top:-4px;right:-4px;background:#dc2626;color:#fff;border-radius:999px;font-size:11px;min-width:18px;height:18px;line-height:18px;padding:0 5px;font-weight:700;"><?php echo (int)$lorinims_notif_count; ?></span>
            </button>
            <div id="headerNotifPanel" style="display:none;position:absolute;right:0;top:48px;width:min(360px,92vw);max-height:320px;overflow-y:auto;background:#fff;border-radius:12px;box-shadow:0 12px 40px rgba(0,0,0,0.2);z-index:200;border:1px solid #e5e7eb;">
                <div style="padding:10px 12px;font-weight:700;font-size:13px;border-bottom:1px solid #f1f5f9;color:#0f172a;">Production alerts</div>
                <?php foreach ($lorinims_notif_items as $ni): ?>
                    <a href="<?php echo htmlspecialchars($ni['link'] ?: 'production_records.php', ENT_QUOTES, 'UTF-8'); ?>"
                       class="header-notif-item" data-nid="<?php echo (int)$ni['id']; ?>"
                       style="display:block;padding:10px 12px;border-bottom:1px solid #f1f5f9;text-decoration:none;color:#334155;font-size:13px;line-height:1.4;">
                        <?php echo htmlspecialchars($ni['message'], ENT_QUOTES, 'UTF-8'); ?>
                        <span style="display:block;font-size:11px;color:#94a3b8;margin-top:4px;"><?php echo htmlspecialchars($ni['created_at'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <script>
        (function(){
            var btn = document.getElementById('headerNotifBtn');
            var panel = document.getElementById('headerNotifPanel');
            if (!btn || !panel) return;
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
            });
            document.addEventListener('click', function() { panel.style.display = 'none'; });
            panel.addEventListener('click', function(e) { e.stopPropagation(); });
            document.querySelectorAll('.header-notif-item').forEach(function(a) {
                a.addEventListener('click', function() {
                    var id = this.getAttribute('data-nid');
                    if (id) {
                        var fd = new FormData();
                        fd.append('notification_id', id);
                        navigator.sendBeacon('api/mark_notification_read.php', fd);
                    }
                });
            });
        })();
        </script>
        <?php endif; ?>
        <div class="user-profile-dropdown">
            <button type="button" class="header-btn user-profile-trigger" onclick="toggleUserProfile(event)" aria-label="User menu">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'U', 0, 1)); ?>
                </div>
                <div class="profile-info">
                    <div class="profile-name">
                        <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User'); ?>
                    </div>
                    <div class="profile-role">
                        <?php echo htmlspecialchars($_SESSION['role'] ?? 'User'); ?>
                    </div>
                </div>
                <span class="profile-chevron">▼</span>
            </button>
            
            <!-- User Menu Dropdown -->
            <div id="userProfileMenu" class="user-profile-menu">
                <div class="menu-header">
                    <div class="menu-user-name">
                        <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User'); ?>
                    </div>
                    <div class="menu-user-role">
                        <?php echo htmlspecialchars($_SESSION['role'] ?? 'User'); ?>
                    </div>
                    <?php if (!empty($_SESSION['email'])): ?>
                        <div class="menu-user-email">
                            <?php echo htmlspecialchars($_SESSION['email']); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="menu-divider"></div>
                <div class="menu-items">
                    <?php if ($isAdmin): ?>
                    <div class="menu-item" style="justify-content: space-between; gap: 12px;">
                        <span style="display:flex; align-items:center; gap:8px;">
                            <span>👁️</span>
                            <span>Admin View-Only</span>
                        </span>
                        <label style="display:inline-flex; align-items:center; gap:6px; cursor:pointer;">
                            <input type="checkbox" id="adminViewOnlyToggle" <?php echo $admin_view_only_mode ? 'checked' : ''; ?>>
                            <span style="font-size:12px; color:#64748b;">ON</span>
                        </label>
                    </div>
                    <div class="menu-divider"></div>
                    <?php endif; ?>
                    <a href="profile.php" class="menu-item">
                        <span>👤</span>
                        <span>My Profile</span>
                    </a>
                    <a href="logout.php" class="menu-item menu-item-logout">
                        <span>🚪</span>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<div class="sidebar-overlay"></div>

<script>
    function toggleUserProfile(event) {
        event.stopPropagation();
        const menu = document.getElementById('userProfileMenu');
        const isHidden = menu.style.display === 'none' || !menu.style.display;
        menu.style.display = isHidden ? 'block' : 'none';
    }

    // Close menu when clicking outside
    document.addEventListener('click', function(event) {
        const dropdown = document.querySelector('.user-profile-dropdown');
        const menu = document.getElementById('userProfileMenu');
        if (dropdown && !dropdown.contains(event.target)) {
            menu.style.display = 'none';
        }
    });
    
    // Prevent menu from closing when clicking inside it
    if (document.getElementById('userProfileMenu')) {
        document.getElementById('userProfileMenu').addEventListener('click', function(event) {
            event.stopPropagation();
        });
    }

    // Mobile navigation dropdown with search
    const dropdownTrigger = document.getElementById('dropdownTrigger');
    const dropdownMenu = document.getElementById('dropdownMenu');
    const dropdownSearch = document.getElementById('dropdownSearch');
    const dropdownItems = document.getElementById('dropdownItems');
    
    // Navigation data from PHP (JSON); menu built server-side so this script stays valid JavaScript
    const navData = <?php echo json_encode($navMenuItems, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS); ?>;

    function renderDropdownItems(items = navData) {
        const groups = {};
        items.forEach(item => {
            if (!groups[item.group]) {
                groups[item.group] = [];
            }
            groups[item.group].push(item);
        });

        dropdownItems.innerHTML = '';
        let noResults = true;

        Object.keys(groups).forEach(groupName => {
            const group = document.createElement('div');
            group.className = 'dropdown-group';
            
            const label = document.createElement('div');
            label.className = 'dropdown-group-label';
            label.textContent = groupName;
            group.appendChild(label);

            groups[groupName].forEach(item => {
                const itemEl = document.createElement('div');
                itemEl.className = 'dropdown-item';
                itemEl.textContent = item.label;
                itemEl.dataset.value = item.value;
                itemEl.addEventListener('click', () => {
                    window.location.href = item.value;
                });
                group.appendChild(itemEl);
                noResults = false;
            });

            dropdownItems.appendChild(group);
        });

        if (noResults) {
            const noResults = document.createElement('div');
            noResults.className = 'dropdown-no-results';
            noResults.textContent = 'No results found';
            dropdownItems.appendChild(noResults);
        }
    }

    // Toggle dropdown
    dropdownTrigger.addEventListener('click', (e) => {
        e.stopPropagation();
        dropdownMenu.classList.toggle('active');
        dropdownTrigger.classList.toggle('active');
        if (dropdownMenu.classList.contains('active')) {
            dropdownSearch.focus();
        }
    });

    // Search functionality
    dropdownSearch.addEventListener('input', (e) => {
        const query = e.target.value.toLowerCase();
        const filtered = navData.filter(item => 
            item.label.toLowerCase().includes(query) || 
            item.group.toLowerCase().includes(query)
        );
        renderDropdownItems(filtered);
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.dropdown-wrapper')) {
            dropdownMenu.classList.remove('active');
            dropdownTrigger.classList.remove('active');
        }
    });

    // Prevent closing when clicking inside dropdown
    dropdownMenu.addEventListener('click', (e) => {
        if (!e.target.closest('.dropdown-item') && !e.target.closest('.dropdown-search')) {
            e.stopPropagation();
        }
    });

    // Initial render
    renderDropdownItems();

    // Admin view-only toggle
    (function () {
        var t = document.getElementById('adminViewOnlyToggle');
        if (!t) return;
        t.addEventListener('change', function () {
            var fd = new FormData();
            fd.append('enabled', t.checked ? '1' : '0');
            fetch('api/toggle_admin_view_only.php', { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (!res || !res.success) {
                        alert((res && res.message) ? res.message : 'Could not update admin view-only mode.');
                        t.checked = !t.checked;
                        return;
                    }
                    window.location.reload();
                })
                .catch(function () {
                    alert('Network error while changing admin view-only mode.');
                    t.checked = !t.checked;
                });
        });
    })();

    // Sidebar toggle button
    const toggleBtn = document.getElementById('toggleSidebar');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar');
            if (sidebar) {
                sidebar.classList.toggle('collapsed');
            }
        });
    }
</script>

<body>