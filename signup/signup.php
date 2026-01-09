<?php
session_start();

// Database configuration
$servername = "localhost";
$username = "root";
$password = "1234";
$dbname = "fakebuddy_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // ---------------- REGULAR USER SIGNUP ----------------
    $name = $_POST['name'];
    $user_name = $_POST['user_name'];
    $email = $_POST['email'];
    $phone = !empty($_POST['phone']) ? $_POST['phone'] : '';
    $address = !empty($_POST['address']) ? $_POST['address'] : '';
    $gender = $_POST['gender'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Check if passwords match
    if ($password !== $confirm_password) {
        echo "<script>alert('Passwords do not match!'); window.history.back();</script>";
        exit;
    }

    // Check if username or email already exists
    $check = $conn->prepare("SELECT user_id FROM users WHERE user_name = ? OR email = ?");
    $check->bind_param("ss", $user_name, $email);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        echo "<script>alert('Username or Email already exists!'); window.history.back();</script>";
        exit;
    }
    $check->close();

    // Hash the password
    $hashed_pass = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user into the database
    $stmt = $conn->prepare("INSERT INTO users (name, user_name, password, email, phone, address, gender, roles) VALUES (?, ?, ?, ?, ?, ?, ?, 'user')");
    $stmt->bind_param("sssssss", $name, $user_name, $hashed_pass, $email, $phone, $address, $gender);

    if ($stmt->execute()) {
        // Set session variables
        $_SESSION['user_id'] = $conn->insert_id;
        $_SESSION['user_name'] = $user_name;
        $_SESSION['user_role'] = 'user';

        // Redirect to user dashboard
        header("Location: ../User/User_dashboard.php");
        exit;
    } else {
        echo "<script>alert('Signup failed! Please try again.'); window.history.back();</script>";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Signup | Buddy</title>
<style>
body { margin:0; font-family:'Arial', sans-serif; background:#debf9eff; min-height:100vh; display:flex; justify-content:center; align-items:center; }
.container { background:#fff; border-radius:20px; box-shadow:0 15px 35px rgba(0,0,0,0.2); width:100%; max-width:500px; padding:40px; margin:20px; }
h1 { text-align:center; color:#333; margin-bottom:20px; }
label { font-weight:600; margin-top:15px; display:block; color:#555; }
input, select, button { width:100%; padding:12px 15px; margin-top:8px; margin-bottom:15px; border-radius:10px; border:1px solid #ccc; font-size:16px; box-sizing:border-box; transition: all 0.3s ease; }
select:focus, input:focus { outline:none; border-color:#667eea; box-shadow:0 0 0 3px rgba(102,126,234,0.1); }
button { background:linear-gradient(135deg,#667eea,#764ba2); color:#fff; border:none; cursor:pointer; font-weight:bold; font-size:16px; transition:all 0.3s ease; }
button:hover { transform:translateY(-2px); box-shadow:0 5px 15px rgba(102,126,234,0.4); }
button:active { transform:translateY(0); }
.password-hint { font-size:12px; color:#888; }
.login-link { text-align:center; margin-top:20px; }
.login-link a { color:#667eea; text-decoration:none; font-weight:bold; }
.login-link a:hover { text-decoration:underline; }
</style>
</head>
<body>
<div class="container">
<h1>Sign Up</h1>

<form id="user_form" method="POST" action="signup.php">
    <label for="name" class="required">Name</label>
    <input type="text" id="name" name="name" placeholder="Enter your name" required>

    <label for="user_name" class="required">Username</label>
    <input type="text" id="user_name" name="user_name" placeholder="Choose a username" required>

    <label for="email" class="required">Email</label>
    <input type="email" id="email" name="email" placeholder="Enter your email" required>

    <label for="phone">Phone Number</label>
    <input type="text" id="phone" name="phone" placeholder="Enter phone number">

    <label for="address">Address</label>
    <input type="text" id="address" name="address" placeholder="Enter your address">

    <label for="gender" class="required">Gender</label>
    <select id="gender" name="gender" required>
        <option value="">Select Gender</option>
        <option value="Male">Male</option>
        <option value="Female">Female</option>
        <option value="Other">Other</option>
    </select>

    <label for="password" class="required">Password</label>
    <input type="password" id="password" name="password" placeholder="Enter password" required minlength="6">
    <div class="password-hint">Password must be at least 6 characters long</div>

    <label for="confirm_password" class="required">Confirm Password</label>
    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm password" required>

    <button type="submit">Sign Up</button>
</form>

<div class="login-link">
    <p>Already have an account? <a href="../login/Login.php">Login here</a></p>
</div>
</div>

<script>
// Real-time password match validation
const password = document.getElementById('password');
const confirm = document.getElementById('confirm_password');
confirm.addEventListener('input', () => {
    if (password.value && confirm.value) {
        confirm.style.borderColor = (password.value === confirm.value) ? '#10b981' : '#e74c3c';
    }
});
</script>
</body>
</html>
