<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Publicly accessible files (no login required)
$public_files = [
    'login.php',
    'index.php'
];

// Files with role-based access control
$private_files = [
    'admin/dashboard.php'     => 'admin',
    'admin/add_hostel.php'    => 'admin',
    'admin/settings.php'      => 'admin',

    'dean/make_announcement.php' => 'dean',
    'dean/dashboard.php'         => 'dean',

    'hostel_owner/dashboard.php' => 'hostel_owner',

    'student/dashboard.php'      => 'student'
];

// Get relative path of current file (e.g. "admin/dashboard.php")
$current_file = str_replace($_SERVER['DOCUMENT_ROOT'], '', $_SERVER['SCRIPT_FILENAME']);
$current_file = ltrim($current_file, '/');

// If not public, require login
if (!in_array(basename($current_file), $public_files)) {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header("Location: /hostel-management/login.php");
        exit();
    }
}

// If specific role required
if (array_key_exists($current_file, $private_files)) {
    $required_role = $private_files[$current_file];
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $required_role) {
        echo "Access denied. Only '$required_role' users can access this page.";
        exit();
    }
}
?>
