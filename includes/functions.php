<?php
function getHostels($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM hostels");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getRoomsByHostel($pdo, $hostel_id) {
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE hostel_id = ?");
    $stmt->execute([$hostel_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAvailableRooms($pdo) {
    $stmt = $pdo->prepare("
        SELECT r.*, COUNT(s.id) as occupied 
        FROM rooms r 
        LEFT JOIN students s ON r.id = s.room_id 
        GROUP BY r.id 
        HAVING occupied < r.capacity
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
