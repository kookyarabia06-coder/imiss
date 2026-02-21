<?php
require_once '../includes/header.php';
require_once '../includes/functions.php';
requireLogin();

$barcode = isset($_GET['barcode']) ? $_GET['barcode'] : '';
$scan_result = null;
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' || !empty($barcode)) {
    $barcode = !empty($_POST['barcode']) ? $_POST['barcode'] : $barcode;
    $barcode = trim($barcode);
    
    if (!empty($barcode)) {
        // Look up item by barcode
        $item = getInventoryByBarcode($barcode);
        
        if ($item) {
            // Log the scan
            $date = date('Y-m-d H:i:s');
            $details = "Scanned barcode: $barcode";
            $conn->query("INSERT INTO activity_log (user_id, action, item_id, details, date_created) 
                          VALUES ({$_SESSION['user_id']}, 'scan', {$item['id']}, '$details', '$date')");
            
            $scan_result = ['success' => true, 'item' => $item];
        } else {
            // Log failed scan
            $date = date('Y-m-d H:i:s');
            $details = "Failed scan attempt: $barcode";
            $conn->query("INSERT INTO activity_log (user_id, action, item_id, details, date_created) 
                          VALUES ({$_SESSION['user_id']}, 'scan', NULL, '$details', '$date')");
            
            $scan_result = ['success' => false, 'message' => 'Item not found'];
        }
    }
}

// Get recent scans for this user
$recent_scans = $conn->query("
    SELECT a.*, i.article_name, i.property_no
    FROM activity_log a
    LEFT JOIN inventory i ON a.item_id = i.id
    WHERE a.user_id = {$_SESSION['user_id']} AND a.action = 'scan'
    ORDER BY a.date_created DESC
    LIMIT 10
");
?>
<?php include '../includes/sidebar.php'; ?>

<div style="padding: 20px;">
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h1 style="color: #2c3e50;"><i class="fas fa-qrcode"></i> Barcode Scanner</h1>
        <div>
            <a href="history.php" class="btn btn-primary">
                <i class="fas fa-history"></i> Scan History
            </a>
            <a href="../<?php echo $_SESSION['role']; ?>/add_inventory.php" class="btn btn-success">
                <i class="fas fa-plus"></i> Add New Item
            </a>
        </div>
    </div>
    
    <!-- Scanner Section -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
        <!-- Scanner Form -->
        <div class="table-container">
            <h3 class="table-title"><i class="fas fa-camera"></i> Scan Barcode</h3>
            
            <?php if ($scan_result): ?>
                <?php if ($scan_result['success']): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> Item found successfully!
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-top: 15px; border-left: 4px solid #27ae60;">
                        <h4 style="margin-bottom: 15px; color: #2c3e50;">Item Details:</h4>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div><strong>Property No:</strong></div>
                            <div><?php echo htmlspecialchars($scan_result['item']['property_no']); ?></div>
                            
                            <div><strong>Article:</strong></div>
                            <div><?php echo htmlspecialchars($scan_result['item']['article_name']); ?></div>
                            
                            <div><strong>Description:</strong></div>
                            <div><?php echo htmlspecialchars($scan_result['item']['description'] ?? 'N/A'); ?></div>
                            
                            <div><strong>Category:</strong></div>
                            <div><?php echo htmlspecialchars($scan_result['item']['category']); ?></div>
                            
                            <div><strong>Type:</strong></div>
                            <div>
                                <span class="badge <?php echo $scan_result['item']['type_equipment'] == 'semi' ? 'badge-info' : 'badge-warning'; ?>">
                                    <?php echo $scan_result['item']['type_equipment'] == 'semi' ? 'Semi-expendable' : 'PPE (50K Above)'; ?>
                                </span>
                            </div>
                            
                            <div><strong>Quantity:</strong></div>
                            <div><strong><?php echo $scan_result['item']['qty_physical_count']; ?></strong></div>
                            
                            <div><strong>Location:</strong></div>
                            <div><?php echo htmlspecialchars($scan_result['item']['location_name'] ?? 'N/A'); ?></div>
                            
                            <div><strong>Condition:</strong></div>
                            <div><?php echo htmlspecialchars($scan_result['item']['condition_text'] ?? 'N/A'); ?></div>
                            
                            <div><strong>Unit Value:</strong></div>
                            <div>₱<?php echo number_format($scan_result['item']['unit_value'], 2); ?></div>
                        </div>
                        
                        <hr style="margin: 15px 0;">
                        
                        <div style="text-align: center;">
                            <a href="../<?php echo $_SESSION['role']; ?>/view_inventory.php?id=<?php echo $scan_result['item']['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-eye"></i> View Full Details
                            </a>
                            <button onclick="printBarcode('<?php echo $scan_result['item']['barcode_data']; ?>')" class="btn btn-warning">
                                <i class="fas fa-print"></i> Print Barcode
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $scan_result['message']; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <form method="POST" id="scanForm" style="margin-top: 20px;">
                <div class="form-group">
                    <label for="barcode" style="font-size: 16px; font-weight: 500;">
                        <i class="fas fa-barcode"></i> Scan or Enter Barcode
                    </label>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" 
                               name="barcode" 
                               id="barcode" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($barcode); ?>"
                               placeholder="Scan barcode or type property number..." 
                               style="flex: 1; font-size: 16px; padding: 12px;"
                               autofocus>
                        <button type="submit" class="btn btn-primary" style="padding: 12px 25px;">
                            <i class="fas fa-search"></i> Find
                        </button>
                    </div>
                </div>
                
                <div style="margin-top: 15px; text-align: center; color: #7f8c8d; font-size: 13px;">
                    <i class="fas fa-info-circle"></i> 
                    You can also scan using a USB barcode scanner - it works like a keyboard
                </div>
            </form>
            
            <div style="margin-top: 20px; display: flex; gap: 10px; justify-content: center;">
                <button onclick="simulateScan('SEMI-2024-1234')" class="btn btn-info btn-sm">
                    <i class="fas fa-flask"></i> Test: Semi Item
                </button>
                <button onclick="simulateScan('PPE-2024-5678')" class="btn btn-warning btn-sm">
                    <i class="fas fa-flask"></i> Test: PPE Item
                </button>
            </div>
        </div>
        
        <!-- Quick Info & Recent Scans -->
        <div class="table-container">
            <h3 class="table-title"><i class="fas fa-clock"></i> Recent Scans</h3>
            
            <?php if ($recent_scans->num_rows > 0): ?>
                <ul class="activity-list">
                    <?php while ($scan = $recent_scans->fetch_assoc()): ?>
                    <li class="activity-item">
                        <div class="activity-dot" style="background: <?php echo $scan['item_id'] ? '#27ae60' : '#e74c3c'; ?>;"></div>
                        <div class="activity-content">
                            <div class="activity-title">
                                <?php if ($scan['item_id']): ?>
                                    <a href="../<?php echo $_SESSION['role']; ?>/view_inventory.php?id=<?php echo $scan['item_id']; ?>" style="color: #3498db;">
                                        <?php echo htmlspecialchars($scan['article_name'] ?? 'Item'); ?>
                                    </a>
                                    <small>(<?php echo htmlspecialchars($scan['property_no']); ?>)</small>
                                <?php else: ?>
                                    <span style="color: #e74c3c;">Failed scan</span>
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
                    <a href="history.php" class="btn btn-primary btn-sm">View All History</a>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: #7f8c8d; padding: 30px;">
                    <i class="fas fa-qrcode" style="font-size: 48px; display: block; margin-bottom: 15px; opacity: 0.5;"></i>
                    No scans yet<br>
                    <small>Scan your first barcode using the form</small>
                </p>
            <?php endif; ?>
            
            <hr style="margin: 20px 0;">
            
            <h4 style="margin-bottom: 15px;">Scanner Tips:</h4>
            <ul style="margin-left: 20px; line-height: 1.8; color: #2c3e50;">
                <li><i class="fas fa-check-circle" style="color: #27ae60;"></i> Ensure barcode scanner is connected via USB</li>
                <li><i class="fas fa-check-circle" style="color: #27ae60;"></i> Click on the input field before scanning</li>
                <li><i class="fas fa-check-circle" style="color: #27ae60;"></i> Scan the barcode - it will auto-submit</li>
                <li><i class="fas fa-check-circle" style="color: #27ae60;"></i> Or type the property number manually</li>
                <li><i class="fas fa-check-circle" style="color: #27ae60;"></i> Recent scans are saved for reference</li>
            </ul>
        </div>
    </div>
    
    <!-- Barcode Format Guide -->
    <div class="table-container">
        <h3 class="table-title"><i class="fas fa-info-circle"></i> Barcode Format Guide</h3>
        
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center;">
                <div style="font-family: 'Courier New'; font-size: 18px; background: white; padding: 10px; border: 1px dashed #3498db;">
                    INV-SEMI-2024-1234
                </div>
                <p style="margin-top: 10px; font-size: 13px;">
                    <strong>Semi-expendable</strong><br>
                    <small>Format: INV-[TYPE]-[YEAR]-[NUMBER]</small>
                </p>
            </div>
            
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center;">
                <div style="font-family: 'Courier New'; font-size: 18px; background: white; padding: 10px; border: 1px dashed #e67e22;">
                    INV-PPE-2024-5678
                </div>
                <p style="margin-top: 10px; font-size: 13px;">
                    <strong>PPE (50K Above)</strong><br>
                    <small>Format: INV-[TYPE]-[YEAR]-[NUMBER]</small>
                </p>
            </div>
            
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center;">
                <div style="font-family: 'Courier New'; font-size: 18px; background: white; padding: 10px; border: 1px dashed #27ae60;">
                    PROP-12345
                </div>
                <p style="margin-top: 10px; font-size: 13px;">
                    <strong>Property Number</strong><br>
                    <small>You can also search by property number</small>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-submit on enter (for scanner)
document.getElementById('barcode').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        document.getElementById('scanForm').submit();
    }
});

// Focus on barcode input
document.getElementById('barcode').focus();

function simulateScan(barcode) {
    document.getElementById('barcode').value = barcode;
    document.getElementById('scanForm').submit();
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
                    padding: 40px;
                    border: 2px solid #333;
                    border-radius: 8px;
                    background: white;
                }
                .barcode {
                    font-size: 36px;
                    letter-spacing: 4px;
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
                    setTimeout(function() {
                        window.print(); 
                        window.close();
                    }, 500);
                }
            <\/script>
        </body>
        </html>
    `);
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl+F to focus search
    if (e.ctrlKey && e.key === 'f') {
        e.preventDefault();
        document.getElementById('barcode').focus();
    }
    
    // Ctrl+Enter to submit
    if (e.ctrlKey && e.key === 'Enter') {
        e.preventDefault();
        document.getElementById('scanForm').submit();
    }
});
</script>

<style>
.table-container {
    background: white;
    border-radius: 8px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.form-control {
    border: 2px solid #e0e0e0;
    transition: all 0.3s;
}

.form-control:focus {
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52,152,219,0.1);
}

.alert {
    padding: 15px 20px;
    border-radius: 6px;
    margin-bottom: 20px;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.activity-list {
    list-style: none;
    padding: 0;
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
    flex-shrink: 0;
}

.activity-content {
    flex: 1;
}

.activity-title {
    font-size: 14px;
    color: #2c3e50;
    margin-bottom: 3px;
}

.activity-time {
    font-size: 11px;
    color: #95a5a6;
}

.badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
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

.btn {
    transition: all 0.3s;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}
</style>

<?php include '../includes/footer.php'; ?>