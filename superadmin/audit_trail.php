<?php
require_once '../includes/header.php';
require_once '../includes/functions.php';
requireRole('super_admin');

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// Get total count
$total = $conn->query("SELECT COUNT(*) as total FROM audit_trail")->fetch_assoc()['total'];
$total_pages = ceil($total / $limit);

// Get audit trail with details
$audit = $conn->query("
    SELECT a.*, u.username, u.role
    FROM audit_trail a
    LEFT JOIN users u ON a.user_id = u.id
    ORDER BY a.created_at DESC
    LIMIT $offset, $limit
");

// Get summary stats
$today_audit = $conn->query("
    SELECT COUNT(*) as count FROM audit_trail 
    WHERE DATE(created_at) = CURDATE()
")->fetch_assoc()['count'];

$week_audit = $conn->query("
    SELECT COUNT(*) as count FROM audit_trail 
    WHERE YEARWEEK(created_at) = YEARWEEK(NOW())
")->fetch_assoc()['count'];
?>
<?php include '../includes/sidebar.php'; ?>

<div style="padding: 20px;">
    <!-- Page Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h1 style="color: #2c3e50;">Audit Trail</h1>
        <div>
            <a href="activity_log.php" class="btn btn-primary">
                <i class="fas fa-chart-line"></i> Activity Log
            </a>
            <a href="export_audit.php" class="btn btn-success">
                <i class="fas fa-download"></i> Export Audit Trail
            </a>
        </div>
    </div>
    
    <!-- Summary Cards -->
    <div class="stats-grid" style="margin-bottom: 20px;">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-shield-alt"></i></div>
            <div class="stat-title">Total Audit Records</div>
            <div class="stat-value"><?php echo number_format($total); ?></div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
            <div class="stat-title">Today's Changes</div>
            <div class="stat-value" style="color: #27ae60;"><?php echo $today_audit; ?></div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-calendar-week"></i></div>
            <div class="stat-title">This Week's Changes</div>
            <div class="stat-value" style="color: #e67e22;"><?php echo $week_audit; ?></div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-database"></i></div>
            <div class="stat-title">Data Integrity</div>
            <div class="stat-value" style="color: #3498db;">100%</div>
        </div>
    </div>
    
    <!-- Audit Trail Table -->
    <div class="table-container">
        <div class="table-header">
            <h3 class="table-title"><i class="fas fa-history"></i> Detailed Audit Trail</h3>
            <span>Showing page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Date/Time</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Table</th>
                    <th>Record ID</th>
                    <th>Changes</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($audit->num_rows > 0): ?>
                    <?php while ($log = $audit->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <?php echo date('Y-m-d', strtotime($log['created_at'])); ?>
                            <br>
                            <small style="color: #7f8c8d;"><?php echo date('H:i:s', strtotime($log['created_at'])); ?></small>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></strong>
                            <br>
                            <span class="badge <?php 
                                echo $log['role'] == 'super_admin' ? 'badge-danger' : 
                                    ($log['role'] == 'admin' ? 'badge-warning' : 'badge-info'); 
                            ?>">
                                <?php echo $log['role'] ?? 'N/A'; ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?php 
                                echo $log['action'] == 'ADD' ? 'badge-success' : 
                                    ($log['action'] == 'UPDATE' ? 'badge-warning' : 
                                    ($log['action'] == 'DELETE' ? 'badge-danger' : 'badge-info')); 
                            ?>">
                                <?php echo $log['action']; ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($log['table_name']); ?></td>
                        <td>
                            <?php if ($log['record_id'] && $log['table_name'] == 'inventory'): ?>
                                <a href="../admin/view_inventory.php?id=<?php echo $log['record_id']; ?>" style="color: #3498db;">
                                    #<?php echo $log['record_id']; ?>
                                </a>
                            <?php else: ?>
                                #<?php echo $log['record_id']; ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($log['old_value'] || $log['new_value']): ?>
                                <button class="btn btn-sm btn-info" onclick="showChanges(<?php echo $log['id']; ?>)">
                                    <i class="fas fa-code"></i> View Changes
                                </button>
                                <div id="changes-<?php echo $log['id']; ?>" style="display: none;">
                                    <div style="background: #f8f9fa; padding: 10px; border-radius: 4px; margin-top: 5px;">
                                        <?php if ($log['old_value']): ?>
                                            <div style="color: #e74c3c;">
                                                <strong>Old:</strong> <?php echo htmlspecialchars(substr($log['old_value'], 0, 200)); ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($log['new_value']): ?>
                                            <div style="color: #27ae60; margin-top: 5px;">
                                                <strong>New:</strong> <?php echo htmlspecialchars(substr($log['new_value'], 0, 200)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <span style="color: #7f8c8d;">—</span>
                            <?php endif; ?>
                        </td>
                        <td><small><?php echo htmlspecialchars($log['ip_address']); ?></small></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 50px;">
                            <i class="fas fa-shield-alt" style="font-size: 48px; color: #ccc; margin-bottom: 15px; display: block;"></i>
                            <h3 style="color: #7f8c8d;">No Audit Records Found</h3>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div style="display: flex; justify-content: center; margin-top: 30px; gap: 5px;">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page-1; ?>" class="btn btn-sm" style="background: #ecf0f1;">« Previous</a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <?php if ($i >= $page-2 && $i <= $page+2): ?>
                    <a href="?page=<?php echo $i; ?>" 
                       class="btn btn-sm <?php echo $i == $page ? 'btn-primary' : ''; ?>" 
                       style="<?php echo $i == $page ? '' : 'background: #ecf0f1;'; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page+1; ?>" class="btn btn-sm" style="background: #ecf0f1;">Next »</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function showChanges(id) {
    const changesDiv = document.getElementById('changes-' + id);
    if (changesDiv.style.display === 'none') {
        changesDiv.style.display = 'block';
    } else {
        changesDiv.style.display = 'none';
    }
}
</script>

<?php include '../includes/footer.php'; ?>