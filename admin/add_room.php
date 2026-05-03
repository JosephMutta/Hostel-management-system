<?php
include('../config/access_control.php');
include('../config/db.php');
include('../includes/header.php');
include('../includes/functions.php');

$hostels = getHostels($pdo);
$selected_hostel_id = $_POST['hostel_id'] ?? null;
$rooms = [];

// Handle deletion
if (isset($_POST['delete_room_id'])) {
    $delete_id = $_POST['delete_room_id'];
    $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
    $stmt->execute([$delete_id]);
    echo "<div class='alert alert-danger'>Room deleted successfully.</div>";
}

// Handle updates
if (isset($_POST['update_rooms'])) {
    foreach ($_POST['rooms'] as $room_id => $room_data) {
        $room_number = trim($room_data['room_number']);
        $capacity = max(1, min(4, (int)$room_data['capacity']));
        if ($room_number !== '') {
            $stmt = $pdo->prepare("UPDATE rooms SET room_number = ?, capacity = ? WHERE id = ?");
            $stmt->execute([$room_number, $capacity, $room_id]);
        }
    }
    echo "<div class='alert alert-success'>Room details updated successfully.</div>";
}

// Handle new room additions
if (isset($_POST['add_rooms'])) {
    $new_rooms = $_POST['new_rooms'] ?? [];
    $added_count = 0;

    foreach ($new_rooms as $room) {
        $room_number = trim($room['room_number']);
        $capacity = max(1, min(4, (int)$room['capacity']));
        if ($room_number !== '') {
            $stmt = $pdo->prepare("INSERT INTO rooms (hostel_id, room_number, capacity) VALUES (?, ?, ?)");
            $stmt->execute([$selected_hostel_id, $room_number, $capacity]);
            $added_count++;
        }
    }

    echo "<div class='alert alert-success'>{$added_count} new room(s) added successfully.</div>";
}

// Load rooms
if ($selected_hostel_id) {
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE hostel_id = ?");
    $stmt->execute([$selected_hostel_id]);
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Helper
function getHostelName($pdo, $hostel_id) {
    $stmt = $pdo->prepare("SELECT name FROM hostels WHERE id = ?");
    $stmt->execute([$hostel_id]);
    return $stmt->fetchColumn();
}
?>

<!-- Bootstrap 5 CDN -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<div class="container mt-5">
    <h2 class="mb-4 text-center">Manage Rooms</h2>

    <!-- Hostel Selector -->
    <form method="POST" class="mb-4">
        <label class="form-label">Select Hostel:</label>
        <select name="hostel_id" class="form-select" onchange="this.form.submit()" required>
            <option value="">-- Choose Hostel --</option>
            <?php foreach ($hostels as $hostel): ?>
                <option value="<?= $hostel['id'] ?>" <?= ($selected_hostel_id == $hostel['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($hostel['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if ($selected_hostel_id): ?>
        <!-- Existing Rooms Table -->
        <form method="POST">
            <input type="hidden" name="hostel_id" value="<?= $selected_hostel_id ?>">
            <input type="hidden" name="update_rooms" value="1">

            <h4>Rooms in <?= htmlspecialchars(getHostelName($pdo, $selected_hostel_id)) ?></h4>
            <table class="table table-bordered table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Room ID</th>
                        <th>Room Number</th>
                        <th>Capacity</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rooms as $room): ?>
                        <tr>
                            <td><?= $room['id'] ?></td>
                            <td>
                                <input type="text" name="rooms[<?= $room['id'] ?>][room_number]" class="form-control" value="<?= htmlspecialchars($room['room_number']) ?>" required>
                            </td>
                            <td>
                                <input type="number" name="rooms[<?= $room['id'] ?>][capacity]" class="form-control" value="<?= $room['capacity'] ?>" min="1" max="4" required>
                            </td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="hostel_id" value="<?= $selected_hostel_id ?>">
                                    <input type="hidden" name="delete_room_id" value="<?= $room['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this room?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="submit" class="btn btn-primary mb-5">Update Rooms</button>
        </form>

        <!-- Add New Rooms -->
        <h4>Add New Rooms</h4>
        <form method="POST">
            <input type="hidden" name="hostel_id" value="<?= $selected_hostel_id ?>">
            <input type="hidden" name="add_rooms" value="1">

            <div id="roomRows">
                <div class="row mb-3 room-entry">
                    <div class="col-md-5">
                        <input type="text" name="new_rooms[0][room_number]" class="form-control" placeholder="Room Number" required>
                    </div>
                    <div class="col-md-4">
                        <input type="number" name="new_rooms[0][capacity]" class="form-control" placeholder="Capacity" min="1" max="4" required>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger remove-row">X</button>
                    </div>
                </div>
            </div>

            <button type="button" class="btn btn-secondary mb-3" id="addMoreRows">Add More Row</button><br>
            <button type="submit" class="btn btn-success">Add Rooms</button>
        </form>
    <?php endif; ?>
</div>

<!-- JavaScript for dynamic row handling -->
<script>
let roomIndex = 1;

document.getElementById('addMoreRows')?.addEventListener('click', function () {
    const container = document.getElementById('roomRows');

    const row = document.createElement('div');
    row.className = 'row mb-3 room-entry';
    row.innerHTML = `
        <div class="col-md-5">
            <input type="text" name="new_rooms[${roomIndex}][room_number]" class="form-control" placeholder="Room Number" required>
        </div>
        <div class="col-md-4">
            <input type="number" name="new_rooms[${roomIndex}][capacity]" class="form-control" placeholder="Capacity" min="1" max="4" required>
        </div>
        <div class="col-md-2">
            <button type="button" class="btn btn-danger remove-row">X</button>
        </div>
    `;
    container.appendChild(row);
    roomIndex++;
});

document.addEventListener('click', function (e) {
    if (e.target.classList.contains('remove-row')) {
        e.target.closest('.room-entry')?.remove();
    }
});
</script>

<?php include('../includes/footer.php'); ?>
