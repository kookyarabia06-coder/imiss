<?php
require_once '../includes/header.php';
require_once '../includes/functions.php';
requireRole('user');

$id = (int)$_GET['id'];
$message = '';
$error = '';

// Get item details
$item = $conn->query("SELECT * FROM inventory WHERE id = $id")->fetch_assoc();

if (!$item) {
    header('Location: my_inventory.php');
    exit();
}

// Get locations
$locations = $conn->query("SELECT * FROM departments ORDER BY name");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $article_name = mysqli_real_escape_string($conn, $_POST['article_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description'] ?? '');
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $uom = mysqli_real_escape_string($conn, $_POST['uom']);
    $quantity = (float)$_POST['quantity'];
    $unit_value = (float)$_POST['unit_value'];
    $location_id = !empty($_POST['location_id']) ? (int)$_POST['location_id'] : 'NULL';
    $condition_text = mysqli_real_escape_string($conn, $_POST['condition_text']);
    $remarks = mysqli_real_escape_string($conn, $_POST['remarks'] ?? '');
    
    $query = "UPDATE inventory SET 
              article_name = '$article_name',
              description = '$description',
              category = '$category',
              uom = '$uom',
              qty_physical_count = $quantity,
              unit_value = $unit_value,
              location_id = $location_id,
              condition_text = '$condition_text',
              remarks = '$remarks',
              date_updated = NOW()
              WHERE id = $id";
    
    if ($conn->query($query)) {
        // Log activity
        $date = date('Y-m-d H:i:s');
        $conn->query("INSERT INTO activity_log (user_id, action, item_id, details, date_created) 
                      VALUES ({$_SESSION['user_id']}, 'edit', $id, 'Updated inventory item', '$date')");
        
        $message = "Item updated successfully!";
        $item = $conn->query("SELECT * FROM inventory WHERE id = $id")->fetch_assoc();
    } else {
        $error = "Error: " . $conn->error;
    }
}
?>
<?php include '../includes/sidebar.php'; ?>

<div style="padding: 20px; max-width: 800px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h1 style="color: #2c3e50;">Edit Inventory Item</h1>
        <a href="view_inventory.php?id=<?php echo $id; ?>" class="btn btn-primary">
            <i class="fas fa-arrow-left"></i> Back to Details
        </a>
    </div>
    
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
                <input type="text" name="article_name" class="form-control" 
                       value="<?php echo htmlspecialchars($item['article_name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($item['description']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Category *</label>
                <select name="category" class="form-control" required>
                    <option value="">Select Category</option>
                    <option value="MEDICAL" <?php echo $item['category'] == 'MEDICAL' ? 'selected' : ''; ?>>Medical</option>
                    <option value="PHARMACY" <?php echo $item['category'] == 'PHARMACY' ? 'selected' : ''; ?>>Pharmacy</option>
                    <option value="ICT" <?php echo $item['category'] == 'ICT' ? 'selected' : ''; ?>>ICT</option>
                    <option value="OFFICE" <?php echo $item['category'] == 'OFFICE' ? 'selected' : ''; ?>>Office</option>
                    <option value="FURNITURE" <?php echo $item['category'] == 'FURNITURE' ? 'selected' : ''; ?>>Furniture</option>
                    <option value="DRRM" <?php echo $item['category'] == 'DRRM' ? 'selected' : ''; ?>>DRRM</option>
                    <option value="LABORATORY" <?php echo $item['category'] == 'LABORATORY' ? 'selected' : ''; ?>>Laboratory</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Unit of Measure *</label>
                <select name="uom" class="form-control" required>
                    <option value="Unit" <?php echo $item['uom'] == 'Unit' ? 'selected' : ''; ?>>Unit</option>
                    <option value="Box" <?php echo $item['uom'] == 'Box' ? 'selected' : ''; ?>>Box</option>
                    <option value="Lot" <?php echo $item['uom'] == 'Lot' ? 'selected' : ''; ?>>Lot</option>
                    <option value="Set" <?php echo $item['uom'] == 'Set' ? 'selected' : ''; ?>>Set</option>
                    <option value="Piece" <?php echo $item['uom'] == 'Piece' ? 'selected' : ''; ?>>Piece</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Quantity *</label>
                <input type="number" name="quantity" class="form-control" 
                       value="<?php echo $item['qty_physical_count']; ?>" min="0" step="0.01" required>
            </div>
            
            <div class="form-group">
                <label>Unit Value (₱) *</label>
                <input type="number" name="unit_value" class="form-control" 
                       value="<?php echo $item['unit_value']; ?>" min="0" step="0.01" required>
            </div>
            
            <div class="form-group">
                <label>Location</label>
                <select name="location_id" class="form-control">
                    <option value="">Select Location</option>
                    <?php while ($loc = $locations->fetch_assoc()): ?>
                        <option value="<?php echo $loc['id']; ?>" 
                            <?php echo $item['location_id'] == $loc['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($loc['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Condition</label>
                <select name="condition_text" class="form-control">
                    <option value="Serviceable" <?php echo $item['condition_text'] == 'Serviceable' ? 'selected' : ''; ?>>Serviceable</option>
                    <option value="Unserviceable" <?php echo $item['condition_text'] == 'Unserviceable' ? 'selected' : ''; ?>>Unserviceable</option>
                    <option value="For Repair" <?php echo $item['condition_text'] == 'For Repair' ? 'selected' : ''; ?>>For Repair</option>
                    <option value="For Disposal" <?php echo $item['condition_text'] == 'For Disposal' ? 'selected' : ''; ?>>For Disposal</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Remarks</label>
                <textarea name="remarks" class="form-control" rows="2"><?php echo htmlspecialchars($item['remarks']); ?></textarea>
            </div>
            
            <button type="submit" class="btn btn-success">Update Item</button>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>