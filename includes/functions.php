<?php
require_once __DIR__ . '/../config/database.php';

function formatDate($date, $format = 'M d, Y') {
    if (!$date) return '';
    return date($format, strtotime($date));
}

function formatCurrency($amount) {
    return '₱' . number_format($amount, 2);
}

function getInventoryStats() {
    global $conn;
    
    $stats = [];
    
    // Total items
    $result = $conn->query("SELECT COUNT(*) as total FROM inventory");
    $stats['total_items'] = $result->fetch_assoc()['total'] ?? 0;
    
    // Total quantity
    $result = $conn->query("SELECT SUM(qty_physical_count) as total FROM inventory");
    $stats['total_quantity'] = $result->fetch_assoc()['total'] ?? 0;
    
    // Total value
    $result = $conn->query("SELECT SUM(qty_physical_count * unit_value) as total FROM inventory");
    $stats['total_value'] = $result->fetch_assoc()['total'] ?? 0;
    
    // Recent items
    $result = $conn->query("SELECT i.*, d.name as location_name 
                            FROM inventory i 
                            LEFT JOIN departments d ON i.location_id = d.id 
                            ORDER BY i.date_added DESC LIMIT 10");
    $stats['recent'] = [];
    while ($row = $result->fetch_assoc()) {
        $stats['recent'][] = $row;
    }
    
    return $stats;
}

function getRecentActivities($limit = 10) {
    global $conn;
    
    $limit = (int)$limit;
    $result = $conn->query("SELECT a.*, u.username 
                            FROM activity_log a 
                            LEFT JOIN users u ON a.user_id = u.id 
                            ORDER BY a.date_created DESC 
                            LIMIT $limit");
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    return $items;
}

function getAuditTrail($limit = 100) {
    global $conn;
    
    $limit = (int)$limit;
    $result = $conn->query("SELECT a.*, u.username, u.role 
                            FROM audit_trail a 
                            LEFT JOIN users u ON a.user_id = u.id 
                            ORDER BY a.created_at DESC 
                            LIMIT $limit");
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    return $items;
}

function getInventoryByBarcode($barcode) {
    global $conn;
    
    $barcode = mysqli_real_escape_string($conn, $barcode);
    $query = "SELECT i.*, d.name as location_name 
              FROM inventory i 
              LEFT JOIN departments d ON i.location_id = d.id 
              WHERE i.barcode_data = '$barcode' OR i.property_no = '$barcode'";
    
    $result = $conn->query($query);
    return $result->fetch_assoc();
}

function getAllInventory() {
    global $conn;
    
    $query = "SELECT i.*, d.name as location_name, s.name as section_name 
              FROM inventory i 
              LEFT JOIN departments d ON i.location_id = d.id 
              LEFT JOIN sections s ON i.section_id = s.id 
              ORDER BY i.date_added DESC";
    
    $result = $conn->query($query);
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    return $items;
}
?>