<?php
require_once '../includes/header.php';
require_once '../includes/functions.php';
requireRole('super_admin');

// Get statistics
$stats = [
    'total_items' => $conn->query("SELECT COUNT(*) as total FROM inventory")->fetch_assoc()['total'],
    'total_users' => $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'],
    'total_activities' => $conn->query("SELECT COUNT(*) as total FROM activity_log")->fetch_assoc()['total'],
    'total_audit' => $conn->query("SELECT COUNT(*) as total FROM audit_trail")->fetch_assoc()['total']
];

// Get recent activities (last 20)
$recent_activities = $conn->query("
    SELECT a.*, u.username, u.role,
           i.article_name, i.property_no
    FROM activity_log a 
    LEFT JOIN users u ON a.user_id = u.id 
    LEFT JOIN inventory i ON a.item_id = i.id
    ORDER BY a.date_created DESC 
    LIMIT 20
");

// Get activity summary by type
$activity_summary = $conn->query("
    SELECT action, COUNT(*) as count 
    FROM activity_log 
    WHERE date_created >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY action
    ORDER BY count DESC
");

// Get top users by activity
$top_users = $conn->query("
    SELECT u.username, u.role, COUNT(*) as activity_count
    FROM activity_log a
    JOIN users u ON a.user_id = u.id
    WHERE a.date_created >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY u.id, u.username, u.role
    ORDER BY activity_count DESC
    LIMIT 5
");

// Get today's activities count
$today_count = $conn->query("
    SELECT COUNT(*) as count 
    FROM activity_log 
    WHERE DATE(date_created) = CURDATE()
")->fetch_assoc()['count'];

// Get this week's activities count
$week_count = $conn->query("
    SELECT COUNT(*) as count 
    FROM activity_log 
    WHERE YEARWEEK(date_created) = YEARWEEK(NOW())
")->fetch_assoc()['count'];
?>
<?php include '../includes/sidebar.php'; ?>

<div style="padding: 20px;">
    <!-- Page Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h1 style="color: #2c3e50;">Super Admin Dashboard</h1>
        <div style="display: flex; gap: 10px;">
            <a href="audit_trail.php" class="btn btn-primary">
                <i class="fas fa-history"></i> Full Audit Trail
            </a>
            <a href="activity_log.php" class="btn btn-info">
                <i class="fas fa-chart-line"></i> Activity Log
            </a>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-boxes"></i></div>
            <div class="stat-title">Total Inventory Items</div>
            <div class="stat-value"><?php echo number_format($stats['total_items']); ?></div>
            <div class="stat-sub">Across all departments</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-title">System Users</div>
            <div class="stat-value"><?php echo $stats['total_users']; ?></div>
            <div class="stat-sub">
                <?php
                $admin_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'")->fetch_assoc()['count'];
                $user_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'")->fetch_assoc()['count'];
                echo "$admin_count Admins, $user_count Users";
                ?>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
            <div class="stat-title">Today's Activity</div>
            <div class="stat-value" style="color: #27ae60;"><?php echo $today_count; ?></div>
            <div class="stat-sub">actions performed today</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-calendar-week"></i></div>
            <div class="stat-title">This Week</div>
            <div class="stat-value" style="color: #e67e22;"><?php echo $week_count; ?></div>
            <div class="stat-sub">actions this week</div>
        </div>
    </div>
    
    <!-- Activity Summary and Top Users -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
        <!-- Activity Summary Chart -->
        <div class="table-container">
            <div class="table-header">
                <h3 class="table-title"><i class="fas fa-chart-pie"></i> Activity Summary (Last 7 Days)</h3>
            </div>
            <div>
                <?php if ($activity_summary->num_rows > 0): ?>
                    <?php while ($act = $activity_summary->fetch_assoc()): ?>
                        <div style="margin-bottom: 15px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                <span>
                                    <span class="badge <?php 
                                        echo $act['action'] == 'add' ? 'badge-success' : 
                                            ($act['action'] == 'edit' ? 'badge-warning' : 
                                            ($act['action'] == 'delete' ? 'badge-danger' : 'badge-info')); 
                                    ?>" style="text-transform: capitalize;">
                                        <?php echo $act['action']; ?>
                                    </span>
                                </span>
                                <span><strong><?php echo $act['count']; ?></strong> actions</span>
                            </div>
                            <div style="background: #ecf0f1; height: 8px; border-radius: 4px;">
                                <?php
                                $total = $conn->query("SELECT COUNT(*) as total FROM activity_log WHERE date_created >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['total'];
                                $width = $total > 0 ? ($act['count'] / $total * 100) : 0;
                                ?>
                                <div style="width: <?php echo $width; ?>%; height: 8px; background: <?php 
                                    echo $act['action'] == 'add' ? '#27ae60' : 
                                        ($act['action'] == 'edit' ? '#f39c12' : 
                                        ($act['action'] == 'delete' ? '#e74c3c' : '#3498db')); 
                                ?>; border-radius: 4px;"></div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #7f8c8d; padding: 20px;">No activity in the last 7 days</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Top Active Users -->
        <div class="table-container">
            <div class="table-header">
                <h3 class="table-title"><i class="fas fa-trophy"></i> Most Active Users (Last 7 Days)</h3>
            </div>
            <div>
                <?php if ($top_users->num_rows > 0): ?>
                    <?php $rank = 1; ?>
                    <?php while ($user = $top_users->fetch_assoc()): ?>
                        <div style="display: flex; align-items: center; padding: 10px; border-bottom: 1px solid #ecf0f1;">
                            <div style="width: 30px; height: 30px; background: <?php 
                                echo $rank == 1 ? '#f1c40f' : ($rank == 2 ? '#bdc3c7' : ($rank == 3 ? '#e67e22' : '#ecf0f1')); 
                            ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px; font-weight: bold;">
                                <?php echo $rank; ?>
                            </div>
                            <div style="flex: 1;">
                                <div><strong><?php echo htmlspecialchars($user['username']); ?></strong></div>
                                <div>
                                    <span class="badge <?php 
                                        echo $user['role'] == 'super_admin' ? 'badge-danger' : 
                                            ($user['role'] == 'admin' ? 'badge-warning' : 'badge-info'); 
                                    ?>">
                                        <?php echo $user['role']; ?>
                                    </span>
                                </div>
                            </div>
                            <div style="font-size: 20px; font-weight: bold; color: #2c3e50;">
                                <?php echo $user['activity_count']; ?>
                            </div>
                        </div>
                        <?php $rank++; ?>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #7f8c8d; padding: 20px;">No user activity in the last 7 days</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Recent Activity Log -->
    <div class="table-container">
        <div class="table-header">
            <h3 class="table-title"><i class="fas fa-history"></i> Recent System Activity</h3>
            <div>
                <a href="activity_log.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-list"></i> View Full Log
                </a>
                <a href="audit_trail.php" class="btn btn-info btn-sm">
                    <i class="fas fa-shield-alt"></i> Audit Trail
                </a>
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Time</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Item</th>
                    <th>Details</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($recent_activities->num_rows > 0): ?>
                    <?php while ($act = $recent_activities->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo date('M d, H:i:s', strtotime($act['date_created'])); ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($act['username'] ?? 'System'); ?></strong>
                            <br>
                            <small><?php echo $act['role'] ?? 'N/A'; ?></small>
                        </td>
                        <td>
                            <span class="badge <?php 
                                echo $act['action'] == 'add' ? 'badge-success' : 
                                    ($act['action'] == 'edit' ? 'badge-warning' : 
                                    ($act['action'] == 'delete' ? 'badge-danger' : 'badge-info')); 
                            ?>" style="text-transform: capitalize;">
                                <?php echo $act['action']; ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($act['item_id']): ?>
                                <a href="../admin/view_inventory.php?id=<?php echo $act['item_id']; ?>" style="color: #3498db; text-decoration: none;">
                                    <?php echo htmlspecialchars($act['article_name'] ?? 'Item #' . $act['item_id']); ?>
                                </a>
                                <br>
                                <small><?php echo htmlspecialchars($act['property_no'] ?? ''); ?></small>
                            <?php else: ?>
                                <span style="color: #7f8c8d;">System</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($act['details'] ?? ''); ?></td>
                        <td><small><?php echo $_SERVER['REMOTE_ADDR']; ?></small></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 30px;">
                            <i class="fas fa-inbox" style="font-size: 48px; color: #ccc; margin-bottom: 10px; display: block;"></i>
                            No activity recorded yet
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Quick System Overview -->
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
        <!-- Database Stats -->
        <div class="table-container">
            <h4 style="margin-bottom: 15px;"><i class="fas fa-database"></i> Database Overview</h4>
            <?php
            $tables = [
                'inventory' => 'Inventory Items',
                'users' => 'Users',
                'activity_log' => 'Activity Logs',
                'audit_trail' => 'Audit Trails',
                'departments' => 'Departments',
                'equipment' => 'Equipment'
            ];
            ?>
            <table style="width: 100%;">
                <?php foreach ($tables as $table => $label): ?>
                <?php
                $count = $conn->query("SELECT COUNT(*) as total FROM $table")->fetch_assoc()['total'];
                ?>
                <tr>
                    <td><?php echo $label; ?></td>
                    <td><strong><?php echo number_format($count); ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        
        <!-- Recent Users -->
        <div class="table-container">
            <h4 style="margin-bottom: 15px;"><i class="fas fa-user-plus"></i> Recently Joined Users</h4>
            <?php
            $recent_users = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
            ?>
            <?php if ($recent_users->num_rows > 0): ?>
                <?php while ($user = $recent_users->fetch_assoc()): ?>
                <div style="padding: 8px 0; border-bottom: 1px solid #ecf0f1;">
                    <div><strong><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></strong></div>
                    <div style="display: flex; justify-content: space-between;">
                        <span class="badge <?php 
                            echo $user['role'] == 'super_admin' ? 'badge-danger' : 
                                ($user['role'] == 'admin' ? 'badge-warning' : 'badge-info'); 
                        ?>"><?php echo $user['role']; ?></span>
                        <small><?php echo formatDate($user['created_at'], 'M d, Y'); ?></small>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color: #7f8c8d;">No users found</p>
            <?php endif; ?>
        </div>
        
        <!-- System Health -->
        <div class="table-container">
            <h4 style="margin-bottom: 15px;"><i class="fas fa-heartbeat"></i> System Health</h4>
            <div style="margin-bottom: 15px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                    <span>Database</span>
                    <span class="badge badge-success">Connected</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                    <span>Session Status</span>
                    <span class="badge badge-success">Active</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                    <span>Last Backup</span>
                    <span class="badge badge-warning">Not Available</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                    <span>PHP Version</span>
                    <span><?php echo phpversion(); ?></span>
                </div>
            </div>
            <div style="background: #f8f9fa; padding: 15px; border-radius: 6px;">
                <i class="fas fa-info-circle"></i>
                System is running normally
            </div>
        </div>
    </div>
</div>

<style>
.stat-card {
    position: relative;
    overflow: hidden;
}
.stat-card:hover .stat-icon {
    transform: scale(1.1);
    opacity: 0.3;
}
.stat-icon {
    position: absolute;
    right: 20px;
    top: 20px;
    font-size: 48px;
    color: #3498db;
    opacity: 0.15;
    transition: all 0.3s;
}
.badge {
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 11px;
}
</style>

<?php include '../includes/footer.php'; ?>