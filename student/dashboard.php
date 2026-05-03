<?php
session_start();
include('../config/access_control.php');
include('../config/db.php');
include('../includes/header.php');

// Ensure only logged-in student can access
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}

$username = $_SESSION['username']; // this is reg_number
$stmt = $pdo->prepare("
    SELECT s.name, s.email, s.phone, h.name AS hostel_name, r.room_number
    FROM students s
    LEFT JOIN hostels h ON s.hostel_id = h.id
    LEFT JOIN rooms r ON s.room_id = r.id
    WHERE s.reg_number = ?
");
$stmt->execute([$username]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch announcements
$annStmt = $pdo->query("SELECT title, message, created_at FROM announcements ORDER BY created_at DESC LIMIT 5");
$announcements = $annStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Bootstrap and styles -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<style>
/* Sidebar */
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
#sidebar ul { list-style: none; padding: 0; margin: 0; }
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

/* Sidebar Toggle Button */
#menu-toggle-btn {
    position: fixed;
    top: 15px;
    left: 15px;
    z-index: 1100;
    font-size: 22px;
}

/* Top Bar */
.top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 15px 0;
    flex-wrap: wrap;
}
.profile-pic {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background-color: #ccc;
    background-image: url('../images/default-profile.png');
    background-size: cover;
    background-position: center;
}
.notification-icon {
    position: relative;
    margin-right: 20px;
    cursor: pointer;
}
.notification-icon i {
    font-size: 24px;
}
.notification-dropdown {
    position: absolute;
    top: 30px;
    right: 0;
    background-color: #fff;
    border: 1px solid #ddd;
    width: 300px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
    display: none;
    z-index: 2000;
}
.notification-dropdown.active {
    display: block;
}
.notification-dropdown .item {
    padding: 10px;
    border-bottom: 1px solid #eee;
}
.notification-dropdown .item:last-child {
    border-bottom: none;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    #sidebar {
        width: 200px;
    }
    .top-bar h4 {
        font-size: 16px;
    }
    .notification-dropdown {
        width: 90vw;
        right: 10px;
    }
}
</style>

<!-- Sidebar Toggle Button (Top Left) -->
<button id="menu-toggle-btn" class="btn btn-primary">&#9776;</button>

<!-- Sidebar Menu -->
<div id="sidebar">
    <ul>
        <li><a href="edit_profile.php">📝 Edit Profile</a></li>
        <li><a href="change_password.php">🔒 Change Password</a></li>
        <li><a href="send_complaint.php">🆘 Send Complaint</a></li>
        <li><a href="../logout.php">🚪 Logout</a></li>
        <li><a href="#" onclick="document.getElementById('sidebar').classList.remove('active'); return false;">⬅️ Back</a></li>
    </ul>
</div>

<!-- Page Content -->
<div class="container">
    <!-- Top Bar -->
    <div class="top-bar">
        <h4 class="m-0">Welcome, <?= htmlspecialchars($student['name']) ?>!</h4>
        <div class="d-flex align-items-center">
            <div class="notification-icon" onclick="toggleNotifications()">
                <i class="bi bi-bell"></i>
                <div class="notification-dropdown" id="notificationDropdown">
                    <strong class="px-3 pt-2 d-block">📢 Announcements</strong>
                    <?php foreach ($announcements as $note): ?>
                        <div class="item">
                            <strong><?= htmlspecialchars($note['title']) ?></strong><br>
                            <small><?= htmlspecialchars($note['message']) ?></small><br>
                            <small class="text-muted"><?= date('d M Y', strtotime($note['created_at'])) ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="profile-pic ms-3"></div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="mt-3">
        <p><strong>Registration Number:</strong> <?= htmlspecialchars($username) ?></p>

        <?php if ($student['hostel_name'] && $student['room_number']): ?>
            <h5>Hostel Details</h5>
            <p><strong>Hostel:</strong> <?= htmlspecialchars($student['hostel_name']) ?></p>
            <p><strong>Room:</strong> <?= htmlspecialchars($student['room_number']) ?></p>
        <?php else: ?>
            <p class="text-warning">You are not yet assigned to any hostel or room.</p>

            <?php
            // Fetch all hostels with name, distance, and gender
            $hostelStmt = $pdo->query("SELECT name, distance, gender FROM hostels WHERE status = 'approved' ORDER BY name ASC");
            $hostels = $hostelStmt->fetchAll(PDO::FETCH_ASSOC);
            ?>

            <?php if ($hostels): ?>
                <h5>Available Hostels</h5>
                <table class="table table-striped table-hover table-bordered shadow-sm rounded">
                    <thead class="table-primary">
                        <tr>
                            <th>Hostel Name</th>
                            <th>Distance (km)</th>
                            <th>Admits</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($hostels as $hostel): ?>
                            <tr>
                                <td><?= htmlspecialchars($hostel['name']) ?></td>
                                <td><?= htmlspecialchars($hostel['distance']) ?></td>
                                <td>
                                    <?php
                                    $gender = $hostel['gender'];
                                    if (strtolower($gender) === 'male') {
                                        echo 'Males';
                                    } elseif (strtolower($gender) === 'female') {
                                        echo 'Females';
                                    } else {
                                        echo 'Both';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No hostels available at the moment.</p>
            <?php endif; ?>

            <a href="register.php" class="btn btn-outline-primary">Register for Hostel</a>
        <?php endif; ?>
    </div>
</div>

<!-- Scripts -->
<script>
document.getElementById("menu-toggle-btn").addEventListener("click", function () {
    document.getElementById("sidebar").classList.toggle("active");
});

function toggleNotifications() {
    const dropdown = document.getElementById('notificationDropdown');
    dropdown.classList.toggle('active');
}
</script>

<?php include('../includes/footer.php'); ?>
