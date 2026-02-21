<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    
    // Get item property number
    $result = $conn->query("SELECT property_no FROM inventory WHERE id = $id");
    if ($result->num_rows > 0) {
        $item = $result->fetch_assoc();
        $barcode = 'INV-' . $item['property_no'];
        
        // Update barcode
        if ($conn->query("UPDATE inventory SET barcode_data = '$barcode' WHERE id = $id")) {
            echo json_encode(['success' => true, 'barcode' => $barcode]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Item not found']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
?>