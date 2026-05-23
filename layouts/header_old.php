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
?>

<div class="header" style="background: linear-gradient(135deg, rgba(255, 107, 53, 0.85) 0%, rgba(255, 140, 90, 0.85) 100%), url('assets/images/360_F_548522072_V4Cdwk3kUFFrMBRAWTC7fsGS9q0W4VAR.jpg') center/cover; box-shadow: 0 8px 32px rgba(255, 107, 53, 0.15); position: relative;">
    <!-- Decorative overlay -->
    <div style="position: absolute; top: -50%; right: -10%; width: 500px; height: 500px; background: rgba(255, 255, 255, 0.03); border-radius: 50%; pointer-events: none;"></div>
    <div style="position: absolute; bottom: -30%; left: -5%; width: 300px; height: 300px; background: rgba(255, 255, 255, 0.02); border-radius: 50%; pointer-events: none;"></div>
    
    <div style="display: flex; align-items: center; gap: 15px; flex: 1; min-width: 0; position: relative; z-index: 2;">
        <div class="desktop-logo"><?php include "layouts/logo.php"; ?></div>
        <div class="mobile-logo"><img src="<?php echo dirname($_SERVER['PHP_SELF']); ?>/assets/images/1653274078_SITE-LOGO.png" alt="Logo">
        <script src="<?php echo dirname($_SERVER['PHP_SELF']); ?>/assets/js/mobile_nav.js"></script></div>
        <div id="mobileModuleSelect" class="mobile-module-select">
            <select id="mobileNavSelect" aria-label="Navigate to" class="mobile-nav-select">
                <option value="">📍 Select a page...</option>
                
                <!-- DASHBOARDS -->
                <optgroup label="📊 DASHBOARDS">
                    <?php if ($isAdmin): ?><option value="admin_dashboard.php" <?php echo $currentPage == 'admin_dashboard.php' ? 'selected' : ''; ?>>Admin Dashboard</option><?php endif; ?>
                    <?php if ($role == 'production'): ?><option value="production_dashboard.php" <?php echo $currentPage == 'production_dashboard.php' ? 'selected' : ''; ?>>Production Dashboard</option><?php endif; ?>
                    <?php if ($role == 'warehouse'): ?><option value="warehouse_dashboard.php" <?php echo $currentPage == 'warehouse_dashboard.php' ? 'selected' : ''; ?>>Warehouse Dashboard</option><?php endif; ?>
                    <?php if ($role == 'qc'): ?><option value="quality_dashboard.php" <?php echo $currentPage == 'quality_dashboard.php' ? 'selected' : ''; ?>>Quality Dashboard</option><?php endif; ?>
                    <?php if ($role == 'sales'): ?><option value="sales_dashboard.php" <?php echo $currentPage == 'sales_dashboard.php' ? 'selected' : ''; ?>>Sales Dashboard</option><?php endif; ?>
                    <?php if ($role == 'accounting'): ?><option value="accounting_dashboard.php" <?php echo $currentPage == 'accounting_dashboard.php' ? 'selected' : ''; ?>>Accounting Dashboard</option><?php endif; ?>
                    <?php if ($role == 'procurement'): ?><option value="procurement_dashboard.php" <?php echo $currentPage == 'procurement_dashboard.php' ? 'selected' : ''; ?>>Procurement Dashboard</option><?php endif; ?>
                </optgroup>
                
                <!-- PRODUCTION -->
                <?php if ($hasProduction): ?>
                <optgroup label="⚙️ PRODUCTION">
                    <option value="production.php" <?php echo $currentPage == 'production.php' ? 'selected' : ''; ?>>Production Home</option>
                    <option value="production_record.php" <?php echo $currentPage == 'production_record.php' ? 'selected' : ''; ?>>Record Batch</option>
                    <option value="production_records.php" <?php echo $currentPage == 'production_records.php' ? 'selected' : ''; ?>>Batch Records</option>
                    <option value="production_requests.php" <?php echo $currentPage == 'production_requests.php' ? 'selected' : ''; ?>>Production Requests</option>
                    <option value="production_products.php" <?php echo $currentPage == 'production_products.php' ? 'selected' : ''; ?>>Products</option>
                </optgroup>
                <?php endif; ?>
                
                <!-- WAREHOUSE & INVENTORY -->
                <?php if ($hasWarehouse): ?>
                <optgroup label="📦 WAREHOUSE & INVENTORY">
                    <option value="inventory.php" <?php echo $currentPage == 'inventory.php' ? 'selected' : ''; ?>>Inventory Home</option>
                    <option value="inventory_summary.php" <?php echo $currentPage == 'inventory_summary.php' ? 'selected' : ''; ?>>Inventory Summary</option>
                    <option value="inventory_items.php" <?php echo $currentPage == 'inventory_items.php' ? 'selected' : ''; ?>>Inventory Items</option>
                    <option value="inventory_raw_materials.php" <?php echo $currentPage == 'inventory_raw_materials.php' ? 'selected' : ''; ?>>Raw Materials</option>
                </optgroup>
                <?php endif; ?>
                
                <!-- PROCUREMENT -->
                <?php if ($hasWarehouse || $isAdmin): ?>
                <optgroup label="🛒 PROCUREMENT">
                    <option value="procurement.php" <?php echo $currentPage == 'procurement.php' ? 'selected' : ''; ?>>Procurement Home</option>
                    <option value="procurement_suppliers.php" <?php echo $currentPage == 'procurement_suppliers.php' ? 'selected' : ''; ?>>Suppliers</option>
                    <option value="procurement_requests.php" <?php echo $currentPage == 'procurement_requests.php' ? 'selected' : ''; ?>>Purchase Requests</option>
                    <option value="procurement_requisitions.php" <?php echo $currentPage == 'procurement_requisitions.php' ? 'selected' : ''; ?>>Requisitions</option>
                    <option value="procurement_orders.php" <?php echo $currentPage == 'procurement_orders.php' ? 'selected' : ''; ?>>Purchase Orders</option>
                    <option value="procurement_invoices.php" <?php echo $currentPage == 'procurement_invoices.php' ? 'selected' : ''; ?>>Supplier Invoices</option>
                    <option value="procurement_receiving.php" <?php echo $currentPage == 'procurement_receiving.php' ? 'selected' : ''; ?>>Goods Receipt</option>
                    <option value="procurement_returns.php" <?php echo $currentPage == 'procurement_returns.php' ? 'selected' : ''; ?>>Returns</option>
                </optgroup>
                <?php endif; ?>
                
                <!-- QUALITY CONTROL -->
                <?php if ($hasQC): ?>
                <optgroup label="✓ QUALITY CONTROL">
                    <option value="qc.php" <?php echo $currentPage == 'qc.php' ? 'selected' : ''; ?>>QC Home</option>
                    <option value="qc_inspection.php" <?php echo $currentPage == 'qc_inspection.php' ? 'selected' : ''; ?>>Record Inspection</option>
                    <option value="qc_records.php" <?php echo $currentPage == 'qc_records.php' ? 'selected' : ''; ?>>QC Records</option>
                    <option value="qc_raw_materials.php" <?php echo $currentPage == 'qc_raw_materials.php' ? 'selected' : ''; ?>>Raw Material QC</option>
                </optgroup>
                <?php endif; ?>
                
                <!-- SALES & DELIVERY -->
                <?php if ($hasSales): ?>
                <optgroup label="🚚 SALES & DELIVERY">
                    <option value="sales.php" <?php echo $currentPage == 'sales.php' ? 'selected' : ''; ?>>Sales Home</option>
                    <option value="sales_dashboard.php" <?php echo $currentPage == 'sales_dashboard.php' ? 'selected' : ''; ?>>Sales Orders</option>
                    <option value="sales_products.php" <?php echo $currentPage == 'sales_products.php' ? 'selected' : ''; ?>>Products</option>
                    <option value="sales_delivery.php" <?php echo $currentPage == 'sales_delivery.php' ? 'selected' : ''; ?>>Delivery Tracking</option>
                    <option value="customers_transactions.php" <?php echo $currentPage == 'customers_transactions.php' ? 'selected' : ''; ?>>Customer Transactions</option>
                </optgroup>
                <?php endif; ?>
                
                <!-- ACCOUNTING & PAYROLL -->
                <?php if ($hasAccounting): ?>
                <optgroup label="💰 ACCOUNTING & PAYROLL">
                    <option value="accounting.php" <?php echo $currentPage == 'accounting.php' ? 'selected' : ''; ?>>Accounting Home</option>
                    <option value="accounting_dashboard.php" <?php echo $currentPage == 'accounting_dashboard.php' ? 'selected' : ''; ?>>Accounting Dashboard</option>
                    <option value="accounting_invoices.php" <?php echo $currentPage == 'accounting_invoices.php' ? 'selected' : ''; ?>>Invoices</option>
                    <option value="accounting_expenses.php" <?php echo $currentPage == 'accounting_expenses.php' ? 'selected' : ''; ?>>Expenses</option>
                    <option value="accounting_payments.php" <?php echo $currentPage == 'accounting_payments.php' ? 'selected' : ''; ?>>Payments</option>
                    <option value="payroll.php" <?php echo $currentPage == 'payroll.php' ? 'selected' : ''; ?>>Payroll Home</option>
                    <option value="payroll_employees.php" <?php echo $currentPage == 'payroll_employees.php' ? 'selected' : ''; ?>>Employees</option>
                    <option value="payroll_attendance.php" <?php echo $currentPage == 'payroll_attendance.php' ? 'selected' : ''; ?>>Attendance</option>
                    <option value="payroll_process.php" <?php echo $currentPage == 'payroll_process.php' ? 'selected' : ''; ?>>Process Payroll</option>
                </optgroup>
                <?php endif; ?>
                
                <!-- DELIVERY & GPS -->
                <?php if ($hasDelivery): ?>
                <optgroup label="📍 DELIVERY & GPS">
                    <option value="driver_gps.php" <?php echo $currentPage == 'driver_gps.php' ? 'selected' : ''; ?>>GPS Tracking</option>
                    <option value="gps.php" <?php echo $currentPage == 'gps.php' ? 'selected' : ''; ?>>Map View</option>
                </optgroup>
                <?php endif; ?>
                
                <!-- ADMIN -->
                <?php if ($isAdmin): ?>
                <optgroup label="⚙️ ADMINISTRATION">
                    <option value="users.php" <?php echo $currentPage == 'users.php' ? 'selected' : ''; ?>>User Management</option>
                    <option value="settings_warehouse.php" <?php echo $currentPage == 'settings_warehouse.php' ? 'selected' : ''; ?>>Warehouse Settings</option>
                    <option value="settings_production.php" <?php echo $currentPage == 'settings_production.php' ? 'selected' : ''; ?>>Production Settings</option>
                    <option value="settings_qc.php" <?php echo $currentPage == 'settings_qc.php' ? 'selected' : ''; ?>>QC Settings</option>
                    <option value="settings_sales.php" <?php echo $currentPage == 'settings_sales.php' ? 'selected' : ''; ?>>Sales Settings</option>
                    <option value="settings_accounting.php" <?php echo $currentPage == 'settings_accounting.php' ? 'selected' : ''; ?>>Accounting Settings</option>
                    <option value="settings_payroll.php" <?php echo $currentPage == 'settings_payroll.php' ? 'selected' : ''; ?>>Payroll Settings</option>
                    <option value="reports.php" <?php echo $currentPage == 'reports.php' ? 'selected' : ''; ?>>Reports</option>
                </optgroup>
                <?php endif; ?>
                
                <!-- ACCOUNT -->
                <optgroup label="👤 ACCOUNT">
                    <option value="profile.php" <?php echo $currentPage == 'profile.php' ? 'selected' : ''; ?>>My Profile</option>
                    <option value="logout.php">🚪 Logout</option>
                </optgroup>
            </select>
        </div>
        
        <button id="toggleSidebar" title="Toggle Sidebar" style="
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            padding: 8px 12px;
            border-radius: var(--border-radius-sm);
            cursor: pointer;
            font-size: 16px;
            transition: all var(--transition-fast);
            display: none;"
            onmouseover="this.style.background='rgba(255, 255, 255, 0.3)'"
            onmouseout="this.style.background='rgba(255, 255, 255, 0.2)'">
            ☰
        </button>

        <div style="display: flex; flex-direction: column; gap: 2px;" class="header-title">
            <h1 style="font-family: 'Georgia', 'Times New Roman', serif; font-size: 28px; font-weight: 700; margin: 0; color: white; letter-spacing: 1px; text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);">LORENZANA FOOD CORPORATION</h1>
            <p style="font-family: 'Georgia', 'Times New Roman', serif; font-size: 12px; font-weight: 500; margin: 0; color: rgba(255, 255, 255, 0.95); letter-spacing: 2px; text-transform: uppercase; font-style: italic;">Integrated Management System</p>
        </div>

        <!-- Compact Logo for Closed Sidebar -->
        <div class="header-logo-compact" style="display: none; align-items: center; justify-content: center;">
            <div style="width: 100px; height: 50px; background: linear-gradient(135deg, #FF6B35 0%, #FF8C5A 100%); border: 2px solid white; border-radius: 50%; display: flex; flex-direction: column; justify-content: center; align-items: center; box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);">
                <div style="font-family: 'Georgia', 'Times New Roman', serif; font-size: 18px; font-weight: bold; color: white; letter-spacing: 1px; text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);">LORINS</div>
                <div style="font-family: Arial, sans-serif; font-size: 8px; color: white; letter-spacing: 1px; margin-top: 2px;">Since 1973</div>
            </div>
        </div>
    </div>

    <div style="display: flex; align-items: center; gap: 15px; flex: 0 0 auto; position: relative; z-index: 2;">
        <div style="position: relative;" class="user-profile-dropdown">
            <button type="button" onclick="toggleUserProfile(this)" style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 10px 16px; border-radius: 12px; transition: all var(--transition-fast); background: rgba(255, 255, 255, 0.15); border: 1px solid rgba(255, 255, 255, 0.2); flex-wrap: nowrap;" class="user-profile-trigger" onmouseover="this.style.background='rgba(255, 255, 255, 0.25)'; this.style.boxShadow='0 4px 12px rgba(0, 0, 0, 0.2)';" onmouseout="this.style.background='rgba(255, 255, 255, 0.15)'; this.style.boxShadow='none';">
                <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #FFF9E6 0%, #FFE6CC 100%); display: flex; align-items: center; justify-content: center; color: #FF6B35; font-weight: bold; font-size: 16px; border: 2px solid rgba(255, 255, 255, 0.4);">
                    <?php echo strtoupper(substr($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'U', 0, 1)); ?>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 14px; font-weight: 600; color: white;">
                        <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User'); ?>
                    </div>
                    <div style="font-size: 11px; color: rgba(255, 255, 255, 0.85); text-transform: capitalize;">
                        <?php echo htmlspecialchars($_SESSION['role'] ?? 'User'); ?>
                    </div>
                </div>
                <span style="font-size: 12px; color: rgba(255, 255, 255, 0.7);">▼</span>
            </button>
            <div id="userProfileMenu" style="display: none; position: absolute; top: 100%; right: 0; margin-top: 10px; background: white; border-radius: 12px; box-shadow: var(--shadow-lg); min-width: 250px; z-index: 1000; border: 1px solid var(--border-color);">
                <div style="padding: 20px; border-bottom: 1px solid var(--border-color);">
                    <div style="font-weight: 600; color: var(--text-primary); margin-bottom: 5px;">
                        <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User'); ?>
                    </div>
                    <div style="font-size: 12px; color: var(--text-muted); text-transform: capitalize;">
                        <?php echo htmlspecialchars($_SESSION['role'] ?? 'User'); ?>
                    </div>
                    <?php if (!empty($_SESSION['email'])): ?>
                        <div style="font-size: 12px; color: var(--text-muted); margin-top: 5px;">
                            <?php echo htmlspecialchars($_SESSION['email']); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div style="padding: 10px;">
                    <a href="profile.php" style="display: block; padding: 12px; color: var(--text-primary); text-decoration: none; border-radius: 8px; transition: all var(--transition-fast); margin-bottom: 5px;" onmouseover="this.style.background='var(--bg-tertiary)'" onmouseout="this.style.background='transparent'">
                        <strong>👤 My Profile</strong>
                    </a>
                    <a href="logout.php" style="display: block; padding: 12px; color: #dc2626; text-decoration: none; border-radius: 8px; transition: all var(--transition-fast);" onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='transparent'">
                        <strong>🚪 Logout</strong>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <style>
        .user-profile-trigger:hover {
            background: rgba(255, 255, 255, 0.25) !important;
        }
        #userProfileMenu {
            animation: slideDown 0.2s ease-out;
        }
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .header {
            position: relative;
        }
        .header h1 {
            animation: fadeInDown 0.6s ease-out;
        }
        .header p {
            animation: fadeInDown 0.6s ease-out 0.1s both;
        }
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>

    <script>
        function toggleUserProfile(btn) {
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
        document.getElementById('userProfileMenu').addEventListener('click', function(event) {
            event.stopPropagation();
        });
    </script>
</div>

<div class="sidebar-overlay"></div>

<style>
    @media (min-width: 771px) {
        #mobileMenuToggle {
            display: none !important;
        }
        #mobileModuleSelect {
            display: none !important;
        }
        #toggleSidebar {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            pointer-events: auto !important;
        }
        .header .lorins-logo {
            display: none;
        }
    }
    @media (max-width: 770px) {
        #toggleSidebar {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            pointer-events: none !important;
        }
        #mobileMenuToggle {
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
            pointer-events: auto !important;
            z-index: 1001;
            position: relative;
            cursor: pointer;
        }
        .header .lorins-logo {
            display: flex;
            align-items: center;
        }
        .header .lorins-logo .logo-oval {
            width: 70px;
            height: 35px;
            min-width: 70px;
        }
        .header .lorins-logo .logo-main {
            font-size: 16px;
        }
        .header .lorins-logo .logo-since {
            font-size: 7px;
        }
        .header {
            flex-direction: row;
            flex-wrap: nowrap;
            align-items: center;
            gap: 10px;
        }
        .header > div:first-child {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }
        .header > div:last-child {
            flex: 0 0 auto;
        }
        .header h1 {
            font-size: 18px !important;
        }
        .header p {
            font-size: 9px !important;
        }
    }
    
    @media (max-width: 480px) {
        .header .lorins-logo {
            display: none;
        }
        .header {
            padding: var(--spacing-sm);
        }
        .user-profile-trigger > div:nth-child(2) {
            display: none;
        }
        .user-profile-trigger {
            justify-content: center;
            padding: var(--spacing-sm);
        }
        .header h1 {
            font-size: 14px !important;
        }
        .header p {
            font-size: 8px !important;
        }
        
        /* Improve dropdown on extra small devices */
        #mobileModuleSelect {
            display: flex !important;
            flex: 1 1 auto;
            min-width: 150px;
            margin: 0 4px;
        }
        
        .mobile-nav-select {
            width: 100%;
            font-size: 13px;
            padding: 8px 10px;
            padding-right: 28px;
        }
    }
</style>

<script src="assets/js/mobile_nav.js"></script>

