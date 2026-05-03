<?php
require('../config/db.php');

// Get hostel-wise student count
$hostelQuery = $pdo->query("
    SELECT h.name AS hostel, COUNT(s.id) AS total_students
    FROM hostels h
    LEFT JOIN students s ON s.hostel_id = h.id
    GROUP BY h.id
");
$hostelData = $hostelQuery->fetchAll(PDO::FETCH_ASSOC);

// Get room occupancy (room number and number of assigned students)
$roomQuery = $pdo->query("
    SELECT r.room_number, COUNT(s.id) AS occupants
    FROM rooms r
    LEFT JOIN students s ON s.room_id = r.id
    GROUP BY r.id
    LIMIT 10
");
$roomData = $roomQuery->fetchAll(PDO::FETCH_ASSOC);

// Total stats
$totalStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$totalRooms = $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
$totalHostels = $pdo->query("SELECT COUNT(*) FROM hostels")->fetchColumn();

// Return as JSON
echo json_encode([
    'hostel_chart' => $hostelData,
    'room_chart' => $roomData,
    'totals' => [
        'students' => $totalStudents,
        'rooms' => $totalRooms,
        'hostels' => $totalHostels
    ]
]);
