<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/header.php';
require_once '../includes/functions.php';
requireRole('user');

// Get statistics
$stats = [
    'total_semi' => $conn->query("SELECT COUNT(*) as count FROM inventory WHERE type_equipment = 'semi'")->fetch_assoc()['count'],
    'total_ppe' => $conn->query("SELECT COUNT(*) as count FROM inventory WHERE type_equipment = 'ppe'")->fetch_assoc()['count'],
    'total_value' => $conn->query("SELECT SUM(qty_physical_count * unit_value) as total FROM inventory")->fetch_assoc()['total'] ?? 0,
    'recent' => $conn->query("
        SELECT i.*, d.name as location_name, e.name as equipment_name
        FROM inventory i
        LEFT JOIN departments d ON i.location_id = d.id
        LEFT JOIN equipment e ON i.equipment_id = e.id
        ORDER BY i.date_added DESC 
        LIMIT 10
    ")
];

// Get low stock alerts
$low_stock = $conn->query("
    SELECT * FROM inventory 
    WHERE qty_physical_count <= 5 AND qty_physical_count > 0
    ORDER BY qty_physical_count ASC
    LIMIT 5
");

// Get recent scans
$recent_scans = $conn->query("
    SELECT a.*, u.username, i.article_name, i.property_no
    FROM activity_log a
    LEFT JOIN users u ON a.user_id = u.id
    LEFT JOIN inventory i ON a.item_id = i.id
    WHERE a.action = 'scan'
    ORDER BY a.date_created DESC
    LIMIT 5
");
?>
<?php include '../includes/sidebar.php'; ?>

<div style="padding: 20px;">
    <!-- Welcome Header -->
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 12px; margin-bottom: 30px; color: white;">
        <h1 style="font-size: 28px; margin-bottom: 10px;">Welcome back, <?php echo htmlspecialchars($_SESSION['fullname'] ?? $_SESSION['username']); ?>!</h1>
        <p style="opacity: 0.9;">Manage your inventory items efficiently with our quick add and barcode scanning system.</p>
    </div>
    
    <!-- Quick Action Cards - Now with Scan Barcode -->
    <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 20px; margin-bottom: 30px;">
        <a href="semi_expendable.php" style="text-decoration: none;">
            <div class="stat-card" style="text-align: center; padding: 25px; background: linear-gradient(135deg, #3498db, #2980b9); color: white;">
                <i class="fas fa-box" style="font-size: 40px; margin-bottom: 15px;"></i>
                <h3>Semi-expendable</h3>
                <p style="font-size: 24px; font-weight: bold;"><?php echo $stats['total_semi']; ?></p>
                <span class="btn btn-light btn-sm" style="margin-top: 10px;">Quick Add</span>
            </div>
        </a>
        
        <a href="ppe_equipment.php" style="text-decoration: none;">
            <div class="stat-card" style="text-align: center; padding: 25px; background: linear-gradient(135deg, #e74c3c, #c0392b); color: white;">
                <i class="fas fa-building" style="font-size: 40px; margin-bottom: 15px;"></i>
                <h3>PPE (50K Above)</h3>
                <p style="font-size: 24px; font-weight: bold;"><?php echo $stats['total_ppe']; ?></p>
                <span class="btn btn-light btn-sm" style="margin-top: 10px;">Quick Add</span>
            </div>
        </a>
        
        <a href="../scan/index.php" style="text-decoration: none;">
            <div class="stat-card" style="text-align: center; padding: 25px; background: linear-gradient(135deg, #f39c12, #e67e22); color: white;">
                <i class="fas fa-qrcode" style="font-size: 40px; margin-bottom: 15px;"></i>
                <h3>Scan Barcode</h3>
                <p style="font-size: 24px; font-weight: bold;">Quick Scan</p>
                <span class="btn btn-light btn-sm" style="margin-top: 10px;">Scan Now</span>
            </div>
        </a>
        
        <a href="bulk_add.php" style="text-decoration: none;">
            <div class="stat-card" style="text-align: center; padding: 25px; background: linear-gradient(135deg, #9b59b6, #8e44ad); color: white;">
                <i class="fas fa-layer-group" style="font-size: 40px; margin-bottom: 15px;"></i>
                <h3>Bulk Add</h3>
                <p style="font-size: 24px; font-weight: bold;">Multiple</p>
                <span class="btn btn-light btn-sm" style="margin-top: 10px;">Add Multiple</span>
            </div>
        </a>
        
        <a href="print_labels.php" style="text-decoration: none;">
            <div class="stat-card" style="text-align: center; padding: 25px; background: linear-gradient(135deg, #2ecc71, #27ae60); color: white;">
                <i class="fas fa-print" style="font-size: 40px; margin-bottom: 15px;"></i>
                <h3>Print Labels</h3>
                <p style="font-size: 24px; font-weight: bold;">Barcodes</p>
                <span class="btn btn-light btn-sm" style="margin-top: 10px;">Print Now</span>
            </div>
        </a>
    </div>
    
    <!-- Main Content Grid -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 30px;">
        <!-- Recent Items -->
        <div class="table-container">
            <div class="table-header">
                <h3 class="table-title"><i class="fas fa-clock"></i> Recent Additions</h3>
                <a href="my_inventory.php" class="btn btn-primary btn-sm">View All</a>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Property No</th>
                        <th>Article</th>
                        <th>Type</th>
                        <th>Location</th>
                        <th>Quantity</th>
                        <th>Barcode</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($item = $stats['recent']->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['property_no']); ?></td>
                        <td><?php echo htmlspecialchars($item['article_name']); ?></td>
                        <td>
                            <span class="badge <?php echo $item['type_equipment'] == 'semi' ? 'badge-info' : 'badge-warning'; ?>">
                                <?php echo $item['type_equipment'] == 'semi' ? 'Semi' : 'PPE'; ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($item['location_name'] ?? 'N/A'); ?></td>
                        <td><?php echo $item['qty_physical_count']; ?></td>
                        <td><small><?php echo htmlspecialchars($item['barcode_data']); ?></small></td>
                        <td>
                            <div style="display: flex; gap: 5px;">
                                <a href="view_inventory.php?id=<?php echo $item['id']; ?>" class="btn btn-info btn-sm" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <button onclick="scanBarcode('<?php echo $item['barcode_data']; ?>')" class="btn btn-warning btn-sm" title="Scan Barcode">
                                    <i class="fas fa-qrcode"></i>
                                </button>
                                <button onclick="printBarcode('<?php echo $item['barcode_data']; ?>')" class="btn btn-success btn-sm" title="Print Barcode">
                                    <i class="fas fa-print"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Right Column - Alerts and Recent Scans -->
        <div>
            <!-- Low Stock Alerts -->
            <div class="table-container" style="margin-bottom: 20px;">
                <h3 class="table-title"><i class="fas fa-exclamation-triangle" style="color: #e74c3c;"></i> Low Stock Alerts</h3>
                
                <?php if ($low_stock->num_rows > 0): ?>
                    <?php while ($item = $low_stock->fetch_assoc()): ?>
                    <div style="background: #fef5f5; padding: 15px; border-radius: 6px; margin-bottom: 10px; border-left: 4px solid #e74c3c;">
                        <div style="display: flex; justify-content: space-between;">
                            <strong><?php echo htmlspecialchars($item['article_name']); ?></strong>
                            <span class="badge badge-danger">Low Stock</span>
                        </div>
                        <div style="font-size: 13px; color: #7f8c8d; margin-top: 5px;">
                            Property: <?php echo htmlspecialchars($item['property_no']); ?><br>
                            Quantity: <strong style="color: #e74c3c;"><?php echo $item['qty_physical_count']; ?></strong>
                            <button onclick="scanBarcode('<?php echo $item['barcode_data']; ?>')" class="btn btn-warning btn-sm" style="float: right;">
                                <i class="fas fa-qrcode"></i> Scan
                            </button>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #7f8c8d; padding: 20px;">No low stock items</p>
                <?php endif; ?>
            </div>
            
            <!-- Recent Scans -->
            <div class="table-container">
                <h3 class="table-title"><i class="fas fa-history"></i> Recent Scans</h3>
                
                <?php if ($recent_scans && $recent_scans->num_rows > 0): ?>
                    <ul class="activity-list">
                        <?php while ($scan = $recent_scans->fetch_assoc()): ?>
                        <li class="activity-item">
                            <div class="activity-dot" style="background: #f39c12;"></div>
                            <div class="activity-content">
                                <div class="activity-title">
                                    <strong><?php echo htmlspecialchars($scan['username'] ?? 'User'); ?></strong>
                                    scanned 
                                    <?php if ($scan['item_id']): ?>
                                        <a href="view_inventory.php?id=<?php echo $scan['item_id']; ?>" style="color: #3498db;">
                                            <?php echo htmlspecialchars($scan['article_name'] ?? 'Item'); ?>
                                        </a>
                                    <?php else: ?>
                                        an item
                                    <?php endif; ?>
                                </div>
                                <div class="activity-time">
                                    <?php echo date('M d, H:i A', strtotime($scan['date_created'])); ?>
                                </div>
                            </div>
                        </li>
                        <?php endwhile; ?>
                    </ul>
                    <div style="text-align: center; margin-top: 15px;">
                        <a href="../scan/history.php" class="btn btn-primary btn-sm">View All Scan History</a>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; color: #7f8c8d; padding: 20px;">
                        <i class="fas fa-qrcode" style="font-size: 36px; display: block; margin-bottom: 10px; opacity: 0.5;"></i>
                        No scans yet<br>
                        <a href="../scan/index.php" class="btn btn-primary btn-sm" style="margin-top: 10px;">Scan Now</a>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr);">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-boxes"></i></div>
            <div class="stat-title">Total Items</div>
            <div class="stat-value"><?php echo $stats['total_semi'] + $stats['total_ppe']; ?></div>
            <div class="stat-sub">Semi: <?php echo $stats['total_semi']; ?> | PPE: <?php echo $stats['total_ppe']; ?></div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
            <div class="stat-title">Total Value</div>
            <div class="stat-value">₱<?php echo number_format($stats['total_value'], 2); ?></div>
            <div class="stat-sub">Combined inventory value</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-tag"></i></div>
            <div class="stat-title">Categories</div>
            <div class="stat-value">
                <?php
                $cats = $conn->query("SELECT COUNT(DISTINCT category) as count FROM inventory")->fetch_assoc()['count'];
                echo $cats;
                ?>
            </div>
            <div class="stat-sub">Different categories</div>
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
            <div class="stat-sub">Items with barcodes</div>
        </div>
    </div>
</div>

<script>
function scanBarcode(barcodeData) {
    // Redirect to scan page with barcode pre-filled
    window.location.href = '../scan/index.php?barcode=' + encodeURIComponent(barcodeData);
}

function printBarcode(barcodeData) {
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Print Barcode</title>
            <style>
                body { 
                    display: flex; 
                    justify-content: center; 
                    align-items: center; 
                    height: 100vh; 
                    font-family: Arial, sans-serif;
                    margin: 0;
                    padding: 20px;
                }
                .barcode-container {
                    text-align: center;
                    padding: 30px;
                    border: 2px solid #3498db;
                    border-radius: 8px;
                    background: white;
                }
                .barcode {
                    font-size: 32px;
                    letter-spacing: 3px;
                    margin: 20px 0;
                    font-family: 'Courier New', monospace;
                }
            </style>
        </head>
        <body>
            <div class="barcode-container">
                <h3>Inventory Item</h3>
                <div class="barcode">${barcodeData}</div>
                <p>Scan this barcode to view item details</p>
            </div>
            <script>
                window.onload = function() { 
                    window.print(); 
                    window.close();
                }
            <\/script>
        </body>
        </html>
    `);
}

// Quick scan shortcut (Ctrl+Shift+S)
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.shiftKey && e.key === 'S') {
        e.preventDefault();
        window.location.href = '../scan/index.php';
    }
});
</script>

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
    transition: transform 0.3s;
    text-decoration: none;
    color: inherit;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
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
    letter-spacing: 0.5px;
    margin-bottom: 5px;
}

.stat-value {
    font-size: 28px;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 5px;
}

.stat-sub {
    color: #95a5a6;
    font-size: 12px;
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

th {
    background: #34495e;
    color: white;
    padding: 10px;
    white-space: nowrap;
}

td {
    padding: 8px 10px;
    border-bottom: 1px solid #ecf0f1;
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
.btn-light { background: white; color: #2c3e50; }

.badge {
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
}

.badge-info {
    background: #d1ecf1;
    color: #0c5460;
}

.badge-warning {
    background: #fff3cd;
    color: #856404;
}

.badge-danger {
    background: #f8d7da;
    color: #721c24;
}

.activity-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.activity-item {
    padding: 12px 0;
    border-bottom: 1px solid #ecf0f1;
    display: flex;
    align-items: center;
    gap: 15px;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #3498db;
    flex-shrink: 0;
}

.activity-content {
    flex: 1;
}

.activity-title {
    font-size: 13px;
    color: #2c3e50;
    margin-bottom: 3px;
}

.activity-time {
    font-size: 11px;
    color: #95a5a6;
}
</style>

<?php include '../includes/footer.php'; ?>