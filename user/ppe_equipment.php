<?php
require_once '../includes/header.php';
require_once '../includes/functions.php';
requireRole('user');

// Handle quick add
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['quick_add'])) {
    $article = mysqli_real_escape_string($conn, $_POST['article']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $location_id = (int)$_POST['location_id'];
    $equipment_id = (int)$_POST['equipment_id'];
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $quantity = (float)$_POST['quantity'];
    $unit_value = (float)$_POST['unit_value'];
    $accountable = !empty($_POST['accountable']) ? (int)$_POST['accountable'] : 'NULL';
    $fund_cluster = mysqli_real_escape_string($conn, $_POST['fund_cluster']);
    
    // Generate property number
    $prefix = 'PPE';
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
        'ppe', $accountable, '$fund_cluster', NOW(), {$_SESSION['user_id']}
    )";
    
    if ($conn->query($query)) {
        $id = $conn->insert_id;
        $_SESSION['success'] = "PPE item added successfully! Property No: $property_no";
        header("Location: view_inventory.php?id=$id");
        exit();
    }
}

// Get data for dropdowns
$locations = $conn->query("
    SELECT d.*, b.name as building_name, b.floor 
    FROM departments d
    JOIN buildings b ON d.building_id = b.id
    ORDER BY b.name, d.name
");

$equipment = $conn->query("SELECT * FROM equipment WHERE category IN ('MEDICAL', 'ICT', 'DRRM', 'LABORATORY') ORDER BY name");

$users = $conn->query("SELECT id, firstname, lastname FROM users WHERE status = 'active' ORDER BY firstname");

$fund_clusters = ['GF', 'TF', 'IGF', 'MOOE', 'CO', 'HI', 'RF', 'RAF', 'TR'];

// Get recent PPE items
$recent = $conn->query("
    SELECT i.*, d.name as location_name, e.name as equipment_name,
           CONCAT(u.firstname, ' ', u.lastname) as accountable_name
    FROM inventory i
    LEFT JOIN departments d ON i.location_id = d.id
    LEFT JOIN equipment e ON i.equipment_id = e.id
    LEFT JOIN users u ON i.allocate_to = u.id
    WHERE i.type_equipment = 'ppe'
    ORDER BY i.date_added DESC
    LIMIT 10
");
?>
<?php include '../includes/sidebar.php'; ?>

<div style="padding: 20px;">
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h1 style="color: #2c3e50;">
            <i class="fas fa-building"></i> Property Plant Equipment (50K Above)
        </h1>
        <div>
            <a href="add_inventory.php?type=ppe" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> Advanced Add
            </a>
            <a href="print_labels.php?type=ppe" class="btn btn-info">
                <i class="fas fa-print"></i> Print Labels
            </a>
        </div>
    </div>
    
    <!-- Quick Add Form -->
    <div class="table-container" style="margin-bottom: 30px; background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); color: white;">
        <h3 style="margin-bottom: 20px;"><i class="fas fa-bolt"></i> Quick Add PPE (50K Above)</h3>
        
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
                    <option value="ICT">ICT</option>
                    <option value="DRRM">DRRM</option>
                    <option value="LABORATORY">Laboratory</option>
                </select>
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 5px; font-size: 13px;">Equipment Type *</label>
                <select name="equipment_id" class="form-control" required>
                    <option value="">Select</option>
                    <?php while ($eq = $equipment->fetch_assoc()): ?>
                        <option value="<?php echo $eq['id']; ?>"><?php echo htmlspecialchars($eq['name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 5px; font-size: 13px;">Location *</label>
                <select name="location_id" class="form-control" required>
                    <option value="">Select Location</option>
                    <?php while ($loc = $locations->fetch_assoc()): ?>
                        <option value="<?php echo $loc['id']; ?>">
                            <?php echo htmlspecialchars($loc['building_name'] . ' (' . $loc['floor'] . 'F) - ' . $loc['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 5px; font-size: 13px;">Quantity *</label>
                <input type="number" name="quantity" class="form-control" min="1" value="1" required>
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 5px; font-size: 13px;">Unit Value (₱) *</label>
                <input type="number" name="unit_value" class="form-control" min="50000" step="0.01" placeholder="50,000.00" required>
                <small style="color: rgba(255,255,255,0.7);">Min: ₱50,000</small>
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 5px; font-size: 13px;">Fund Cluster *</label>
                <select name="fund_cluster" class="form-control" required>
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
                    <?php while ($user = $users->fetch_assoc()): ?>
                        <option value="<?php echo $user['id']; ?>">
                            <?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div style="display: flex; align-items: flex-end; grid-column: span 4;">
                <button type="submit" class="btn btn-success" style="width: 200px;">
                    <i class="fas fa-plus"></i> Add PPE Item
                </button>
            </div>
        </form>
    </div>
    
    <!-- Inventory List -->
    <div class="table-container">
        <div class="table-header">
            <h3 class="table-title"><i class="fas fa-list"></i> PPE Inventory List (50K Above)</h3>
            <div>
                <input type="text" id="searchInput" placeholder="Search..." style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Barcode</th>
                    <th>Property No</th>
                    <th>Location</th>
                    <th>Equipment</th>
                    <th>Article</th>
                    <th>Description</th>
                    <th>Qty</th>
                    <th>Unit Value</th>
                    <th>Total Value</th>
                    <th>Accountable</th>
                    <th>Fund Cluster</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($item = $recent->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $item['id']; ?></td>
                    <td><small><?php echo htmlspecialchars($item['barcode_data']); ?></small></td>
                    <td><?php echo htmlspecialchars($item['property_no']); ?></td>
                    <td><?php echo htmlspecialchars($item['location_name'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($item['equipment_name'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($item['article_name']); ?></td>
                    <td><?php echo htmlspecialchars(substr($item['description'] ?? '', 0, 30)); ?></td>
                    <td><?php echo $item['qty_physical_count']; ?></td>
                    <td><?php echo formatCurrency($item['unit_value']); ?></td>
                    <td><strong><?php echo formatCurrency($item['qty_physical_count'] * $item['unit_value']); ?></strong></td>
                    <td><?php echo htmlspecialchars($item['accountable_name'] ?? 'N/A'); ?></td>
                    <td><?php echo $item['fund_cluster']; ?></td>
                    <td>
                        <a href="view_inventory.php?id=<?php echo $item['id']; ?>" class="btn btn-info btn-sm">
                            <i class="fas fa-eye"></i>
                        </a>
                        <button onclick="printBarcode('<?php echo $item['barcode_data']; ?>')" class="btn btn-warning btn-sm">
                            <i class="fas fa-print"></i>
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.getElementById('searchInput').addEventListener('keyup', function() {
    const searchText = this.value.toLowerCase();
    const rows = document.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchText) ? '' : 'none';
    });
});

function printBarcode(barcodeData) {
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Print Barcode - PPE</title>
            <style>
                body { display: flex; justify-content: center; align-items: center; height: 100vh; }
                .barcode { text-align: center; padding: 40px; border: 2px solid #333; }
                .code { font-size: 36px; letter-spacing: 3px; margin: 20px 0; }
                .warning { color: #e74c3c; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="barcode">
                <h3>PROPERTY PLANT EQUIPMENT</h3>
                <h4>(50K Above)</h4>
                <div class="code">${barcodeData}</div>
                <p class="warning">This equipment is valued above ₱50,000</p>
            </div>
            <script>
                window.onload = function() { window.print(); window.close(); }
            <\/script>
        </body>
        </html>
    `);
}
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