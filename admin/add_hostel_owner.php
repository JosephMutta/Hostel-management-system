<?php
session_start();
include('../config/access_control.php');
include('../config/db.php');
include('../includes/header.php');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $owner_name = trim($_POST['owner_name']);
    $hostel_name = trim($_POST['hostel_name']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($owner_name) || empty($hostel_name) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $first_name = explode(' ', $owner_name)[0]; // Get first name as username
        $username = strtolower($first_name); // normalize

        // Check if username already exists
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $checkStmt->execute([$username]);

        if ($checkStmt->fetchColumn() > 0) {
            $error = "Username '$username' is already in use. Try a different name or add a number.";
        } else {
            // Insert hostel and owner name
            $stmt1 = $pdo->prepare("INSERT INTO hostels (name, owner_name) VALUES (?, ?)");
            $stmt1->execute([$hostel_name, $owner_name]);

            // Insert into users with first name as username
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt2 = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'hostel_owner')");
            $stmt2->execute([$username, $hashedPassword]);

            $success = "Hostel Owner registered successfully. Username: <strong>$username</strong>";
        }
    }
}
?>

<!-- Styles -->
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

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

<!-- Sidebar -->
<div id="sidebar">
    <ul>
        <li><a href="register_student.php">Register Student</a></li>
        <li><a href="add_hostel_owner.php">Register Hostel Owner</a></li>
        <li><a href="register_dean.php">Register Dean</a></li>
        <li><a href="settings.php">Settings</a></li>
        <li><a href="../logout.php">Logout</a></li>
        <li><a href="#" onclick="document.getElementById('sidebar').classList.remove('active'); return false;">&#8592; Back</a></li>
    </ul>
</div>

<!-- Toggle Sidebar Button -->
<button id="menu-toggle">&#9776;</button>

<!-- Main Content -->
<div class="container mt-4">
    <h2>Register Hostel Owner</h2>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php elseif ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-3">
            <label class="form-label">Owner's Full Name:</label>
            <input type="text" name="owner_name" class="form-control" placeholder="e.g. Joseph Mutta" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Hostel Name:</label>
            <input type="text" name="hostel_name" class="form-control" placeholder="e.g. Umoja Hostel" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Password:</label>
            <input type="password" name="password" id="password" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Confirm Password:</label>
            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
        </div>

        <div class="mb-3">
            <input type="checkbox" id="showPassword" onclick="togglePassword()"> Show Password
        </div>

        <input type="submit" value="Register Hostel Owner" class="btn btn-primary">
    </form>
</div>

<!-- Scripts -->
<script>
function togglePassword() {
    const pw1 = document.getElementById("password");
    const pw2 = document.getElementById("confirm_password");
    const type = pw1.type === "password" ? "text" : "password";
    pw1.type = type;
    pw2.type = type;
}

document.getElementById("menu-toggle").addEventListener("click", function () {
    document.getElementById("sidebar").classList.toggle("active");
});
</script>

<?php include('../includes/footer.php'); ?>
