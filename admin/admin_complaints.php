<?php
session_start();
include('../config/access_control.php');
include('../config/db.php');
include('../includes/header.php');

// Admin access only
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complaint_id'], $_POST['status'])) {
    $complaint_id = $_POST['complaint_id'];
    $status = $_POST['status'];

    $update = $pdo->prepare("UPDATE complaints SET status = ? WHERE id = ?");
    $update->execute([$status, $complaint_id]);
}

// Fetch complaints with student and hostel info
$stmt = $pdo->prepare("
    SELECT c.*, s.name AS student_name, s.reg_number, h.name AS hostel_name
    FROM complaints c
    LEFT JOIN students s ON c.reg_number = s.reg_number
    LEFT JOIN hostels h ON s.hostel_id = h.id
    ORDER BY c.created_at DESC
");
$stmt->execute();
$complaints = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Complaints</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4">📬 Student Complaints Management</h2>

    <?php if (isset($_POST['complaint_id'])): ?>
        <div class="alert alert-success">✅ Complaint status updated.</div>
    <?php endif; ?>

    <table class="table table-striped table-bordered align-middle">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Student Name</th>
                <th>Reg Number</th>
                <th>Hostel</th>
                <th>Title</th>
                <th>Message</th>
                <th>Status</th>
                <th>Submitted</th>
                <th>Update Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($complaints): ?>
                <?php foreach ($complaints as $index => $row): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= htmlspecialchars($row['student_name'] ?? 'Unknown') ?></td>
                        <td><?= htmlspecialchars($row['reg_number']) ?></td>
                        <td><?= htmlspecialchars($row['hostel_name'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($row['title']) ?></td>
                        <td><?= nl2br(htmlspecialchars($row['message'])) ?></td>
                        <td>
                            <?php
                                $badge = match ($row['status']) {
                                    'pending' => 'warning',
                                    'in progress' => 'info',
                                    'resolved' => 'success',
                                    default => 'secondary'
                                };
                            ?>
                            <span class="badge bg-<?= $badge ?> text-uppercase"><?= $row['status'] ?></span>
                        </td>
                        <td><?= date('d M Y H:i', strtotime($row['created_at'])) ?></td>
                        <td>
                            <form method="POST" class="d-flex gap-2">
                                <input type="hidden" name="complaint_id" value="<?= $row['id'] ?>">
                                <select name="status" class="form-select form-select-sm" required>
                                    <option <?= $row['status'] == 'pending' ? 'selected' : '' ?>>pending</option>
                                    <option <?= $row['status'] == 'in progress' ? 'selected' : '' ?>>in progress</option>
                                    <option <?= $row['status'] == 'resolved' ? 'selected' : '' ?>>resolved</option>
                                </select>
                                <button type="submit" class="btn btn-sm btn-primary">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="9" class="text-center text-muted">No complaints yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <a href="dashboard.php" class="btn btn-secondary mt-3"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
</div>

<?php include('../includes/footer.php'); ?>
</body>
</html>
