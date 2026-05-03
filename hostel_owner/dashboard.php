<?php
session_start();
include('../config/access_control.php');
include('../config/db.php');
include('../includes/header.php');

// Ensure only hostel_owner
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'hostel_owner') {
    header("Location: ../index.php");
    exit();
}

$username = $_SESSION['username'];

// Fetch hostels owned by this owner
$stmt = $pdo->prepare("SELECT * FROM hostels WHERE owner_name = ?");
$stmt->execute([$username]);
$hostels = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch pending update requests
$pendingStmt = $pdo->prepare("SELECT * FROM hostel_update_requests WHERE owner_name = ? AND status = 'pending'");
$pendingStmt->execute([$username]);
$pendingRequests = $pendingStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch verified count
$verifiedStmt = $pdo->prepare("SELECT COUNT(*) FROM hostel_update_requests WHERE owner_name = ? AND status = 'verified'");
$verifiedStmt->execute([$username]);
$verifiedCount = $verifiedStmt->fetchColumn();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Hostel Owner Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
            position: fixed;
            top: 15px;
            left: 15px;
            font-size: 22px;
            background: none;
            border: none;
            color: #003366;
            z-index: 1100;
        }
        .main-content {
            margin-top: 80px;
        }
        .card-title {
            font-size: 1.25rem;
            font-weight: bold;
        }
        .badge-custom {
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div id="sidebar">
    <ul>
        <li><a href="dashboard.php">🏠 Dashboard</a></li>
        <li><a href="add_hostel.php">➕ Register Another Hostel</a></li>
        <li><a href="owner_students.php">👨‍🎓 My Students</a></li> <!-- New link added here -->
        <li><a href="../logout.php">🚪 Logout</a></li>
    </ul>
</div>

<!-- Toggle Sidebar Button -->
<button id="menu-toggle">&#9776;</button>

<!-- Main Content -->
<div class="container main-content">
    <h2 class="mb-4">Welcome, <?= htmlspecialchars($username) ?>!</h2>

    <!-- Notifications -->
    <div class="mb-4">
        <?php if (count($pendingRequests) > 0): ?>
            <div class="alert alert-warning">📌 You have <strong><?= count($pendingRequests) ?></strong> update request(s) pending approval.</div>
        <?php endif; ?>

        <?php if ($verifiedCount > 0): ?>
            <div class="alert alert-success">✅ <strong><?= $verifiedCount ?></strong> request(s) have been verified and approved.</div>
        <?php endif; ?>
    </div>

    <!-- Hostels List -->
    <div class="row mb-5">
        <?php if (count($hostels) > 0): ?>
            <?php foreach ($hostels as $hostel): ?>
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($hostel['name']) ?></h5>
                            <p class="card-text">
                                <strong>Location:</strong> <?= $hostel['location'] ?? '<i>Not Set</i>' ?><br>
                                <strong>Capacity:</strong> <?= $hostel['capacity'] ?? '<i>Not Set</i>' ?>
                            </p>
                            <a href="edit_hostel_request.php?id=<?= $hostel['id'] ?>" class="btn btn-outline-primary btn-sm">✏️ Update Hostel</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-warning">You haven't registered any hostels yet.</div>
        <?php endif; ?>
    </div>

    <!-- Pending Requests Details -->
    <?php if (count($pendingRequests) > 0): ?>
        <h4 class="mb-3">🕒 Pending Update Requests</h4>
        <div class="table-responsive mb-5">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Hostel Name</th>
                        <th>Proposed Location</th>
                        <th>Proposed Capacity</th>
                        <th>Proposed Rooms</th>
                        <th>Status</th>
                        <th>Date Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingRequests as $req): ?>
                        <tr>
                            <td><?= htmlspecialchars($req['hostel_name']) ?></td>
                            <td><?= htmlspecialchars($req['proposed_location']) ?></td>
                            <td><?= htmlspecialchars($req['proposed_capacity']) ?></td>
                            <td>
                                <ul>
                                    <?php
                                    $rooms = json_decode($req['proposed_rooms'], true);
                                    if (is_array($rooms)) {
                                        foreach ($rooms as $room) {
                                            echo "<li>" . htmlspecialchars($room) . "</li>";
                                        }
                                    } else {
                                        echo "<li><i>Invalid data</i></li>";
                                    }
                                    ?>
                                </ul>
                            </td>
                            <td><span class="badge bg-warning text-dark"><?= htmlspecialchars($req['status']) ?></span></td>
                            <td><?= date('d M Y, H:i', strtotime($req['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
    document.getElementById("menu-toggle").addEventListener("click", function () {
        document.getElementById("sidebar").classList.toggle("active");
    });
</script>

<?php include('../includes/footer.php'); ?>
</body>
</html>
