<?php
session_start();
include(__DIR__ . '/../db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit;
}


if ($_SESSION['user_type'] !== 'user') {
    switch ($_SESSION['user_type']) {
        case 'admin': header("Location: ../Admin/Admin_dashboard.php"); exit;
        case 'vet': header("Location: ../Vet/Vet_dashboard.php"); exit;
        case 'shelter': header("Location: ../Shelter/Shelter_dashboard.php"); exit;
        default: header("Location: ../login/login.php"); exit;
    }
}

$user_id = $_SESSION['user_id'];


$stmt = $conn->prepare("SELECT user_name, email FROM users WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();


$stmt = $conn->prepare("
    SELECT p.pet_id, p.name, p.dob, p.gender, pt.species, pt.breed, a.status, p.adoption_fee, p.image
    FROM adoption_application a
    JOIN pet p ON a.pet_id = p.pet_id
    JOIN pet_type pt ON p.type_id = pt.type_id
    WHERE a.user_id = ? AND a.status='Approved'
    ORDER BY a.adoption_date DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Pets | Buddy</title>
<link rel="stylesheet" href="User.css">
<style>

.table-container{background:#fff;padding:20px;border-radius:12px;box-shadow:0 4px 10px rgba(0,0,0,0.08);}
table{width:100%;border-collapse:collapse;border-radius:10px;overflow:hidden;}
th,td{padding:12px 14px;border:1px solid #eee;text-align:center;}
th{background:#2f3640;color:#fff;}

.status{padding:6px 12px;border-radius:20px;font-size:13px;font-weight:bold;color:#fff;}
.approved{background:#4caf50;}
img.pet-img{width:80px;height:60px;object-fit:cover;border-radius:6px;}

.empty-appointments{text-align:center;margin-top:30px;}
.empty-appointments button{padding:10px 18px;background:#3e2f24;color:#fff;border:none;border-radius:6px;font-weight:600;cursor:pointer;}
.empty-appointments button:hover{background:#ce9a16;}

</style>
</head>
<body>


<header>
  <div class="logo">
    <img src="../picture/Group.png" alt="Buddy Logo">
    <h2>Buddy</h2>
  </div>
  <nav class="navbar">
    <ul>
      <li><a href="../pet/pet.php">Browse Pets</a></li>
      <li><a href="vet_booking.php">Vet Services</a></li>
      <li><a href="#">Contact</a></li>
    </ul>
  </nav>
  <a href="../logout.php" class="login">Logout</a>
</header>

<div class="dashboard-container">

  
  <div class="sidebar">
    <ul class="sidebar-nav">
      <li><a href="User_dashboard.php">Dashboard</a></li>
      <li><a href="my_pets.php" class="active">My Pets</a></li>
      <li><a href="appointments.php">Appointments</a></li>
      <li><a href="adoption_applications.php">Adoption Applications</a></li>
    </ul>

    <div class="user-profile">
  <img src="https://cdn-icons-png.flaticon.com/512/847/847969.png" alt="User">
  <div class="user-info">
    <h4><?= htmlspecialchars($user['user_name']); ?></h4>
    <p><?= htmlspecialchars($user['email']); ?></p>
  </div>
</div>

  </div>


  <div class="main-content">
    <h2>My Adopted Pets</h2>

    <?php if ($pets): ?>
    <div class="table-container">
      <table>
        <tr>
          <th>Image</th>
          <th>Name</th>
          <th>Age</th>
          <th>Gender</th>
          <th>Species</th>
          <th>Breed</th>
          <th>Status</th>
        </tr>

        <?php foreach($pets as $pet):
            // Image handling
            $defaultImg = '../picture/happy.png';
            $imageSrc = $defaultImg;
            if(!empty($pet['image'])){
                $img = trim($pet['image']);
                $fullPath = __DIR__ . '/../' . $img;
                if(file_exists($fullPath)){
                    $imageSrc = '../' . ltrim($img,'/'); // proper relative path
                } elseif(filter_var($img, FILTER_VALIDATE_URL)){
                    $imageSrc = $img;
                }
            }

           
            $age = 'Unknown';
            if(!empty($pet['dob']) && $pet['dob'] !== '0000-00-00'){
                $dob = new DateTime($pet['dob']);
                $today = new DateTime();
                $age = $today->diff($dob)->y;
            }
        ?>
        <tr>
          <td><img src="<?= $imageSrc ?>" alt="<?= htmlspecialchars($pet['name']) ?>" class="pet-img"></td>
          <td><?= htmlspecialchars($pet['name']) ?></td>
          <td><?= $age ?> years</td>
          <td><?= htmlspecialchars($pet['gender']) ?></td>
          <td><?= htmlspecialchars($pet['species']) ?></td>
          <td><?= htmlspecialchars($pet['breed']) ?></td>
          <td><span class="status approved"><?= htmlspecialchars($pet['status']) ?></span></td>
        </tr>
        <?php endforeach; ?>
      </table>
    </div>

    <?php else: ?>
      <div class="empty-appointments">
        <h3>No Adopted Pets Yet</h3>
        <p>You don't have any adopted pets yet.</p>
        <button onclick="window.location.href='../pet/pet.php'">Browse Pets</button>
      </div>
    <?php endif; ?>

  </div>
</div>

</body>
</html>
