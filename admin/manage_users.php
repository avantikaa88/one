<?php
session_start();
include(__DIR__ . '/../db.php');

// ---------------- AUTH CHECK ----------------
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    // Redirect non-admin users to login
    header("Location: ../login/login.php");
    exit;
}

// ---------------- FETCH USERS ----------------
// Fetch all non-admin users, latest registered first
$users = $conn->query("SELECT * FROM users WHERE roles != 'admin' ORDER BY register_date DESC");

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Users</title>
<link rel="stylesheet" href="admin.css" />
<style>
    /* Main Content */
    .main-content { 
        flex: 1; 
        padding: 40px; 
        margin-left: 220px; 
        background: #f4f4f4;
    }
    h1 { margin-bottom: 20px; }

    /* Alert messages */
    .alert { padding: 12px 15px; border-radius: 5px; margin: 15px 0; }
    .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

    /* Table styling */
    table { 
        width: 100%; 
        border-collapse: collapse; 
        background: #fff; 
        border-radius: 10px; 
        overflow: hidden; 
        margin-top: 20px; 
        box-shadow: 0 4px 10px rgba(0,0,0,0.1); 
    }
    th, td { padding: 12px; border: 1px solid #ccc; text-align: left; }
    th { background:#8a5826; color:#fff; }
    tr:nth-child(even) { background:#f7f7f7; }
    .delete-link { color:#e84118; text-decoration: none; }
    .delete-link:hover { color:#c23616; }

    a { text-decoration: none; color: #2f3640; }
    a:hover { text-decoration: underline; }
</style>
</head>
<body>

<div class="dashboard-container">

    <!-- Sidebar -->
    <div class="sidebar">
        <h2>Buddy Admin</h2>
    <ul>
        <li><a class="active" href="admin_dashboard.php">Dashboard</a></li>
        <li><a href="manage_users.php">Manage Users</a></li>
        <li><a href="manage_vet.php">Manage Vet</a></li>
        <li><a href="manage_pets.php">Manage Pets</a></li>
        <li><a href="adoption_request.php">Adoption Requests</a></li>
        <li><a href="admin_appointments.php">Appointments</a></li>
    </ul>
        <a href="../logout.php" class="logout-button">Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h1>Manage Users</h1>

        <!-- Display Success/Error Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Gender</th>
                    <th>Address</th>
                    <th>Type</th>
                    <th>Registered</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php
            // Color codes for different user types
            $type_color = [
                'user' => 'color:#666;',
                'vet' => 'color:#0097e6;',
                'shelter' => 'color:#44bd32;'
            ];
            ?>
            <?php while($user = $users->fetch_assoc()): ?>
                <tr>
                    <td><?= $user['user_id'] ?></td>
                    <td><?= htmlspecialchars($user['name']) ?></td>
                    <td><?= htmlspecialchars($user['user_name']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= htmlspecialchars($user['phone']) ?></td>
                    <td><?= htmlspecialchars($user['gender']) ?></td>
                    <td><?= htmlspecialchars($user['address']) ?></td>
                    <td>
                 <span style="<?= $type_color[$user['roles']] ?? '' ?>">
                    <?= htmlspecialchars($user['roles']) ?>
                 </span>

                    </td>
                    <td><?= date('M d, Y', strtotime($user['register_date'])) ?></td>
                    <td>
                        <a href="edit_user.php?id=<?= $user['user_id'] ?>">Edit</a> | 
                        <a href="delete_user.php?id=<?= $user['user_id'] ?>" 
                           class="delete-link" 
                           onclick="return confirm('Are you sure you want to delete this user?\n\nUser: <?= addslashes($user['name']) ?>\nThis action cannot be undone!')">
                           Delete
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>

</div>

</body>
</html>
