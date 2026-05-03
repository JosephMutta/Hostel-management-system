<?php
session_start();
include('../config/access_control.php');
include('../config/db.php');
include('../includes/header.php');

// Only hostel owners can access this
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'hostel_owner') {
    header("Location: ../index.php");
    exit();
}

$owner_name = $_SESSION['username'];
$hostel_id = $_GET['id'] ?? null;

if (!$hostel_id) {
    echo "<div class='alert alert-danger'>Invalid hostel ID.</div>";
    exit();
}

// Fetch hostel to verify ownership
$stmt = $pdo->prepare("SELECT * FROM hostels WHERE id = ? AND owner_name = ?");
$stmt->execute([$hostel_id, $owner_name]);
$hostel = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$hostel) {
    echo "<div class='alert alert-danger'>Hostel not found or you are not the owner.</div>";
    exit();
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $location = trim($_POST['location']);
    $capacity = intval($_POST['capacity']);
    $rooms = array_filter($_POST['rooms'], fn($r) => !empty(trim($r)));
    $proposed_rooms = json_encode($rooms);

    try {
        $stmt = $pdo->prepare("INSERT INTO hostel_update_requests 
            (hostel_id, owner_name, proposed_location, proposed_capacity, proposed_rooms) 
            VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$hostel_id, $owner_name, $location, $capacity, $proposed_rooms]);
        $success = "Update request sent successfully and is pending admin approval.";
    } catch (Exception $e) {
        $error = "Failed to submit request. Please try again.";
    }
}
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../css/style.css">

<div class="container mt-5">
    <h3>Edit Request for: <?= htmlspecialchars($hostel['name']) ?></h3>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label>Proposed Location:</label>
            <input type="text" name="location" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Proposed Capacity:</label>
            <input type="number" name="capacity" class="form-control" min="1" required>
        </div>

        <div class="mb-3">
            <label>Add Rooms:</label>
            <div id="roomFields">
                <div class="input-group mb-2">
                    <input type="text" name="rooms[]" class="form-control" placeholder="e.g. Room 101">
                    <button type="button" class="btn btn-outline-secondary" onclick="addRoomField()">➕</button>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Submit Request</button>
        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </form>
</div>

<script>
function addRoomField() {
    const div = document.createElement('div');
    div.className = 'input-group mb-2';
    div.innerHTML = `
        <input type="text" name="rooms[]" class="form-control" placeholder="e.g. Room 102">
        <button type="button" class="btn btn-outline-danger" onclick="this.parentElement.remove()">❌</button>
    `;
    document.getElementById('roomFields').appendChild(div);
}
</script>

<?php include('../includes/footer.php'); ?>
