<?php
require_once '../includes/auth.php';
requireRole('super_admin');

// Get date range
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Get audit trail
$audit = $conn->query("
    SELECT 
        a.created_at,
        u.username,
        u.role,
        a.action,
        a.table_name,
        a.record_id,
        a.old_value,
        a.new_value,
        a.ip_address
    FROM audit_trail a
    LEFT JOIN users u ON a.user_id = u.id
    WHERE DATE(a.created_at) BETWEEN '$start_date' AND '$end_date'
    ORDER BY a.created_at DESC
");

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="audit_trail_' . $start_date . '_to_' . $end_date . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add CSV headers
fputcsv($output, ['Date/Time', 'Username', 'Role', 'Action', 'Table', 'Record ID', 'Old Value', 'New Value', 'IP Address']);

// Add data rows
while ($row = $audit->fetch_assoc()) {
    fputcsv($output, [
        $row['created_at'],
        $row['username'] ?? 'System',
        $row['role'] ?? 'N/A',
        $row['action'],
        $row['table_name'],
        $row['record_id'],
        $row['old_value'] ?? '',
        $row['new_value'] ?? '',
        $row['ip_address'] ?? ''
    ]);
}

fclose($output);
exit();
?>