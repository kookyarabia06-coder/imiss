<?php
session_start();
require_once __DIR__ . '/../config/database.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function login($username, $password) {
    global $conn;
    
    // Escape username to prevent SQL injection
    $username = mysqli_real_escape_string($conn, $username);
    $query = "SELECT * FROM users WHERE username = '$username' AND status = 'active'";
    $result = $conn->query($query);
    
    // FIX: Check if query succeeded before accessing num_rows
    if ($result && $result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['fullname'] = $user['firstname'] . ' ' . $user['lastname'];
            
            // Log login
            $date = date('Y-m-d H:i:s');
            $conn->query("INSERT INTO activity_log (user_id, action, details, date_created) 
                          VALUES ({$user['id']}, 'login', 'User logged in', '$date')");
            
            return true;
        }
    } else {
        // Optional: Log the error for debugging
        if (!$result) {
            error_log("Login query failed: " . $conn->error);
        }
    }
    return false;
}

function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] == $role;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../login.php');
        exit();
    }
}

function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        if (hasRole('super_admin')) {
            header('Location: ../superadmin/dashboard.php');
        } elseif (hasRole('admin')) {
            header('Location: ../admin/dashboard.php');
        } else {
            header('Location: ../user/dashboard.php');
        }
        exit();
    }
}
?>