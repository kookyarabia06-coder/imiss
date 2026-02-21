<?php
require_once '../includes/header.php';
require_once '../includes/functions.php';
requireLogin();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// Filters
$filter_user = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$filter_date = isset($_GET['date']) ? $_GET['date'] : '';

// Build query with filters
$where = ["a.action = 'scan'"];
if ($filter_user > 0) {
    $where[] = "a.user_id = $filter_user";
}
if ($filter_date) {
    $where[] = "DATE(a.date_created) = '$filter_date'";
}
$where_clause = "WHERE " . implode(" AND ", $where);

// Get total count for pagination
$total = $conn->query("SELECT COUNT(*) as total FROM activity_log a $where_clause")->fetch_assoc()['total'];
$total_pages = ceil($total / $limit);

// Get scans with details
$scans = $conn->query("
    SELECT a.*, u.username, u.role, i.article_name, i.property_no, i.barcode_data
    FROM activity_log a
    LEFT JOIN users u ON a.user_id = u.id
    LEFT JOIN inventory i ON a.item_id = i.id
    $where_clause
    ORDER BY a.date_created DESC
    LIMIT $offset, $limit
");

// Get users for filter dropdown
$users = $conn->query("SELECT id, username, role FROM users WHERE status = 'active' ORDER BY username");

// Get scan statistics
$stats = [
    'today' => $conn->query("SELECT COUNT(*) as count FROM activity_log WHERE action = 'scan' AND DATE(date_created) = CURDATE()")->fetch_assoc()['count'],
    'week' => $conn->query("SELECT COUNT(*) as count FROM activity_log WHERE action = 'scan' AND YEARWEEK(date_created) = YEARWEEK(NOW())")->fetch_assoc()['count'],
    'month' => $conn->query("SELECT COUNT(*) as count FROM activity_log WHERE action = 'scan' AND MONTH(date_created) = MONTH(NOW())")->fetch_assoc()['count'],
    'unique_items' => $conn->query("SELECT COUNT(DISTINCT item_id) as count FROM activity_log WHERE action = 'scan' AND item_id IS NOT NULL")->fetch_assoc()['count']
];
?>
<?php include '../includes/sidebar.php'; ?>

<div style="padding: 20px;">
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h1 style="color: #2c3e50;"><i class="fas fa-history"></i> Barcode Scan History</h1>
        <div>
            <a href="index.php" class="btn btn-primary">
                <i class="fas fa-qrcode"></i> Scan New Barcode
            </a>
            <a href="export_scans.php" class="btn btn-success">
                <i class="fas fa-download"></i> Export History
            </a>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="stats-grid" style="margin-bottom: 20px; grid-template-columns: repeat(4, 1fr);">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
            <div class="stat-title">Today's Scans</div>
            <div class="stat-value"><?php echo $stats['today']; ?></div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-calendar-week"></i></div>
            <div class="stat-title">This Week</div>
            <div class="stat-value"><?php echo $stats['week']; ?></div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
            <div class="stat-title">This Month</div>
            <div class="stat-value"><?php echo $stats['month']; ?></div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-boxes"></i></div>
            <div class="stat-title">Unique Items</div>
            <div class="stat-value"><?php echo $stats['unique_items']; ?></div>
        </div>
    </div>
    
    <!-- Filter Section -->
    <div class="table-container" style="margin-bottom: 20px;">
        <form method="GET" style="display: grid; grid-template-columns: repeat(3, 1fr) auto; gap: 15px; align-items: end;">
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
                <a href="history.php" class="btn" style="background: #95a5a6; color: white;">Reset</a>
            </div>
        </form>
    </div>
    
    <!-- Scan History Table -->
    <div class="table-container">
        <div class="table-header">
            <h3 class="table-title"><i class="fas fa-list"></i> Scan Records</h3>
            <span>Showing <?php echo min($limit, $scans->num_rows); ?> of <?php echo $total; ?> scans</span>
        </div>
        
        <div style="overflow-x: auto;">
            <table style="min-width: 1200px;">
                <thead>
                    <tr>
                        <th>Date/Time</th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Item Scanned</th>
                        <th>Property No</th>
                        <th>Barcode</th>
                        <th>Location</th>
                        <th>Details</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($scans->num_rows > 0): ?>
                        <?php while ($scan = $scans->fetch_assoc()): 
                            // Get item location if available
                            $location = 'N/A';
                            if ($scan['item_id']) {
                                $loc_result = $conn->query("
                                    SELECT d.name as location_name 
                                    FROM inventory i 
                                    LEFT JOIN departments d ON i.location_id = d.id 
                                    WHERE i.id = {$scan['item_id']}
                                ");
                                if ($loc_result && $loc_row = $loc_result->fetch_assoc()) {
                                    $location = $loc_row['location_name'] ?? 'N/A';
                                }
                            }
                        ?>
                        <tr>
                            <td><?php echo date('Y-m-d H:i:s', strtotime($scan['date_created'])); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($scan['username'] ?? 'System'); ?></strong>
                            </td>
                            <td>
                                <span class="badge <?php 
                                    echo $scan['role'] == 'super_admin' ? 'badge-danger' : 
                                        ($scan['role'] == 'admin' ? 'badge-warning' : 'badge-info'); 
                                ?>">
                                    <?php echo $scan['role'] ?? 'N/A'; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($scan['item_id']): ?>
                                    <a href="../<?php echo $_SESSION['role']; ?>/view_inventory.php?id=<?php echo $scan['item_id']; ?>" style="color: #3498db; text-decoration: none;">
                                        <?php echo htmlspecialchars($scan['article_name'] ?? 'Item #' . $scan['item_id']); ?>
                                    </a>
                                <?php else: ?>
                                    <span style="color: #7f8c8d;">Unknown Item</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($scan['property_no'] ?? 'N/A'); ?></td>
                            <td>
                                <small style="font-family: 'Courier New';"><?php echo htmlspecialchars($scan['barcode_data'] ?? 'N/A'); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($location); ?></td>
                            <td>
                                <small><?php echo htmlspecialchars($scan['details'] ?? ''); ?></small>
                            </td>
                            <td>
                                <div style="display: flex; gap: 5px;">
                                    <?php if ($scan['item_id']): ?>
                                        <a href="../<?php echo $_SESSION['role']; ?>/view_inventory.php?id=<?php echo $scan['item_id']; ?>" class="btn btn-info btn-sm" title="View Item">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="index.php?barcode=<?php echo urlencode($scan['barcode_data']); ?>" class="btn btn-warning btn-sm" title="Scan Again">
                                        <i class="fas fa-qrcode"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 50px;">
                                <i class="fas fa-qrcode" style="font-size: 48px; color: #ccc; margin-bottom: 15px; display: block;"></i>
                                <h3 style="color: #7f8c8d; margin-bottom: 10px;">No Scan History Found</h3>
                                <p>No barcode scans match your filter criteria</p>
                                <a href="index.php" class="btn btn-primary btn-sm" style="margin-top: 10px;">
                                    <i class="fas fa-qrcode"></i> Scan Now
                                </a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div style="display: flex; justify-content: center; margin-top: 30px; gap: 5px;">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page-1; ?>&user_id=<?php echo $filter_user; ?>&date=<?php echo $filter_date; ?>" 
                   class="btn btn-sm" style="background: #ecf0f1;">« Previous</a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page-2); $i <= min($page+2, $total_pages); $i++): ?>
                <a href="?page=<?php echo $i; ?>&user_id=<?php echo $filter_user; ?>&date=<?php echo $filter_date; ?>" 
                   class="btn btn-sm <?php echo $i == $page ? 'btn-primary' : ''; ?>" 
                   style="<?php echo $i == $page ? '' : 'background: #ecf0f1;'; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page+1; ?>&user_id=<?php echo $filter_user; ?>&date=<?php echo $filter_date; ?>" 
                   class="btn btn-sm" style="background: #ecf0f1;">Next »</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Scan Activity Chart -->
    <div class="table-container" style="margin-top: 20px;">
        <h3 class="table-title"><i class="fas fa-chart-bar"></i> Scan Activity (Last 7 Days)</h3>
        
        <?php
        $chart_data = $conn->query("
            SELECT DATE(date_created) as scan_date, COUNT(*) as count
            FROM activity_log
            WHERE action = 'scan' AND date_created >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(date_created)
            ORDER BY scan_date
        ");
        
        $dates = [];
        $counts = [];
        $max_count = 0;
        
        while ($row = $chart_data->fetch_assoc()) {
            $dates[] = $row['scan_date'];
            $counts[] = $row['count'];
            if ($row['count'] > $max_count) $max_count = $row['count'];
        }
        ?>
        
        <div style="display: flex; align-items: flex-end; gap: 15px; height: 200px; padding: 20px 0;">
            <?php for ($i = 0; $i < count($dates); $i++): ?>
                <div style="flex: 1; text-align: center;">
                    <div style="height: <?php echo ($counts[$i] / max($max_count, 1)) * 150; ?>px; 
                                background: linear-gradient(0deg, #3498db, #2980b9); 
                                border-radius: 4px 4px 0 0; 
                                margin-bottom: 5px;
                                min-height: 5px;">
                    </div>
                    <div style="font-size: 12px;"><?php echo date('M d', strtotime($dates[$i])); ?></div>
                    <div style="font-size: 11px; color: #7f8c8d;"><?php echo $counts[$i]; ?> scans</div>
                </div>
            <?php endfor; ?>
            
            <?php if (empty($dates)): ?>
                <p style="text-align: center; width: 100%; color: #7f8c8d;">No scan data available for the last 7 days</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.table-container {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    margin-bottom: 20px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    position: relative;
}

.stat-icon {
    position: absolute;
    right: 15px;
    top: 15px;
    font-size: 40px;
    color: #3498db;
    opacity: 0.2;
}

.stat-title {
    color: #7f8c8d;
    font-size: 13px;
    text-transform: uppercase;
    margin-bottom: 5px;
}

.stat-value {
    font-size: 28px;
    font-weight: 700;
    color: #2c3e50;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th {
    background: #34495e;
    color: white;
    padding: 12px 10px;
    text-align: left;
    font-size: 13px;
}

td {
    padding: 10px;
    border-bottom: 1px solid #ecf0f1;
    font-size: 13px;
}

tr:hover td {
    background: #f8f9fa;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    font-size: 13px;
    transition: all 0.3s;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.btn-sm {
    padding: 4px 8px;
    font-size: 11px;
}

.btn-primary { background: #3498db; color: white; }
.btn-success { background: #27ae60; color: white; }
.btn-warning { background: #f39c12; color: white; }
.btn-info { background: #3498db; color: white; }

.form-control {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    width: 100%;
    font-size: 13px;
}

.form-control:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52,152,219,0.1);
}

.badge {
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
}

.badge-danger { background: #f8d7da; color: #721c24; }
.badge-warning { background: #fff3cd; color: #856404; }
.badge-info { background: #d1ecf1; color: #0c5460; }
</style>

<?php include '../includes/footer.php'; ?>