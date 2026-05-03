<?php
session_start();

// List of protected files (login required)
$protected_files = [
    'dashboard.php',
    'add_hostel.php',
    'add_room.php',
    'add_student.php',
    'view_hostels.php',
    'view_rooms.php',
    'view_students.php'
];

// List of private files with required roles
$private_files = [
    'dashboard.php' => 'admin',
    'add_hostel.php'      => 'admin',
    'settings.php'        => 'admin'
];

// Get current file name
$current_file = basename($_SERVER['PHP_SELF']);

// Check if file requires login
if (in_array($current_file, $protected_files)) {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header("Location: ../hostel-management/login.php");
        exit();
    }
}

// Check if file has role restriction
if (array_key_exists($current_file, $private_files)) {
    $required_role = $private_files[$current_file];
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $required_role) {
        echo "Access denied. Only $required_role users can access this page.";
        exit();
    }
}
?>
