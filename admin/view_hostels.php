<?php
include('../config/access_control.php');
include('../config/db.php');
include('../includes/header.php');

$stmt = $pdo->prepare("SELECT * FROM hostels");
$stmt->execute();
$hostels = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="../css/style.css">
<h2>All Hostels</h2>
<table border="1" cellpadding="8">
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Location</th>
        <th>Capacity</th>
        <th>Gender</th>
        <th>Owner Name</th>
        <th>Owner Contact</th>
        <th>Distance (km)</th>
    </tr>
    <?php foreach ($hostels as $hostel): ?>
    <tr>
        <td><?= htmlspecialchars($hostel['id']) ?></td>
        <td><?= htmlspecialchars($hostel['name']) ?></td>
        <td><?= htmlspecialchars($hostel['location']) ?></td>
        <td><?= htmlspecialchars($hostel['capacity']) ?></td>
        <td><?= htmlspecialchars($hostel['gender']) ?></td>
        <td><?= htmlspecialchars($hostel['owner_name']) ?></td>
        <td><?= htmlspecialchars($hostel['owner_contact']) ?></td>
        <td><?= htmlspecialchars($hostel['distance']) ?></td>
    </tr>
    <?php endforeach; ?>
</table>

<?php include('../includes/footer.php'); ?>
