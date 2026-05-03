<?php
$host = 'localhost';
$db = 'hostel_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>

