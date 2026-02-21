<?php
require_once '../includes/header.php';
require_once '../includes/functions.php';
requireRole('super_admin');

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// Filters
$filter_user = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$filter_action = isset($_GET['action']) ? $_GET['action'] : '';
$filter_date = isset($_GET['date']) ? $_GET['date'] : '';

// Build query with filters
$where = [];
if ($filter_user > 0) {
    $where[] = "a.user_id = $filter_user";
}
if ($filter_action) {
    $action = mysqli_real_escape_string($conn, $filter_action);
    $where[] = "a.action = '$action'";
}
if ($filter_date) {
    $where[] = "DATE(a.date_created) = '$filter_date'";
}

$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Get total count for pagination
$total = $conn->query("SELECT COUNT(*) as total FROM activity_log a $where_clause")->fetch_assoc()['total'];
$total_pages = ceil($total / $limit);

// Get activities with details
$activities = $conn->query("
    SELECT a.*, u.username, u.role,
           i.article_name, i.property_no
    FROM activity_log a 
    LEFT JOIN users u ON a.user_id = u.id 
    LEFT JOIN inventory i ON a.item_id = i.id
    $where_clause
    ORDER BY a.date_created DESC 
    LIMIT $offset, $limit
");

// Get users for filter dropdown
$users = $conn->query("SELECT id, username, role FROM users ORDER BY username");

// Get action types for filter
$actions = $conn->query("SELECT DISTINCT action FROM activity_log ORDER BY action");

// Get summary stats
$summary = [
    'total' => $total,
    'today' => $conn->query("SELECT COUNT(*) as count FROM activity_log WHERE DATE(date_created) = CURDATE()")->fetch_assoc()['count'],
    'week' => $conn->query("SELECT COUNT(*) as count FROM activity_log WHERE YEARWEEK(date_created) = YEARWEEK(NOW())")->fetch_assoc()['count'],
    'month' => $conn->query("SELECT COUNT(*) as count FROM activity_log WHERE MONTH(date_created) = MONTH(NOW()) AND YEAR(date_created) = YEAR(NOW())")->fetch_assoc()['count']
];
?>
<?php include '../includes/sidebar.php'; ?>

<div style="padding: 20px;">
    <!-- Page Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h1 style="color: #2c3e50;">Activity Log</h1>
        <div>
            <a href="audit_trail.php" class="btn btn-primary">
                <i class="fas fa-shield-alt"></i> View Audit Trail
            </a>
            <a href="export_activity.php" class="btn btn-success">
                <i class="fas fa-download"></i> Export Log
            </a>
        </div>
    </div>
    
    <!-- Summary Cards -->
    <div class="stats-grid" style="margin-bottom: 20px;">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-list"></i></div>
            <div class="stat-title">Total Activities</div>
            <div class="stat-value"><?php echo number_format($summary['total']); ?></div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
            <div class="stat-title">Today</div>
            <div class="stat-value" style="color: #27ae60;"><?php echo $summary['today']; ?></div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-calendar-week"></i></div>
            <div class="stat-title">This Week</div>
            <div class="stat-value" style="color: #e67e22;"><?php echo $summary['week']; ?></div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
            <div class="stat-title">This Month</div>
            <div class="stat-value" style="color: #3498db;"><?php echo $summary['month']; ?></div>
        </div>
    </div>
    
    <!-- Filter Section -->
    <div class="table-container" style="margin-bottom: 20px;">
        <form method="GET" style="display: grid; grid-template-columns: repeat(4, 1fr) auto; gap: 15px; align-items: end;">
            <div>
                <label style="display: block; margin-bottom: 5px; font-size: 13px;">Filter by User</label>
                <select name="user_id" class="form-control">
                    <option value="">All Users</option>
                    <?php while ($user = $users->fetch_assoc()): ?>
                        <option value="<?php echo $user['id']; ?>" <?php echo $filter_user == $user['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['username']); ?> (<?php echo $user['role']; ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 5px; font-size: 13px;">Filter by Action</label>
                <select name="action" class="form-control">
                    <option value="">All Actions</option>
                    <?php while ($act = $actions->fetch_assoc()): ?>
                        <option value="<?php echo $act['action']; ?>" <?php echo $filter_action == $act['action'] ? 'selected' : ''; ?>>
                            <?php echo ucfirst($act['action']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 5px; font-size: 13px;">Filter by Date</label>
                <input type="date" name="date" class="form-control" value="<?php echo $filter_date; ?>">
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 5px; font-size: 13px;">Items per page</label>
                <select name="limit" class="form-control">
                    <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                    <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
                    <option value="200" <?php echo $limit == 200 ? 'selected' : ''; ?>>200</option>
                </select>
            </div>
            
            <div>
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="activity_log.php" class="btn" style="background: #95a5a6; color: white;">Reset</a>
            </div>
        </form>
    </div>
    
    <!-- Activity Table -->
    <div class="table-container">
        <div class="table-header">
            <h3 class="table-title"><i class="fas fa-history"></i> System Activity Log</h3>
            <span>Showing <?php echo min($limit, $activities->num_rows); ?> of <?php echo $total; ?> entries</span>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Item</th>
                    <th>Details</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($activities->num_rows > 0): ?>
                    <?php while ($act = $activities->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <?php echo date('Y-m-d', strtotime($act['date_created'])); ?>
                            <br>
                            <small style="color: #7f8c8d;"><?php echo date('H:i:s', strtotime($act['date_created'])); ?></small>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($act['username'] ?? 'System'); ?></strong>
                            <br>
                            <span class="badge <?php 
                                echo $act['role'] == 'super_admin' ? 'badge-danger' : 
                                    ($act['role'] == 'admin' ? 'badge-warning' : 
                                    ($act['role'] == 'user' ? 'badge-info' : 'badge-secondary')); 
                            ?>">
                                <?php echo $act['role'] ?? 'N/A'; ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?php 
                                echo $act['action'] == 'add' ? 'badge-success' : 
                                    ($act['action'] == 'edit' || $act['action'] == 'update' ? 'badge-warning' : 
                                    ($act['action'] == 'delete' ? 'badge-danger' : 
                                    ($act['action'] == 'login' ? 'badge-info' : 
                                    ($act['action'] == 'logout' ? 'badge-secondary' : 'badge-primary')))); 
                            ?>" style="text-transform: capitalize; padding: 5px 12px;">
                                <i class="fas <?php 
                                    echo $act['action'] == 'add' ? 'fa-plus' : 
                                        ($act['action'] == 'edit' || $act['action'] == 'update' ? 'fa-edit' : 
                                        ($act['action'] == 'delete' ? 'fa-trash' : 
                                        ($act['action'] == 'login' ? 'fa-sign-in-alt' : 
                                        ($act['action'] == 'logout' ? 'fa-sign-out-alt' : 'fa-circle')))); 
                                ?>"></i>
                                <?php echo ucfirst($act['action']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($act['item_id']): ?>
                                <a href="../admin/view_inventory.php?id=<?php echo $act['item_id']; ?>" style="color: #3498db; text-decoration: none; font-weight: 500;">
                                    <?php echo htmlspecialchars($act['article_name'] ?? 'Item #' . $act['item_id']); ?>
                                </a>
                                <?php if ($act['property_no']): ?>
                                    <br>
                                    <small style="color: #7f8c8d;"><?php echo htmlspecialchars($act['property_no']); ?></small>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color: #7f8c8d;">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($act['details']): ?>
                                <span title="<?php echo htmlspecialchars($act['details']); ?>">
                                    <?php echo htmlspecialchars(substr($act['details'], 0, 50)) . (strlen($act['details']) > 50 ? '...' : ''); ?>
                                </span>
                            <?php else: ?>
                                <span style="color: #7f8c8d;">—</span>
                            <?php endif; ?>
                        </td>
                        <td><small><?php echo htmlspecialchars($act['ip_address'] ?? $_SERVER['REMOTE_ADDR']); ?></small></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 50px;">
                            <i class="fas fa-inbox" style="font-size: 48px; color: #ccc; margin-bottom: 15px; display: block;"></i>
                            <h3 style="color: #7f8c8d; margin-bottom: 10px;">No Activity Found</h3>
                            <p>No activity logs match your filter criteria</p>
                            <?php if ($filter_user || $filter_action || $filter_date): ?>
                                <a href="activity_log.php" class="btn btn-primary btn-sm" style="margin-top: 10px;">Clear Filters</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div style="display: flex; justify-content: center; margin-top: 30px; gap: 5px;">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page-1; ?>&user_id=<?php echo $filter_user; ?>&action=<?php echo urlencode($filter_action); ?>&date=<?php echo $filter_date; ?>" 
                   class="btn btn-sm" style="background: #ecf0f1;">« Previous</a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page-2); $i <= min($page+2, $total_pages); $i++): ?>
                <a href="?page=<?php echo $i; ?>&user_id=<?php echo $filter_user; ?>&action=<?php echo urlencode($filter_action); ?>&date=<?php echo $filter_date; ?>" 
                   class="btn btn-sm <?php echo $i == $page ? 'btn-primary' : ''; ?>" 
                   style="<?php echo $i == $page ? '' : 'background: #ecf0f1;'; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page+1; ?>&user_id=<?php echo $filter_user; ?>&action=<?php echo urlencode($filter_action); ?>&date=<?php echo $filter_date; ?>" 
                   class="btn btn-sm" style="background: #ecf0f1;">Next »</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Activity Charts -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
        <!-- Activity by Day -->
        <div class="table-container">
            <h4 style="margin-bottom: 15px;"><i class="fas fa-chart-bar"></i> Activity by Day (Last 7 Days)</h4>
            <?php
            $daily = $conn->query("
                SELECT DATE(date_created) as day, COUNT(*) as count
                FROM activity_log
                WHERE date_created >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY DATE(date_created)
                ORDER BY day DESC
            ");
            ?>
            <div style="height: 200px; display: flex; align-items: flex-end; gap: 10px; padding: 10px 0;">
                <?php 
                $max_count = 0;
                $daily_data = [];
                while ($d = $daily->fetch_assoc()) {
                    $daily_data[] = $d;
                    if ($d['count'] > $max_count) $max_count = $d['count'];
                }
                
                foreach ($daily_data as $d): 
                    $height = $max_count > 0 ? ($d['count'] / $max_count * 180) : 0;
                ?>
                    <div style="flex: 1; text-align: center;">
                        <div style="height: <?php echo $height; ?>px; background: #3498db; border-radius: 4px 4px 0 0; margin-bottom: 5px;"></div>
                        <div style="font-size: 12px;"><?php echo date('M d', strtotime($d['day'])); ?></div>
                        <div style="font-size: 11px; color: #7f8c8d;"><?php echo $d['count']; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Activity by User -->
        <div class="table-container">
            <h4 style="margin-bottom: 15px;"><i class="fas fa-users"></i> Activity by User (Last 7 Days)</h4>
            <?php
            $user_activity = $conn->query("
                SELECT u.username, COUNT(*) as count
                FROM activity_log a
                JOIN users u ON a.user_id = u.id
                WHERE a.date_created >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY u.id, u.username
                ORDER BY count DESC
                LIMIT 5
            ");
            ?>
            <?php if ($user_activity->num_rows > 0): ?>
                <?php while ($ua = $user_activity->fetch_assoc()): ?>
                    <div style="margin-bottom: 10px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 3px;">
                            <span><?php echo htmlspecialchars($ua['username']); ?></span>
                            <span><?php echo $ua['count']; ?> activities</span>
                        </div>
                        <div style="background: #ecf0f1; height: 6px; border-radius: 3px;">
                            <div style="width: <?php echo ($ua['count'] / $user_activity->num_rows * 100); ?>%; height: 6px; background: #e67e22; border-radius: 3px;"></div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color: #7f8c8d;">No user activity in the last 7 days</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.badge {
    font-size: 11px;
    padding: 4px 8px;
}
.stat-card {
    padding: 20px;
}
</style>

<?php include '../includes/footer.php'; ?>