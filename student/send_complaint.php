<?php
session_start();
include('../config/access_control.php');
include('../config/db.php');
include('../includes/header.php');

// Ensure only students can access
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}

$reg_number = $_SESSION['username'];
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $message = trim($_POST['message']);

    if (empty($title) || empty($message)) {
        $error = "Please fill in both the title and complaint message.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO complaints (reg_number, title, message) VALUES (?, ?, ?)");
        if ($stmt->execute([$reg_number, $title, $message])) {
            $success = "Your complaint has been submitted successfully.";
        } else {
            $error = "Something went wrong. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submit Complaint</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', sans-serif;
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

        #sidebar ul {
            list-style: none;
            padding: 0;
        }

        #sidebar ul li {
            padding: 15px 20px;
            border-bottom: 1px solid #555;
        }

        #sidebar ul li a {
            color: white;
            text-decoration: none;
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
            background: none;
            border: none;
            color: #003366;
            z-index: 1100;
        }

        .container {
            margin-left: 0;
            padding-top: 80px;
            transition: margin-left 0.3s ease;
        }

        #sidebar.active ~ .container {
            margin-left: 250px;
        }

        .top-bar {
            position: fixed;
            top: 0;
            width: 100%;
            height: 60px;
            background-color: #ffffff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            padding-left: 60px;
            padding-right: 20px;
            z-index: 100;
        }

        .top-bar h4 {
            margin: 0;
        }

        .complaint-form {
            max-width: 700px;
            margin: 0 auto;
            background-color: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .profile-pic {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: url('../images/default-profile.png') no-repeat center;
            background-size: cover;
            margin-left: auto;
        }

        .alert {
            max-width: 700px;
            margin: 15px auto;
        }
    </style>
</head>
<body>

<!-- Sidebar Toggle -->
<button id="menu-toggle"><i class="bi bi-list"></i></button>

<!-- Sidebar -->
<div id="sidebar">
    <ul>
        <li><a href="dashboard.php"><i class="bi bi-house"></i> Dashboard</a></li>
        <li><a href="edit_profile.php"><i class="bi bi-pencil-square"></i> Edit Profile</a></li>
        <li><a href="change_password.php"><i class="bi bi-key"></i> Change Password</a></li>
        <li><a href="send_complaint.php"><i class="bi bi-exclamation-circle"></i> Send Complaint</a></li>
        <li><a href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        <li><a href="#" onclick="document.getElementById('sidebar').classList.remove('active'); return false;">⬅️ Hide</a></li>
    </ul>
</div>

<!-- Top Bar -->
<div class="top-bar">
    <h4 class="mb-0">📢 Send Complaint</h4>
    <div class="profile-pic"></div>
</div>

<!-- Main Content -->
<div class="container">
    <?php if ($success): ?>
        <div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($success) ?></div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="complaint-form">
        <h5 class="mb-3">What's the issue?</h5>
        <form method="POST">
            <div class="mb-3">
                <label for="title" class="form-label">Complaint Title</label>
                <input type="text" name="title" id="title" class="form-control" placeholder="E.g. Broken window in Room 3" required>
            </div>

            <div class="mb-3">
                <label for="message" class="form-label">Describe Your Complaint</label>
                <textarea name="message" id="message" rows="5" class="form-control" placeholder="Please explain clearly what the problem is..." required></textarea>
            </div>

            <div class="d-flex justify-content-between">
                <a href="dashboard.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
                <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i> Submit Complaint</button>
            </div>
        </form>
    </div>
</div>

<!-- Scripts -->
<script>
document.querySelectorAll("#menu-toggle").forEach(button => {
    button.addEventListener("click", () => {
        document.getElementById("sidebar").classList.toggle("active");
    });
});
</script>

<?php include('../includes/footer.php'); ?>
</body>
</html>
