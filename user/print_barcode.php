<?php
require_once '../includes/auth.php';
requireLogin();

$id = (int)$_GET['id'];
$item = $conn->query("SELECT * FROM inventory WHERE id = $id")->fetch_assoc();

if (!$item) {
    die('Item not found');
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Print Barcode - <?php echo $item['property_no']; ?></title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            text-align: center;
            padding: 50px;
        }
        .barcode-container {
            border: 2px dashed #333;
            padding: 30px;
            display: inline-block;
            background: white;
        }
        .barcode {
            font-size: 48px;
            letter-spacing: 5px;
            margin: 20px 0;
        }
        .property {
            font-size: 24px;
            margin: 10px 0;
        }
        .article {
            font-size: 18px;
            color: #666;
        }
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="barcode-container">
        <div class="barcode">
            <?php echo htmlspecialchars($item['barcode_data']); ?>
        </div>
        <div class="property">
            <?php echo htmlspecialchars($item['property_no']); ?>
        </div>
        <div class="article">
            <?php echo htmlspecialchars($item['article_name']); ?>
        </div>
    </div>
    
    <div class="no-print" style="margin-top: 30px;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px;">Print Barcode</button>
        <button onclick="window.close()" style="padding: 10px 20px; font-size: 16px;">Close</button>
    </div>
    
    <script>
        window.onload = function() {
            // Auto-print dialog
            // window.print();
        }
    </script>
</body>
</html>