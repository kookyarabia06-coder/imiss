<?php
require_once '../includes/header.php';
require_once '../includes/functions.php';
requireRole('user');

$user_id = $_SESSION['user_id'];

// Get user's inventory items
$items = $conn->query("SELECT i.*, d.name as location_name 
                       FROM inventory i 
                       LEFT JOIN departments d ON i.location_id = d.id 
                       WHERE i.approved_by = $user_id OR i.verified_by = $user_id
                       ORDER BY i.date_added DESC");
?>
<?php include '../includes/sidebar.php'; ?>

<div style="padding: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h1 style="color: #2c3e50;">My Inventory Items</h1>
        <a href="add_inventory.php" class="btn btn-success">
            <i class="fas fa-plus"></i> Add New Item
        </a>
    </div>
    
    <div class="table-container">
        <div class="table-header">
            <h3 class="table-title">Items I've Added</h3>
            <input type="text" id="searchInput" placeholder="Search items..." 
                   style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        
        <table id="inventoryTable">
            <thead>
                <tr>
                    <th>Property No</th>
                    <th>Article Name</th>
                    <th>Category</th>
                    <th>Quantity</th>
                    <th>Unit Value</th>
                    <th>Total Value</th>
                    <th>Location</th>
                    <th>Date Added</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($items->num_rows > 0): ?>
                    <?php while ($item = $items->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['property_no']); ?></td>
                        <td><?php echo htmlspecialchars($item['article_name']); ?></td>
                        <td><?php echo htmlspecialchars($item['category']); ?></td>
                        <td><?php echo $item['qty_physical_count']; ?></td>
                        <td><?php echo formatCurrency($item['unit_value']); ?></td>
                        <td><?php echo formatCurrency($item['qty_physical_count'] * $item['unit_value']); ?></td>
                        <td><?php echo htmlspecialchars($item['location_name'] ?? 'N/A'); ?></td>
                        <td><?php echo formatDate($item['date_added']); ?></td>
                        <td>
                            <a href="view_inventory.php?id=<?php echo $item['id']; ?>" class="btn btn-info btn-sm">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="edit_inventory.php?id=<?php echo $item['id']; ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="print_barcode.php?id=<?php echo $item['id']; ?>" class="btn btn-warning btn-sm" target="_blank">
                                <i class="fas fa-qrcode"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 30px;">
                            <i class="fas fa-box-open" style="font-size: 48px; color: #ccc; margin-bottom: 10px; display: block;"></i>
                            No inventory items found<br>
                            <a href="add_inventory.php" class="btn btn-success btn-sm" style="margin-top: 10px;">
                                Add Your First Item
                            </a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Search functionality
document.getElementById('searchInput').addEventListener('keyup', function() {
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
</script>

<?php include '../includes/footer.php'; ?>