<?php
session_start();

// If user is already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id'])) {
    $user_type = $_SESSION['user_type'] ?? 'user';
    header("Location: " . getDashboardPath($user_type));
    exit;
}

// Display login errors if any
$error = '';
if (isset($_SESSION['login_errors']) && !empty($_SESSION['login_errors'])) {
    $error = implode("<br>", $_SESSION['login_errors']);
    unset($_SESSION['login_errors']); // Clear errors after displaying
}

function getDashboardPath($user_type) {
    switch($user_type) {
        case 'admin':
            return '../Admin/Admin_dashboard.php';
        case 'vet':
            return '../Vet/Vet_dashboard.php';
        case 'shelter':
            return '../Shelter/Shelter_dashboard.php';
        default:
            return '../User/User_dashboard.php';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Buddy</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <div class="container">
        <h1>Login</h1>
        
        <?php if (!empty($error)): ?>
            <div style="color: red; margin-bottom: 15px; padding: 10px; background: #ffebee; border-radius: 5px;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form action="login_process.php" method="POST">
            <label for="username">Username:</label>
            <input type="text" name="username" placeholder="Enter Your Username" class="input" required>

            <label for="password">Password:</label> 
            <input type="password" name="password" placeholder="Enter Your Password" class="input" required>
            
            <label>User Type:</label>
            <select name="user_type" class="input" required>
                <option value="">Select User Type</option>
                <option value="user">User</option>
                <option value="shelter">Shelter</option>
                <option value="vet">Vet</option>
                <option value="admin">Admin</option>
            </select>

            
            <button type="submit" class="btn" style="width: 100%; border: none; cursor: pointer;">Login</button>
        </form>
        
        <div style="margin-top: 15px; text-align: center;">
            <a href="" style="color: #7c4b2c;">Forgot Password</a><br>
            <a href="../signup/Signup.php" style="color: #7c4b2c;">Don't have an account? Sign up</a>
        </div>
    </div>
</body>
</html>