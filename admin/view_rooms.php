<?php
include('../config/access_control.php');
include('../config/db.php');
include('../includes/header.php');

$stmt = $pdo->prepare("
    SELECT r.*, h.name AS hostel_name 
    FROM rooms r
    JOIN hostels h ON r.hostel_id = h.id
");
$stmt->execute();
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Bootstrap 5 CDN -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<div class="container mt-5">
    <h2 class="mb-4 text-center">All Rooms</h2>
    
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th scope="col">Room ID</th>
                    <th scope="col">Room Number</th>
                    <th scope="col">Hostel</th>
                    <th scope="col">Capacity</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rooms as $room): ?>
                <tr>
                    <td><?= htmlspecialchars($room['id']) ?></td>
                    <td><?= htmlspecialchars($room['room_number']) ?></td>
                    <td><?= htmlspecialchars($room['hostel_name']) ?></td>
                    <td><?= htmlspecialchars($room['capacity']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include('../includes/footer.php'); ?>
