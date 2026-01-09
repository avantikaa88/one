<?php
session_start();
include('../db.php');

$error = '';

// --------------- REDIRECT IF ALREADY LOGGED IN ----------------
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
    switch ($_SESSION['user_type']) {
        case 'admin':
            header("Location: ../Admin/Admin_dashboard.php");
            exit;
        case 'vet':
            header("Location: ../Vet/Vet_dashboard.php");
            exit;
        default:
            header("Location: ../User/User_dashboard.php");
            exit;
    }
}

// --------------- HANDLE LOGIN ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if ($username === '' || $password === '') {
        $error = "Please enter both username and password.";
    } else {
        $user = null;

        // -------- 1. Check users table (users/admins) --------
        $stmt = $conn->prepare("SELECT user_id, password, roles FROM users WHERE user_name = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
        }
        $stmt->close();

        // -------- 2. If not found in users, check vet table --------
        if (!$user) {
            $stmt = $conn->prepare("SELECT vet_id, password FROM vet WHERE username = ? LIMIT 1");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 1) {
                $vet = $result->fetch_assoc();
                $user = [
                    'user_id' => $vet['vet_id'], // store vet_id in session
                    'password' => $vet['password'],
                    'roles' => 'vet'
                ];
            }
            $stmt->close();
        }

        // -------- 3. Verify password and set session --------
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_type'] = $user['roles'];

            // Redirect based on role
            switch ($user['roles']) {
                case 'admin':
                    header("Location: ../Admin/Admin_dashboard.php");
                    exit;
                case 'vet':
                    header("Location: ../Vet/Vet_dashboard.php");
                    exit;
                default:
                    header("Location: ../User/User_dashboard.php");
                    exit;
            }
        } else {
            $error = "Invalid username or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login | Buddy</title>
<style>
body {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 700px;
    background-color: #ffe6e6;
    font-family: Arial, sans-serif;
}
.login {
    background-color: rgb(243, 219, 189);
    padding: 25px 20px;
    border-radius: 15px;
    width: 350px;
    text-align: center;
}
.input {
    width: 80%;
    padding: 10px;
    margin: 10px 0;
    border-radius: 5px;
    border: 1px solid #ccc;
}
label { display: block; text-align: left; margin-bottom: 5px; }
.button {
    display: inline-block;
    background-color: rgb(232, 172, 93);
    color: white;
    padding: 10px;
    margin: 5px;
    text-decoration: none;
    border-radius: 8px;
    font-weight: bold;
    border: none;
    cursor: pointer;
}
.button:hover { background-color: rgb(224, 153, 130); }
.error-box {
    color: red;
    margin-bottom: 15px;
    padding: 10px;
    background: #ffebee;
    border-radius: 5px;
}
</style>
</head>
<body>
<div class="login">
    <h1>Login</h1>

    <?php if (!empty($error)): ?>
        <div class="error-box"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="username">Username:</label>
        <input type="text" name="username" placeholder="Enter Your Username" class="input" required>

        <label for="password">Password:</label>
        <input type="password" name="password" placeholder="Enter Your Password" class="input" required>

        <button type="submit" class="button" style="width: 100%;">Login</button>
    </form>

    <div style="margin-top: 15px; text-align: center;">
        <a href="" style="color: #7c4b2c;">Forgot Password</a><br>
        <a href="../signup/Signup.php" style="color: #7c4b2c;">Don't have an account? Sign up</a>
    </div>
</div>
</body>
</html>
