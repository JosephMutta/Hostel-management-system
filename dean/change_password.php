<?php
session_start();
include('../config/access_control.php');
include('../config/db.php');
include('../includes/header.php');

// Restrict to logged-in dean only
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'dean') {
    header("Location: ../login.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_SESSION['username'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    $stmt = $pdo->prepare("SELECT password FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($current_password, $user['password'])) {
        $error = "Current password is incorrect.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
        $stmt->execute([$hashed, $username]);

        // Spinner with delay and redirect
        echo "<div class='spinner-overlay d-flex' id='spinner'>
                <div class='text-center'>
                    <div class='spinner-border text-light' role='status'></div>
                    <p class='mt-3'>Password changed successfully. Redirecting to login page...</p>
                </div>
              </div>
              <script>
                  setTimeout(() => {
                      window.location.href = '../login.php';
                  }, 3000);
              </script>";
        session_unset();
        session_destroy();
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password - Dean</title>
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
            display: flex;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.7);
            justify-content: center;
            align-items: center;
            color: white;
            font-size: 18px;
            flex-direction: column;
            z-index: 9999;
        }

        .strength-meter {
            height: 5px;
            width: 100%;
            background-color: #ddd;
            margin-top: 5px;
        }
        .strength-meter-fill {
            height: 100%;
            width: 0%;
            transition: width 0.3s ease-in-out;
        }
    </style>
</head>
<body>

<!-- Sidebar toggle -->
<button id="menu-toggle">&#9776;</button>

<!-- Sidebar for Dean -->
<div id="sidebar">
    <ul>
        <li><a href="dashboard.php">🏠 Home</a></li>
        <li><a href="students_list.php">👨‍🎓 View Students</a></li>
        <li><a href="hostel_occupancy.php">🏢 Hostel Occupancy</a></li>
        <li><a href="change_password.php">🔒 Change Password</a></li>
        <li><a href="../logout.php">🚪 Logout</a></li>
        <li><a href="#" onclick="document.getElementById('sidebar').classList.remove('active'); return false;">⬅️ Back</a></li>
    </ul>
</div>

<!-- Main Form -->
<div class="container mt-5" style="max-width: 500px;">
    <h3 class="mb-4">Change Password</h3>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Current Password:</label>
            <input type="password" name="current_password" id="current_password" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">New Password:</label>
            <input type="password" name="new_password" id="new_password" class="form-control" required>
            <div class="strength-meter"><div id="strengthBar" class="strength-meter-fill bg-danger"></div></div>
        </div>

        <div class="mb-3">
            <label class="form-label">Confirm New Password:</label>
            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
        </div>

        <div class="mb-3">
            <input type="checkbox" onclick="togglePassword()"> Show Password
        </div>

        <div class="d-flex justify-content-between">
            <button type="submit" class="btn btn-primary">Update Password</button>
            <a href="dashboard.php" class="btn btn-secondary">Back</a>
        </div>
    </form>
</div>

<!-- Scripts -->
<script>
    document.getElementById("menu-toggle").addEventListener("click", () => {
        document.getElementById("sidebar").classList.toggle("active");
    });

    function togglePassword() {
        ['current_password', 'new_password', 'confirm_password'].forEach(id => {
            const input = document.getElementById(id);
            input.type = input.type === 'password' ? 'text' : 'password';
        });
    }

    const password = document.getElementById("new_password");
    const strengthBar = document.getElementById("strengthBar");

    password.addEventListener("input", function () {
        const val = password.value;
        let strength = 0;
        if (val.length >= 6) strength++;
        if (/[A-Z]/.test(val)) strength++;
        if (/[0-9]/.test(val)) strength++;
        if (/[^A-Za-z0-9]/.test(val)) strength++;

        const widths = ["0%", "25%", "50%", "75%", "100%"];
        const classes = ["bg-danger", "bg-warning", "bg-info", "bg-primary", "bg-success"];
        strengthBar.style.width = widths[strength];
        classes.forEach(c => strengthBar.classList.remove(c));
        strengthBar.classList.add(classes[strength]);
    });
</script>

<?php include('../includes/footer.php'); ?>
</body>
</html>
