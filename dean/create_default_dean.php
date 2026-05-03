<?php
include('../config/access_control.php');
require('../config/db.php');

// Default dean credentials
$username = 'iaadean';
$password = 'iaadean123';
$role = 'dean';

// Hash the password securely
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Check if the user already exists
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = ?");
$stmt->execute([$username, $role]);

if ($stmt->rowCount() > 0) {
    echo "Default dean already exists.";
} else {
    // Insert default dean into the users table
    $insert = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $insert->execute([$username, $hashedPassword, $role]);

    echo "Default dean created successfully.";
}
?>
