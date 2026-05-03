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

        $success = "Profile updated successfully.";
        $student['phone'] = $formattedPhone; // reflect change on page
    }
}
?>

<!-- Styles and Bootstrap -->
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
    #sidebar.active {
        left: 0;
    }
    #sidebar ul {
        list-style-type: none;
        padding: 0;
    }
    #sidebar ul li {
        padding: 15px 20px;
        border-bottom: 1px solid #555;
    }
    #sidebar ul li a {
        color: white;
        text-decoration: none;
        display: block;
    }
    #sidebar ul li a:hover {
        background-color: #00509e;
        border-radius: 5px;
    }
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
</style>

<!-- Sidebar Toggle -->
<button id="menu-toggle">&#9776;</button>

<!-- Sidebar -->
<div id="sidebar">
    <ul>
        <li><a href="dashboard.php">🏠 Home</a></li>
        <li><a href="change_password.php">🔒 Change Password</a></li>
        <li><a href="send_complaint.php">🆘 Send Complaint</a></li>
        <li><a href="#" onclick="document.getElementById('sidebar').classList.remove('active'); return false;">⬅️ Back</a></li>
        <li><a href="../logout.php">🚪 Logout</a></li>
    </ul>
</div>

<!-- Main Content -->
<div class="container mt-5">
    <h2>Edit Profile</h2>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
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
            <input type="text" name="phone" class="form-control" maxlength="9"
                   pattern="\d{9}" title="Enter 9-digit phone number (no leading 0)"
                   value="<?= htmlspecialchars(ltrim($student['phone'], '0')) ?>" required>
            <small class="text-muted">Enter 9-digit phone number (without 0). E.g., 712345678</small>
        </div>

        <button type="submit" class="btn btn-primary">Update</button>
        <a href="dashboard.php" class="btn btn-secondary">Back</a>
    </form>
</div>

<!-- JS -->
<script>
document.getElementById("menu-toggle").addEventListener("click", function () {
    document.getElementById("sidebar").classList.toggle("active");
});
</script>

<?php include('../includes/footer.php'); ?>
