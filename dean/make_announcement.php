<?php
session_start();
include('../config/access_control.php');
include('../config/db.php');
include('../includes/header.php');

// Access control
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'dean') {
    header("Location: ../login.php");
    exit();
}

$success = $error = '';

// Fetch hostels for dropdown
$hostel_stmt = $pdo->query("SELECT id, name FROM hostels");
$hostels = $hostel_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $message = trim($_POST['message']);
    $hostel_id = $_POST['hostel_id'] === 'all' ? null : (int)$_POST['hostel_id'];
    $dean_username = $_SESSION['username'];

    if ($title && $message) {
        $stmt = $pdo->prepare("INSERT INTO announcements (dean_username, hostel_id, title, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$dean_username, $hostel_id, $title, $message]);
        $success = "Announcement posted successfully.";
    } else {
        $error = "Please fill in all required fields.";
    }
}
?>

<link rel="stylesheet" href="../css/style.css">

<h2>Post Announcement</h2>

<?php if ($success): ?>
    <p class="success"><?= $success ?></p>
<?php endif; ?>

<?php if ($error): ?>
    <p class="error"><?= $error ?></p>
<?php endif; ?>

<form method="POST" action="">
    <label>Title:</label><br>
    <input type="text" name="title" required><br><br>

    <label>Message:</label><br>
    <textarea name="message" rows="5" required></textarea><br><br>

    <label>Post To:</label><br>
    <select name="hostel_id" required>
        <option value="all">All Hostels</option>
        <?php foreach ($hostels as $hostel): ?>
            <option value="<?= $hostel['id'] ?>"><?= htmlspecialchars($hostel['name']) ?></option>
        <?php endforeach; ?>
    </select><br><br>

    <input type="submit" value="Post Announcement">
</form>

<a href="dashboard.php" class="card">← Back to Dashboard</a>

<?php include('../includes/footer.php'); ?>
