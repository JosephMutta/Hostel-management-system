<?php
session_start();
include('../config/db.php');
include('../includes/header1.php');

// Check student is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}

// Fetch student info from DB using registration number
$reg_number = $_SESSION['username'];
$stmt = $pdo->prepare("SELECT * FROM students WHERE reg_number = ?");
$stmt->execute([$reg_number]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// If student record not found, show error
if (!$student) {
    echo "<p style='color:red;'>Student details not found. Please contact admin.</p>";
    include('../includes/footer.php');
    exit();
}

// Fetch hostels
$hostels = $pdo->query("SELECT id, name FROM hostels")->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="../css/style.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<div class="container mt-4">
    <h2 class="mb-4">Hostel Allocation</h2>

    <form method="POST" action="register_submit.php">
        <div class="mb-3">
            <label class="form-label">Full Name:</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($student['name']) ?>" readonly>
        </div>

        <div class="mb-3">
            <label class="form-label">Registration Number:</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($student['reg_number']) ?>" readonly>
        </div>

        <div class="mb-3">
            <label class="form-label">Email:</label>
            <input type="email" class="form-control" value="<?= htmlspecialchars($student['email']) ?>" readonly>
        </div>

        <div class="mb-3">
            <label class="form-label">Phone:</label>
            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($student['phone']) ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Select Hostel:</label>
            <select id="hostelSelect" name="hostel_id" class="form-select" required>
                <option value="">-- Select Hostel --</option>
                <?php foreach ($hostels as $hostel): ?>
                    <option value="<?= $hostel['id'] ?>"><?= $hostel['name'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Available Rooms:</label>
            <select id="roomSelect" name="room_id" class="form-select" required>
                <option value="">-- Select a hostel first --</option>
            </select>
        </div>

        <input type="submit" value="Submit" class="btn btn-primary">
    </form>
</div>

<script>
document.getElementById('hostelSelect').addEventListener('change', function () {
    const hostelId = this.value;
    const roomSelect = document.getElementById('roomSelect');
    roomSelect.innerHTML = '<option>Loading...</option>';

    fetch(`get_rooms.php?hostel_id=${hostelId}`)
        .then(res => res.json())
        .then(data => {
            roomSelect.innerHTML = '<option value="">-- Select Room --</option>';
            if (data.length === 0) {
                roomSelect.innerHTML = '<option>No available rooms</option>';
            } else {
                data.forEach(room => {
                    const option = document.createElement('option');
                    option.value = room.id;
                    option.textContent = `Room ${room.room_number} (${room.occupied}/${room.capacity})`;
                    roomSelect.appendChild(option);
                });
            }
        });
});
</script>

<?php include('../includes/footer.php'); ?>
