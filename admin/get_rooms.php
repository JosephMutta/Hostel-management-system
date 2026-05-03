<?php
header('Content-Type: application/json'); // ✅ tell the browser it's JSON
include('../hostel-management/config/db.php'); // adjust as needed

$hostel_id = $_GET['hostel_id'] ?? 0;

try {
    $stmt = $pdo->prepare("SELECT id, room_number, capacity,
        (SELECT COUNT(*) FROM students WHERE room_id = rooms.id) AS occupied
        FROM rooms WHERE hostel_id = ? HAVING occupied < capacity");

    $stmt->execute([$hostel_id]);
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($rooms);
} catch (Exception $e) {
    echo json_encode(['error' => 'Something went wrong.']);
}
