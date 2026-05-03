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

// Fetch all hostels with occupancy data
$hostels = $pdo->query("
    SELECT h.id, h.name,
           COUNT(r.id) AS total_rooms,
           COALESCE(SUM(r.capacity), 0) AS total_capacity,
           COALESCE(SUM(r.occupied), 0) AS total_occupied
    FROM hostels h
    LEFT JOIN rooms r ON h.id = r.hostel_id
    GROUP BY h.id, h.name
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Hostel Occupancy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        .accordion-button:after {
            content: "\f078";
            font-family: FontAwesome;
            float: right;
        }
        .accordion-button.collapsed:after {
            content: "\f054";
        }
    </style>
</head>
<body class="bg-light">

<div class="container mt-5">
    <h2 class="mb-4">🏨 Hostel Occupancy Report</h2>

    <?php if (count($hostels) === 0): ?>
        <div class="alert alert-info">No hostel data found.</div>
    <?php else: ?>
        <div class="accordion" id="hostelAccordion">
            <?php foreach ($hostels as $index => $hostel): ?>
                <?php
                    $percentage = ($hostel['total_capacity'] > 0)
                        ? round(($hostel['total_occupied'] / $hostel['total_capacity']) * 100)
                        : 0;
                    $hostelId = $hostel['id'];
                ?>
                <div class="accordion-item mb-2">
                    <h2 class="accordion-header" id="heading<?= $index ?>">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $index ?>">
                            <?= htmlspecialchars($hostel['name']) ?> 
                            &nbsp; — <?= $percentage ?>% Occupied
                        </button>
                    </h2>
                    <div id="collapse<?= $index ?>" class="accordion-collapse collapse" data-bs-parent="#hostelAccordion">
                        <div class="accordion-body">
                            <p><strong>Total Rooms:</strong> <?= $hostel['total_rooms'] ?></p>
                            <p><strong>Total Capacity:</strong> <?= $hostel['total_capacity'] ?></p>
                            <p><strong>Total Occupied:</strong> <?= $hostel['total_occupied'] ?></p>

                            <hr>
                            <h6>Room Details:</h6>
                            <table class="table table-bordered table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Room Number</th>
                                        <th>Capacity</th>
                                        <th>Occupied</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmt = $pdo->prepare("SELECT room_number, capacity, occupied FROM rooms WHERE hostel_id = ?");
                                    $stmt->execute([$hostelId]);
                                    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($rooms as $room):
                                    ?>
                                        <tr>
                                            <td><?= htmlspecialchars($room['room_number']) ?></td>
                                            <td><?= $room['capacity'] ?></td>
                                            <td><?= $room['occupied'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>

                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="mt-4">
        <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
