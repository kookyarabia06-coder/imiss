<?php
require_once '../includes/auth.php';
requireRole('user');

$type = $_GET['type'] ?? 'all';
$format = $_GET['format'] ?? 'csv';

// Build query based on type
$query = "SELECT 
    i.id,
    i.barcode_data as Barcode,
    i.property_no as 'Property No',
    CONCAT(b.name, ' (', b.floor, 'F) - ', d.name) as Location,
    i.type_equipment as 'Equipment Type',
    e.name as 'Equipment',
    i.category as Category,
    i.article_name as Article,
    i.description as Description,
    i.qty_property_card as 'Qty (Prop)',
    i.qty_physical_count as 'Qty (Phy)',
    i.unit_value as 'Unit Value',
    (i.qty_physical_count * i.unit_value) as 'Total Value',
    i.uom as 'UOM',
    CONCAT(u1.firstname, ' ', u1.lastname) as Accountable,
    CONCAT(u2.firstname, ' ', u2.lastname) as 'Certified Correct',
    CONCAT(u3.firstname, ' ', u3.lastname) as Approved,
    CONCAT(u4.firstname, ' ', u4.lastname) as Verified,
    i.fund_cluster as 'Fund Cluster',
    i.condition_text as Condition,
    i.remarks as Remarks,
    i.date_added as 'Date Added'
FROM inventory i
LEFT JOIN departments d ON i.location_id = d.id
LEFT JOIN buildings b ON d.building_id = b.id
LEFT JOIN equipment e ON i.equipment_id = e.id
LEFT JOIN users u1 ON i.allocate_to = u1.id
LEFT JOIN users u2 ON FIND_IN_SET(u2.id, i.certified_correct)
LEFT JOIN users u3 ON i.approved_by = u3.id
LEFT JOIN users u4 ON i.verified_by = u4.id";

if ($type != 'all') {
    $query .= " WHERE i.type_equipment = '" . mysqli_real_escape_string($conn, $type) . "'";
}

$query .= " ORDER BY i.date_added DESC";

$result = $conn->query($query);

if ($format == 'csv') {
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="inventory_' . $type . '_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add headers
    if ($row = $result->fetch_assoc()) {
        fputcsv($output, array_keys($row));
        
        // Add data rows
        do {
            fputcsv($output, $row);
        } while ($row = $result->fetch_assoc());
    }
    
    fclose($output);
} elseif ($format == 'excel') {
    // For Excel, we'll output HTML table with .xls extension
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="inventory_' . $type . '_' . date('Y-m-d') . '.xls"');
    
    echo '<table border="1">';
    
    // Headers
    if ($row = $result->fetch_assoc()) {
        echo '<tr>';
        foreach (array_keys($row) as $header) {
            echo '<th>' . htmlspecialchars($header) . '</th>';
        }
        echo '</tr>';
        
        // Data rows
        do {
            echo '<tr>';
            foreach ($row as $value) {
                echo '<td>' . htmlspecialchars($value ?? '') . '</td>';
            }
            echo '</tr>';
        } while ($row = $result->fetch_assoc());
    }
    
    echo '</table>';
}

exit();
?>