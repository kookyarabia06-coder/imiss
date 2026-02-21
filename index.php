<?php
require_once 'includes/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$role = $_SESSION['role'];
if ($role == 'super_admin') {
    header('Location: superadmin/dashboard.php');
} elseif ($role == 'admin') {
    header('Location: admin/dashboard.php');
} else {
    header('Location: user/dashboard.php');
}
exit();
?>