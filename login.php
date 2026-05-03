<?php
session_start();
require_once 'config/db.php';
include 'includes/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role     = $_POST['role'];

    // Validate user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = ?");
    $stmt->execute([$username, $role]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);

        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $role;

        // Redirect to respective dashboard
        switch ($role) {
            case 'admin':
                header("Location: admin/dashboard.php");
                break;
            case 'hostel_owner':
                header("Location: hostel_owner/dashboard.php");
                break;
            case 'dean':
                header("Location: dean/dashboard.php");
                break;
            case 'student':
                header("Location: student/dashboard.php");
                break;
            default:
                $error = "Unknown role.";
        }
        exit();
    } else {
        $error = "Invalid username, password, or role.";
    }
}
?>

<link rel="stylesheet" href="css/style.css">
<h2>Login</h2>

<?php if (isset($error)) : ?>
    <p style="color: red;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="POST" action="">
    <label>Username:</label><br>
    <input type="text" name="username" required><br><br>

    <label>Password:</label><br>
    <input type="password" name="password" required><br><br>

    <label>Select Role:</label><br>
    <select name="role" required>
        <option value="">-- Select Role --</option>
        <option value="admin">Admin</option>
        <option value="hostel_owner">Hostel Owner</option>
        <option value="dean">Dean of Students</option>
        <option value="student">Student</option>
    </select><br><br>

    <input type="submit" value="Login">
</form>

<?php include 'includes/footer.php'; ?>
