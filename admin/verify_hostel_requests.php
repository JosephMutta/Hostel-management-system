<?php
session_start();
require_once('../config/access_control.php');
require_once('../config/db.php');
require_once('../includes/header.php');

// Admin access only
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$success = '';
$error = '';

// Handle all POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle hostel update requests (verify/reject)
    if (isset($_POST['request_id']) && isset($_POST['action'])) {
        $requestId = $_POST['request_id'];
        $action = $_POST['action'];

        if ($action === 'verify') {
            // Fetch pending request
            $stmt = $pdo->prepare("SELECT * FROM hostel_update_requests WHERE id = ? AND status = 'pending'");
            $stmt->execute([$requestId]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($request) {
                try {
                    $pdo->beginTransaction();

                    // Update hostels table
                    $update = $pdo->prepare("UPDATE hostels 
                        SET location = ?, capacity = ?, gender = ?, distance = ?, rooms = ?
                        WHERE name = ?");
                    $update->execute([
                        $request['proposed_location'],
                        $request['proposed_capacity'],
                        $request['proposed_gender'],
                        $request['proposed_distance'],
                        $request['proposed_rooms'],
                        $request['hostel_name']
                    ]);

                    // Mark request as verified
                    $mark = $pdo->prepare("UPDATE hostel_update_requests 
                        SET status = 'verified', processed_at = NOW(), processed_by = ?
                        WHERE id = ?");
                    $mark->execute([$_SESSION['user_id'], $requestId]);

                    $pdo->commit();
                    $success = "Request #{$requestId} verified successfully.";
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = "Error processing request: " . $e->getMessage();
                }
            } else {
                $error = "Request not found or already processed.";
            }
        } elseif ($action === 'reject' && isset($_POST['reason'])) {
            $reason = trim($_POST['reason']);
            if (empty($reason)) {
                $error = "Rejection reason required.";
            } else {
                $stmt = $pdo->prepare("UPDATE hostel_update_requests 
                    SET status = 'rejected', rejection_reason = ?, processed_at = NOW(), processed_by = ?
                    WHERE id = ?");
                $stmt->execute([$reason, $_SESSION['user_id'], $requestId]);
                $success = "Request #{$requestId} rejected.";
            }
        }
    }
    // Handle new hostel approvals
    elseif (isset($_POST['hostel_id']) && isset($_POST['action'])) {
        $hostel_id = $_POST['hostel_id'];
        $action = $_POST['action'];

        if (in_array($action, ['approved', 'rejected'])) {
            $stmt = $pdo->prepare("UPDATE hostels 
                SET status = ?
                WHERE id = ?");
            $stmt->execute([$action, $hostel_id]);
            
            $success = "Hostel status updated to " . ucfirst($action);
        }
    }
}

// Fetch data for both sections
$pendingHostels = $pdo->query("SELECT * FROM hostels WHERE status = 'pending'")->fetchAll(PDO::FETCH_ASSOC);
$updateRequests = $pdo->query("SELECT * FROM hostel_update_requests WHERE status = 'pending' ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$stats = $pdo->query("SELECT 
    (SELECT COUNT(*) FROM hostels) as total_hostels,
    (SELECT COUNT(*) FROM hostels WHERE status = 'approved') as approved_hostels,
    (SELECT COUNT(*) FROM hostel_update_requests WHERE status = 'pending') as pending_updates
")->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Hostel Management Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .stat-card {
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .nav-pills .nav-link.active {
            background-color: #0d6efd;
        }
        .tab-content {
            background: white;
            border: 1px solid #dee2e6;
            border-top: none;
            border-radius: 0 0 0.375rem 0.375rem;
            padding: 1.5rem;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 bg-dark text-white min-vh-100 p-3">
            <h4 class="text-center mb-4">Admin Panel</h4>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link text-white active" href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="hostels.php"><i class="bi bi-building me-2"></i>All Hostels</a>
                </li>
               <!--
                 <li class="nav-item">
                    <a class="nav-link text-white" href="users.php"><i class="bi bi-people me-2"></i>Users</a>
                </li>
                -->
                <li class="nav-item mt-4">
                    <a class="nav-link text-white" href="../logout.php"><i class="bi bi-box-arrow-left me-2"></i>Logout</a>
                </li>
            </ul>
        </div>
        
        <div class="col-md-10 p-4">
            <h2 class="mb-4"><i class="bi bi-speedometer2 me-2"></i>Hostel Management Dashboard</h2>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card stat-card text-white bg-primary">
                        <div class="card-body">
                            <h5 class="card-title">Total Hostels</h5>
                            <h2 class="card-text"><?= $stats['total_hostels'] ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card text-white bg-success">
                        <div class="card-body">
                            <h5 class="card-title">Approved Hostels</h5>
                            <h2 class="card-text"><?= $stats['approved_hostels'] ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card text-white bg-warning">
                        <div class="card-body">
                            <h5 class="card-title">Pending Updates</h5>
                            <h2 class="card-text"><?= $stats['pending_updates'] ?></h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Navigation -->
            <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="pills-new-tab" data-bs-toggle="pill" data-bs-target="#pills-new" type="button">
                        New Hostels (<?= count($pendingHostels) ?>)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="pills-updates-tab" data-bs-toggle="pill" data-bs-target="#pills-updates" type="button">
                        Update Requests (<?= count($updateRequests) ?>)
                    </button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="pills-tabContent">
                <!-- New Hostels Tab -->
                <div class="tab-pane fade show active" id="pills-new" role="tabpanel">
                    <?php if (count($pendingHostels) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Name</th>
                                        <th>Location</th>
                                        <th>Capacity</th>
                                        <th>Type</th>
                                        <th>Owner</th>
                                        <th>Contact</th>
                                        <th>Distance</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pendingHostels as $hostel): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($hostel['name']) ?></td>
                                            <td><?= htmlspecialchars($hostel['location']) ?></td>
                                            <td><?= $hostel['capacity'] ?></td>
                                            <td><?= ucfirst($hostel['gender']) ?></td>
                                            <td><?= htmlspecialchars($hostel['owner_name']) ?></td>
                                            <td><?= htmlspecialchars($hostel['owner_contact']) ?></td>
                                            <td><?= number_format($hostel['distance'], 2) ?> km</td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <form method="POST">
                                                        <input type="hidden" name="hostel_id" value="<?= $hostel['id'] ?>">
                                                        <input type="hidden" name="action" value="approved">
                                                        <button type="submit" class="btn btn-success btn-sm">
                                                            <i class="bi bi-check-circle"></i> Approve
                                                        </button>
                                                    </form>
                                                    <form method="POST" onsubmit="return confirm('Reject this hostel application?');">
                                                        <input type="hidden" name="hostel_id" value="<?= $hostel['id'] ?>">
                                                        <input type="hidden" name="action" value="rejected">
                                                        <button type="submit" class="btn btn-danger btn-sm">
                                                            <i class="bi bi-x-circle"></i> Reject
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> No new hostels awaiting approval.
                        </div>
                    <?php endif ?>
                </div>

                <!-- Update Requests Tab -->
                <div class="tab-pane fade" id="pills-updates" role="tabpanel">
                    <?php if (count($updateRequests) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Hostel</th>
                                        <th>Current vs Proposed</th>
                                        <th>Submitted</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($updateRequests as $req): 
                                        $current = $pdo->query("SELECT * FROM hostels WHERE name = '{$req['hostel_name']}'")->fetch(PDO::FETCH_ASSOC);
                                        ?>
                                        <tr>
                                            <td><?= $req['id'] ?></td>
                                            <td>
                                                <strong><?= htmlspecialchars($req['hostel_name']) ?></strong><br>
                                                <small class="text-muted">By: <?= htmlspecialchars($req['owner_name']) ?></small>
                                            </td>
                                            <td>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <h6>Current</h6>
                                                        <ul class="list-unstyled">
                                                            <li><small>Location: <?= htmlspecialchars($current['location']) ?></small></li>
                                                            <li><small>Capacity: <?= $current['capacity'] ?></small></li>
                                                            <li><small>Type: <?= ucfirst($current['gender']) ?></small></li>
                                                            <li><small>Distance: <?= number_format($current['distance'], 2) ?> km</small></li>
                                                        </ul>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h6>Proposed</h6>
                                                        <ul class="list-unstyled">
                                                            <li><small>Location: <?= htmlspecialchars($req['proposed_location']) ?></small></li>
                                                            <li><small>Capacity: <?= $req['proposed_capacity'] ?></small></li>
                                                            <li><small>Type: <?= ucfirst($req['proposed_gender']) ?></small></li>
                                                            <li><small>Distance: <?= number_format($req['proposed_distance'], 2) ?> km</small></li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?= date('d M Y', strtotime($req['created_at'])) ?><br>
                                                <small class="text-muted"><?= date('H:i', strtotime($req['created_at'])) ?></small>
                                            </td>
                                            <td>
                                                <form method="POST" class="mb-3">
                                                    <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                                    <input type="hidden" name="action" value="verify">
                                                    <button type="submit" class="btn btn-success btn-sm w-100">
                                                        <i class="bi bi-check-circle"></i> Approve Changes
                                                    </button>
                                                </form>
                                                
                                                <form method="POST">
                                                    <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <div class="input-group mb-2">
                                                        <textarea name="reason" class="form-control form-control-sm" 
                                                            rows="2" placeholder="Rejection reason" required></textarea>
                                                    </div>
                                                    <button type="submit" class="btn btn-danger btn-sm w-100">
                                                        <i class="bi bi-x-circle"></i> Reject Changes
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> No pending update requests.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Enable Bootstrap tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
</script>
</body>
</html>