<?php
session_start();
include('../config/access_control.php');
include('../config/db.php');
include('../includes/header.php');

// Ensure only student can access
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}

$reg_number = $_SESSION['username'];
$success = '';
$error = '';

// Fetch current student info
$stmt = $pdo->prepare("SELECT name, email, phone FROM students WHERE reg_number = ?");
$stmt->execute([$reg_number]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone']);
    $phone = preg_replace('/\D/', '', $phone); // remove non-digits

    if (!preg_match('/^\d{9}$/', $phone)) {
        $error = "Phone number must be exactly 9 digits (without leading 0).";
    } else {
        $formattedPhone = '0' . $phone;

        // Update phone only
        $stmt = $pdo->prepare("UPDATE students SET phone = ? WHERE reg_number = ?");
        $stmt->execute([$formattedPhone, $reg_number]);

        // Proceed with hostel and room registration
        if (empty($error)) {
            $hostel_id = $_POST['hostel_id'];
            $room_id = $_POST['room_id'];

            // Validate room and capacity
            $stmt = $pdo->prepare("SELECT capacity, occupied FROM rooms WHERE id = ? AND hostel_id = ?");
            $stmt->execute([$room_id, $hostel_id]);
            $room = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$room) {
                $error = "Invalid room or hostel selected.";
            } elseif ($room['occupied'] >= $room['capacity']) {
                $error = "Room is already full. Please select another.";
            } else {
                // Check if student already assigned
                $stmt = $pdo->prepare("SELECT hostel_id, room_id FROM students WHERE reg_number = ?");
                $stmt->execute([$reg_number]);
                $studentCheck = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!empty($studentCheck['hostel_id']) && !empty($studentCheck['room_id'])) {
                    $error = "You are already assigned to a hostel and room.";
                } else {
                    // Assign hostel and room to student
                    $stmt = $pdo->prepare("UPDATE students SET hostel_id = ?, room_id = ? WHERE reg_number = ?");
                    if ($stmt->execute([$hostel_id, $room_id, $reg_number])) {
                        // Increment room occupancy
                        $incStmt = $pdo->prepare("UPDATE rooms SET occupied = occupied + 1 WHERE id = ?");
                        $incStmt->execute([$room_id]);
                    }

                    // Notify hostel owner
                    $ownerStmt = $pdo->prepare("SELECT owner_id FROM hostels WHERE id = ?");
                    $ownerStmt->execute([$hostel_id]);
                    $owner = $ownerStmt->fetch(PDO::FETCH_ASSOC);

                    if ($owner && !empty($owner['owner_id'])) {
                        $message = "Student {$student['name']} has registered to your hostel.";
                        $notiStmt = $pdo->prepare("INSERT INTO hostel_owner_notifications (owner_id, message) VALUES (?, ?)");
                        $notiStmt->execute([$owner['owner_id'], $message]);
                    }

                    $success = "Hostel and room assigned successfully!";
                }
            }
        }
    }
}
?>

<!-- HTML and Bootstrap UI -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="../css/style.css">

<style>
    #sidebar {
        position: fixed;
        top: 0;
        left: -250px;
        width: 230px;
        height: 100%;
        background-color: #003366;
        color: white;
        transition: left 0.3s ease;
        padding-top: 60px;
        z-index: 1000;
    }
    #sidebar.active { left: 0; }
    #sidebar ul { list-style-type: none; padding: 0; }
    #sidebar ul li { padding: 15px 20px; border-bottom: 1px solid #555; }
    #sidebar ul li a { color: white; text-decoration: none; display: block; }
    #sidebar ul li a:hover { background-color: #00509e; border-radius: 5px; }
    #menu-toggle {
        position: absolute;
        top: 15px;
        left: 15px;
        font-size: 22px;
        color: #003366;
        background: none;
        border: none;
        z-index: 1100;
    }
    .spinner-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.6);
        z-index: 2000;
        justify-content: center;
        align-items: center;
        color: white;
        font-size: 18px;
        flex-direction: column;
    }
</style>

<!-- Sidebar and Spinner -->
<button id="menu-toggle">&#9776;</button>
<div id="sidebar">
    <ul>
        <li><a href="dashboard.php">🏠 Home</a></li>
        <li><a href="change_password.php">🔒 Change Password</a></li>
        <li><a href="send_complaint.php">🆘 Send Complaint</a></li>
        <li><a href="#" onclick="document.getElementById('sidebar').classList.remove('active'); return false;">⬅️ Back</a></li>
        <li><a href="../logout.php">🚪 Logout</a></li>
    </ul>
</div>

<div class="spinner-overlay d-flex" id="spinner">
    <div class="text-center">
        <div class="spinner-border text-light" role="status"></div>
        <p class="mt-3">Profile updated successfully. Redirecting to dashboard...</p>
    </div>
</div>

<div class="container mt-5">
    <h2>Edit Profile</h2>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                document.getElementById("spinner").style.display = "flex";
                setTimeout(function () {
                    window.location.href = "dashboard.php";
                }, 3000);
            });
        </script>
    <?php elseif ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-3">
            <label class="form-label">Full Name:</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($student['name']) ?>" disabled>
        </div>
        <div class="mb-3">
            <label class="form-label">Registration Number:</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($reg_number) ?>" disabled>
        </div>
        <div class="mb-3">
            <label class="form-label">Email:</label>
            <input type="email" class="form-control" value="<?= htmlspecialchars($student['email']) ?>" disabled>
        </div>
        <div class="mb-3">
            <label class="form-label">Phone Number:</label>
            <input type="text" name="phone" class="form-control" maxlength="9" pattern="\d{9}"
                   title="Enter 9-digit phone number (no leading 0)"
                   value="<?= htmlspecialchars(ltrim($student['phone'], '0')) ?>" required>
            <small class="text-muted">Enter 9-digit phone number (without 0). E.g., 712345678</small>
        </div>
        <div class="mb-3">
            <label class="form-label">Hostel ID:</label>
            <input type="number" name="hostel_id" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Room ID:</label>
            <input type="number" name="room_id" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Update</button>
        <a href="dashboard.php" class="btn btn-secondary">Back</a>
    </form>
</div>

<script>
    document.getElementById("menu-toggle").addEventListener("click", function () {
        document.getElementById("sidebar").classList.toggle("active");
    });
</script>

<?php include('../includes/footer.php'); ?>
