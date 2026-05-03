<?php
include('../hostel-management/config/db.php'); // adjust if needed
include('../hostel-management/includes/header1.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = htmlspecialchars($_POST['name']);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = htmlspecialchars($_POST['phone']);
    $reg_number = htmlspecialchars($_POST['reg_number']); // New: capture reg number
    $hostel_id = $_POST['hostel_id'];
    $room_id = $_POST['room_id'];

    // Ensure room is not already full
    $stmt = $pdo->prepare("SELECT capacity, occupied FROM rooms WHERE id = ? AND hostel_id = ?");
    $stmt->execute([$room_id, $hostel_id]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($room && $room['occupied'] < $room['capacity']) {

        // Check for duplicate registration number
        $check = $pdo->prepare("SELECT id FROM students WHERE reg_number = ?");
        $check->execute([$reg_number]);
        if ($check->rowCount() > 0) {
            echo "<p style='color:red;'>Registration number already exists. Please use a unique one.</p>";
        } else {
            // Insert student
            $stmt = $pdo->prepare("INSERT INTO students (name, email, phone, reg_number, hostel_id, room_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $phone, $reg_number, $hostel_id, $room_id]);

            // Update occupied count
            $stmt = $pdo->prepare("UPDATE rooms SET occupied = occupied + 1 WHERE id = ?");
            $stmt->execute([$room_id]);

            echo "<p style='color:green;'>Student registered and room assigned successfully.</p>";
        }

    } else {
        echo "<p style='color:red;'>Room is already full or invalid selection.</p>";
    }
} else {
    echo "<p style='color:red;'>Invalid request.</p>";
}

include('../hostel-management/includes/footer.php');
