<?php
session_start();
include('../config/access_control.php');
include('../config/db.php');
include('../includes/header.php');

// Redirect if not admin
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Fetch latest 5 complaints for notification
$notiStmt = $pdo->prepare("
    SELECT c.title, c.created_at, s.name AS student_name, h.name AS hostel_name
    FROM complaints c
    LEFT JOIN students s ON c.reg_number = s.reg_number
    LEFT JOIN hostels h ON s.hostel_id = h.id
    ORDER BY c.created_at DESC
    LIMIT 5
");
$notiStmt->execute();
$notifications = $notiStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>

    <!-- CSS & Icons -->
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --bg-color: #f8f9fa;
            --text-color: #212529;
            --card-bg: #ffffff;
        }

        body.dark-mode {
            --bg-color: #121212;
            --text-color: #ffffff;
            --card-bg: #1f1f1f;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            transition: background 0.3s ease, color 0.3s ease;
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

        .submenu.show { display: block; }

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

        .notification-icon {
            position: fixed;
            top: 15px;
            right: 15px;
            z-index: 1000;
            cursor: pointer;
        }

        .notification-icon i { font-size: 20px; }

        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: red;
            color: white;
            font-size: 11px;
            padding: 2px 6px;
            border-radius: 50%;
        }

        .notification-dropdown {
            position: absolute;
            top: 40px;
            right: 0;
            background: white;
            border: 1px solid #ccc;
            width: 300px;
            max-height: 350px;
            overflow-y: auto;
            z-index: 1001;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: none;
        }

        .notification-dropdown.active { display: block; }

        .notification-dropdown .item {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        .notification-dropdown .item:last-child { border-bottom: none; }

        .theme-toggle {
            position: fixed;
            top: 15px;
            right: 60px;
            z-index: 1000;
        }

        main {
            margin-left: 0;
            padding: 20px;
            transition: margin-left 0.3s ease-in-out;
        }

        #sidebar.active ~ main {
            margin-left: 260px;
        }

        .stats-cards {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }

        .stats-card {
            background-color: var(--card-bg);
            border-radius: 10px;
            padding: 25px;
            flex: 1;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .export-btns {
            margin: 20px 0;
        }

        .chart-container {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .chart-box {
            background: var(--card-bg);
            border-radius: 8px;
            padding: 20px;
            flex: 1 1 45%;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        @media (max-width: 768px) {
            #sidebar { width: 200px; }
            #sidebar.active ~ main { margin-left: 0; }
        }
    </style>
</head>
<body>

<!-- Sidebar toggle -->
<button class="toggle-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>

<!-- Notification Bell -->
<div class="notification-icon" onclick="toggleNotifications()">
    <i class="fas fa-bell"></i>
    <?php if (count($notifications) > 0): ?>
        <span class="notification-badge"><?= count($notifications) ?></span>
    <?php endif; ?>
    <div class="notification-dropdown" id="notificationDropdown">
        <strong class="d-block p-2 border-bottom">📬 New Complaints</strong>
        <?php foreach ($notifications as $note): ?>
            <div class="item">
                <strong><?= htmlspecialchars($note['title']) ?></strong><br>
                <small>
                    <?= htmlspecialchars($note['student_name']) ?> (<?= htmlspecialchars($note['hostel_name']) ?>)
                </small><br>
                <small class="text-muted"><?= date('d M Y, H:i', strtotime($note['created_at'])) ?></small>
            </div>
        <?php endforeach; ?>
        <div class="p-2 text-end">
            <a href="admin_complaints.php" class="btn btn-sm btn-outline-primary">View All</a>
        </div>
    </div>
</div>

<!-- Theme toggle -->
<div class="theme-toggle">
    <button class="btn btn-sm btn-dark" onclick="toggleTheme()">Toggle Theme</button>
</div>

<!-- Sidebar -->
<div id="sidebar">
    <ul>
        <li><a href="add_hostel.php"><i class="fas fa-building"></i> Add Hostel</a></li>
        <li><a href="add_room.php"><i class="fas fa-door-open"></i> Add Room</a></li>
        <li><a href="verify_hostel_requests.php"><i class="fas fa-check-circle"></i> Verify Hostel Updates</a></li>

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

<main>
    <h1>Welcome Admin, <?= htmlspecialchars($_SESSION['username']) ?></h1>

    <!-- Stats Cards -->
    <div class="stats-cards">
        <div class="stats-card"><h3>0</h3><p>Total Students</p></div>
        <div class="stats-card"><h3>0</h3><p>Total Rooms</p></div>
        <div class="stats-card"><h3>0</h3><p>Total Hostels</p></div>
    </div>

    <!-- Export Buttons -->
    <div class="export-btns">
        <form method="POST" action="export_dashboard.php" class="d-flex gap-2">
            <input type="hidden" name="export_type" value="pdf" id="exportTypeInput">
            <button type="submit" class="btn btn-danger" onclick="document.getElementById('exportTypeInput').value='pdf'">Export PDF</button>
            <button type="submit" class="btn btn-success" onclick="document.getElementById('exportTypeInput').value='excel'">Export Excel</button>
        </form>
    </div>

    <!-- Chart Containers -->
    <div class="chart-container">
        <div class="chart-box">
            <canvas id="hostelPieChart"></canvas>
        </div>
        <div class="chart-box">
            <canvas id="roomBarChart"></canvas>
        </div>
    </div>
</main>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('active');
}

function toggleSubmenu(e) {
    e.preventDefault();
    document.getElementById('userSubmenu').classList.toggle('show');
}

function toggleTheme() {
    document.body.classList.toggle('dark-mode');
    localStorage.setItem('theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
}

function toggleNotifications() {
    document.getElementById('notificationDropdown').classList.toggle('active');
}

let hostelPieChart, roomBarChart;

window.onload = function () {
    if (localStorage.getItem('theme') === 'dark') {
        document.body.classList.add('dark-mode');
    }
    fetchData();
    setInterval(fetchData, 10000);
};

function fetchData() {
    fetch('fetch_dashboard_data.php')
        .then(res => res.json())
        .then(data => {
            updateStats(data.totals);
            updateHostelChart(data.hostel_chart);
            updateRoomChart(data.room_chart);
        });
}

function updateStats(totals) {
    const cards = document.querySelectorAll('.stats-card h3');
    cards[0].textContent = totals.students;
    cards[1].textContent = totals.rooms;
    cards[2].textContent = totals.hostels;
}

function updateHostelChart(data) {
    const labels = data.map(item => item.hostel);
    const counts = data.map(item => item.total_students);
    if (hostelPieChart) {
        hostelPieChart.data.labels = labels;
        hostelPieChart.data.datasets[0].data = counts;
        hostelPieChart.update();
    } else {
        hostelPieChart = new Chart(document.getElementById('hostelPieChart'), {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: counts,
                    backgroundColor: ['#3498db', '#e74c3c', '#2ecc71', '#f1c40f', '#9b59b6']
                }]
            }
        });
    }
}

function updateRoomChart(data) {
    const labels = data.map(item => item.room_number);
    const counts = data.map(item => item.occupants);
    if (roomBarChart) {
        roomBarChart.data.labels = labels;
        roomBarChart.data.datasets[0].data = counts;
        roomBarChart.update();
    } else {
        roomBarChart = new Chart(document.getElementById('roomBarChart'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Occupants',
                    data: counts,
                    backgroundColor: '#2ecc71'
                }]
            },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true } }
            }
        });
    }
}
</script>

<?php include('../includes/footer.php'); ?>
</body>
</html>
