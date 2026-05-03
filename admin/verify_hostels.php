<?php
session_start();
include('../config/access_control.php');
include('../config/db.php');
include('../includes/header.php');

// Ensure admin only
//if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
  //  header("Location: ../login.php");
    //exit();
//}

// Handle Approve/Reject Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hostel_id = $_POST['hostel_id'];
    $action = $_POST['action'];

    if (in_array($action, ['approved', 'rejected'])) {
        $stmt = $pdo->prepare("UPDATE hostels SET status = ? WHERE id = ?");
        $stmt->execute([$action, $hostel_id]);
    }
}

// Fetch pending hostels
$stmt = $pdo->query("SELECT * FROM hostels WHERE status = 'pending'");
$hostels = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify Hostels</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4">Pending Hostel Approvals</h2>

    <?php if (count($hostels) === 0): ?>
        <div class="alert alert-info">No hostels pending approval.</div>
    <?php else: ?>
        <table class="table table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>Name</th>
                    <th>Location</th>
                    <th>Capacity</th>
                    <th>Gender</th>
                    <th>Owner</th>
                    <th>Contact</th>
                    <th>Distance</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($hostels as $hostel): ?>
                    <tr>
                        <td><?= htmlspecialchars($hostel['name']) ?></td>
                        <td><?= htmlspecialchars($hostel['location']) ?></td>
                        <td><?= $hostel['capacity'] ?></td>
                        <td><?= ucfirst($hostel['gender']) ?></td>
                        <td><?= htmlspecialchars($hostel['owner_name']) ?></td>
                        <td><?= htmlspecialchars($hostel['owner_contact']) ?></td>
                        <td><?= htmlspecialchars($hostel['distance']) ?> km</td>
                        <td>
                            <form method="POST" style="display:inline-block;">
                                <input type="hidden" name="hostel_id" value="<?= $hostel['id'] ?>">
                                <input type="hidden" name="action" value="approved">
                                <button class="btn btn-success btn-sm">Approve</button>
                            </form>
                            <form method="POST" style="display:inline-block;" onsubmit="return confirm('Reject this hostel?');">
                                <input type="hidden" name="hostel_id" value="<?= $hostel['id'] ?>">
                                <input type="hidden" name="action" value="rejected">
                                <button class="btn btn-danger btn-sm">Reject</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    <?php endif ?>
</div>
<?php include('../includes/footer.php'); ?>
</body>
</html>
