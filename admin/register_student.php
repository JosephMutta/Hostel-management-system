<?php
session_start();
include('../config/access_control.php');
include('../config/db.php');
include('../includes/header.php');

// Ensure only admin can access
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $reg_number = strtoupper(trim($_POST['reg_number']));
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Server-side name validation
    if (!preg_match("/^[A-Za-z]+(?:\s[A-Za-z]+){2}$/", $name)) {
        $error = "Please enter first, middle, and last name using letters only.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $role = 'student';

        // Check if registration number already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$reg_number]);

        if ($stmt->rowCount() > 0) {
            $error = "This registration number is already used.";
        } else {
            // Insert into students table
            $stmt = $pdo->prepare("INSERT INTO students (name, reg_number, email) VALUES (?, ?, ?)");
            $stmt->execute([$name, $reg_number, $email]);

            // Insert into users table
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt->execute([$reg_number, $hashedPassword, $role]);

            $success = "Student registered successfully.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register Student</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body {
            background-color: #f4f6f8;
        }

        #sidebar {
            width: 250px;
            background-color: #003366;
            color: white;
            position: fixed;
            top: 0;
            left: -260px;
            height: 100vh;
            overflow-y: auto;
            transition: 0.3s ease-in-out;
            padding-top: 60px;
            z-index: 999;
        }

        #sidebar.active { left: 0; }

        #sidebar ul { list-style: none; padding: 0; }

        #sidebar ul li {
            padding: 15px 20px;
        }

        #sidebar ul li a {
            color: white;
            text-decoration: none;
            display: block;
        }

        .submenu {
            padding-left: 20px;
            display: none;
        }

        .submenu.show {
            display: block;
        }

        .toggle-btn {
            position: fixed;
            top: 15px;
            left: 15px;
            background-color: #003366;
            color: white;
            border: none;
            padding: 10px 14px;
            border-radius: 4px;
            cursor: pointer;
            z-index: 1000;
        }

        main {
            margin-left: 0;
            padding: 30px;
            transition: margin-left 0.3s ease-in-out;
        }

        #sidebar.active ~ main {
            margin-left: 260px;
        }

        .form-container {
            max-width: 600px;
            margin: auto;
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .show-password {
            cursor: pointer;
            position: absolute;
            top: 35px;
            right: 15px;
        }

        .password-wrapper {
            position: relative;
        }
    </style>
</head>
<body>

<!-- Sidebar Toggle -->
<button class="toggle-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>

<!-- Sidebar -->
<div id="sidebar">
    <ul>
        <li><a href="add_hostel.php"><i class="fas fa-building"></i> Add Hostel</a></li>
        <li><a href="add_room.php"><i class="fas fa-door-open"></i> Add Room</a></li>
        <li>
            <a href="#" onclick="toggleSubmenu(event)">
                <i class="fas fa-users"></i> Manage Users <i class="fas fa-caret-down float-end"></i>
            </a>
            <ul class="submenu" id="userSubmenu">
                <li><a href="register_student.php">Register Student</a></li>
                <li><a href="add_hostel_owner.php">Register Hostel Owner</a></li>
                <li><a href="add_dean.php">Register Dean</a></li>
            </ul>
        </li>
        <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
        <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<!-- Main Content -->
<main>
    <div class="form-container">
        <h2 class="mb-4">Register New Student</h2>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php elseif (!empty($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">Student Name (First Middle Last):</label>
                <input type="text" name="name" class="form-control"
                       pattern="^[A-Za-z]+(?:\s[A-Za-z]+){2}$"
                       title="Enter first, middle, and last name using letters only"
                       required>
            </div>

            <div class="mb-3">
                <label class="form-label">Registration Number:</label>
                <input type="text" name="reg_number" class="form-control"
                       placeholder="e.g., BCSe-01-0000-2022"
                       pattern="^[A-Za-z]{2,6}-\d{2}-\d{4}-\d{4}$"
                       title="Format: ABC-01-0000-2022" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Email:</label>
                <input type="email" name="email" class="form-control" required>
            </div>

            <div class="mb-3 password-wrapper">
                <label class="form-label">Password:</label>
                <input type="password" id="password" name="password" class="form-control" minlength="6" required>
                <span class="show-password" onclick="togglePassword('password')"><i class="fas fa-eye"></i></span>
            </div>

            <div class="mb-3 password-wrapper">
                <label class="form-label">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" minlength="6" required>
                <span class="show-password" onclick="togglePassword('confirm_password')"><i class="fas fa-eye"></i></span>
            </div>

            <button type="submit" class="btn btn-primary w-100">Register Student</button>
        </form>
    </div>
</main>

<!-- Scripts -->
<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('active');
}

function toggleSubmenu(e) {
    e.preventDefault();
    document.getElementById('userSubmenu').classList.toggle('show');
}

function togglePassword(fieldId) {
    const input = document.getElementById(fieldId);
    const icon = input.nextElementSibling.querySelector('i');
    if (input.type === "password") {
        input.type = "text";
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = "password";
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>

<?php include('../includes/footer.php'); ?>
</body>
</html>
