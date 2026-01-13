<?php
session_start();
include(__DIR__ . '/../db.php');

// ---------------- AUTH CHECK ----------------
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login/login.php");
    exit;
}

/* ================= DELETE VET ================= */
if (isset($_GET['delete'])) {
    $vet_id = (int)$_GET['delete'];

    $stmt = $conn->prepare("DELETE FROM vet WHERE vet_id = ?");
    $stmt->bind_param("i", $vet_id);
    $stmt->execute();
    $stmt->close();

    header("Location: manage_vet.php");
    exit;
}

/* ================= FETCH VETS ================= */
$vets = $conn->query("
    SELECT vet_id, email, specialization, licence_no,
           clinic_location, availability, experience, contact_info, created_at
    FROM vet
    ORDER BY vet_id DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Vet</title>
<link rel="stylesheet" href="admin.css">
<style>
    .add-button, .edit-button {
        display: inline-block;
        padding: 10px 20px;
        background-color: #8B4513;
        color: white;
        text-decoration: none;
        border-radius: 5px;
        margin-bottom: 20px;
        font-weight: bold;
    }
    .add-button:hover, .edit-button:hover {
        background-color: #a0522d;
    }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    table th, table td { text-align: center; padding: 10px; border: 1px solid #ccc; }
    th { background: #8B5826; color: white; }
    tr:nth-child(even) { background: #f7f7f7; }
    .action-buttons a { margin: 0 5px; font-weight: bold; text-decoration: none; }
    .delete-btn { color: red; }
    .edit-btn { color: green; }
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
        <li><a class="active" href="manage_vet.php">Manage Vet</a></li>
        <li><a href="manage_pets.php">Manage Pets</a></li>
        <li><a href="adoption_request.php">Adoption Requests</a></li>
        <li><a href="admin_appointments.php">Appointments</a></li>
    </ul>
    <a href="../logout.php" class="logout-button">Logout</a>
</div>

<!-- Main Content -->
<div class="main-content">

<h2>Vet Management</h2>

<!-- Add Vet Button -->
<a href="add_vet.php" class="add-button">+ Add New Vet</a>

<hr>

<h2>Vet List</h2>

<table width="100%" cellpadding="10" cellspacing="0">
    <tr>
        <th>ID</th>
        <th>Email</th>
        <th>Specialization</th>
        <th>Licence</th>
        <th>Clinic</th>
        <th>Availability</th>
        <th>Experience</th>
        <th>Contact</th>
        <th>Added Date</th>
        <th>Action</th>
    </tr>

    <?php if ($vets->num_rows > 0): ?>
        <?php while ($row = $vets->fetch_assoc()): ?>
            <tr>
                <td><?= $row['vet_id'] ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= htmlspecialchars($row['specialization']) ?></td>
                <td><?= htmlspecialchars($row['licence_no']) ?></td>
                <td><?= htmlspecialchars($row['clinic_location']) ?></td>
                <td><?= htmlspecialchars($row['availability']) ?></td>
                <td><?= $row['experience'] ?> yrs</td>
                <td><?= htmlspecialchars($row['contact_info']) ?></td>
                <td>
                    <?= !empty($row['created_at']) ? date("M d, Y", strtotime($row['created_at'])) : 'N/A'; ?>
                </td>
                <td class="action-buttons">
                    <a href="edit_vet.php?id=<?= $row['vet_id'] ?>" class="edit-btn">Edit</a>
                    <a href="?delete=<?= $row['vet_id'] ?>" class="delete-btn" onclick="return confirm('Delete this vet?')">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="10">No vets found</td></tr>
    <?php endif; ?>
</table>

</div>
</div>

</body>
</html>
