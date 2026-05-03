<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('../config/access_control.php');
include('../config/db.php');
include('../includes/header.php');

// Ensure only Dean can access
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'dean') {
    header("Location: ../index.php");
    exit();
}

$selected_hostel_id = $_GET['hostel_id'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Fetch all hostels for dropdown
$hostelStmt = $pdo->query("SELECT id, name FROM hostels ORDER BY name");
$hostels = $hostelStmt->fetchAll(PDO::FETCH_ASSOC);

// Count total students for pagination
$countQuery = "SELECT COUNT(*) FROM students";
$countParams = [];

if (!empty($selected_hostel_id)) {
    $countQuery .= " WHERE hostel_id = ?";
    $countParams[] = $selected_hostel_id;
}
$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($countParams);
$totalStudents = $countStmt->fetchColumn();
$totalPages = ceil($totalStudents / $limit);

// Fetch students
$query = "
    SELECT 
        s.id,
        s.name,
        s.email,
        s.phone,
        h.name AS hostel_name,
        h.location AS hostel_location,
        r.id AS room_id,
        r.room_number,
        r.capacity
    FROM students s
    LEFT JOIN hostels h ON s.hostel_id = h.id
    LEFT JOIN rooms r ON s.room_id = r.id
";
$params = [];

if (!empty($selected_hostel_id)) {
    $query .= " WHERE s.hostel_id = ?";
    $params[] = $selected_hostel_id;
}
$query .= " ORDER BY s.id DESC LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch room occupancies
$roomIds = array_values(array_filter(array_column($students, 'room_id')));
$occupancyMap = [];

if (!empty($roomIds)) {
    $placeholders = implode(',', array_fill(0, count($roomIds), '?'));
    $occStmt = $pdo->prepare("
        SELECT room_id, COUNT(*) AS occupants 
        FROM students 
        WHERE room_id IN ($placeholders) 
        GROUP BY room_id
    ");
    $occStmt->execute($roomIds);
    foreach ($occStmt->fetchAll(PDO::FETCH_ASSOC) as $occ) {
        $occupancyMap[$occ['room_id']] = $occ['occupants'];
    }
}
?>

<!-- Styles -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>🎓 Student List</h2>
        <a href="dashboard.php" class="btn btn-secondary">← Go Back to Dashboard</a>
    </div>

    <!-- Hostel Filter -->
    <form method="GET" class="mb-4">
        <label for="hostel_id" class="form-label">Filter by Hostel:</label>
        <div class="d-flex gap-2">
            <select name="hostel_id" id="hostel_id" class="form-select" style="max-width: 300px;">
                <option value="">-- All Hostels --</option>
                <?php foreach ($hostels as $hostel): ?>
                    <option value="<?= htmlspecialchars($hostel['id']) ?>" <?= $selected_hostel_id == $hostel['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($hostel['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary">Filter</button>
        </div>
    </form>

    <!-- Export Buttons -->
    <form method="POST" action="export_students.php" class="mb-3 d-flex gap-2">
        <input type="hidden" name="hostel_id" value="<?= htmlspecialchars($selected_hostel_id) ?>">
        <button type="submit" name="export" value="pdf" class="btn btn-danger">📄 Export PDF</button>
        <button type="submit" name="export" value="excel" class="btn btn-success">📊 Export Excel</button>
    </form>

    <!-- Students Table -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-dark">
                <tr>
                    <th>#ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Hostel</th>
                    <th>Location</th>
                    <th>Room</th>
                    <th>Capacity</th>
                    <th>Occupants</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($students) > 0): ?>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?= htmlspecialchars($student['id'] ?? '') ?></td>
                            <td><?= htmlspecialchars($student['name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($student['email'] ?? '') ?></td>
                            <td><?= htmlspecialchars($student['phone'] ?? '') ?></td>
                            <td><?= htmlspecialchars($student['hostel_name'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($student['hostel_location'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($student['room_number'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($student['capacity'] ?? 'N/A') ?></td>
                            <td>
                                <?= isset($occupancyMap[$student['room_id']]) 
                                    ? $occupancyMap[$student['room_id']] 
                                    : 'N/A' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="9" class="text-center">No students found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <nav aria-label="Student pagination">
            <ul class="pagination justify-content-center">
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <li class="page-item <?= $p == $page ? 'active' : '' ?>">
                        <a class="page-link" href="?hostel_id=<?= htmlspecialchars($selected_hostel_id) ?>&page=<?= $p ?>"><?= $p ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<?php include('../includes/footer.php'); ?>
