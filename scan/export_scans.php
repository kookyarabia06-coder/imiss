<?php
require_once '../includes/auth.php';
requireLogin();

// Get filters
$filter_user = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$filter_date = isset($_GET['date']) ? $_GET['date'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Build query
$where = ["a.action = 'scan'"];
if ($filter_user > 0) {
    $where[] = "a.user_id = $filter_user";
}
if ($filter_date) {
    $where[] = "DATE(a.date_created) = '$filter_date'";
} else {
    $where[] = "DATE(a.date_created) BETWEEN '$start_date' AND '$end_date'";
}
$where_clause = "WHERE " . implode(" AND ", $where);

// Get scan data
$scans = $conn->query("
    SELECT 
        a.date_created,
        u.username,
        u.role,
        i.article_name,
        i.property_no,
        i.barcode_data,
        d.name as location_name,
        a.details
    FROM activity_log a
    LEFT JOIN users u ON a.user_id = u.id
    LEFT JOIN inventory i ON a.item_id = i.id
    LEFT JOIN departments d ON i.location_id = d.id
    $where_clause
    ORDER BY a.date_created DESC
");

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="scan_history_' . $start_date . '_to_' . $end_date . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add CSV headers
fputcsv($output, ['Date/Time', 'User', 'Role', 'Item', 'Property No', 'Barcode', 'Location', 'Details']);

// Add data rows
while ($row = $scans->fetch_assoc()) {
    fputcsv($output, [
        $row['date_created'],
        $row['username'] ?? 'System',
        $row['role'] ?? 'N/A',
        $row['article_name'] ?? 'Unknown Item',
        $row['property_no'] ?? 'N/A',
        $row['barcode_data'] ?? 'N/A',
        $row['location_name'] ?? 'N/A',
        $row['details'] ?? ''
    ]);
}

fclose($output);
exit();
?>