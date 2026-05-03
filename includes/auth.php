<?php
session_start();

if (!isset($_SESSION['logged_in']) || !isset($_SESSION['role'])) {
    header("Location: ../login.php");
    exit();
}

// Optional: Role check
function require_role($role) {
    if ($_SESSION['role'] !== $role) {
        header("Location: ../unauthorized.php");
        exit();
    }
}
