<?php
require_once '../includes/header.php';
require_once '../includes/functions.php';
requireRole('user');

$id = (int)$_GET['id'];
$item = $conn->query("SELECT i.*, d.name as location_name, s.name as section_name,
                      u1.firstname as approved_first, u1.lastname as approved_last,
                      u2.firstname as verified_first, u2.lastname as verified_last
                      FROM inventory i 
                      LEFT JOIN departments d ON i.location_id = d.id
                      LEFT JOIN sections s ON i.section_id = s.id
                      LEFT JOIN users u1 ON i.approved_by = u1.id
                      LEFT JOIN users u2 ON i.verified_by = u2.id
                      WHERE i.id = $id")->fetch_assoc();

if (!$item) {
    header('Location: my_inventory.php');
    exit();
}
?>
<?php include '../includes/sidebar.php'; ?>

<div style="padding: 20px; max-width: 900px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h1 style="color: #2c3e50;">Inventory Item Details</h1>
        <div>
            <a href="my_inventory.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
            <a href="edit_inventory.php?id=<?php echo $id; ?>" class="btn btn-warning">
                <i class="fas fa-edit"></i> Edit
            </a>
        </div>
    </div>
    
    <div class="table-container">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
            <!-- Item Details -->
            <div>
                <h4 style="margin-bottom: 15px;">Basic Information</h4>
                <table style="width: 100%;">
                    <tr>
                        <th style="width: 40%;">Property No:</th>
                        <td><strong><?php echo htmlspecialchars($item['property_no']); ?></strong></td>
                    </tr>
                    <tr>
                        <th>Article Name:</th>
                        <td><?php echo htmlspecialchars($item['article_name']); ?></td>
                    </tr>
                    <tr>
                        <th>Description:</th>
                        <td><?php echo htmlspecialchars($item['description'] ?: 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Category:</th>
                        <td><?php echo htmlspecialchars($item['category']); ?></td>
                    </tr>
                    <tr>
                        <th>Unit of Measure:</th>
                        <td><?php echo htmlspecialchars($item['uom']); ?></td>
                    </tr>
                </table>
                
                <h4 style="margin: 20px 0 15px;">Quantity & Value</h4>
                <table style="width: 100%;">
                    <tr>
                        <th style="width: 40%;">Quantity:</th>
                        <td><strong><?php echo $item['qty_physical_count']; ?></strong></td>
                    </tr>
                    <tr>
                        <th>Unit Value:</th>
                        <td><?php echo formatCurrency($item['unit_value']); ?></td>
                    </tr>
                    <tr>
                        <th>Total Value:</th>
                        <td><strong><?php echo formatCurrency($item['qty_physical_count'] * $item['unit_value']); ?></strong></td>
                    </tr>
                </table>
            </div>
            
            <!-- Location and Status -->
            <div>
                <h4 style="margin-bottom: 15px;">Location & Status</h4>
                <table style="width: 100%;">
                    <tr>
                        <th style="width: 40%;">Location:</th>
                        <td><?php echo htmlspecialchars($item['location_name'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Section:</th>
                        <td><?php echo htmlspecialchars($item['section_name'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Condition:</th>
                        <td>
                            <span class="badge <?php 
                                echo $item['condition_text'] == 'Serviceable' ? 'badge-success' : 
                                    ($item['condition_text'] == 'Unserviceable' ? 'badge-danger' : 'badge-warning'); 
                            ?>">
                                <?php echo htmlspecialchars($item['condition_text']); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Fund Cluster:</th>
                        <td><?php echo htmlspecialchars($item['fund_cluster'] ?: 'N/A'); ?></td>
                    </tr>
                </table>
                
                <h4 style="margin: 20px 0 15px;">Approval Information</h4>
                <table style="width: 100%;">
                    <tr>
                        <th style="width: 40%;">Approved By:</th>
                        <td><?php echo $item['approved_first'] ? $item['approved_first'] . ' ' . $item['approved_last'] : 'N/A'; ?></td>
                    </tr>
                    <tr>
                        <th>Verified By:</th>
                        <td><?php echo $item['verified_first'] ? $item['verified_first'] . ' ' . $item['verified_last'] : 'N/A'; ?></td>
                    </tr>
                    <tr>
                        <th>Date Added:</th>
                        <td><?php echo formatDate($item['date_added'], 'F d, Y h:i A'); ?></td>
                    </tr>
                    <tr>
                        <th>Last Updated:</th>
                        <td><?php echo $item['date_updated'] ? formatDate($item['date_updated'], 'F d, Y h:i A') : 'Never'; ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Remarks -->
        <?php if ($item['remarks']): ?>
        <div style="margin-top: 30px;">
            <h4>Remarks</h4>
            <div style="background: #f8f9fa; padding: 15px; border-radius: 6px;">
                <?php echo nl2br(htmlspecialchars($item['remarks'])); ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Barcode -->
        <div style="margin-top: 30px; text-align: center;">
            <h4>Barcode</h4>
            <div class="barcode-container">
                <div style="font-family: 'Courier New'; font-size: 24px; letter-spacing: 2px;">
                    <?php echo htmlspecialchars($item['barcode_data']); ?>
                </div>
                <p style="margin-top: 10px; color: #7f8c8d;">
                    <i class="fas fa-info-circle"></i> 
                    Scan this barcode to quickly find this item
                </p>
                <button onclick="window.print()" class="btn btn-primary btn-sm">
                    <i class="fas fa-print"></i> Print Barcode
                </button>
            </div>
        </div>
    </div>
</div>

<style>
table td, table th {
    padding: 10px;
    border-bottom: 1px solid #eee;
}
th {
    color: #7f8c8d;
    font-weight: 500;
}
</style>

<?php include '../includes/footer.php'; ?>