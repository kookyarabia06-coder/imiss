<?php
require_once '../includes/header.php';
require_once '../includes/functions.php';
requireRole('admin');

// Get statistics
$stats = getInventoryStats();

// Get recent scans
$recent_scans = $conn->query("
    SELECT a.*, u.username, i.article_name, i.property_no
    FROM activity_log a
    LEFT JOIN users u ON a.user_id = u.id
    LEFT JOIN inventory i ON a.item_id = i.id
    WHERE a.action = 'scan'
    ORDER BY a.date_created DESC
    LIMIT 10
");
?>
<?php include '../includes/sidebar.php'; ?>

<div style="padding: 20px;">
    <!-- Page Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h1 style="color: #2c3e50;">Admin Dashboard</h1>
        <div style="display: flex; gap: 10px;">
            <a href="../scan/index.php" class="btn btn-warning">
                <i class="fas fa-qrcode"></i> Scan Barcode
            </a>
            <a href="add_inventory.php" class="btn btn-success">
                <i class="fas fa-plus"></i> Add Item
            </a>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-boxes"></i></div>
            <div class="stat-title">Total Items</div>
            <div class="stat-value"><?php echo number_format($stats['total_items']); ?></div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-cubes"></i></div>
            <div class="stat-title">Total Quantity</div>
            <div class="stat-value"><?php echo number_format($stats['total_quantity']); ?></div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
            <div class="stat-title">Total Value</div>
            <div class="stat-value"><?php echo formatCurrency($stats['total_value']); ?></div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-qrcode"></i></div>
            <div class="stat-title">Barcodes</div>
            <div class="stat-value">
                <?php
                $barcodes = $conn->query("SELECT COUNT(*) as count FROM inventory WHERE barcode_data IS NOT NULL")->fetch_assoc()['count'];
                echo $barcodes;
                ?>
            </div>
        </div>
    </div>
    
    <!-- Recent Scans Section -->
    <div class="table-container">
        <div class="table-header">
            <h3 class="table-title"><i class="fas fa-qrcode"></i> Recent Barcode Scans</h3>
            <a href="../scan/history.php" class="btn btn-primary btn-sm">View All</a>
        </div>
        
        <?php if ($recent_scans && $recent_scans->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Date/Time</th>
                        <th>User</th>
                        <th>Item Scanned</th>
                        <th>Property No</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($scan = $recent_scans->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo date('M d, H:i A', strtotime($scan['date_created'])); ?></td>
                        <td><?php echo htmlspecialchars($scan['username'] ?? 'Unknown'); ?></td>
                        <td>
                            <?php if ($scan['item_id']): ?>
                                <a href="../admin/view_inventory.php?id=<?php echo $scan['item_id']; ?>">
                                    <?php echo htmlspecialchars($scan['article_name'] ?? 'Item #' . $scan['item_id']); ?>
                                </a>
                            <?php else: ?>
                                Unknown Item
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($scan['property_no'] ?? 'N/A'); ?></td>
                        <td>
                            <a href="../scan/index.php?barcode=<?php echo urlencode($scan['details']); ?>" class="btn btn-info btn-sm">
                                <i class="fas fa-sync"></i> Rescan
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center; color: #7f8c8d; padding: 40px;">
                <i class="fas fa-qrcode" style="font-size: 48px; display: block; margin-bottom: 15px; opacity: 0.5;"></i>
                No barcode scans yet<br>
                <a href="../scan/index.php" class="btn btn-primary btn-sm" style="margin-top: 10px;">Start Scanning</a>
            </p>
        <?php endif; ?>
    </div>
    
    <!-- Quick Actions -->
    <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
        <a href="../scan/index.php" style="text-decoration: none;">
            <div class="stat-card" style="text-align: center; background: linear-gradient(135deg, #f39c12, #e67e22); color: white;">
                <i class="fas fa-qrcode" style="font-size: 40px; margin-bottom: 10px;"></i>
                <h3>Scan Barcode</h3>
                <p>Quick scan items</p>
            </div>
        </a>
        
        <a href="add_inventory.php" style="text-decoration: none;">
            <div class="stat-card" style="text-align: center; background: linear-gradient(135deg, #27ae60, #2ecc71); color: white;">
                <i class="fas fa-plus-circle" style="font-size: 40px; margin-bottom: 10px;"></i>
                <h3>Add Inventory</h3>
                <p>Add new items</p>
            </div>
        </a>
        
        <a href="inventory.php" style="text-decoration: none;">
            <div class="stat-card" style="text-align: center; background: linear-gradient(135deg, #3498db, #2980b9); color: white;">
                <i class="fas fa-list" style="font-size: 40px; margin-bottom: 10px;"></i>
                <h3>View All</h3>
                <p>Manage inventory</p>
            </div>
        </a>
    </div>
    
    <!-- Rest of your existing dashboard content -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
        <!-- Recent Inventory -->
        <div class="table-container">
            <div class="table-header">
                <h3 class="table-title">Recent Inventory</h3>
                <a href="inventory.php" class="btn btn-primary btn-sm">View All</a>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Property No</th>
                        <th>Article</th>
                        <th>Quantity</th>
                        <th>Barcode</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($stats['recent'], 0, 5) as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['property_no']); ?></td>
                        <td><?php echo htmlspecialchars($item['article_name'] ?: $item['description']); ?></td>
                        <td><?php echo $item['qty_physical_count']; ?></td>
                        <td><small><?php echo htmlspecialchars($item['barcode_data']); ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Recent Activity -->
        <div class="table-container">
            <div class="table-header">
                <h3 class="table-title">Recent Activity</h3>
            </div>
            
            <ul class="activity-list">
                <?php foreach ($stats['recent_activities'] as $activity): ?>
                <li class="activity-item">
                    <div class="activity-dot"></div>
                    <div>
                        <strong><?php echo htmlspecialchars($activity['username'] ?? 'System'); ?></strong>
                        <?php echo htmlspecialchars($activity['action']); ?>
                    </div>
                    <div class="activity-time"><?php echo formatDate($activity['date_created'], 'H:i'); ?></div>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>