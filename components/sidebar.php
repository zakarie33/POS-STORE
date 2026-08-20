<?php
require_once __DIR__ . '/../utils/auth_check.php';
require_login();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <i class="ph-fill ph-storefront"></i>
            <span>SuperPOS</span>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="ph ph-squares-four"></i>
            <span>Dashboard</span>
        </a>
        
        <a href="pos.php" class="nav-item <?php echo $current_page == 'pos.php' ? 'active' : ''; ?>">
            <i class="ph ph-desktop"></i>
            <span>Point of Sale</span>
        </a>

        <div style="margin: 1rem 1.5rem 0.5rem; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 600;">Inventory</div>
        
        <a href="products.php" class="nav-item <?php echo $current_page == 'products.php' ? 'active' : ''; ?>">
            <i class="ph ph-package"></i>
            <span>Products</span>
        </a>
        
        <a href="categories.php" class="nav-item <?php echo $current_page == 'categories.php' ? 'active' : ''; ?>">
            <i class="ph ph-tag"></i>
            <span>Categories</span>
        </a>

        <div style="margin: 1rem 1.5rem 0.5rem; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 600;">People</div>
        
        <a href="customers.php" class="nav-item <?php echo $current_page == 'customers.php' ? 'active' : ''; ?>">
            <i class="ph ph-users"></i>
            <span>Customers</span>
        </a>
        
        <a href="suppliers.php" class="nav-item <?php echo $current_page == 'suppliers.php' ? 'active' : ''; ?>">
            <i class="ph ph-truck"></i>
            <span>Suppliers</span>
        </a>

        <?php if(has_role('admin')): ?>
        <a href="users.php" class="nav-item <?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
            <i class="ph ph-user-gear"></i>
            <span>Users</span>
        </a>
        <?php endif; ?>

        <?php if(has_role(['admin', 'manager'])): ?>
        <div style="margin: 1rem 1.5rem 0.5rem; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 600;">Reports & Logs</div>
        
        <a href="reports.php" class="nav-item <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
            <i class="ph ph-chart-line-up"></i>
            <span>Reports</span>
        </a>
        <?php endif; ?>
        
        <?php if(has_role('admin')): ?>
        <a href="settings.php" class="nav-item <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
            <i class="ph ph-gear"></i>
            <span>Settings</span>
        </a>
        <?php endif; ?>
    </nav>
</aside>
