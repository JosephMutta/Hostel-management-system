<?php
session_start();
include('../config/access_control.php');
include('../config/db.php');
include('../includes/header.php');

// Restrict to hostel owner
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'hostel_owner') {
    header("Location: ../login.php");
    exit();
}

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $location = trim($_POST['location']);
    $capacity = intval($_POST['capacity']);
    $gender = $_POST['gender'];
    $distance = floatval($_POST['distance']);
    $owner_name = $_SESSION['username'];
    $owner_contact_input = preg_replace('/\D/', '', $_POST['owner_contact']); // Remove non-digits

    // Validate phone number: must be exactly 9 digits without leading 0
    if (strlen($owner_contact_input) !== 9 || !ctype_digit($owner_contact_input)) {
        $error = "Phone number must be 9 digits and should not start with 0. Example: 712345678";
    } elseif (empty($name) || empty($location)) {
        $error = "Please fill in all required fields.";
    } elseif (!in_array($gender, ['male', 'female', 'mixed'])) {
        $error = "Invalid gender option.";
    } else {
        $owner_contact = '0' . $owner_contact_input;

        $stmt = $pdo->prepare("INSERT INTO hostels (name, location, capacity, gender, owner_name, owner_contact, distance)
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $location, $capacity, $gender, $owner_name, $owner_contact, $distance]);
        $success = "Hostel registered successfully!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register Hostel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body {
            background-color: #f4f7fa;
        }
        .container {
            max-width: 700px;
            margin-top: 50px;
            background-color: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        h2 {
            color: #003366;
        }
        .form-control:focus {
            border-color: #0056b3;
            box-shadow: none;
        }
        .btn-primary {
            background-color: #003366;
            border-color: #003366;
        }
        .btn-primary:hover {
            background-color: #00509e;
            border-color: #00509e;
        }
    </style>
</head>
<body>

<div class="container">
    <h2><i class="fas fa-building"></i> Register Hostel</h2>
    <hr>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-3">
            <label class="form-label">Hostel Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" required placeholder="e.g., Sunrise Hostel">
        </div>

        <div class="mb-3">
            <label class="form-label">Location <span class="text-danger">*</span></label>
            <input type="text" name="location" class="form-control" required placeholder="e.g., University Road">
        </div>

        <div class="mb-3">
            <label class="form-label">Capacity <span class="text-danger">*</span></label>
            <input type="number" name="capacity" class="form-control" required min="1" placeholder="e.g., 50">
        </div>

        <div class="mb-3">
            <label class="form-label">Gender <span class="text-danger">*</span></label>
            <select name="gender" class="form-select" required>
                <option value="">-- Select Gender --</option>
                <option value="male">Male Only</option>
                <option value="female">Female Only</option>
                <option value="mixed">Mixed</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Distance from Campus (km)</label>
            <input type="number" step="0.1" name="distance" class="form-control" placeholder="e.g., 0.5">
        </div>

        <div class="mb-3">
            <label class="form-label">Your Contact <span class="text-danger">*</span></label>
            <input type="text" name="owner_contact" class="form-control" maxlength="9" pattern="\d{9}"
                   title="Enter 9-digit number without starting 0 (e.g., 712345678)" required
                   placeholder="e.g., 712345678">
            <small class="text-muted">Enter 9-digit number without leading 0. System will add it automatically.</small>
        </div>

        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Submit Hostel</button>
        <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
    </form>
</div>

<?php include('../includes/footer.php'); ?>
</body>
</html>
<?php
session_start();
include('../config/access_control.php');
include('../config/db.php');
include('../includes/header.php');

// Restrict to hostel owner
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'hostel_owner') {
    header("Location: ../login.php");
    exit();
}

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $location = trim($_POST['location']);
    $capacity = intval($_POST['capacity']);
    $gender = $_POST['gender'];
    $distance = floatval($_POST['distance']);
    $owner_name = $_SESSION['username'];  // assuming username = owner_name
    $owner_contact = $_POST['owner_contact'];

    if (empty($name) || empty($location) || empty($owner_contact)) {
        $error = "Please fill in all required fields.";
    } elseif (!in_array($gender, ['male', 'female', 'mixed'])) {
        $error = "Invalid gender option.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO hostels (name, location, capacity, gender, owner_name, owner_contact, distance)
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $location, $capacity, $gender, $owner_name, $owner_contact, $distance]);
        $success = "Hostel registered successfully!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register Hostel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body {
            background-color: #f4f7fa;
        }
        .container {
            max-width: 700px;
            margin-top: 50px;
            background-color: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        h2 {
            color: #003366;
        }
        .form-control:focus {
            border-color: #0056b3;
            box-shadow: none;
        }
        .btn-primary {
            background-color: #003366;
            border-color: #003366;
        }
        .btn-primary:hover {
            background-color: #00509e;
            border-color: #00509e;
        }
    </style>
</head>
<body>

<div class="container">
    <h2><i class="fas fa-building"></i> Register Hostel</h2>
    <hr>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-3">
            <label class="form-label">Hostel Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" required placeholder="e.g., Sunrise Hostel">
        </div>

        <div class="mb-3">
            <label class="form-label">Location <span class="text-danger">*</span></label>
            <input type="text" name="location" class="form-control" required placeholder="e.g., University Road">
        </div>

        <div class="mb-3">
            <label class="form-label">Capacity <span class="text-danger">*</span></label>
            <input type="number" name="capacity" class="form-control" required min="1" placeholder="e.g., 50">
        </div>

        <div class="mb-3">
            <label class="form-label">Gender <span class="text-danger">*</span></label>
            <select name="gender" class="form-select" required>
                <option value="">-- Select Gender --</option>
                <option value="male">Male Only</option>
                <option value="female">Female Only</option>
                <option value="mixed">Mixed</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Distance from Campus (km)</label>
            <input type="number" step="0.1" name="distance" class="form-control" placeholder="e.g., 0.5">
        </div>

        <div class="mb-3">
            <label class="form-label">Your Contact <span class="text-danger">*</span></label>
            <input type="text" name="owner_contact" class="form-control" required placeholder="e.g., 0712345678">
        </div>

        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Submit Hostel</button>
        <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
    </form>
</div>

<?php include('../includes/footer.php'); ?>
</body>
</html>
