<?php
require_once '../includes/header.php';
require_once '../includes/functions.php';
requireRole('admin');

$inventory = getAllInventory();
?>
<?php include '../includes/sidebar.php'; ?>

<div style="padding: 20px;">
    <div class="table-header" style="justify-content: space-between; margin-bottom: 20px;">
        <h1>Inventory List</h1>
        <a href="add_inventory.php" class="btn btn-success">
            <i class="fas fa-plus"></i> Add New Item
        </a>
    </div>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Property No</th>
                    <th>Article Name</th>
                    <th>Category</th>
                    <th>Quantity</th>
                    <th>Location</th>
                    <th>Unit Value</th>
                    <th>Date Added</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($inventory as $item): ?>
                <tr>
                    <td><?php echo $item['id']; ?></td>
                    <td><?php echo htmlspecialchars($item['property_no']); ?></td>
                    <td><?php echo htmlspecialchars($item['article_name'] ?: $item['description']); ?></td>
                    <td><?php echo htmlspecialchars($item['category']); ?></td>
                    <td><?php echo $item['qty_physical_count']; ?></td>
                    <td><?php echo htmlspecialchars($item['location_name'] ?? 'N/A'); ?></td>
                    <td><?php echo formatCurrency($item['unit_value']); ?></td>
                    <td><?php echo formatDate($item['date_added']); ?></td>
                    <td>
                        <a href="view_inventory.php?id=<?php echo $item['id']; ?>" class="btn btn-info btn-sm">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="edit_inventory.php?id=<?php echo $item['id']; ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="delete_inventory.php?id=<?php echo $item['id']; ?>" 
                           class="btn btn-danger btn-sm" 
                           onclick="return confirm('Are you sure?')">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>