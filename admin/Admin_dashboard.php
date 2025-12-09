<?php
session_start();
include(__DIR__ . '/../db.php');


if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit;
}


function getDashboardUrl($user_type) {
    switch($user_type) {
        case 'user': return '../user/User_dashboard.php';
        case 'vet': return '../vet/Vet_dashboard.php';
        case 'shelter': return '../shelter/Shelter_dashboard.php';
        default: return '../login/login.php';
    }
}


if ($_SESSION['user_type'] !== 'admin') {
    header("Location: " . getDashboardUrl($_SESSION['user_type']));
    exit;
}


function getCount($conn, $table) {
    $res = $conn->query("SELECT COUNT(*) AS cnt FROM $table");
    return $res ? $res->fetch_assoc()['cnt'] : 0;
}

$total_users = getCount($conn, "users");
$total_pets = getCount($conn, "pet");
$total_shops = 1; 
$total_applications = getCount($conn, "adoption_application");


$applications = $conn->query("
    SELECT aa.adoption_id, u.name AS user_name, p.name AS pet_name, 
           aa.status, aa.payment_status, aa.adoption_date
    FROM adoption_application aa
    JOIN users u ON aa.user_id = u.user_id
    JOIN pet p ON aa.pet_id = p.pet_id
    ORDER BY aa.adoption_date DESC
    LIMIT 50
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>
<link rel="stylesheet" href="../index/stylee.css">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
    
    body { background-color: #f5f6fa; color: #333; }
    .dashboard-container { display: flex; min-height: 100vh; }

    .sidebar { width: 220px; background-color: #2f3640; color: #fff; padding: 20px; position: fixed; height: 100vh; }
    .sidebar h2 { margin-bottom: 30px; text-align: center; }
    .sidebar ul { list-style: none; }
    .sidebar ul li { margin: 15px 0; }
    .sidebar ul li a { color: #fff; text-decoration: none; padding: 8px 10px; display: block; border-radius: 5px; }
    .sidebar ul li a:hover, .active { background-color: #00a8ff; }

    
    .main-content { flex: 1; padding: 40px; margin-left: 220px; }
    .main-content h1 { margin-bottom: 20px; }

   
    .cards-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; }
    .card { background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); text-align: center; }
    .card h3 { font-size: 16px; color: #666; margin-bottom: 10px; }
    .card p { font-size: 24px; font-weight: bold; color: #333; }

    table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 10px; overflow: hidden; margin-top: 25px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
    th, td { padding: 12px 15px; border: 1px solid #ddd; }
    th { background-color: #2f3640; color: #fff; font-weight: 600; }
    tr:nth-child(even) { background-color: #f9f9f9; }
    tr:hover { background-color: #f5f5f5; }

    
    .status-pending { background-color: #fff3cd; color: #856404; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    .status-approved { background-color: #d4edda; color: #155724; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    .status-rejected { background-color: #f8d7da; color: #721c24; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    
    
    .payment-pending { background-color: #fff3cd; color: #856404; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    .payment-paid { background-color: #d4edda; color: #155724; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }

    
    .action-buttons { display: flex; gap: 8px; }
    .button-approve { background-color: #28a745; color: white; padding: 5px 12px; border-radius: 4px; text-decoration: none; font-size: 13px; }
    .button-approve:hover { background-color: #218838; }
    .button-reject { background-color: #dc3545; color: white; padding: 5px 12px; border-radius: 4px; text-decoration: none; font-size: 13px; }
    .button-reject:hover { background-color: #c82333; }

  
    .logout-button { display: block; margin-top: 20px; padding: 10px 20px; background: #e84118; text-align: center; color: #fff; border-radius: 5px; text-decoration: none; }
    .logout-button:hover { background: #c23616; }
    

    .message { padding: 10px 15px; border-radius: 5px; margin-bottom: 20px; }
    .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .warning { background-color: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
</style>
</head>
<body>
<div class="dashboard-container">

   
    <div class="sidebar">
        <h2>Buddy Admin</h2>
        <ul>
            <li><a class="active" href="admin_dashboard.php">Dashboard</a></li>
            <li><a href="manage_users.php">Manage Users</a></li>
            <li><a href="manage_pets.php">Manage Pets</a></li>
            <li><a href="adoption_requests.php">Adoption Requests</a></li>
            <li><a href="appointments.php">Appointments</a></li>
        </ul>
        <a href="../logout.php" class="logout-button">Logout</a>
    </div>

  
    <div class="main-content">
        <h1>Welcome, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?>!</h1>
        
        <?php
       
        if (isset($_GET['message'])) {
            $type = $_GET['type'] ?? 'success';
            $message = htmlspecialchars($_GET['message']);
            echo "<div class='message $type'>$message</div>";
        }
        ?>

        <
        <div class="cards-grid">
            <div class="card"><h3>Total Users</h3><p><?= $total_users ?></p></div>
            <div class="card"><h3>Total Pets</h3><p><?= $total_pets ?></p></div>
            <div class="card"><h3>Total Shops</h3><p><?= $total_shops ?></p></div>
            <div class="card"><h3>Adoption Applications</h3><p><?= $total_applications ?></p></div>
        </div>

      
        <h2 style="margin-top:30px;">Adoption Applications</h2>

        <table>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Pet</th>
                <th>Application Date</th>
                <th>Status</th>
                <th>Payment</th>
                <th>Actions</th>
            </tr>

            <?php if ($applications && $applications->num_rows > 0): ?>
                <?php while ($app = $applications->fetch_assoc()): 
                    $status_class = strtolower($app['status']);
                    $payment_class = strtolower($app['payment_status']);
                    $formatted_date = date('M j, Y', strtotime($app['adoption_date']));
                ?>
                <tr>
                    <td><?= $app['adoption_id'] ?></td>
                    <td><?= htmlspecialchars($app['user_name']) ?></td>
                    <td><?= htmlspecialchars($app['pet_name']) ?></td>
                    <td><?= $formatted_date ?></td>
                    <td><span class="status-<?= $status_class ?>"><?= htmlspecialchars($app['status']) ?></span></td>
                    <td><span class="payment-<?= $payment_class ?>"><?= htmlspecialchars($app['payment_status']) ?></span></td>
                    <td>
                        <div class="action-buttons">
                            <?php if ($app['status'] == 'Pending'): ?>
                                <a href="approve_adoption.php?id=<?= $app['adoption_id'] ?>&action=approve" class="button-approve">Approve</a>
                                <a href="approve_adoption.php?id=<?= $app['adoption_id'] ?>&action=reject" class="button-reject">Reject</a>
                            <?php else: ?>
                                <span style="color: #666; font-size: 13px;">Action taken</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7" style="text-align:center; padding:20px;">No adoption applications found</td></tr>
            <?php endif; ?>
        </table>

    </div>
</div>

</body>
</html>