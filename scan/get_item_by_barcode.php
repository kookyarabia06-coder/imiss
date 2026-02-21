<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

$barcode = $_GET['barcode'] ?? '';
$item = getInventoryByBarcode($barcode);

if ($item) {
    echo json_encode(['success' => true, 'item' => $item]);
} else {
    echo json_encode(['success' => false]);
}
?>