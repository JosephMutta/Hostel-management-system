<?php
include('../config/access_control.php');
require('../config/db.php');

$default_username = 'admin';
$default_password = 'admin123'; // plaintext for creation

// Hash the password
$hashed_password = password_hash($default_password, PASSWORD_DEFAULT);

// Check if admin already exists
$stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE username = ?");
$stmt->execute([$default_username]);
$count = $stmt->fetchColumn();

if ($count == 0) {
    $stmt = $pdo->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
    if ($stmt->execute([$default_username, $hashed_password])) {
        echo "Default admin created: Username: <strong>admin</strong>, Password: <strong>admin123</strong>";
    } else {
        echo "Error creating admin.";
    }
} else {
    echo "Default admin already exists.";
}
?>
