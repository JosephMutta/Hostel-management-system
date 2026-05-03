<?php
session_start();
include('../config/access_control.php');
include('../config/db.php');
include('../includes/header.php');

// Ensure only dean can access
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'dean') {
    header("Location: ../index.php");
    exit();
}

// Fetch latest unread complaints
$complaintStmt = $pdo->prepare("SELECT reg_number, title, message, created_at FROM complaints WHERE status = 'unread' ORDER BY created_at DESC LIMIT 5");
$complaintStmt->execute();
$complaints = $complaintStmt->fetchAll(PDO::FETCH_ASSOC);
$unreadCount = count($complaints);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dean Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">

    <style>
        body.dark-mode {
            background-color: #121212;
            color: #ffffff;
        }

        body.dark-mode .card {
            background-color: #1f1f1f;
            color: white;
        }

        body.dark-mode .notification-dropdown {
            background-color: #1f1f1f;
            color: white;
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

        #sidebar ul { list-style-type: none; padding: 0; }

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
            position: fixed;
            top: 15px;
            left: 15px;
            font-size: 22px;
            color: #003366;
            background: none;
            border: none;
            z-index: 1100;
        }

        .main-content {
            margin-left: 0;
            padding: 20px;
            transition: margin-left 0.3s ease;
        }

        #sidebar.active ~ .main-content {
            margin-left: 230px;
        }

        .notification-icon {
            position: fixed;
            top: 15px;
            right: 70px;
            font-size: 24px;
            color: #003366;
            cursor: pointer;
            z-index: 1101;
        }

        .notification-icon .badge {
            background-color: red;
            color: white;
            border-radius: 50%;
            font-size: 12px;
            padding: 4px 7px;
            vertical-align: top;
            position: relative;
            top: -12px;
            left: -10px;
        }

        .notification-dropdown {
            display: none;
            position: fixed;
            top: 50px;
            right: 20px;
            width: 320px;
            background-color: white;
            border: 1px solid #ccc;
            border-radius: 5px;
            z-index: 1102;
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
        }

        .notification-dropdown.active {
            display: block;
        }

        .notification-dropdown h6 {
            background-color: #003366;
            color: white;
            margin: 0;
            padding: 10px;
        }

        .notification-dropdown .item {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        .notification-dropdown .item:last-child {
            border-bottom: none;
        }

        .notification-dropdown .item small {
            color: gray;
            font-size: 0.85em;
        }

        .theme-toggle {
            position: fixed;
            top: 15px;
            right: 20px;
            font-size: 22px;
            cursor: pointer;
            z-index: 1101;
        }
    </style>
</head>
<body>

<!-- Toggle Sidebar Button -->
<button id="menu-toggle"><i class="fas fa-bars"></i></button>

<!-- Notification Bell -->
<div class="notification-icon" id="notif-icon">
    <i class="fas fa-bell"></i>
    <?php if ($unreadCount > 0): ?>
        <span class="badge"><?= $unreadCount ?></span>
    <?php endif; ?>
</div>

<!-- Theme Toggle Button -->
<div class="theme-toggle" id="theme-toggle">
    <i class="fas fa-moon"></i>
</div>

<!-- Notification Dropdown -->
<div class="notification-dropdown" id="notif-dropdown">
    <h6>Unread Complaints</h6>
    <?php if ($unreadCount > 0): ?>
        <?php foreach ($complaints as $c): ?>
            <div class="item">
                <strong><?= htmlspecialchars($c['reg_number']) ?></strong><br>
                <span><?= htmlspecialchars($c['title']) ?></span><br>
                <small><?= date("d M Y, H:i", strtotime($c['created_at'])) ?></small>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="item">No unread complaints.</div>
    <?php endif; ?>
</div>

<!-- Sidebar -->
<div id="sidebar">
    <ul>
        <li><a href="view_students.php"><i class="fas fa-users"></i> View Students</a></li>
        <li><a href="hostel_occupancy.php"><i class="fas fa-bed"></i> Hostel Occupancy</a></li>
        <li><a href="change_password.php"><i class="fas fa-key"></i> Change Password</a></li>
        <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        <li><a href="#" onclick="document.getElementById('sidebar').classList.remove('active'); return false;"><i class="fas fa-arrow-left"></i> Back</a></li>
    </ul>
</div>

<!-- Main Content -->
<div class="main-content">
    <h2 class="mb-4">Welcome Dean of Students, <?= htmlspecialchars($_SESSION['username']) ?>!</h2>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <i class="fas fa-users fa-2x mb-3 text-primary"></i>
                    <h5>All Hostel Students</h5>
                    <a href="view_students.php" class="btn btn-outline-primary">Go</a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <i class="fas fa-search fa-2x mb-3 text-success"></i>
                    <h5>Search by Reg No</h5>
                    <a href="search_student.php" class="btn btn-outline-success">Go</a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <i class="fas fa-bed fa-2x mb-3 text-warning"></i>
                    <h5>Hostel Occupancy</h5>
                    <a href="hostel_occupancy.php" class="btn btn-outline-warning">View</a>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <i class="fas fa-bullhorn fa-2x mb-3 text-info"></i>
                    <h5>Make Announcements</h5>
                    <a href="make_announcement.php" class="btn btn-outline-info">Create</a>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <i class="fas fa-file-alt fa-2x mb-3 text-secondary"></i>
                    <h5>Generate Reports</h5>
                    <a href="generate_reports.php" class="btn btn-outline-secondary">Generate</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script>
    // Sidebar toggle
    document.getElementById("menu-toggle").addEventListener("click", () => {
        document.getElementById("sidebar").classList.toggle("active");
    });

    // Notification toggle
    document.getElementById("notif-icon").addEventListener("click", () => {
        document.getElementById("notif-dropdown").classList.toggle("active");
    });

    // Theme toggle logic
    const themeToggle = document.getElementById("theme-toggle");
    const icon = themeToggle.querySelector("i");

    function applyTheme() {
        const theme = localStorage.getItem("theme");
        if (theme === "dark") {
            document.body.classList.add("dark-mode");
            icon.classList.remove("fa-moon");
            icon.classList.add("fa-sun");
        } else {
            document.body.classList.remove("dark-mode");
            icon.classList.remove("fa-sun");
            icon.classList.add("fa-moon");
        }
    }

    themeToggle.addEventListener("click", () => {
        const currentTheme = document.body.classList.contains("dark-mode") ? "light" : "dark";
        localStorage.setItem("theme", currentTheme);
        applyTheme();
    });

    // Initial theme load
    applyTheme();
</script>

<?php include('../includes/footer.php'); ?>
</body>
</html>
