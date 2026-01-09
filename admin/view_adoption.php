<?php
session_start();
include(__DIR__ . '/../db.php');


if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit;
}

if ($_SESSION['user_type'] !== 'admin') {
    header("Location: ../login/login.php");
    exit;
}


if (!isset($_GET['id'])) {
    header("Location: admin_dashboard.php");
    exit;
}

$adoption_id = (int)$_GET['id'];


$stmt = $conn->prepare("
    SELECT 
        aa.*,
        u.user_id, u.name as user_name, u.email, u.phone, u.address,
        p.pet_id, p.name as pet_name, p.age, p.gender, p.status as pet_status, p.adoption_fee,
        pt.species, pt.breed, pt.size, pt.life_span
    FROM adoption_application aa
    JOIN users u ON aa.user_id = u.user_id
    JOIN pet p ON aa.pet_id = p.pet_id
    JOIN pet_type pt ON p.type_id = pt.type_id
    WHERE aa.adoption_id = ?
");
$stmt->bind_param("i", $adoption_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: admin_dashboard.php?error=Application not found");
    exit;
}

$application = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View Adoption Application</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
    body { background-color: #f5f6fa; padding: 20px; }
    .container { max-width: 800px; margin: 0 auto; background: white; border-radius: 10px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
    .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
    .btn-back { padding: 8px 15px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; }
    .btn-back:hover { background: #2980b9; }
    h1 { color: #2c3e50; margin-bottom: 20px; }
    .section { margin-bottom: 25px; padding-bottom: 20px; border-bottom: 1px solid #eee; }
    .section h2 { color: #34495e; margin-bottom: 15px; font-size: 18px; }
    .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; }
    .info-item { margin-bottom: 10px; }
    .info-label { font-weight: bold; color: #7f8c8d; }
    .info-value { color: #2c3e50; }
    .status { display: inline-block; padding: 5px 10px; border-radius: 5px; font-weight: bold; }
    .status-pending { background: #fef9e7; color: #f39c12; }
    .status-approved { background: #e8f8f5; color: #27ae60; }
    .status-rejected { background: #fdedec; color: #e74c3c; }
    .payment-status { display: inline-block; padding: 5px 10px; border-radius: 5px; font-weight: bold; }
    .payment-pending { background: #fef9e7; color: #f39c12; }
    .payment-paid { background: #e8f8f5; color: #27ae60; }
    .actions { margin-top: 30px; text-align: center; }
    .btn { padding: 10px 20px; margin: 0 10px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
    .btn-approve { background: #27ae60; color: white; }
    .btn-approve:hover { background: #229954; }
    .btn-reject { background: #e74c3c; color: white; }
    .btn-reject:hover { background: #c0392b; }
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Adoption Application Details</h1>
        <a href="admin_dashboard.php" class="btn-back">← Back to Dashboard</a>
    </div>
    
    <?php if (isset($_GET['success'])): ?>
        <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
            <?= htmlspecialchars($_GET['success']) ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
            <?= htmlspecialchars($_GET['error']) ?>
        </div>
    <?php endif; ?>
   
    <div class="section">
        <h2>Application Information</h2>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Application ID:</div>
                <div class="info-value">#<?= $application['adoption_id'] ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Application Date:</div>
                <div class="info-value"><?= date('F j, Y', strtotime($application['adoption_date'])) ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Status:</div>
                <div class="info-value">
                    <span class="status status-<?= strtolower($application['status']) ?>">
                        <?= $application['status'] ?>
                    </span>
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">Payment Status:</div>
                <div class="info-value">
                    <span class="payment-status payment-<?= strtolower($application['payment_status']) ?>">
                        <?= $application['payment_status'] ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
    
    
    <div class="section">
        <h2>User Information</h2>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Full Name:</div>
                <div class="info-value"><?= htmlspecialchars($application['user_name']) ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Email:</div>
                <div class="info-value"><?= htmlspecialchars($application['email']) ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Phone:</div>
                <div class="info-value"><?= htmlspecialchars($application['phone']) ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Address:</div>
                <div class="info-value"><?= htmlspecialchars($application['address']) ?></div>
            </div>
        </div>
    </div>
    
    
    <div class="section">
        <h2>Pet Information</h2>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Pet Name:</div>
                <div class="info-value"><?= htmlspecialchars($application['pet_name']) ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Species:</div>
                <div class="info-value"><?= htmlspecialchars($application['species']) ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Breed:</div>
                <div class="info-value"><?= htmlspecialchars($application['breed']) ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Age:</div>
                <div class="info-value"><?= $application['age'] ?> years</div>
            </div>
            <div class="info-item">
                <div class="info-label">Gender:</div>
                <div class="info-value"><?= htmlspecialchars($application['gender']) ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Size:</div>
                <div class="info-value"><?= htmlspecialchars($application['size']) ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Life Span:</div>
                <div class="info-value"><?= htmlspecialchars($application['life_span']) ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Adoption Fee:</div>
                <div class="info-value">रु <?= number_format($application['adoption_fee'], 2) ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Pet Status:</div>
                <div class="info-value"><?= htmlspecialchars($application['pet_status']) ?></div>
            </div>
        </div>
    </div>
    
    <?php if ($application['status'] == 'Pending'): ?>
    <div class="actions">
        <a href="approve_adoption.php?id=<?= $adoption_id ?>&action=approve" class="btn btn-approve">Approve Adoption</a>
        <a href="approve_adoption.php?id=<?= $adoption_id ?>&action=reject" class="btn btn-reject" onclick="return confirm('Are you sure you want to reject this adoption application?')">Reject Adoption</a>
    </div>
    <?php endif; ?>
</div>
</body>
</html>