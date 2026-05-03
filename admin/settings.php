<?php
session_start();
include('../config/access_control.php');
include('../config/db.php');
include('../includes/header.php');

// Restrict to logged-in admin only
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$error = '';
$success = '';

// Handle Change Password
if (isset($_POST['change_password'])) {
    $username = $_SESSION['username'];
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $stmt = $pdo->prepare("SELECT password FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($current_password, $user['password'])) {
        $error = "Current password is incorrect.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
        $stmt->execute([$hashed, $username]);

        $success = "Password changed successfully.";
    }
}

// Handle Delete Users
if (isset($_POST['delete_users'])) {
    $user_ids = $_POST['user_ids'] ?? [];
    if (!empty($user_ids) && is_array($user_ids)) {
        // Prepare placeholders for IN clause
        $placeholders = implode(',', array_fill(0, count($user_ids), '?'));
        $stmt = $pdo->prepare("DELETE FROM users WHERE id IN ($placeholders)");
        if ($stmt->execute($user_ids)) {
            $success = "Selected user(s) deleted successfully.";
        } else {
            $error = "Failed to delete selected user(s).";
        }
    } else {
        $error = "No users selected for deletion.";
    }
}

// Fetch all users for display (excluding current admin)
$stmt = $pdo->prepare("SELECT id, username,  role FROM users WHERE role != 'admin'");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Settings - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">

    <style>
        #sidebar {
            position: fixed;
            top: 0;
            left: -250px;
            width: 230px;
            height: 100%;
            background-color: #003366;
            color: white;
            transition: left 0.3s ease;
            padding-top: 60px;
            z-index: 1000;
        }
        #sidebar.active { left: 0; }
        #sidebar ul { list-style-type: none; padding: 0; }
        #sidebar ul li { padding: 15px 20px; border-bottom: 1px solid #555; }
        #sidebar ul li a { color: white; text-decoration: none; display: block; }
        #sidebar ul li a:hover { background-color: #00509e; border-radius: 5px; }

        #menu-toggle {
            position: absolute;
            top: 15px;
            left: 15px;
            font-size: 22px;
            color: #003366;
            background: none;
            border: none;
            z-index: 1100;
        }

        .spinner-overlay {
            display: flex;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.7);
            justify-content: center;
            align-items: center;
            color: white;
            font-size: 18px;
            flex-direction: column;
            z-index: 9999;
        }

        .strength-meter {
            height: 5px;
            width: 100%;
            background-color: #ddd;
            margin-top: 5px;
        }
        .strength-meter-fill {
            height: 100%;
            width: 0%;
            transition: width 0.3s ease-in-out;
        }

        .table-container {
            overflow-x: auto;
        }

        .styled-table {
            border-collapse: collapse;
            margin: 25px 0;
            font-size: 0.9em;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-width: 600px;
            box-shadow: 0 0 20px rgba(0,0,0,0.15);
        }
        .styled-table thead tr {
            background-color: #003366;
            color: #ffffff;
            text-align: left;
        }
        .styled-table th,
        .styled-table td {
            padding: 12px 15px;
        }
        .styled-table tbody tr {
            border-bottom: 1px solid #dddddd;
        }
        .styled-table tbody tr:nth-of-type(even) {
            background-color: #f3f3f3;
        }
        .styled-table tbody tr:last-of-type {
            border-bottom: 2px solid #003366;
        }
        .styled-table tbody tr.active-row {
            font-weight: bold;
            color: #003366;
        }
    </style>
</head>
<body>

<!-- Sidebar toggle -->
<button id="menu-toggle">&#9776;</button>

<!-- Sidebar for Admin -->
<div id="sidebar">
    <ul>
        <li><a href="dashboard.php">🏠 Home</a></li>
        <li><a href="register_student.php">➕ Register Student</a></li>
        <li><a href="view_students.php">👨‍🎓 View Students</a></li>
        <li><a href="settings.php" class="active">⚙️ Settings</a></li>
        <li><a href="../logout.php">🚪 Logout</a></li>
        <li><a href="#" onclick="document.getElementById('sidebar').classList.remove('active'); return false;">⬅️ Back</a></li>
    </ul>
</div>

<div class="container mt-5">
    <h3 class="mb-4">Change Password</h3>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" class="mb-5">
        <input type="hidden" name="change_password" value="1">
        <div class="mb-3">
            <label class="form-label">Current Password:</label>
            <input type="password" name="current_password" id="current_password" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">New Password:</label>
            <input type="password" name="new_password" id="new_password" class="form-control" required>
            <div class="strength-meter"><div id="strengthBar" class="strength-meter-fill bg-danger"></div></div>
        </div>

        <div class="mb-3">
            <label class="form-label">Confirm New Password:</label>
            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
        </div>

        <div class="mb-3">
            <input type="checkbox" onclick="togglePassword()"> Show Password
        </div>

        <div class="d-flex justify-content-between">
            <button type="submit" class="btn btn-primary">Update Password</button>
        </div>
    </form>

    <h3 class="mb-4">Delete Users</h3>

    <form method="POST" onsubmit="return confirmDelete();">
        <input type="hidden" name="delete_users" value="1">
    <div class="table-container">
        <table class="table table-striped table-hover table-bordered">
            <thead class="table-primary">
                <tr>
                    <th><input type="checkbox" id="select_all"></th>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><input type="checkbox" name="user_ids[]" value="<?= htmlspecialchars($user['id']) ?>"></td>
                    <td><?= htmlspecialchars($user['id']) ?></td>
                    <td><?= htmlspecialchars($user['username']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= htmlspecialchars($user['role']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <nav aria-label="User list pagination">
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

    <button type="submit" class="btn btn-danger mt-3"><i class="fa fa-trash"></i> Delete Selected Users</button>
</form>
</div>

<!-- Scripts -->
<script>
    document.getElementById("menu-toggle").addEventListener("click", () => {
        document.getElementById("sidebar").classList.toggle("active");
    });

    function togglePassword() {
        ['current_password', 'new_password', 'confirm_password'].forEach(id => {
            const input = document.getElementById(id);
            input.type = input.type === 'password' ? 'text' : 'password';
        });
    }

    const password = document.getElementById("new_password");
    const strengthBar = document.getElementById("strengthBar");

    password.addEventListener("input", function () {
        const val = password.value;
        let strength = 0;
        if (val.length >= 6) strength++;
        if (/[A-Z]/.test(val)) strength++;
        if (/[0-9]/.test(val)) strength++;
        if (/[^A-Za-z0-9]/.test(val)) strength++;

        const widths = ["0%", "25%", "50%", "75%", "100%"];
        const classes = ["bg-danger", "bg-warning", "bg-info", "bg-primary", "bg-success"];
        strengthBar.style.width = widths[strength];
        classes.forEach(c => strengthBar.classList.remove(c));
        strengthBar.classList.add(classes[strength]);
    });

    // Select/Deselect all checkboxes
    document.getElementById('select_all').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('input[name="user_ids[]"]');
        checkboxes.forEach(cb => cb.checked = this.checked);
    });

    // Confirm before delete
    function confirmDelete() {
        const checked = document.querySelectorAll('input[name="user_ids[]"]:checked').length;
        if (checked === 0) {
            alert('Please select at least one user to delete.');
            return false;
        }
        return confirm('Are you sure you want to delete the selected user(s)? This action cannot be undone.');
    }
</script>

<?php include('../includes/footer.php'); ?>
</body>
</html>
