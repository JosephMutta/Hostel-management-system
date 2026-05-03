<?php
session_start();
include('../config/access_control.php');
include('../config/db.php');
include('../includes/header.php');

// Allow only logged-in dean
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'dean') {
    header("Location: ../index.php");
    exit();
}

$student = null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reg_number = strtoupper(trim($_POST['reg_number']));

    if (!empty($reg_number)) {
        $stmt = $pdo->prepare("
            SELECT s.name, s.email, s.phone, h.name AS hostel_name, r.room_number
            FROM students s
            LEFT JOIN hostels h ON s.hostel_id = h.id
            LEFT JOIN rooms r ON s.room_id = r.id
            WHERE s.reg_number = ?
        ");
        $stmt->execute([$reg_number]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$student) {
            $error = "No student found with that registration number.";
        }
    } else {
        $error = "Please enter a registration number.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Search Student</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">

    <h2 class="mb-4">🔍 Search Student by Registration Number</h2>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="" class="mb-4">
        <div class="mb-3">
            <label class="form-label">Registration Number</label>
            <input type="text" name="reg_number" class="form-control" placeholder="e.g., BCSE-01-0001-2022" required>
        </div>
        <button type="submit" class="btn btn-primary">Search</button>
    </form>

    <?php if ($student): ?>
        <div class="card">
            <div class="card-header bg-primary text-white">Student Details</div>
            <div class="card-body">
                <p><strong>Name:</strong> <?= htmlspecialchars($student['name']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($student['email']) ?></p>
                <p><strong>Phone:</strong> <?= htmlspecialchars($student['phone']) ?></p>
                <?php if ($student['hostel_name'] && $student['room_number']): ?>
                    <p><strong>Hostel:</strong> <?= htmlspecialchars($student['hostel_name']) ?></p>
                    <p><strong>Room:</strong> <?= htmlspecialchars($student['room_number']) ?></p>
                <?php else: ?>
                    <p class="text-warning">This student is not yet assigned to any hostel or room.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

<?php include('../includes/footer.php'); ?>
</body>
</html>
