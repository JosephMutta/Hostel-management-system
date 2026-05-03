<?php
session_start();
include('../config/access_control.php');
include('../config/db.php');
include('../includes/header.php');

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}

$username = $_SESSION['username']; // reg_number
$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    $stmt = $pdo->prepare("SELECT password FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($current, $user['password'])) {
        $message = "<p class='text-danger'>Current password is incorrect.</p>";
    } elseif ($new !== $confirm) {
        $message = "<p class='text-danger'>New passwords do not match.</p>";
    } else {
        $newHash = password_hash($new, PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
        $update->execute([$newHash, $username]);
        $success = true;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Change Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6f8;
        }
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
        #sidebar ul { list-style: none; padding: 0; }
        #sidebar ul li { padding: 15px 20px; border-bottom: 1px solid #555; }
        #sidebar ul li a { color: white; text-decoration: none; }
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
        .strength-meter {
            height: 5px;
            background: #e0e0e0;
            margin-top: 5px;
            border-radius: 3px;
        }
        .strength-meter-fill {
            height: 100%;
            width: 0%;
            background-color: red;
            border-radius: 3px;
            transition: width 0.3s ease-in-out;
        }
    </style>
</head>
<body>

<!-- Sidebar Toggle -->
<button id="menu-toggle">&#9776;</button>

<!-- Sidebar -->
<div id="sidebar">
    <ul>
        <li><a href="dashboard.php">🏠 Home</a></li>
        <li><a href="edit_profile.php">📝 Edit Profile</a></li>
        <li><a href="send_complaint.php">🆘 Send Complaint</a></li>
        <li><a href="view_announcements.php">📢 Announcements</a></li>
        <li><a href="../logout.php">🚪 Logout</a></li>
        <li><a href="#" onclick="document.getElementById('sidebar').classList.remove('active'); return false;">⬅️ Back</a></li>
    </ul>
</div>

<!-- Main Content -->
<div class="container mt-5">
    <h2 class="mb-4">Change Password</h2>

    <?php
    if ($success) {
        echo "
        <div class='text-center mt-5'>
            <div class='d-flex justify-content-center'>
                <div class='spinner-border text-success' role='status' style='width: 3rem; height: 3rem;'>
                    <span class='visually-hidden'>Loading...</span>
                </div>
            </div>
            <p class='mt-3 text-success'>Password changed successfully. Redirecting to login page in <span id='countdown'>3</span> seconds...</p>
        </div>
        <script>
            let countdown = 3;
            const interval = setInterval(() => {
                countdown--;
                document.getElementById('countdown').textContent = countdown;
                if (countdown <= 0) {
                    clearInterval(interval);
                    window.location.href = '../login.php';
                }
            }, 1000);
        </script>
        ";
    } else {
        echo $message;
    ?>
    <form method="POST" class="w-50 mx-auto">
        <div class="mb-3">
            <label>Current Password:</label>
            <input type="password" name="current_password" id="current_password" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>New Password:</label>
            <input type="password" name="new_password" id="new_password" class="form-control" required oninput="checkStrength()">
            <div class="strength-meter">
                <div class="strength-meter-fill" id="strengthMeter"></div>
            </div>
        </div>
        <div class="mb-3">
            <label>Confirm New Password:</label>
            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
        </div>

        <div class="form-check mb-3">
            <input type="checkbox" class="form-check-input" onclick="togglePassword()" id="showPasswordCheck">
            <label class="form-check-label" for="showPasswordCheck">Show Passwords</label>
        </div>

        <button type="submit" class="btn btn-primary">Change Password</button>
    </form>
    <?php } ?>
</div>

<script>
document.getElementById("menu-toggle").addEventListener("click", function () {
    document.getElementById("sidebar").classList.toggle("active");
});

function togglePassword() {
    let fields = ["current_password", "new_password", "confirm_password"];
    fields.forEach(id => {
        let input = document.getElementById(id);
        input.type = input.type === "password" ? "text" : "password";
    });
}

function checkStrength() {
    let pwd = document.getElementById('new_password').value;
    let meter = document.getElementById('strengthMeter');
    let strength = 0;

    if (pwd.length >= 6) strength++;
    if (/[A-Z]/.test(pwd)) strength++;
    if (/[0-9]/.test(pwd)) strength++;
    if (/[\W]/.test(pwd)) strength++;

    const colors = ['red', 'orange', 'yellow', 'green'];
    const widths = ['25%', '50%', '75%', '100%'];
    meter.style.width = widths[strength - 1] || '0%';
    meter.style.backgroundColor = colors[strength - 1] || 'transparent';
}
</script>

<?php include('../includes/footer.php'); ?>
</body>
</html>
