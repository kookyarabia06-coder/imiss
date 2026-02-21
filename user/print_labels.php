<?php
require_once '../includes/header.php';
require_once '../includes/functions.php';
requireRole('user');

$type = $_GET['type'] ?? 'semi';
$type_label = $type == 'semi' ? 'Semi-expendable Equipment' : 'Property Plant Equipment (50K Above)';

// Get items based on type
$items = $conn->query("
    SELECT i.*, d.name as location_name, e.name as equipment_name
    FROM inventory i
    LEFT JOIN departments d ON i.location_id = d.id
    LEFT JOIN equipment e ON i.equipment_id = e.id
    WHERE i.type_equipment = '$type'
    ORDER BY i.date_added DESC
");
?>
<?php include '../includes/sidebar.php'; ?>

<div style="padding: 20px;">
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h1 style="color: #2c3e50;">
            <i class="fas fa-print"></i> Print Labels - <?php echo $type_label; ?>
        </h1>
        <a href="<?php echo $type == 'semi' ? 'semi_expendable.php' : 'ppe_equipment.php'; ?>" class="btn btn-primary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>
    
    <!-- Print Options -->
    <div class="table-container" style="margin-bottom: 20px;">
        <div style="display: flex; gap: 20px; align-items: center;">
            <div>
                <label style="display: block; margin-bottom: 5px;">Label Size</label>
                <select id="labelSize" class="form-control" style="width: 150px;">
                    <option value="small">Small (2x1 inch)</option>
                    <option value="medium" selected>Medium (3x2 inch)</option>
                    <option value="large">Large (4x3 inch)</option>
                </select>
            </div>
            <div>
                <label style="display: block; margin-bottom: 5px;">Copies per Item</label>
                <input type="number" id="copies" class="form-control" value="1" min="1" max="10" style="width: 100px;">
            </div>
            <div>
                <label style="display: block; margin-bottom: 5px;">Layout</label>
                <select id="layout" class="form-control" style="width: 150px;">
                    <option value="single">Single Column</option>
                    <option value="double" selected>Double Column</option>
                    <option value="triple">Triple Column</option>
                </select>
            </div>
            <div style="margin-left: auto;">
                <button onclick="selectAll()" class="btn btn-info">
                    <i class="fas fa-check-double"></i> Select All
                </button>
                <button onclick="deselectAll()" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Deselect All
                </button>
                <button onclick="printSelected()" class="btn btn-success">
                    <i class="fas fa-print"></i> Print Selected
                </button>
            </div>
        </div>
    </div>
    
    <!-- Items Grid -->
    <div class="table-container">
        <div class="table-header">
            <h3 class="table-title"><i class="fas fa-boxes"></i> Select Items to Print</h3>
            <div>
                <input type="text" id="searchInput" placeholder="Search items..." 
                       style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; width: 250px;">
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px; margin-top: 20px;" id="itemsGrid">
            <?php while ($item = $items->fetch_assoc()): ?>
            <div class="item-card" data-id="<?php echo $item['id']; ?>" data-search="<?php echo strtolower($item['article_name'] . ' ' . $item['property_no'] . ' ' . ($item['description'] ?? '')); ?>">
                <div style="display: flex; align-items: start; gap: 10px;">
                    <input type="checkbox" class="item-checkbox" value="<?php echo $item['id']; ?>" 
                           data-property="<?php echo htmlspecialchars($item['property_no']); ?>"
                           data-barcode="<?php echo htmlspecialchars($item['barcode_data']); ?>"
                           data-article="<?php echo htmlspecialchars($item['article_name']); ?>"
                           data-location="<?php echo htmlspecialchars($item['location_name'] ?? 'N/A'); ?>"
                           data-equipment="<?php echo htmlspecialchars($item['equipment_name'] ?? 'N/A'); ?>">
                    <div style="flex: 1;">
                        <div style="display: flex; justify-content: space-between;">
                            <strong><?php echo htmlspecialchars($item['property_no']); ?></strong>
                            <span class="badge <?php echo $type == 'semi' ? 'badge-info' : 'badge-warning'; ?>">
                                <?php echo $type == 'semi' ? 'Semi' : 'PPE'; ?>
                            </span>
                        </div>
                        <div style="font-size: 14px; margin: 5px 0;"><?php echo htmlspecialchars($item['article_name']); ?></div>
                        <div style="font-size: 12px; color: #7f8c8d;">
                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($item['location_name'] ?? 'N/A'); ?><br>
                            <i class="fas fa-barcode"></i> <?php echo htmlspecialchars($item['barcode_data']); ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        
        <!-- Barcode Preview Modal -->
        <div id="printPreview" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; overflow: auto;">
            <div style="background: white; width: 90%; max-width: 1200px; margin: 30px auto; padding: 30px; border-radius: 8px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
                    <h2>Print Preview</h2>
                    <button onclick="closePreview()" class="btn btn-danger">Close</button>
                </div>
                <div id="previewContent" style="min-height: 500px;"></div>
                <div style="text-align: center; margin-top: 20px;">
                    <button onclick="window.print()" class="btn btn-success btn-lg">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let selectedItems = [];

function selectAll() {
    document.querySelectorAll('.item-checkbox').forEach(cb => {
        cb.checked = true;
        addToSelected(cb);
    });
}

function deselectAll() {
    document.querySelectorAll('.item-checkbox').forEach(cb => {
        cb.checked = false;
    });
    selectedItems = [];
}

function addToSelected(checkbox) {
    if (checkbox.checked) {
        selectedItems.push({
            id: checkbox.value,
            property: checkbox.dataset.property,
            barcode: checkbox.dataset.barcode,
            article: checkbox.dataset.article,
            location: checkbox.dataset.location,
            equipment: checkbox.dataset.equipment
        });
    } else {
        selectedItems = selectedItems.filter(item => item.id != checkbox.value);
    }
}

document.querySelectorAll('.item-checkbox').forEach(cb => {
    cb.addEventListener('change', function() {
        addToSelected(this);
    });
});

// Search functionality
document.getElementById('searchInput').addEventListener('keyup', function() {
    const searchTerm = this.value.toLowerCase();
    document.querySelectorAll('.item-card').forEach(card => {
        const searchText = card.dataset.search;
        if (searchText.includes(searchTerm)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
});

function printSelected() {
    if (selectedItems.length === 0) {
        alert('Please select at least one item to print');
        return;
    }
    
    const labelSize = document.getElementById('labelSize').value;
    const copies = parseInt(document.getElementById('copies').value);
    const layout = document.getElementById('layout').value;
    
    let previewHtml = '<div style="font-family: Arial; padding: 20px;">';
    
    if (layout === 'double') {
        previewHtml += '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">';
    } else if (layout === 'triple') {
        previewHtml += '<div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">';
    } else {
        previewHtml += '<div style="display: flex; flex-direction: column; gap: 15px;">';
    }
    
    // Generate labels for each selected item
    for (let i = 0; i < copies; i++) {
        selectedItems.forEach(item => {
            previewHtml += generateLabel(item, labelSize);
        });
    }
    
    previewHtml += '</div></div>';
    
    document.getElementById('previewContent').innerHTML = previewHtml;
    document.getElementById('printPreview').style.display = 'block';
}

function generateLabel(item, size) {
    const styles = {
        small: 'padding: 5px; font-size: 10px;',
        medium: 'padding: 10px; font-size: 12px;',
        large: 'padding: 15px; font-size: 14px;'
    };
    
    return `
        <div style="border: 1px solid #333; ${styles[size]} border-radius: 4px; background: white;">
            <div style="text-align: center; margin-bottom: 5px;">
                <strong>${item.property}</strong>
            </div>
            <div style="font-family: 'Courier New'; font-size: ${size === 'small' ? '14px' : size === 'medium' ? '18px' : '22px'}; 
                        letter-spacing: 2px; text-align: center; margin: 5px 0;">
                ${item.barcode}
            </div>
            <div style="text-align: center; font-size: ${size === 'small' ? '9px' : size === 'medium' ? '11px' : '13px'};">
                ${item.article}<br>
                ${item.location}
            </div>
            <div style="text-align: center; margin-top: 5px; font-size: 8px; color: #7f8c8d;">
                ${item.equipment}
            </div>
        </div>
    `;
}

function closePreview() {
    document.getElementById('printPreview').style.display = 'none';
}


document.addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.key === 'a') {
        e.preventDefault();
        selectAll();
    }
});
</script>

<style>
.item-card {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 6px;
    border: 1px solid #ecf0f1;
    transition: all 0.3s;
}
.item-card:hover {
    border-color: #3498db;
    box-shadow: 0 2px 10px rgba(52,152,219,0.1);
}
.item-checkbox {
    width: 20px;
    height: 20px;
    margin-top: 3px;
    cursor: pointer;
}
.badge-info {
    background: #d1ecf1;
    color: #0c5460;
}
.badge-warning {
    background: #fff3cd;
    color: #856404;
}
@media print {
    body * {
        visibility: hidden;
    }
    #printPreview, #printPreview * {
        visibility: visible;
    }
    #printPreview {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        background: white;
    }
    .btn {
        display: none;
    }
}
</style>

<?php include '../includes/footer.php'; ?>