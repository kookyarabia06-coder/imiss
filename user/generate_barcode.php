<?php
require_once '../includes/header.php';
require_once '../includes/functions.php';
requireRole('user');

$type = $_GET['type'] ?? 'semi';
$message = '';

// Generate barcodes for items without them
if (isset($_POST['generate'])) {
    $items = $conn->query("SELECT id, property_no FROM inventory WHERE barcode_data IS NULL OR barcode_data = ''");
    
    $count = 0;
    while ($item = $items->fetch_assoc()) {
        $barcode = 'INV-' . $item['property_no'];
        $conn->query("UPDATE inventory SET barcode_data = '$barcode' WHERE id = {$item['id']}");
        $count++;
    }
    
    $message = "<div class='alert alert-success'>Generated $count barcodes successfully!</div>";
}

// Get items without barcodes
$no_barcode = $conn->query("
    SELECT i.*, d.name as location_name 
    FROM inventory i
    LEFT JOIN departments d ON i.location_id = d.id
    WHERE i.barcode_data IS NULL OR i.barcode_data = ''
    ORDER BY i.date_added DESC
");
?>
<?php include '../includes/sidebar.php'; ?>

<div style="padding: 20px;">
    <h1 style="margin-bottom: 20px;">Generate Missing Barcodes</h1>
    
    <?php echo $message; ?>
    
    <div class="table-container">
        <form method="POST">
            <button type="submit" name="generate" class="btn btn-success">
                <i class="fas fa-sync"></i> Generate All Missing Barcodes
            </button>
        </form>
    </div>
    
    <?php if ($no_barcode->num_rows > 0): ?>
    <div class="table-container">
        <h3>Items Missing Barcodes (<?php echo $no_barcode->num_rows; ?>)</h3>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Property No</th>
                    <th>Article</th>
                    <th>Location</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($item = $no_barcode->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $item['id']; ?></td>
                    <td><?php echo htmlspecialchars($item['property_no']); ?></td>
                    <td><?php echo htmlspecialchars($item['article_name']); ?></td>
                    <td><?php echo htmlspecialchars($item['location_name'] ?? 'N/A'); ?></td>
                    <td>
                        <button class="btn btn-primary btn-sm" onclick="generateSingle(<?php echo $item['id']; ?>)">
                            Generate Barcode
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<script>
function generateSingle(id) {
    if (confirm('Generate barcode for this item?')) {
        fetch('ajax/generate_barcode.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + id
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Barcode generated: ' + data.barcode);
                location.reload();
            } else {
                alert('Error: ' + data.error);
            }
        });
    }
}
</script>

<?php include '../includes/footer.php'; ?> 	