<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/header.php';
require_once '../includes/functions.php';
requireRole('user');

$message = '';
$error = '';

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

$equipment = $conn->query("SELECT * FROM equipment ORDER BY category, name");

if (!$equipment) {
    die("Error fetching equipment: " . $conn->error);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_add'])) {
    $items = json_decode($_POST['items_data'], true);
    $success_count = 0;
    $failed_items = [];
    
    if (is_array($items)) {
        foreach ($items as $item) {
            $article = mysqli_real_escape_string($conn, $item['article']);
            $description = mysqli_real_escape_string($conn, $item['description'] ?? '');
            $location_id = (int)$item['location_id'];
            $equipment_id = (int)$item['equipment_id'];
            $category = mysqli_real_escape_string($conn, $item['category']);
            $quantity = (float)$item['quantity'];
            $unit_value = (float)$item['unit_value'];
            $type = mysqli_real_escape_string($conn, $item['type']);
            
            // Generate property number
            $prefix = $type == 'semi' ? 'SEMI' : 'PPE';
            $year = date('Y');
            $random = rand(1000, 9999);
            $property_no = $prefix . '-' . $year . '-' . $random;
            $barcode_data = 'INV-' . $property_no;
            
            $query = "INSERT INTO inventory (
                article_name, description, property_no, location_id, equipment_id,
                category, uom, qty_physical_count, unit_value, barcode_data, 
                type_equipment, date_added, approved_by
            ) VALUES (
                '$article', '$description', '$property_no', $location_id, $equipment_id,
                '$category', 'Unit', $quantity, $unit_value, '$barcode_data',
                '$type', NOW(), {$_SESSION['user_id']}
            )";
            
            if ($conn->query($query)) {
                $success_count++;
            } else {
                $failed_items[] = $article;
            }
        }
    }
    
    if ($success_count > 0) {
        $message = "<div class='alert alert-success'>Successfully added $success_count items!</div>";
        if (!empty($failed_items)) {
            $message .= "<div class='alert alert-warning'>Failed items: " . implode(', ', $failed_items) . "</div>";
        }
    } else {
        $error = "No items were added. Please check your data.";
    }
}
?>
<?php include '../includes/sidebar.php'; ?>

<div style="padding: 20px; max-width: 1200px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h1 style="color: #2c3e50;">Bulk Add Inventory Items</h1>
        <a href="dashboard.php" class="btn btn-primary">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
    
    <?php echo $message; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="table-container">
        <div class="table-header">
            <h3 class="table-title"><i class="fas fa-layer-group"></i> Add Multiple Items</h3>
            <div>
                <button onclick="addRow()" class="btn btn-success btn-sm">
                    <i class="fas fa-plus"></i> Add Row
                </button>
                <button onclick="submitBulk()" class="btn btn-primary btn-sm">
                    <i class="fas fa-save"></i> Save All
                </button>
                <button onclick="clearAll()" class="btn btn-danger btn-sm">
                    <i class="fas fa-trash"></i> Clear All
                </button>
            </div>
        </div>
        
        <form method="POST" id="bulkForm">
            <input type="hidden" name="bulk_add" value="1">
            <input type="hidden" name="items_data" id="itemsData">
            
            <div style="overflow-x: auto; max-height: 500px; overflow-y: auto;">
                <table id="bulkTable" style="width: 100%; border-collapse: collapse;">
                    <thead style="position: sticky; top: 0; background: #34495e; color: white; z-index: 10;">
                        <tr>
                            <th style="padding: 10px;">Type</th>
                            <th style="padding: 10px;">Article</th>
                            <th style="padding: 10px;">Description</th>
                            <th style="padding: 10px;">Category</th>
                            <th style="padding: 10px;">Equipment</th>
                            <th style="padding: 10px;">Location</th>
                            <th style="padding: 10px;">Qty</th>
                            <th style="padding: 10px;">Unit Value</th>
                            <th style="padding: 10px;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <tr class="item-row">
                            <td>
                                <select class="form-control type-select" style="width: 80px;">
                                    <option value="semi">Semi</option>
                                    <option value="ppe">PPE</option>
                                </select>
                            </td>
                            <td>
                                <input type="text" class="form-control article-input" placeholder="Article name" style="width: 150px;">
                            </td>
                            <td>
                                <input type="text" class="form-control desc-input" placeholder="Description" style="width: 150px;">
                            </td>
                            <td>
                                <select class="form-control category-select" style="width: 120px;">
                                    <option value="MEDICAL">Medical</option>
                                    <option value="ICT">ICT</option>
                                    <option value="OFFICE">Office</option>
                                    <option value="DRRM">DRRM</option>
                                    <option value="LABORATORY">Laboratory</option>
                                    <option value="COMMUNICATION">Communication</option>
                                </select>
                            </td>
                            <td>
                                <select class="form-control equipment-select" style="width: 150px;">
                                    <option value="">Select</option>
                                    <?php 
                                    $equipment->data_seek(0);
                                    while ($eq = $equipment->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $eq['id']; ?>"><?php echo htmlspecialchars($eq['name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </td>
                            <td>
                                <select class="form-control location-select" style="width: 180px;">
                                    <option value="">Select</option>
                                    <?php 
                                    $locations->data_seek(0);
                                    while ($loc = $locations->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $loc['id']; ?>">
                                            <?php echo htmlspecialchars($loc['building_name'] . ' - ' . $loc['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </td>
                            <td>
                                <input type="number" class="form-control qty-input" value="1" min="1" style="width: 70px;">
                            </td>
                            <td>
                                <input type="number" class="form-control value-input" value="0.00" min="0" step="0.01" style="width: 100px;">
                            </td>
                            <td>
                                <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div style="margin-top: 20px; text-align: right; padding: 10px; background: #f8f9fa; border-radius: 4px;">
                <span id="rowCount">1</span> item(s) ready to add
                <button type="button" onclick="submitBulk()" class="btn btn-success" style="margin-left: 20px;">
                    <i class="fas fa-save"></i> Save All Items
                </button>
            </div>
        </form>
    </div>
    
    <!-- Instructions -->
    <div class="table-container" style="margin-top: 20px;">
        <h4><i class="fas fa-info-circle"></i> Instructions</h4>
        <ul style="margin-left: 20px; line-height: 1.8;">
            <li>Fill in the details for each item you want to add</li>
            <li>Click "Add Row" to add more items</li>
            <li>PPE items must have a unit value of ₱50,000 or above</li>
            <li>All items will get auto-generated property numbers and barcodes</li>
            <li>Click "Save All" when you're done</li>
        </ul>
    </div>
</div>

<!-- Template for new row -->
<template id="rowTemplate">
    <tr class="item-row">
        <td>
            <select class="form-control type-select" style="width: 80px;">
                <option value="semi">Semi</option>
                <option value="ppe">PPE</option>
            </select>
        </td>
        <td>
            <input type="text" class="form-control article-input" placeholder="Article name" style="width: 150px;">
        </td>
        <td>
            <input type="text" class="form-control desc-input" placeholder="Description" style="width: 150px;">
        </td>
        <td>
            <select class="form-control category-select" style="width: 120px;">
                <option value="MEDICAL">Medical</option>
                <option value="ICT">ICT</option>
                <option value="OFFICE">Office</option>
                <option value="DRRM">DRRM</option>
                <option value="LABORATORY">Laboratory</option>
                <option value="COMMUNICATION">Communication</option>
            </select>
        </td>
        <td>
            <select class="form-control equipment-select" style="width: 150px;">
                <option value="">Select</option>
                <?php 
                $equipment->data_seek(0);
                while ($eq = $equipment->fetch_assoc()): 
                ?>
                    <option value="<?php echo $eq['id']; ?>"><?php echo htmlspecialchars($eq['name']); ?></option>
                <?php endwhile; ?>
            </select>
        </td>
        <td>
            <select class="form-control location-select" style="width: 180px;">
                <option value="">Select</option>
                <?php 
                $locations->data_seek(0);
                while ($loc = $locations->fetch_assoc()): 
                ?>
                    <option value="<?php echo $loc['id']; ?>">
                        <?php echo htmlspecialchars($loc['building_name'] . ' - ' . $loc['name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </td>
        <td>
            <input type="number" class="form-control qty-input" value="1" min="1" style="width: 70px;">
        </td>
        <td>
            <input type="number" class="form-control value-input" value="0.00" min="0" step="0.01" style="width: 100px;">
        </td>
        <td>
            <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    </tr>
</template>

<script>
function addRow() {
    const template = document.getElementById('rowTemplate');
    const clone = template.content.cloneNode(true);
    document.getElementById('tableBody').appendChild(clone);
    updateRowCount();
}

function removeRow(btn) {
    const rows = document.querySelectorAll('.item-row');
    if (rows.length > 1) {
        btn.closest('tr').remove();
    } else {
        alert('At least one row is required');
        // Clear the first row instead of removing it
        const firstRow = rows[0];
        firstRow.querySelector('.article-input').value = '';
        firstRow.querySelector('.desc-input').value = '';
        firstRow.querySelector('.equipment-select').value = '';
        firstRow.querySelector('.location-select').value = '';
    }
    updateRowCount();
}

function clearAll() {
    if (confirm('Clear all rows? This cannot be undone.')) {
        const tbody = document.getElementById('tableBody');
        tbody.innerHTML = '';
        // Add one empty row
        addRow();
    }
}

function updateRowCount() {
    const count = document.querySelectorAll('.item-row').length;
    document.getElementById('rowCount').textContent = count;
}

function submitBulk() {
    const rows = document.querySelectorAll('.item-row');
    const items = [];
    let isValid = true;
    let errorMessage = '';
    
    rows.forEach((row, index) => {
        const type = row.querySelector('.type-select').value;
        const article = row.querySelector('.article-input').value.trim();
        const category = row.querySelector('.category-select').value;
        const equipment = row.querySelector('.equipment-select').value;
        const location = row.querySelector('.location-select').value;
        const quantity = row.querySelector('.qty-input').value;
        const value = parseFloat(row.querySelector('.value-input').value);
        
        if (!article) {
            errorMessage = `Row ${index + 1}: Please enter article name`;
            isValid = false;
            return;
        }
        if (!equipment) {
            errorMessage = `Row ${index + 1}: Please select equipment type`;
            isValid = false;
            return;
        }
        if (!location) {
            errorMessage = `Row ${index + 1}: Please select location`;
            isValid = false;
            return;
        }
        if (type === 'ppe' && value < 50000) {
            errorMessage = `Row ${index + 1}: PPE items must have value of ₱50,000 or above`;
            isValid = false;
            return;
        }
        
        items.push({
            type: type,
            article: article,
            description: row.querySelector('.desc-input').value,
            category: category,
            equipment_id: equipment,
            location_id: location,
            quantity: parseFloat(quantity),
            unit_value: value
        });
    });
    
    if (!isValid) {
        alert(errorMessage);
        return;
    }
    
    if (items.length === 0) {
        alert('No items to add');
        return;
    }
    
    if (confirm(`Add ${items.length} items to inventory?`)) {
        document.getElementById('itemsData').value = JSON.stringify(items);
        document.getElementById('bulkForm').submit();
    }
}

// Validate PPE value on change
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('value-input')) {
        const row = e.target.closest('tr');
        const type = row.querySelector('.type-select').value;
        const value = parseFloat(e.target.value);
        
        if (type === 'ppe' && value < 50000) {
            e.target.style.borderColor = '#e74c3c';
            e.target.setAttribute('title', 'PPE items must be ₱50,000 or above');
        } else {
            e.target.style.borderColor = '#ddd';
            e.target.removeAttribute('title');
        }
    }
});

// Initialize
updateRowCount();
</script>

<style>
.table-container {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    margin-bottom: 20px;
}
.form-control {
    padding: 6px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 13px;
}
.form-control:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 2px rgba(52,152,219,0.1);
}
.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    font-size: 13px;
}
.btn-sm {
    padding: 4px 8px;
    font-size: 12px;
}
.btn-primary { background: #3498db; color: white; }
.btn-success { background: #27ae60; color: white; }
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
.alert-warning {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeeba;
}
</style>

<?php include '../includes/footer.php'; ?>