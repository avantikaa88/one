<?php
session_start();
include(__DIR__ . '/../db.php');

// ---------------- AUTH CHECK ----------------
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login/login.php");
    exit;
}

/* ================= HANDLE ACTIONS ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $adoption_id = intval($_POST['adoption_id']);

    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'approve') {
            $stmt = $conn->prepare("UPDATE adoption_application SET status = 'Approved' WHERE adoption_id = ?");
            $stmt->bind_param("i", $adoption_id);
            $stmt->execute();
            $stmt->close();

        } elseif ($action === 'reject') {
            $cancel_reason = 'Rejected by admin';
            $stmt = $conn->prepare("UPDATE adoption_application 
                                     SET status = 'Cancelled', cancel_reason = ?, cancelled_at = NOW() 
                                     WHERE adoption_id = ?");
            $stmt->bind_param("si", $cancel_reason, $adoption_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    // ---------- PAYMENT UPDATE ----------
    if (isset($_POST['payment_status'])) {
        $payment_status = $_POST['payment_status'];
        $stmt = $conn->prepare("UPDATE adoption_application SET payment_status = ? WHERE adoption_id = ?");
        $stmt->bind_param("si", $payment_status, $adoption_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: adoption_request.php");
    exit;
}

/* ================= FETCH ALL ADOPTION REQUESTS ================= */
$query = "
    SELECT 
        aa.adoption_id,
        u.user_name AS user_name,
        u.email AS user_email,
        p.name AS pet_name,
        pt.species AS pet_species,
        pt.breed AS pet_breed,
        aa.status,
        aa.payment_status,
        aa.adoption_fee,
        aa.reason,
        aa.legal_id_image,
        aa.cancel_reason,
        aa.cancelled_at,
        aa.adoption_date
    FROM adoption_application aa
    JOIN users u ON aa.user_id = u.user_id
    JOIN pet p ON aa.pet_id = p.pet_id
    JOIN pet_type pt ON p.type_id = pt.type_id
    ORDER BY aa.adoption_date DESC
";

$requests = $conn->query($query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Adoption Requests</title>
    <link rel="stylesheet" href="admin.css">
    <style>
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 3px 10px rgba(0,0,0,0.1); }
        th, td { padding: 10px; text-align: center; border-bottom: 1px solid #ddd; }
        th { background-color: #8B4513; color: white; }
        tr:nth-child(even) { background-color: #f9f9f9; }

        .status-Pending { color: #ffa500; font-weight: bold; }
        .status-Approved { color: #28a745; font-weight: bold; }
        .status-Cancelled { color: #dc3545; font-weight: bold; }

        .btn { padding: 5px 10px; border-radius: 4px; color: #fff; border: none; cursor: pointer; margin: 2px; }
        .approve-btn { background-color: #28a745; }
        .reject-btn { background-color: #dc3545; }
        .update-btn { background-color: #007bff; }
        .btn:hover { opacity: 0.9; }
        select { padding: 4px; border-radius: 4px; }
    </style>
</head>

<body>
<div class="dashboard-container">

    <!-- Sidebar -->
    <div class="sidebar">
        <h2>Buddy Admin</h2>
        <ul>
            <li><a href="admin_dashboard.php">Dashboard</a></li>
            <li><a href="manage_users.php">Manage Users</a></li>
            <li><a href="manage_vet.php">Manage Vet</a></li>
            <li><a href="manage_pets.php">Manage Pets</a></li>
            <li><a class="active" href="adoption_request.php">Adoption Requests</a></li>
            <li><a href="admin_appointments.php">Appointments</a></li>
        </ul>
        <a href="../logout.php" class="logout-button">Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h2>Adoption Requests</h2>

        <table>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Email</th>
                <th>Pet</th>
                <th>Species</th>
                <th>Breed</th>
                <th>Status</th>
                <th>Fee</th>
                <th>Reason</th>
                <th>Legal ID</th>
                <th>Cancelled Reason</th>
                <th>Cancelled At</th>
                <th>Request Date</th>
                <th>Action</th>
            </tr>

            <?php if ($requests->num_rows > 0): ?>
                <?php while ($row = $requests->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['adoption_id'] ?></td>
                        <td><?= htmlspecialchars($row['user_name']) ?></td>
                        <td><?= htmlspecialchars($row['user_email']) ?></td>
                        <td><?= htmlspecialchars($row['pet_name']) ?></td>
                        <td><?= htmlspecialchars($row['pet_species']) ?></td>
                        <td><?= htmlspecialchars($row['pet_breed']) ?></td>
                        <td class="status-<?= $row['status'] ?>"><?= $row['status'] ?></td>
                        <td><?= $row['adoption_fee'] ? 'Rs '.$row['adoption_fee'] : '-' ?></td>
                        <td><?= htmlspecialchars($row['reason']) ?></td>

                        <!-- Legal ID -->
                        <td>
                            <?php if($row['legal_id_image']): ?>
                                <a href="../uploads/legal_ids/<?= htmlspecialchars($row['legal_id_image']) ?>" target="_blank">View</a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>

                        <!-- Cancelled Reason -->
                        <td><?= htmlspecialchars($row['cancel_reason'] ?: '-') ?></td>

                        <!-- Cancelled At (DATE ONLY) -->
                        <td><?= $row['cancelled_at'] ? date("d M Y", strtotime($row['cancelled_at'])) : '-' ?></td>

                        <!-- Adoption Date (DATE ONLY) -->
                        <td><?= $row['adoption_date'] ? date("d M Y", strtotime($row['adoption_date'])) : '-' ?></td>

                        <!-- Actions -->
                        <td>
                            <?php if($row['status'] === 'Pending'): ?>
                                <form method="POST" style="display:inline-block;">
                                    <input type="hidden" name="adoption_id" value="<?= $row['adoption_id'] ?>">
                                    <button type="submit" name="action" value="approve" class="btn approve-btn">Approve</button>
                                    <button type="submit" name="action" value="reject" class="btn reject-btn">Reject</button>
                                </form>
                            <?php elseif($row['status'] === 'Approved'): ?>
                                <form method="POST" style="display:inline-block;">
                                    <input type="hidden" name="adoption_id" value="<?= $row['adoption_id'] ?>">
                                    <select name="payment_status">
                                        <option value="Unpaid" <?= $row['payment_status']=='Unpaid'?'selected':'' ?>>Unpaid</option>
                                        <option value="Paid" <?= $row['payment_status']=='Paid'?'selected':'' ?>>Paid</option>
                                    </select>
                                    <button type="submit" class="btn update-btn">Update Payment</button>
                                </form>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="14">No adoption requests found</td></tr>
            <?php endif; ?>
        </table>
    </div>
</div>
</body>
</html>
