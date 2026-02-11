<?php
session_start();
include(__DIR__ . '/../db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login/login.php");
    exit;
}

/* ================= ADD VET ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username         = trim($_POST['username']);
    $email            = trim($_POST['email']);
    $password         = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $specialization   = trim($_POST['specialization']);
    $licence_no       = trim($_POST['licence_no']);
    $clinic_location  = trim($_POST['clinic_location']);
    $availability     = trim($_POST['availability']);
    $experience       = (int)$_POST['experience'];
    $contact_info     = trim($_POST['contact_info']);

    $stmt = $conn->prepare("
        INSERT INTO vet 
        (username, password, specialization, licence_no, clinic_location,
         availability, experience, contact_info, email)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        "ssssssiss",
        $username,
        $password,
        $specialization,
        $licence_no,
        $clinic_location,
        $availability,
        $experience,
        $contact_info,
        $email
    );
    $stmt->execute();
    $stmt->close();

    header("Location: manage_vet.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Vet</title>
    <link rel="stylesheet" href="admin.css">
    <style>

        .form-box {
            max-width: 600px;
            margin: 0 auto;
            background: #fff;
            padding: 30px 40px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }

        .form-box input {
            width: 100%;
            padding: 12px 15px;
            margin: 12px 0;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 16px;
            transition: border 0.3s, box-shadow 0.3s;
        }

        .form-box input:focus {
            border-color: #8B4513;
            box-shadow: 0 0 5px rgba(139,69,19,0.3);
            outline: none;
        }

        .form-box button {
            width: 100%;
            padding: 14px;
            margin-top: 15px;
            background-color: #8B4513;
            color: #fff;
            font-size: 18px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s, transform 0.2s;
        }

        .form-box button:hover {
            background-color: #a0522d;
            transform: translateY(-2px);
        }

        /* Back link */
        .back-link {
            display: block;
            margin-top: 20px;
            text-align: center;
            color: #8B4513;
            font-weight: 500;
            text-decoration: none;
            transition: color 0.3s;
        }

        .back-link:hover {
            color: #a0522d;
        }

    </style>
</head>
<body>

<div class="dashboard-container">

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

<div class="main-content">

<h2>Add New Vet</h2>

<form method="post" class="form-box">

    <input type="text" name="username" placeholder="username" required>
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Password" required>

    <input type="text" name="specialization" placeholder="Specialization" required>
    <input type="text" name="licence_no" placeholder="Licence No" required>
    <input type="text" name="clinic_location" placeholder="Clinic Location" required>

    <input type="text" name="availability" placeholder="Availability (e.g. Mon–Fri)">
    <input type="number" name="experience" placeholder="Experience (years)" min="0">
    <input type="text" name="contact_info" placeholder="Contact Info" required>

    <button type="submit">Add Vet</button>
</form>

<a href="manage_vet.php" class="back-link">← Back to Vet List</a>

</div>
</div>

</body>
</html>