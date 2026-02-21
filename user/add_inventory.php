<?php
require_once '../includes/header.php';
require_once '../includes/functions.php';
requireRole('user');

$message = '';
$error = '';

// Get locations with building info
$locations = $conn->query("
    SELECT d.*, b.name as building_name, b.floor 
    FROM departments d
    JOIN buildings b ON d.building_id = b.id
    ORDER BY b.name, d.name
");

// Get equipment types
$equipment_types = $conn->query("SELECT * FROM equipment ORDER BY category, name");

// Get users for accountable/approved/verified
$users = $conn->query("SELECT id, firstname, lastname, role FROM users WHERE status = 'active' ORDER BY firstname");

// Get fund clusters
$fund_clusters = ['GF', 'TF', 'IGF', 'MOOE', 'CO', 'HI', 'RF', 'RAF', 'TR'];

// Generate next property number
$last_property = $conn->query("SELECT property_no FROM inventory ORDER BY id DESC LIMIT 1")->fetch_assoc();
$next_number = $last_property ? intval(substr($last_property['property_no'], -4)) + 1 : 1000;
$next_property = 'PPE-' . date('Y') . '-' . str_pad($next_number, 4, '0', STR_PAD_LEFT);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $type = $_POST['type'] ?? 'semi'; // semi or ppe
    
    // Common fields
    $article = mysqli_real_escape_string($conn, $_POST['article']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $location_id = (int)$_POST['location_id'];
    $equipment_id = (int)$_POST['equipment_id'];
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $uom = mysqli_real_escape_string($conn, $_POST['uom']);
    $qty_property = (float)$_POST['qty_property'];
    $qty_physical = (float)$_POST['qty_physical'];
    $unit_value = (float)$_POST['unit_value'];
    $accountable = !empty($_POST['accountable']) ? (int)$_POST['accountable'] : 'NULL';
    $certified = !empty($_POST['certified']) ? (int)$_POST['certified'] : 'NULL';
    $approved = !empty($_POST['approved']) ? (int)$_POST['approved'] : 'NULL';
    $verified = !empty($_POST['verified']) ? (int)$_POST['verified'] : 'NULL';
    $fund_cluster = mysqli_real_escape_string($conn, $_POST['fund_cluster']);
    $remarks = mysqli_real_escape_string($conn, $_POST['remarks'] ?? '');
    
    // Generate property number based on type
    if ($_POST['property_no_auto'] == 'auto') {
        if ($type == 'semi') {
            $prefix = 'SEMI';
        } else {
            $prefix = 'PPE';
        }
        $year = date('Y');
        $random = rand(1000, 9999);
        $property_no = $prefix . '-' . $year . '-' . $random;
    } else {
        $property_no = mysqli_real_escape_string($conn, $_POST['property_no']);
    }
    
    // Generate barcode
    $barcode_data = 'INV-' . $property_no;
    
    // Insert into database
    $query = "INSERT INTO inventory (
        article_name, description, property_no, location_id, equipment_id,
        category, uom, qty_property_card, qty_physical_count, unit_value,
        approved_by, verified_by, certified_correct, allocate_to,
        fund_cluster, remarks, barcode_data, type_equipment, date_added
    ) VALUES (
        '$article', '$description', '$property_no', $location_id, $equipment_id,
        '$category', '$uom', $qty_property, $qty_physical, $unit_value,
        $approved, $verified, '$certified', $accountable,
        '$fund_cluster', '$remarks', '$barcode_data', '$type', NOW()
    )";
    
    if ($conn->query($query)) {
        $id = $conn->insert_id;
        
        // Log activity
        $date = date('Y-m-d H:i:s');
        $conn->query("INSERT INTO activity_log (user_id, action, item_id, details, date_created) 
                      VALUES ({$_SESSION['user_id']}, 'add', $id, 'Added $type equipment: $article', '$date')");
        
        $message = "
            <div class='alert alert-success'>
                <h4><i class='fas fa-check-circle'></i> Item Added Successfully!</h4>
                <p><strong>Property No:</strong> $property_no</p>
                <p><strong>Barcode:</strong> $barcode_data</p>
                <p><strong>Type:</strong> " . ($type == 'semi' ? 'Semi-expendable Equipment' : 'Property Plant Equipment (50K Above)') . "</p>
                <hr>
                <a href='view_inventory.php?id=$id' class='btn btn-primary btn-sm'>View Item</a>
                <a href='add_inventory.php' class='btn btn-success btn-sm'>Add Another</a>
                <button onclick='printBarcode(\"$barcode_data\")' class='btn btn-warning btn-sm'>
                    <i class='fas fa-print'></i> Print Barcode
                </button>
            </div>
        ";
    } else {
        $error = "Error: " . $conn->error;
    }
}
?>
<?php include '../includes/sidebar.php'; ?>

<div style="padding: 20px; max-width: 1200px; margin: 0 auto;">
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h1 style="color: #2c3e50;">Add Inventory Item</h1>
        <div>
            <a href="my_inventory.php" class="btn btn-primary">
                <i class="fas fa-list"></i> My Inventory
            </a>
        </div>
    </div>
    
    <?php echo $message; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <!-- Type Selection Tabs -->
    <div style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #ecf0f1; padding-bottom: 10px;">
        <button class="btn <?php echo (!isset($_GET['type']) || $_GET['type'] == 'semi') ? 'btn-primary' : ''; ?>" 
                onclick="switchType('semi')" id="tab-semi">
            <i class="fas fa-box"></i> Semi-expendable Equipment
        </button>
        <button class="btn <?php echo isset($_GET['type']) && $_GET['type'] == 'ppe' ? 'btn-primary' : ''; ?>" 
                onclick="switchType('ppe')" id="tab-ppe">
            <i class="fas fa-building"></i> Property Plant Equipment (50K Above)
        </button>
    </div>
    
    <!-- Main Form -->
    <div class="table-container">
        <form method="POST" id="inventoryForm" onsubmit="return validateForm()">
            <input type="hidden" name="type" id="equipmentType" value="<?php echo $_GET['type'] ?? 'semi'; ?>">
            
            <!-- Property No Generation -->
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 25px;">
                <div style="display: flex; gap: 20px; align-items: flex-end;">
                    <div style="flex: 2;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">
                            <i class="fas fa-barcode"></i> Property Number
                        </label>
                        <div style="display: flex; gap: 10px;">
                            <div style="flex: 1;">
                                <select name="property_no_auto" id="propertyNoAuto" class="form-control" onchange="togglePropertyNo()">
                                    <option value="auto">Auto-generate</option>
                                    <option value="manual">Manual Entry</option>
                                </select>
                            </div>
                            <div style="flex: 2;">
                                <input type="text" name="property_no" id="propertyNo" class="form-control" 
                                       placeholder="Enter Property No" value="<?php echo $next_property; ?>"
                                       <?php echo (!isset($_GET['type']) || $_GET['type'] == 'semi') ? 'readonly' : ''; ?>>
                            </div>
                        </div>
                    </div>
                    <div style="flex: 1; text-align: center;">
                        <div style="background: white; padding: 10px; border: 1px dashed #3498db; border-radius: 4px;">
                            <small style="display: block; color: #7f8c8d;">Barcode Preview</small>
                            <div id="barcodePreview" style="font-family: 'Courier New'; font-size: 18px; letter-spacing: 2px;">
                                INV-<?php echo $next_property; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Two Column Layout -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
                <!-- Left Column -->
                <div>
                    <h4 style="margin-bottom: 15px; color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 5px;">
                        <i class="fas fa-info-circle"></i> Item Details
                    </h4>
                    
                    <div class="form-group">
                        <label>Article *</label>
                        <input type="text" name="article" class="form-control" required 
                               placeholder="e.g., Medical Bed, Laptop, etc.">
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3" 
                                  placeholder="Detailed description of the item"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Category *</label>
                        <select name="category" class="form-control" required id="category" onchange="updateEquipmentOptions()">
                            <option value="">Select Category</option>
                            <option value="MEDICAL">Medical</option>
                            <option value="ICT">ICT</option>
                            <option value="OFFICE">Office</option>
                            <option value="DRRM">DRRM</option>
                            <option value="LABORATORY">Laboratory</option>
                            <option value="FURNITURE">Furniture</option>
                            <option value="COMMUNICATION">Communication</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Type of Equipment *</label>
                        <select name="equipment_id" id="equipment_id" class="form-control" required>
                            <option value="">Select Equipment Type</option>
                            <?php 
                            $equipment_types->data_seek(0);
                            while ($eq = $equipment_types->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $eq['id']; ?>" data-category="<?php echo $eq['category']; ?>">
                                    <?php echo htmlspecialchars($eq['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Unit of Measure *</label>
                        <select name="uom" class="form-control" required>
                            <option value="Unit">Unit</option>
                            <option value="Box">Box</option>
                            <option value="Lot">Lot</option>
                            <option value="Set">Set</option>
                            <option value="Piece">Piece</option>
                            <option value="Bottle">Bottle</option>
                            <option value="Vial">Vial</option>
                            <option value="Pack">Pack</option>
                        </select>
                    </div>
                </div>
                
                <!-- Right Column -->
                <div>
                    <h4 style="margin-bottom: 15px; color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 5px;">
                        <i class="fas fa-map-marker-alt"></i> Location & Values
                    </h4>
                    
                    <div class="form-group">
                        <label>Location / Whereabouts *</label>
                        <select name="location_id" class="form-control" required>
                            <option value="">-- Select Location --</option>
                            <?php while ($loc = $locations->fetch_assoc()): ?>
                                <option value="<?php echo $loc['id']; ?>">
                                    <?php echo htmlspecialchars($loc['building_name'] . ' (' . $loc['floor'] . 'th Floor) - ' . $loc['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Fund Cluster *</label>
                        <select name="fund_cluster" class="form-control" required>
                            <option value="">-- Select Fund Cluster --</option>
                            <?php foreach ($fund_clusters as $fc): ?>
                                <option value="<?php echo $fc; ?>"><?php echo $fc; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div class="form-group">
                            <label>Qty (Property Card)</label>
                            <input type="number" name="qty_property" class="form-control" value="1" min="0" step="0.01">
                        </div>
                        <div class="form-group">
                            <label>Qty (Physical Count)</label>
                            <input type="number" name="qty_physical" class="form-control" value="1" min="0" step="0.01" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Unit Value (₱)</label>
                        <input type="number" name="unit_value" class="form-control" value="0.00" min="0" step="0.01" required>
                    </div>
                </div>
            </div>
            
            <!-- Personnel Section -->
            <h4 style="margin: 25px 0 15px; color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 5px;">
                <i class="fas fa-users"></i> Personnel Accountability
            </h4>
            
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px;">
                <div class="form-group">
                    <label>Accountable Person</label>
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
                
                <div class="form-group">
                    <label>Certified Correct</label>
                    <select name="certified" class="form-control">
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
                
                <div class="form-group">
                    <label>Approved By</label>
                    <select name="approved" class="form-control">
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
                
                <div class="form-group">
                    <label>Verified By</label>
                    <select name="verified" class="form-control">
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
            </div>
            
            <!-- Remarks -->
            <div class="form-group" style="margin-top: 20px;">
                <label>Remarks</label>
                <textarea name="remarks" class="form-control" rows="2" placeholder="Additional notes..."></textarea>
            </div>
            
            <!-- Action Buttons -->
            <div style="display: flex; gap: 10px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ecf0f1;">
                <button type="submit" class="btn btn-success" style="padding: 12px 30px;">
                    <i class="fas fa-save"></i> Save Item
                </button>
                <button type="button" class="btn btn-warning" onclick="printBarcodePreview()" style="padding: 12px 30px;">
                    <i class="fas fa-print"></i> Print Barcode
                </button>
                <button type="reset" class="btn" style="background: #95a5a6; color: white; padding: 12px 30px;">
                    <i class="fas fa-undo"></i> Reset
                </button>
                <a href="dashboard.php" class="btn" style="background: #7f8c8d; color: white; padding: 12px 30px;">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
    
    <!-- Recent Items Preview -->
    <div class="table-container" style="margin-top: 30px;">
        <div class="table-header">
            <h3 class="table-title"><i class="fas fa-clock"></i> Recent Semi-expendable Items</h3>
            <a href="my_inventory.php?type=semi" class="btn btn-primary btn-sm">View All</a>
        </div>
        
        <?php
        $recent_semi = $conn->query("
            SELECT i.*, d.name as location_name, e.name as equipment_name,
                   CONCAT(u1.firstname, ' ', u1.lastname) as accountable_name
            FROM inventory i
            LEFT JOIN departments d ON i.location_id = d.id
            LEFT JOIN equipment e ON i.equipment_id = e.id
            LEFT JOIN users u1 ON i.allocate_to = u1.id
            WHERE i.type_equipment = 'semi'
            ORDER BY i.date_added DESC
            LIMIT 5
        ");
        ?>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Barcode</th>
                    <th>Property No</th>
                    <th>Location</th>
                    <th>Equipment</th>
                    <th>Article</th>
                    <th>Qty</th>
                    <th>Unit Value</th>
                    <th>Accountable</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($item = $recent_semi->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $item['id']; ?></td>
                    <td><small><?php echo htmlspecialchars($item['barcode_data']); ?></small></td>
                    <td><?php echo htmlspecialchars($item['property_no']); ?></td>
                    <td><?php echo htmlspecialchars($item['location_name'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($item['equipment_name'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($item['article_name']); ?></td>
                    <td><?php echo $item['qty_physical_count']; ?></td>
                    <td><?php echo formatCurrency($item['unit_value']); ?></td>
                    <td><?php echo htmlspecialchars($item['accountable_name'] ?? 'N/A'); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function switchType(type) {
    document.getElementById('equipmentType').value = type;
    document.getElementById('tab-semi').className = 'btn ' + (type == 'semi' ? 'btn-primary' : '');
    document.getElementById('tab-ppe').className = 'btn ' + (type == 'ppe' ? 'btn-primary' : '');
    
    // Update property number prefix
    updatePropertyNo();
}

function togglePropertyNo() {
    const auto = document.getElementById('propertyNoAuto').value;
    const propertyNo = document.getElementById('propertyNo');
    
    if (auto == 'auto') {
        propertyNo.readOnly = true;
        updatePropertyNo();
    } else {
        propertyNo.readOnly = false;
        propertyNo.value = '';
        propertyNo.focus();
    }
}

function updatePropertyNo() {
    const type = document.getElementById('equipmentType').value;
    const auto = document.getElementById('propertyNoAuto').value;
    
    if (auto == 'auto') {
        const prefix = type == 'semi' ? 'SEMI' : 'PPE';
        const year = new Date().getFullYear();
        const random = Math.floor(1000 + Math.random() * 9000);
        const propertyNo = prefix + '-' + year + '-' + random;
        
        document.getElementById('propertyNo').value = propertyNo;
        document.getElementById('barcodePreview').textContent = 'INV-' + propertyNo;
    }
}

function updateEquipmentOptions() {
    const category = document.getElementById('category').value;
    const select = document.getElementById('equipment_id');
    
    for (let i = 0; i < select.options.length; i++) {
        const option = select.options[i];
        if (option.value === '') continue;
        
        const optionCategory = option.getAttribute('data-category');
        if (category && optionCategory && optionCategory.toUpperCase() !== category.toUpperCase()) {
            option.style.display = 'none';
        } else {
            option.style.display = '';
        }
    }
}

function validateForm() {
    const article = document.querySelector('input[name="article"]').value.trim();
    const location = document.querySelector('select[name="location_id"]').value;
    const equipment = document.querySelector('select[name="equipment_id"]').value;
    const category = document.querySelector('select[name="category"]').value;
    const quantity = parseFloat(document.querySelector('input[name="qty_physical"]').value);
    
    if (!article) {
        alert('Please enter article name');
        return false;
    }
    if (!location) {
        alert('Please select a location');
        return false;
    }
    if (!equipment) {
        alert('Please select equipment type');
        return false;
    }
    if (!category) {
        alert('Please select a category');
        return false;
    }
    if (quantity <= 0) {
        alert('Quantity must be greater than 0');
        return false;
    }
    return true;
}

function printBarcode(barcodeData) {
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Print Barcode</title>
            <style>
                body { display: flex; justify-content: center; align-items: center; height: 100vh; font-family: 'Courier New'; }
                .barcode { text-align: center; padding: 40px; border: 2px dashed #333; }
                .code { font-size: 32px; letter-spacing: 3px; margin: 20px 0; }
                @media print { body { margin: 0; } }
            </style>
        </head>
        <body>
            <div class="barcode">
                <h3>Inventory Item</h3>
                <div class="code">${barcodeData}</div>
                <p>Scan this barcode to view item details</p>
            </div>
            <script>
                window.onload = function() { window.print(); window.close(); }
            <\/script>
        </body>
        </html>
    `);
}

function printBarcodePreview() {
    const barcode = document.getElementById('barcodePreview').textContent.trim();
    printBarcode(barcode);
}

// Auto-update property number every 5 seconds if on auto
setInterval(function() {
    if (document.getElementById('propertyNoAuto').value == 'auto') {
        updatePropertyNo();
    }
}, 5000);

// Initial setup
updateEquipmentOptions();
</script>

<style>
.form-group {
    margin-bottom: 15px;
}
.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #2c3e50;
    font-size: 13px;
}
.form-control {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    transition: all 0.3s;
}
.form-control:focus {
    border-color: #3498db;
    outline: none;
    box-shadow: 0 0 0 3px rgba(52,152,219,0.1);
}
.btn {
    transition: all 0.3s;
    cursor: pointer;
}
.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}
.table-container {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
</style>

<?php include '../includes/footer.php'; ?>