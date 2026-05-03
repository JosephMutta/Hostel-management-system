<?php
include('../config/access_control.php');
include('../config/db.php');
include('../includes/header.php');

$students_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $students_per_page;

// Get total students count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM students");
$stmt->execute();
$total_students = $stmt->fetchColumn();
$total_pages = ceil($total_students / $students_per_page);

// Fetch students for current page with hostel and room details
$stmt = $pdo->prepare("
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
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, $students_per_page, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="../css/style.css">

<h2 class="mb-4 text-primary"><i class="fa fa-users"></i> All Students</h2>

<div class="table-container">
    <table class="table table-striped table-hover table-bordered align-middle shadow-sm">
        <thead class="table-dark">
            <tr>
                <th scope="col"><i class="fa fa-hashtag"></i> ID</th>
                <th scope="col"><i class="fa fa-user"></i> Name</th>
                <th scope="col"><i class="fa fa-envelope"></i> Email</th>
                <th scope="col"><i class="fa fa-phone"></i> Phone</th>
                <th scope="col"><i class="fa fa-building"></i> Hostel</th>
                <th scope="col"><i class="fa fa-map-marker-alt"></i> Location</th>
                <th scope="col"><i class="fa fa-door-open"></i> Room</th>
                <th scope="col"><i class="fa fa-bed"></i> Capacity</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($students as $student): ?>
            <tr>
                <td><?= htmlspecialchars($student['id']) ?></td>
                <td><?= htmlspecialchars($student['name']) ?></td>
                <td><?= htmlspecialchars($student['email']) ?></td>
                <td><?= htmlspecialchars($student['phone']) ?></td>
                <td><?= htmlspecialchars($student['hostel_name'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($student['hostel_location'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($student['room_number'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($student['capacity'] ?? 'N/A') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<nav aria-label="Student list pagination" class="mt-3">
    <ul class="pagination justify-content-center">
        <?php if ($page > 1): ?>
        <li class="page-item">
            <a class="page-link" href="?page=<?= $page - 1 ?>" aria-label="Previous">
                <span aria-hidden="true">&laquo;</span>
            </a>
        </li>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>

        <?php if ($page < $total_pages): ?>
        <li class="page-item">
            <a class="page-link" href="?page=<?= $page + 1 ?>" aria-label="Next">
                <span aria-hidden="true">&raquo;</span>
            </a>
        </li>
        <?php endif; ?>
    </ul>
</nav>

<?php include('../includes/footer.php'); ?>
