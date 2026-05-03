<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('../config/access_control.php');
include('../config/db.php');
include('../includes/header.php');

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'dean') {
    header("Location: ../index.php");
    exit();
}

$reportType = $_GET['report_type'] ?? 'student_list';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

function fetchStudents($pdo, $limit, $offset) {
    $query = "
        SELECT 
            s.id,
            s.name,
            s.email,
            s.phone,
            h.name AS hostel_name,
            h.location AS hostel_location,
            r.room_number,
            r.capacity
        FROM students s
        LEFT JOIN hostels h ON s.hostel_id = h.id
        LEFT JOIN rooms r ON s.room_id = r.id
        ORDER BY s.id DESC
        LIMIT ? OFFSET ?
    ";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(2, (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function countStudents($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM students");
    return $stmt->fetchColumn();
}

function fetchComplaints($pdo) {
    $stmt = $pdo->prepare("
        SELECT c.*, s.name AS student_name, h.name AS hostel_name
        FROM complaints c
        LEFT JOIN students s ON c.reg_number = s.reg_number
        LEFT JOIN hostels h ON s.hostel_id = h.id
        ORDER BY c.created_at DESC
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$totalItems = 0;
$items = [];

if ($reportType === 'student_list') {
    $totalItems = countStudents($pdo);
    $items = fetchStudents($pdo, $limit, $offset);
} elseif ($reportType === 'complaints') {
    $items = fetchComplaints($pdo);
    $totalItems = count($items);
}

$totalPages = ceil($totalItems / $limit);

if (isset($_GET['download']) && $_GET['download'] == '1') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $reportType . '_report.csv"');
    $output = fopen('php://output', 'w');

    if ($reportType === 'student_list') {
        fputcsv($output, ['Name', 'Email', 'Phone', 'Hostel', 'Location', 'Room', 'Capacity']);
        foreach ($items as $student) {
            fputcsv($output, [
                $student['name'],
                $student['email'],
                $student['phone'] ?? 'N/A',
                $student['hostel_name'] ?? 'N/A',
                $student['hostel_location'] ?? 'N/A',
                $student['room_number'] ?? 'N/A',
                $student['capacity'] ?? 'N/A',
            ]);
        }
    } elseif ($reportType === 'complaints') {
        fputcsv($output, ['Student Name', 'Hostel', 'Title', 'Message', 'Status', 'Submitted']);
        foreach ($items as $complaint) {
            fputcsv($output, [
                $complaint['student_name'] ?? 'Unknown',
                $complaint['hostel_name'] ?? 'N/A',
                $complaint['title'],
                $complaint['message'],
                $complaint['status'],
                $complaint['created_at'],
            ]);
        }
    }
    fclose($output);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Generate Reports - Dean</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .theme-toggle {
            position: fixed;
            top: 15px;
            right: 25px;
            font-size: 22px;
            cursor: pointer;
            z-index: 1101;
        }

        body.dark-mode {
            background-color: #121212;
            color: #ffffff;
        }

        body.dark-mode .table {
            color: #ffffff;
        }

        body.dark-mode .table-striped tbody tr:nth-of-type(odd) {
            background-color: #1f1f1f;
        }

        body.dark-mode .table thead {
            background-color: #2c2c2c;
        }

        body.dark-mode .card, 
        body.dark-mode .form-control, 
        body.dark-mode .form-select, 
        body.dark-mode .btn {
            background-color: #1f1f1f;
            color: #ffffff;
        }

        body.dark-mode .form-select option {
            background-color: #1f1f1f;
            color: #fff;
        }
    </style>
</head>
<body>
<!-- Theme toggle button -->
<div class="theme-toggle" id="theme-toggle">
    <i class="fas fa-moon"></i>
</div>

<div class="container mt-5">
    <h2>Generate Reports</h2>
    <form method="GET" class="mb-4">
        <label for="report_type" class="form-label">Select Report Type:</label>
        <select name="report_type" id="report_type" class="form-select" onchange="this.form.submit()">
            <option value="student_list" <?= $reportType === 'student_list' ? 'selected' : '' ?>>Student List</option>
            <option value="complaints" <?= $reportType === 'complaints' ? 'selected' : '' ?>>Complaints</option>
        </select>
    </form>

    <a href="?report_type=<?= $reportType ?>&page=<?= $page ?>&download=1" class="btn btn-primary mb-3"><i class="fas fa-download"></i> Download CSV</a>

    <?php if ($reportType === 'student_list'): ?>
        <h3>Student List</h3>
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Hostel</th>
                    <th>Location</th>
                    <th>Room</th>
                    <th>Capacity</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($items) > 0): ?>
                    <?php foreach ($items as $student): ?>
                        <tr>
                            <td><?= htmlspecialchars($student['name']) ?></td>
                            <td><?= htmlspecialchars($student['email']) ?></td>
                            <td><?= htmlspecialchars($student['phone'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($student['hostel_name'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($student['hostel_location'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($student['room_number'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($student['capacity'] ?? 'N/A') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center">No students found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
            <nav aria-label="Student pagination">
                <ul class="pagination justify-content-center">
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <li class="page-item <?= $p == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?report_type=student_list&page=<?= $p ?>"><?= $p ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>

    <?php elseif ($reportType === 'complaints'): ?>
        <h3>Complaints</h3>
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Student Name</th>
                    <th>Hostel</th>
                    <th>Title</th>
                    <th>Message</th>
                    <th>Status</th>
                    <th>Submitted</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($items) > 0): ?>
                    <?php foreach ($items as $index => $complaint): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($complaint['student_name'] ?? 'Unknown') ?></td>
                            <td><?= htmlspecialchars($complaint['hostel_name'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($complaint['title']) ?></td>
                            <td><?= nl2br(htmlspecialchars($complaint['message'])) ?></td>
                            <td>
                                <?php
                                    $badge = match ($complaint['status']) {
                                        'pending' => 'warning',
                                        'in progress' => 'info',
                                        'resolved' => 'success',
                                        default => 'secondary'
                                    };
                                ?>
                                <span class="badge bg-<?= $badge ?> text-uppercase"><?= $complaint['status'] ?></span>
                            </td>
                            <td><?= date('d M Y H:i', strtotime($complaint['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center">No complaints found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <a href="dashboard.php" class="btn btn-secondary mt-3"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>

<!-- JavaScript: Theme Toggle Logic -->
<script>
    const toggleBtn = document.getElementById('theme-toggle');
    const icon = toggleBtn.querySelector('i');

    function setTheme(mode) {
        if (mode === 'dark') {
            document.body.classList.add('dark-mode');
            icon.classList.remove('fa-moon');
            icon.classList.add('fa-sun');
        } else {
            document.body.classList.remove('dark-mode');
            icon.classList.remove('fa-sun');
            icon.classList.add('fa-moon');
        }
        localStorage.setItem('theme', mode);
    }

    toggleBtn.addEventListener('click', () => {
        const isDark = document.body.classList.contains('dark-mode');
        setTheme(isDark ? 'light' : 'dark');
    });

    // Apply saved theme on load
    document.addEventListener('DOMContentLoaded', () => {
        const saved = localStorage.getItem('theme') || 'light';
        setTheme(saved);
    });
</script>
<?php include('../includes/footer.php'); ?>
</body>
</html>
