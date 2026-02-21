<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/header.php';
require_once '../includes/functions.php';
requireRole('user');

$message = '';
$error = '';

// Handle quick add
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['quick_add'])) {
    $article = mysqli_real_escape_string($conn, $_POST['article']);
    $description = mysqli_real_escape_string($conn, $_POST['description'] ?? '');
    $location_id = (int)$_POST['location_id'];
    $equipment_id = (int)$_POST['equipment_id'];
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $quantity = (float)$_POST['quantity'];
    $unit_value = (float)$_POST['unit_value'];
    $accountable = !empty($_POST['accountable']) ? (int)$_POST['accountable'] : 'NULL';
    $fund_cluster = mysqli_real_escape_string($conn, $_POST['fund_cluster'] ?? '');
    
    // Generate property number
    $prefix = 'SEMI';
    $year = date('Y');
    $random = rand(1000, 9999);
    $property_no = $prefix . '-' . $year . '-' . $random;
    $barcode_data = 'INV-' . $property_no;
    
    $query = "INSERT INTO inventory (
        article_name, description, property_no, location_id, equipment_id,
        category, uom, qty_physical_count, unit_value, barcode_data, 
        type_equipment, allocate_to, fund_cluster, date_added, approved_by
    ) VALUES (
        '$article', '$description', '$property_no', $location_id, $equipment_id,
        '$category', 'Unit', $quantity, $unit_value, '$barcode_data',
        'semi', $accountable, '$fund_cluster', NOW(), {$_SESSION['user_id']}
    )";
    
    if ($conn->query($query)) {
        $id = $conn->insert_id;
        $_SESSION['success'] = "Semi-expendable item added successfully! Property No: $property_no";
        header("Location: view_inventory.php?id=$id");
        exit();
    } else {
        $error = "Error: " . $conn->error;
    }
}

// Get data for dropdowns
$locations = $conn->query("
    SELECT d.*, b.name as building_name, b.floor 
    FROM departments d
    JOIN buildings b ON d.building_id = b.id
    ORDER BY b.name, d.name
");

if (!$locations) {
    die("Error fetching locations: " . $conn->error);
}

$equipment = $conn->query("SELECT * FROM equipment WHERE category IN ('MEDICAL', 'OFFICE', 'COMMUNICATION', 'LABORATORY') ORDER BY name");

if (!$equipment) {
    die("Error fetching equipment: " . $conn->error);
}

$users = $conn->query("SELECT id, firstname, lastname FROM users WHERE status = 'active' ORDER BY firstname");

if (!$users) {
    die("Error fetching users: " . $conn->error);
}

$fund_clusters = ['GF', 'TF', 'IGF', 'MOOE', 'CO', 'HI', 'RF', 'RAF', 'TR'];

// Get recent semi-expendable items
$recent = $conn->query("
    SELECT i.*, d.name as location_name, e.name as equipment_name,
           CONCAT(u.firstname, ' ', u.lastname) as accountable_name
    FROM inventory i
    LEFT JOIN departments d ON i.location_id = d.id
    LEFT JOIN equipment e ON i.equipment_id = e.id
    LEFT JOIN users u ON i.allocate_to = u.id
    WHERE i.type_equipment = 'semi'
    ORDER BY i.date_added DESC
    LIMIT 10
");

if (!$recent) {
    die("Error fetching recent items: " . $conn->error);
}
?>
<?php include '../includes/sidebar.php'; ?>

<div style="padding: 20px;">
    <!-- Header with Stats -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h1 style="color: #2c3e50;">
            <i class="fas fa-box"></i> Semi-expendable Equipment
        </h1>
        <div>
            <a href="add_inventory.php?type=semi" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> Advanced Add
            </a>
            <a href="generate_barcodes.php?type=semi" class="btn btn-warning">
                <i class="fas fa-qrcode"></i> Generate Missing Barcodes
            </a>
            <a href="print_labels.php?type=semi" class="btn btn-info">
                <i class="fas fa-print"></i> Print Labels
            </a>
            <a href="export_inventory.php?type=semi&format=csv" class="btn btn-success">
                <i class="fas fa-download"></i> Export
            </a>
        </div>
    </div>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <!-- Quick Add Form - Styled like PPE -->
    <div class="table-container" style="margin-bottom: 30px; background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); color: white;">
        <h3 style="margin-bottom: 20px;"><i class="fas fa-bolt"></i> Quick Add Semi-expendable</h3>
        
        <form method="POST" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px;">
            <input type="hidden" name="quick_add" value="1">
            
            <div>
                <label style="display: block; margin-bottom: 5px; font-size: 13px;">Article *</label>
                <input type="text" name="article" class="form-control" required placeholder="Equipment name">
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 5px; font-size: 13px;">Description</label>
                <input type="text" name="description" class="form-control" placeholder="Model/Details">
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 5px; font-size: 13px;">Category *</label>
                <select name="category" class="form-control" required>
                    <option value="MEDICAL">Medical</option>
                    <option value="OFFICE">Office</option>
                    <option value="COMMUNICATION">Communication</option>
                    <option value="LABORATORY">Laboratory</option>
                </select>
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 5px; font-size: 13px;">Equipment Type *</label>
                <select name="equipment_id" class="form-control" required>
                    <option value="">Select</option>
                    <?php 
                    $equipment->data_seek(0);
                    while ($eq = $equipment->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $eq['id']; ?>"><?php echo htmlspecialchars($eq['name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 5px; font-size: 13px;">Location *</label>
                <select name="location_id" class="form-control" required>
                    <option value="">Select Location</option>
                    <?php 
                    $locations->data_seek(0);
                    while ($loc = $locations->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $loc['id']; ?>">
                            <?php echo htmlspecialchars($loc['building_name'] . ' (' . $loc['floor'] . 'F) - ' . $loc['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 5px; font-size: 13px;">Quantity *</label>
                <input type="number" name="quantity" class="form-control" min="1" step="0.01" value="1" required>
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 5px; font-size: 13px;">Unit Value (₱)</label>
                <input type="number" name="unit_value" class="form-control" min="0" step="0.01" value="0.00">
                <small style="color: rgba(255,255,255,0.7);">Below ₱50,000</small>
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 5px; font-size: 13px;">Fund Cluster</label>
                <select name="fund_cluster" class="form-control">
                    <option value="">Select</option>
                    <?php foreach ($fund_clusters as $fc): ?>
                        <option value="<?php echo $fc; ?>"><?php echo $fc; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 5px; font-size: 13px;">Accountable Person</label>
                <select name="accountable" class="form-control">
                    <option value="">-- None --</option>
                    <?php 
                    $users->data_seek(0);
                    while ($user = $users->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $user['id']; ?>">
                            <?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div style="display: flex; align-items: flex-end; grid-column: span 4;">
                <button type="submit" class="btn btn-success" style="width: 200px;">
                    <i class="fas fa-plus"></i> Add Semi-expendable
                </button>
            </div>
        </form>
    </div>
    
    <!-- Inventory List Table - Full columns like in the image -->
    <div class="table-container">
        <div class="table-header">
            <h3 class="table-title"><i class="fas fa-list"></i> Semi-expendable Inventory List</h3>
            <div>
                <input type="text" id="searchInput" placeholder="Search..." style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; width: 250px;">
            </div>
        </div>
        
        <div style="overflow-x: auto;">
            <table id="inventoryTable" style="width: 100%; border-collapse: collapse; min-width: 2000px;">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Barcode</th>
                        <th>Property No</th>
                        <th>Location</th>
                        <th>Type of Equipment</th>
                        <th>Article</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Qty (Prop)</th>
                        <th>Qty (Phy)</th>
                        <th>Unit Value</th>
                        <th>UOM</th>
                        <th>Accountable</th>
                        <th>Certified Correct</th>
                        <th>Approved</th>
                        <th>Verified</th>
                        <th>Fund Cluster</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent->num_rows > 0): ?>
                        <?php while ($item = $recent->fetch_assoc()): 
                            // Get certified correct names (assuming it's stored as comma-separated IDs)
                            $certified_names = '';
                            if (!empty($item['certified_correct'])) {
                                $cert_ids = explode(',', trim($item['certified_correct'], '[]'));
                                $cert_names = [];
                                foreach ($cert_ids as $cid) {
                                    $cresult = $conn->query("SELECT CONCAT(firstname, ' ', lastname) as name FROM users WHERE id = " . (int)$cid);
                                    if ($cresult && $crow = $cresult->fetch_assoc()) {
                                        $cert_names[] = $crow['name'];
                                    }
                                }
                                $certified_names = implode(', ', $cert_names);
                            }
                            
                            // Get approved name
                            $approved_name = '';
                            if ($item['approved_by']) {
                                $aresult = $conn->query("SELECT CONCAT(firstname, ' ', lastname) as name FROM users WHERE id = {$item['approved_by']}");
                                if ($aresult && $arow = $aresult->fetch_assoc()) {
                                    $approved_name = $arow['name'];
                                }
                            }
                            
                            // Get verified name
                            $verified_name = '';
                            if ($item['verified_by']) {
                                $vresult = $conn->query("SELECT CONCAT(firstname, ' ', lastname) as name FROM users WHERE id = {$item['verified_by']}");
                                if ($vresult && $vrow = $vresult->fetch_assoc()) {
                                    $verified_name = $vrow['name'];
                                }
                            }
                        ?>
                        <tr>
                            <td><?php echo $item['id']; ?></td>
                            <td><small><?php echo htmlspecialchars($item['barcode_data']); ?></small></td>
                            <td><?php echo htmlspecialchars($item['property_no']); ?></td>
                            <td><?php echo htmlspecialchars($item['location_name'] ?? 'N/A'); ?></td>
                            <td>Semi-expendable Equipment</td>
                            <td><?php echo htmlspecialchars($item['article_name']); ?></td>
                            <td><?php echo htmlspecialchars($item['category']); ?></td>
                            <td><?php echo htmlspecialchars(substr($item['description'] ?? '', 0, 30)) . (strlen($item['description'] ?? '') > 30 ? '...' : ''); ?></td>
                            <td><?php echo number_format($item['qty_property_card'], 2); ?></td>
                            <td><?php echo number_format($item['qty_physical_count'], 2); ?></td>
                            <td>₱<?php echo number_format($item['unit_value'], 2); ?></td>
                            <td><?php echo $item['uom'] ?: 'Unit'; ?></td>
                            <td><?php echo htmlspecialchars($item['accountable_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($certified_names ?: 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($approved_name ?: 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($verified_name ?: 'N/A'); ?></td>
                            <td><?php echo $item['fund_cluster'] ?: 'N/A'; ?></td>
                            <td>
                                <div style="display: flex; gap: 5px;">
                                    <a href="view_inventory.php?id=<?php echo $item['id']; ?>" class="btn btn-info btn-sm" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit_inventory.php?id=<?php echo $item['id']; ?>" class="btn btn-primary btn-sm" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button onclick="printBarcode('<?php echo $item['barcode_data']; ?>')" class="btn btn-warning btn-sm" title="Print Barcode">
                                        <i class="fas fa-print"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="18" style="text-align: center; padding: 50px;">
                                <i class="fas fa-box-open" style="font-size: 48px; color: #ccc; margin-bottom: 15px; display: block;"></i>
                                <h3 style="color: #7f8c8d; margin-bottom: 10px;">No Semi-expendable Items Found</h3>
                                <p>Click the "Quick Add" button above to add your first item.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Summary Stats - Like PPE -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-top: 20px;">
        <?php
        $total_items = $conn->query("SELECT COUNT(*) as count FROM inventory WHERE type_equipment = 'semi'")->fetch_assoc()['count'];
        $total_value = $conn->query("SELECT SUM(qty_physical_count * unit_value) as total FROM inventory WHERE type_equipment = 'semi'")->fetch_assoc()['total'] ?? 0;
        $avg_value = $total_items > 0 ? $total_value / $total_items : 0;
        $this_month = $conn->query("SELECT COUNT(*) as count FROM inventory WHERE type_equipment = 'semi' AND MONTH(date_added) = MONTH(NOW())")->fetch_assoc()['count'];
        ?>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-boxes"></i></div>
            <div class="stat-title">Total Items</div>
            <div class="stat-value"><?php echo $total_items; ?></div>
            <div class="stat-sub">Semi-expendable</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
            <div class="stat-title">Total Value</div>
            <div class="stat-value">₱<?php echo number_format($total_value, 2); ?></div>
            <div class="stat-sub">Combined value</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
            <div class="stat-title">Average Value</div>
            <div class="stat-value">₱<?php echo number_format($avg_value, 2); ?></div>
            <div class="stat-sub">Per item</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-calendar"></i></div>
            <div class="stat-title">This Month</div>
            <div class="stat-value"><?php echo $this_month; ?></div>
            <div class="stat-sub">New additions</div>
        </div>
    </div>
</div>

<script>
// Search functionality
document.getElementById('searchInput')?.addEventListener('keyup', function() {
    const searchText = this.value.toLowerCase();
    const table = document.getElementById('inventoryTable');
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        const cells = row.getElementsByTagName('td');
        let found = false;
        
        for (let j = 0; j < cells.length - 1; j++) {
            const cell = cells[j];
            if (cell && cell.textContent.toLowerCase().indexOf(searchText) > -1) {
                found = true;
                break;
            }
        }
        
        row.style.display = found ? '' : 'none';
    }
});

function printBarcode(barcodeData) {
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Print Barcode - Semi-expendable</title>
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
                    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
                }
                .barcode {
                    font-size: 32px;
                    letter-spacing: 3px;
                    margin: 20px 0;
                    font-family: 'Courier New', monospace;
                    font-weight: bold;
                }
                .property {
                    font-size: 18px;
                    color: #2c3e50;
                    margin: 10px 0;
                }
                .type {
                    background: #3498db;
                    color: white;
                    padding: 5px 15px;
                    border-radius: 20px;
                    display: inline-block;
                    font-size: 14px;
                    margin-bottom: 15px;
                }
                @media print {
                    body { margin: 0; padding: 0; }
                    .barcode-container { border: none; box-shadow: none; }
                }
            </style>
        </head>
        <body>
            <div class="barcode-container">
                <div class="type">SEMI-EXPENDABLE EQUIPMENT</div>
                <div class="barcode">${barcodeData}</div>
                <div class="property">Property No: ${barcodeData.replace('INV-', '')}</div>
                <p style="color: #7f8c8d; font-size: 12px;">Scan this barcode to view item details</p>
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

document.addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.key === 'f') {
        e.preventDefault();
        document.getElementById('searchInput').focus();
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
    padding: 12px 8px;
    white-space: nowrap;
    font-weight: 500;
    text-align: left;
}

td {
    padding: 10px 8px;
    border-bottom: 1px solid #ecf0f1;
    white-space: nowrap;
}

tr:hover td {
    background: #f8f9fa;
}

.form-control {
    padding: 8px 12px;
    border: 1px solid rgba(255,255,255,0.2);
    background: rgba(255,255,255,0.95);
    border-radius: 4px;
    width: 100%;
    color: #2c3e50;
    font-size: 13px;
}

.form-control:focus {
    background: white;
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52,152,219,0.2);
}

.form-control option {
    color: #2c3e50;
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
.btn-danger { background: #e74c3c; color: white; }

.alert {
    padding: 12px 20px;
    border-radius: 4px;
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

/* Custom scrollbar for table */
::-webkit-scrollbar {
    height: 8px;
    width: 8px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

::-webkit-scrollbar-thumb {
    background: #3498db;
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: #2980b9;
}
</style>

<?php include '../includes/footer.php'; ?>