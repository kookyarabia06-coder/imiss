<?php
require_once '../includes/auth.php';
requireRole('super_admin');

// Get date range
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Get activities
$activities = $conn->query("
    SELECT 
        a.date_created,
        u.username,
        u.role,
        a.action,
        i.article_name,
        i.property_no,
        a.details,
        a.ip_address
    FROM activity_log a
    LEFT JOIN users u ON a.user_id = u.id
    LEFT JOIN inventory i ON a.item_id = i.id
    WHERE DATE(a.date_created) BETWEEN '$start_date' AND '$end_date'
    ORDER BY a.date_created DESC
");

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="activity_log_' . $start_date . '_to_' . $end_date . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add CSV headers
fputcsv($output, ['Date/Time', 'Username', 'Role', 'Action', 'Item', 'Property No', 'Details', 'IP Address']);

// Add data rows
while ($row = $activities->fetch_assoc()) {
    fputcsv($output, [
        $row['date_created'],
        $row['username'] ?? 'System',
        $row['role'] ?? 'N/A',
        $row['action'],
        $row['article_name'] ?? 'N/A',
        $row['property_no'] ?? 'N/A',
        $row['details'] ?? '',
        $row['ip_address'] ?? ''
    ]);
}

fclose($output);
exit();
?>