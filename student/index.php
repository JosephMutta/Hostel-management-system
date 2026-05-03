<?php
include('../config/db.php');
include('../includes/header1.php');

$student = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];

    $stmt = $pdo->prepare("
        SELECT s.*, h.name AS hostel_name, r.room_number 
        FROM students s
        LEFT JOIN hostels h ON s.hostel_id = h.id
        LEFT JOIN rooms r ON s.room_id = r.id
        WHERE s.email = ?
    ");
    $stmt->execute([$email]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<link rel="stylesheet" href="../css/style.css">
<h2>Student Lookup</h2>
<form method="POST">
    <label>Enter Email:</label><br>
    <input type="email" name="email" required><br><br>
    <input type="submit" value="Search">
    <p>Don't have an hostel yet? <a href="register.php"><strong>Register here</strong></a> </p>
</form>

<?php if ($student): ?>
    <h3>Student Details</h3>
    <p><strong>Name:</strong> <?= $student['name'] ?></p>
    <p><strong>Email:</strong> <?= $student['email'] ?></p>
    <p><strong>Phone:</strong> <?= $student['phone'] ?></p>
    <p><strong>Hostel:</strong> <?= $student['hostel_name'] ?></p>
    <p><strong>Room:</strong> <?= $student['room_number'] ?></p>
<?php elseif ($_SERVER['REQUEST_METHOD'] == 'POST'): ?>
    <p>No student found with that email.</p>
<?php endif; ?>

<?php include('../includes/footer.php'); ?>
