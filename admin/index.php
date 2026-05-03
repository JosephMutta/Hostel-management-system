<?php
require('../config/db.php');
include('../includes/header.php');
session_start();

$roles = [
    'admin' => 'admins',
    'hostel_owner' => 'hostel_owners',
    'dean_of_students' => 'deans',
    'student' => 'students'
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    if (!isset($roles[$role])) {
        $error = "Invalid role selected.";
    } else {
        $table = $roles[$role];

        $stmt = $pdo->prepare("SELECT * FROM $table WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $role;
            $_SESSION['user_id'] = $user['id'];

            // Redirect to role-specific dashboard
            switch ($role) {
                case 'admin':
                    header("Location: dashboard.php");
                    break;
                case 'hostel_owner':
                    header("Location: hostel_owner/dashboard.php");
                    break;
                case 'dean_of_students':
                    header("Location: dean/dashboard.php");
                    break;
                case 'student':
                    header("Location: student/dashboard.php");
                    break;
            }
            exit();
        } else {
            $error = "Invalid username or password.";
        }
    }
}
?>

<!-- Bootstrap CDN -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

<div class="container mt-5" style="max-width: 500px;">
    <h2 class="mb-4 text-center">User Login</h2>
    
    <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

    <form method="POST" action="">
        <div class="mb-3">
            <label class="form-label">Username:</label>
            <input type="text" name="username" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Password:</label>
            <input type="password" name="password" class="form-control" required>
        </div>

        <div class="mb-4">
            <label class="form-label">Select Role:</label>
            <select name="role" class="form-select" required>
                <option value="">-- Select Role --</option>
                <option value="admin">Admin</option>
                <option value="hostel_owner">Hostel Owner</option>
                <option value="dean_of_students">Dean of Students</option>
                <option value="student">Student</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary w-100">Login</button>
    </form>
</div>

<?php include('../includes/footer.php'); ?>
