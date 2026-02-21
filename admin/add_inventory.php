<?php
require_once '../includes/header.php';
require_once '../includes/functions.php';
requireRole('admin');

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Generate property number
    $property_no = $_POST['category'] . '-' . date('Ymd') . '-' . rand(100, 999);
    
    // Insert into database
    $article_name = mysqli_real_escape_string($conn, $_POST['article_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $uom = mysqli_real_escape_string($conn, $_POST['uom']);
    $quantity = (float)$_POST['quantity'];
    $unit_value = (float)$_POST['unit_value'];
    $location_id = (int)$_POST['location_id'];
    $condition_text = mysqli_real_escape_string($conn, $_POST['condition_text']);
    
    // Generate barcode
    $barcode_data = 'INV-' . $property_no;
    
    $query = "INSERT INTO inventory (
        article_name, description, property_no, category, uom, 
        qty_physical_count, unit_value, location_id, condition_text, barcode_data
    ) VALUES (
        '$article_name', '$description', '$property_no', '$category', '$uom',
        $quantity, $unit_value, $location_id, '$condition_text', '$barcode_data'
    )";
    
    if ($conn->query($query)) {
        $id = $conn->insert_id;
        
        // Log activity
        $date = date('Y-m-d H:i:s');
        $conn->query("INSERT INTO activity_log (user_id, action, item_id, details, date_created) 
                      VALUES ({$_SESSION['user_id']}, 'add', $id, 'Added inventory item', '$date')");
        
        $message = "Inventory added successfully! Property No: $property_no";
    } else {
        $error = "Error: " . $conn->error;
    }
}

// Get locations for dropdown
$locations = $conn->query("SELECT * FROM departments ORDER BY name");
?>
<?php include '../includes/sidebar.php'; ?>

<div style="padding: 20px; max-width: 800px;">
    <h1 style="margin-bottom: 20px;">Add Inventory Item</h1>
    
    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="table-container">
        <form method="POST">
            <div class="form-group">
                <label>Article Name *</label>
                <input type="text" name="article_name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control" rows="3"></textarea>
            </div>
            
            <div class="form-group">
                <label>Category *</label>
                <select name="category" class="form-control" required>
                    <option value="">Select Category</option>
                    <option value="MEDICAL">Medical</option>
                    <option value="ICT">ICT</option>
                    <option value="OFFICE">Office</option>
                    <option value="DRRM">DRRM</option>
                    <option value="GENERAL">General</option>
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
                </select>
            </div>
            
            <div class="form-group">
                <label>Quantity *</label>
                <input type="number" name="quantity" class="form-control" min="0" step="0.01" required>
            </div>
            
            <div class="form-group">
                <label>Unit Value (₱) *</label>
                <input type="number" name="unit_value" class="form-control" min="0" step="0.01" required>
            </div>
            
            <div class="form-group">
                <label>Location</label>
                <select name="location_id" class="form-control">
                    <option value="">Select Location</option>
                    <?php while ($loc = $locations->fetch_assoc()): ?>
                        <option value="<?php echo $loc['id']; ?>">
                            <?php echo htmlspecialchars($loc['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Condition</label>
                <select name="condition_text" class="form-control">
                    <option value="Serviceable">Serviceable</option>
                    <option value="Unserviceable">Unserviceable</option>
                    <option value="For Repair">For Repair</option>
                    <option value="For Disposal">For Disposal</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-success">Add Inventory</button>
            <a href="inventory.php" class="btn" style="background: #95a5a6; color: white;">Cancel</a>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>