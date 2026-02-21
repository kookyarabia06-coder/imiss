<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "inventory_sys";

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) === TRUE) {
    $conn->select_db($dbname);
} else {
    die("Error creating database: " . $conn->error);
}

// Create tables if they don't exist
$tables = [
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        firstname VARCHAR(100),
        lastname VARCHAR(100),
        username VARCHAR(80) UNIQUE,
        password VARCHAR(255),
        email VARCHAR(100),
        role ENUM('super_admin', 'admin', 'user') DEFAULT 'user',
        status ENUM('active', 'inactive') DEFAULT 'active',
        avatar VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE IF NOT EXISTS buildings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        floor INT DEFAULT 1
    )",
    
    "CREATE TABLE IF NOT EXISTS departments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        building_id INT,
        name VARCHAR(255) NOT NULL,
        FOREIGN KEY (building_id) REFERENCES buildings(id) ON DELETE SET NULL
    )",
    
    "CREATE TABLE IF NOT EXISTS sections (
        id INT AUTO_INCREMENT PRIMARY KEY,
        department_id INT,
        name VARCHAR(255) NOT NULL,
        FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
    )",
    
    "CREATE TABLE IF NOT EXISTS equipment (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        category VARCHAR(50) NOT NULL,
        description VARCHAR(255)
    )",
    
    "CREATE TABLE IF NOT EXISTS employees (
        id INT AUTO_INCREMENT PRIMARY KEY,
        firstname VARCHAR(100) NOT NULL,
        lastname VARCHAR(100) NOT NULL,
        middlename VARCHAR(100),
        email VARCHAR(150) UNIQUE,
        contact VARCHAR(50),
        department_id INT,
        section_id INT,
        position VARCHAR(100),
        date_hired DATE,
        status ENUM('Active','Inactive') DEFAULT 'Active',
        date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        date_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
        FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE SET NULL
    )",
    
    "CREATE TABLE IF NOT EXISTS inventory (
        id INT AUTO_INCREMENT PRIMARY KEY,
        article_name VARCHAR(255) NOT NULL,
        description TEXT,
        property_no VARCHAR(120) UNIQUE,
        uom VARCHAR(50),
        qty_property_card DECIMAL(12,2) DEFAULT 0.00,
        qty_physical_count DECIMAL(12,2) DEFAULT 0.00,
        location_id INT,
        condition_text VARCHAR(100),
        remarks TEXT,
        certified_correct TEXT,
        approved_by INT,
        verified_by INT,
        section_id INT,
        date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        date_updated TIMESTAMP NULL,
        fund_cluster VARCHAR(50),
        unit_value DECIMAL(12,2) DEFAULT 0.00,
        equipment_id INT DEFAULT 1,
        type_equipment VARCHAR(50) DEFAULT '',
        category VARCHAR(50) DEFAULT '',
        allocate_to INT,
        barcode_data VARCHAR(255),
        barcode_image LONGBLOB,
        FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE SET NULL,
        FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
        FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
        FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE SET NULL,
        FOREIGN KEY (location_id) REFERENCES departments(id) ON DELETE SET NULL,
        INDEX idx_barcode (barcode_data)
    )",
    
    "CREATE TABLE IF NOT EXISTS activity_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        action VARCHAR(50) NOT NULL,
        item_id INT,
        details TEXT,
        date_created DATETIME NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )",
    
    "CREATE TABLE IF NOT EXISTS audit_trail (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        action VARCHAR(50) NOT NULL,
        table_name VARCHAR(50),
        record_id INT,
        old_value TEXT,
        new_value TEXT,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )"
];

foreach ($tables as $sql) {
    if (!$conn->query($sql)) {
        // Ignore errors for duplicate column names
        if (strpos($conn->error, "Duplicate column") === false) {
            echo "Error creating table: " . $conn->error . "<br>";
        }
    }
}

// Insert default equipment if not exists
$check = $conn->query("SELECT * FROM equipment LIMIT 1");
if ($check->num_rows == 0) {
    $conn->query("INSERT INTO equipment (name, category, description) VALUES 
                  ('Dummy Equipment', 'GENERAL', 'Placeholder'),
                  ('Laptop', 'ICT', 'Office Laptop'),
                  ('Desktop PC', 'ICT', 'Computer'),
                  ('Medical Bed', 'MEDICAL', 'Hospital Bed')");
}

// Insert default users if not exists
$users = [
    ['superadmin', 'Super', 'Admin', 'superadmin@test.com', 'super_admin', 'admin123'],
    ['admin', 'System', 'Admin', 'admin@test.com', 'admin', 'admin123'],
    ['user', 'Regular', 'User', 'user@test.com', 'user', 'user123']
];

foreach ($users as $u) {
    $check = $conn->query("SELECT * FROM users WHERE username = '$u[0]'");
    if ($check->num_rows == 0) {
        $hash = password_hash($u[5], PASSWORD_DEFAULT);
        $conn->query("INSERT INTO users (username, firstname, lastname, email, role, password, status) 
                      VALUES ('$u[0]', '$u[1]', '$u[2]', '$u[3]', '$u[4]', '$hash', 'active')");
    }
}

// Insert sample buildings if not exists
$check = $conn->query("SELECT * FROM buildings LIMIT 1");
if ($check->num_rows == 0) {
    $conn->query("INSERT INTO buildings (name, floor) VALUES 
                  ('Main Building', 1),
                  ('Ward Building', 2),
                  ('Annex Building', 1)");
}

// Insert sample departments if not exists
$check = $conn->query("SELECT * FROM departments LIMIT 1");
if ($check->num_rows == 0) {
    $conn->query("INSERT INTO departments (building_id, name) VALUES 
                  (1, 'Emergency'),
                  (1, 'Pharmacy'),
                  (2, 'ICU'),
                  (2, 'Surgery')");
}

// Insert sample sections if not exists
$check = $conn->query("SELECT * FROM sections LIMIT 1");
if ($check->num_rows == 0) {
    $conn->query("INSERT INTO sections (department_id, name) VALUES 
                  (1, 'Triage'),
                  (1, 'Treatment'),
                  (2, 'Dispensing'),
                  (3, 'ICU Ward')");
}
?>