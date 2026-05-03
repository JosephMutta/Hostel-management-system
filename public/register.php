<?php
include('../hostel-management/config/db.php');
include('../hostel-management/includes/header.php');
include('../hostel-management/includes/functions.php');

$rooms = getAvailableRooms($pdo);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $room_id = $_POST['room_id'];

    // Get hostel ID from room
    $stmt = $pdo->prepare("SELECT hostel_id FROM rooms WHERE id = ?");
    $stmt->execute([$room_id]);
    $hostel_id = $stmt->fetchColumn();

    $stmt = $pdo->prepare("INSERT INTO students (name, email, phone, hostel_id, room_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $email, $phone, $hostel_id, $room_id]);

    echo "<p>Registered successfully!</p>";
}
?>

<link rel="stylesheet" href="../hostel-management/css/style.css">
<h2>Student Registration</h2>
<form method="POST">
    <label>Name:</label><br>
    <input type="text" name="name" required><br><br>

    <label>Email:</label><br>
    <input type="email" name="email" required><br><br>

    <label>Phone:</label><br>
    <input type="text" name="phone"><br><br>

    <label>Available Rooms:</label><br>
    <select name="room_id" required>
        <option value="">Select Room</option>
        <?php foreach ($rooms as $room): ?>
            <option value="<?= $room['id'] ?>">
                Hostel #<?= $room['hostel_id'] ?> - Room <?= $room['room_number'] ?> (<?= $room['occupied'] ?>/<?= $room['capacity'] ?>)
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <input type="submit" value="Register">
</form>

<?php include('../hostel-management/includes/footer.php'); ?>
            