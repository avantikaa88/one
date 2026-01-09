<?php
session_start();
include('../db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    /* ================== 1️⃣ CHECK ADMIN ================== */
    $stmt = $conn->prepare(
        "SELECT admin_id, password FROM admin WHERE admin_name = ? LIMIT 1"
    );
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Plain text comparison
    if ($admin && $password === $admin['password']) {
        $_SESSION['user_id'] = $admin['admin_id'];
        $_SESSION['user_type'] = 'admin';
        header("Location: ../Admin/Admin_dashboard.php");
        exit;
    }

    /* ================== 2️⃣ CHECK VET ================== */
    $stmt = $conn->prepare(
        "SELECT vet_id, password FROM vet WHERE username = ? LIMIT 1"
    );
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $vet = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($vet && md5($password) === $vet['password']) {
        $_SESSION['user_id'] = $vet['vet_id'];
        $_SESSION['user_type'] = 'vet';
        header("Location: ../Vet/Vet_dashboard.php");
        exit;
    }

    /* ================== 3️⃣ CHECK USERS ================== */
    $stmt = $conn->prepare(
        "SELECT user_id, password, user_type FROM users WHERE user_name = ? LIMIT 1"
    );
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_type'] = $user['user_type'];

        switch ($user['user_type']) {

            case 'admin': header("Location: ../Admin/Admin_dashboard.php"); exit;
            case 'user': header("Location: ../User/User_dashboard.php"); exit;
        }
    }

    /* ================== LOGIN FAILED ================== */
    $_SESSION['login_errors'] = ["Invalid username or password."];
    header("Location: login.php");
    exit;
}
