<?php
include('../config/access_control.php');
include('../config/db.php');
include('../includes/header.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $location = $_POST['location'];
    $capacity = $_POST['capacity'];
    $gender = $_POST['gender'];
    $owner_name = $_POST['owner_name'];
    $owner_contact = $_POST['owner_contact'];
    $distance = $_POST['distance'];

    $stmt = $pdo->prepare("INSERT INTO hostels (name, location, capacity, gender, owner_name, owner_contact, distance) 
                           VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $location, $capacity, $gender, $owner_name, $owner_contact, $distance]);

    echo "<p>Hostel added successfully.</p>";
}
?>

<link rel="stylesheet" href="../css/style.css">
<h2>Add Hostel</h2>
<form method="POST">
    <label>Hostel Name:</label><br>
    <input type="text" name="name" required><br><br>
    
    <label>Location:</label><br>
    <input type="text" name="location"><br><br>

    <label>Capacity:</label><br>
    <input type="number" name="capacity" required><br><br>

    <label>Gender:</label><br>
    <select name="gender" required>
        <option value="">-- Select Gender --</option>
        <option value="Male">Male</option>
        <option value="Female">Female</option>
        <option value="Mixed">Mixed</option>
    </select><br><br>

    <label>Owner Name:</label><br>
    <input type="text" name="owner_name"><br><br>

    <label>Owner Contact:</label><br>
    <input type="text" name="owner_contact"><br><br>

    <label>Distance from University (in km):</label><br>
    <input type="number" step="0.01" name="distance"><br><br>

    <input type="submit" value="Add Hostel">
</form>

<?php include('../includes/footer.php'); ?>
