<?php
$role = $_SESSION['role'] ?? 'user';
$base_path = $role == 'super_admin' ? '../superadmin' : ($role == 'admin' ? '../admin' : '../user');
?>

<div class="sidebar">
    <div class="sidebar-header">
        <h3>IMS</h3>
        <p>Inventory System</p>
    </div>
    
    <div class="sidebar-menu">
        <a href="<?php echo $base_path; ?>/dashboard.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        
        <?php if ($role == 'user'): ?>
            <!-- User Menu Items -->
            <a href="../user/add_inventory.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'add_inventory.php' ? 'active' : ''; ?>">
                <i class="fas fa-plus-circle"></i>
                <span>Add Inventory</span>
            </a>
            
            <a href="../user/my_inventory.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'my_inventory.php' ? 'active' : ''; ?>">
                <i class="fas fa-boxes"></i>
                <span>My Inventory</span>
            </a>
        <?php endif; ?>
        
        <?php if ($role != 'user'): ?>
            <!-- Admin/Super Admin Menu Items -->
            <a href="../admin/inventory.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'inventory.php' ? 'active' : ''; ?>">
                <i class="fas fa-boxes"></i>
                <span>All Inventory</span>
            </a>
            
            <a href="../admin/add_inventory.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'add_inventory.php' ? 'active' : ''; ?>">
                <i class="fas fa-plus-circle"></i>
                <span>Add Inventory</span>
            </a>
        <?php endif; ?>
        
        <a href="../scan/index.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' && strpos($_SERVER['PHP_SELF'], '/scan/') !== false ? 'active' : ''; ?>">
            <i class="fas fa-qrcode"></i>
            <span>Scan Barcode</span>
        </a>
        
        <?php if ($role == 'super_admin'): ?>
            <!-- Super Admin Only -->
            <div style="padding: 15px 25px 5px; font-size: 12px; opacity: 0.5;">SUPER ADMIN</div>
            
            <a href="../superadmin/audit_trail.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'audit_trail.php' ? 'active' : ''; ?>">
                <i class="fas fa-history"></i>
                <span>Audit Trail</span>
            </a>
            
            <a href="../superadmin/activity_log.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'activity_log.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i>
                <span>Activity Log</span>
            </a>
        <?php endif; ?>
        
        <div style="padding: 15px 25px 5px; font-size: 12px; opacity: 0.5;">ACCOUNT</div>
        
        <a href="../profile.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
            <i class="fas fa-user"></i>
            <span>Profile</span>
        </a>
        
        <a href="../logout.php" class="menu-item">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</div>

<div class="main-content">
    <div class="top-bar">
        <div class="page-title">
            <?php 
            $page = basename($_SERVER['PHP_SELF'], '.php');
            echo ucwords(str_replace('_', ' ', $page));
            ?>
        </div>
        <div class="user-info">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['fullname'] ?? $_SESSION['username']); ?></span>
            <div class="user-avatar">
                <?php echo strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)); ?>
            </div>
        </div>
    </div>