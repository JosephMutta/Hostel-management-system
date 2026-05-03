<?php
session_start();
require_once('../config/access_control.php');
require_once('../config/db.php');

// Ensure only hostel_owner
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'hostel_owner') {
    header("Location: ../index.php");
    exit();
}

$username = $_SESSION['username'];
$perPage = 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

// Export to Excel
if (isset($_POST['export_excel'])) {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=hostel_students_" . date("Ymd_His") . ".xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo "Hostel\tName\tRegistration Number\tEmail\tPhone\n";

    $stmt = $pdo->prepare("
        SELECT h.name AS hostel, s.name, s.reg_number, s.email, s.phone
        FROM students s
        INNER JOIN hostels h ON s.hostel_id = h.id
        WHERE h.owner_name = ?
    ");
    $stmt->execute([$username]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo implode("\t", array_map('htmlspecialchars', $row)) . "\n";
    }
    exit;
}

// Fetch paginated students data
$stmt = $pdo->prepare("
    SELECT h.name AS hostel, s.name, s.reg_number, s.email, s.phone
    FROM students s
    INNER JOIN hostels h ON s.hostel_id = h.id
    WHERE h.owner_name = ?
    LIMIT $perPage OFFSET $offset
");
$stmt->execute([$username]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count total for pagination
$countStmt = $pdo->prepare("
    SELECT COUNT(*) FROM students s
    INNER JOIN hostels h ON s.hostel_id = h.id
    WHERE h.owner_name = ?
");
$countStmt->execute([$username]);
$totalStudents = $countStmt->fetchColumn();
$totalPages = ceil($totalStudents / $perPage);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Students in My Hostels</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4"><i class="fa-solid fa-users"></i> Students in My Hostels</h2>

    <div class="d-flex justify-content-between mb-3">
        <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        <form method="post">
            <button type="submit" name="export_excel" class="btn btn-success">
                <i class="fas fa-file-excel"></i> Export to Excel
            </button>
        </form>
    </div>

    <?php if (count($students) > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Hostel</th>
                        <th>Name</th>
                        <th>Registration Number</th>
                        <th>Email</th>
                        <th>Phone</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $index => $s): ?>
                        <tr>
                            <td><?= ($offset + $index + 1) ?></td>
                            <td><?= htmlspecialchars($s['hostel']) ?></td>
                            <td><?= htmlspecialchars($s['name']) ?></td>
                            <td><?= htmlspecialchars($s['reg_number']) ?></td>
                            <td><?= htmlspecialchars($s['email']) ?></td>
                            <td><?= htmlspecialchars($s['phone']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <nav>
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php else: ?>
        <div class="alert alert-info"><i class="fas fa-info-circle"></i> No students found in your hostels.</div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
