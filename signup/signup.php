<?php
session_start();

// Database configuration
$servername = "localhost";
$username = "root";
$password = "1234";
$dbname = "buddy_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user_type = $_POST['user_type'];
    
    if ($user_type === 'admin') {
        // Admin signup
        $admin_name = $_POST['admin_name'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        // Password match check
        if ($password !== $confirm_password) {
            echo "<script>alert('Passwords do not match!'); window.history.back();</script>";
            exit;
        }

        // Check if admin username already exists
        $check = $conn->prepare("SELECT admin_id FROM admin WHERE admin_name = ?");
        $check->bind_param("s", $admin_name);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            echo "<script>alert('Admin username already exists!'); window.history.back();</script>";
            exit;
        }

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert into admin table
        $stmt = $conn->prepare("INSERT INTO admin (admin_name, password) VALUES (?, ?)");
        $stmt->bind_param("ss", $admin_name, $hashed_password);

        if ($stmt->execute()) {
            $admin_id = $conn->insert_id;
            
            // Also insert into users table
            $stmt_user = $conn->prepare("INSERT INTO users 
                (name, user_name, password, email, user_type) 
                VALUES (?, ?, ?, ?, 'admin')");
            
            $email = $admin_name . "@admin.buddy";
            $stmt_user->bind_param("ssss", $admin_name, $admin_name, $hashed_password, $email);
            $stmt_user->execute();
            $stmt_user->close();
            
            // Set session variables
            $_SESSION['user_id'] = $admin_id;
            $_SESSION['user_name'] = $admin_name;
            $_SESSION['user_type'] = 'admin';
            $_SESSION['admin_id'] = $admin_id;
            
            // Redirect to admin dashboard
            header("Location: ../Admin/Admin_dashboard.php");
            exit;
            
        } else {
            echo "<script>alert('Admin signup failed!'); window.history.back();</script>";
        }

        $stmt->close();
        
    } else {
        // Regular user signup (user, vet, shelter)
        $name = $_POST['name'];
        $user_name = $_POST['user_name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'] ?? '';
        $address = $_POST['address'] ?? '';
        $gender = $_POST['gender'];
        $pass = $_POST['password'];
        $confirm = $_POST['confirm_password'];

        // Password match check
        if ($pass !== $confirm) {
            echo "<script>alert('Passwords do not match!'); window.history.back();</script>";
            exit;
        }

        // Check if user exists
        $check = $conn->prepare("SELECT user_id FROM users WHERE user_name = ? OR email = ?");
        $check->bind_param("ss", $user_name, $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            echo "<script>alert('Username or Email already exists!'); window.history.back();</script>";
            exit;
        }

        // Hash password
        $hashed_pass = password_hash($pass, PASSWORD_DEFAULT);

        // Insert user into database
        $stmt = $conn->prepare("INSERT INTO users
            (name, user_name, password, email, phone, address, gender, user_type)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "ssssssss",
            $name, $user_name, $hashed_pass, $email, $phone,
            $address, $gender, $user_type
        );

        if ($stmt->execute()) {
            $_SESSION['user_id'] = $conn->insert_id;
            $_SESSION['user_name'] = $user_name;
            $_SESSION['user_type'] = $user_type;

            // Redirect based on user type
            switch ($user_type) {
                case 'vet':
                    $redirect = "../Vet/Vet_dashboard.php";
                    break;
                case 'shelter':
                    $redirect = "../Shelter/Shelter_dashboard.php";
                    break;
                case 'admin': // This shouldn't happen here, but just in case
                    $redirect = "../Admin/Admin_dashboard.php";
                    break;
                default:
                    $redirect = "../User/User_dashboard.php";
            }

            header("Location: $redirect");
            exit;

        } else {
            echo "<script>alert('Signup failed! Please try again.'); window.history.back();</script>";
        }
        
        $stmt->close();
    }

    $conn->close();
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Signup | Buddy</title>
<style>
body {
    margin: 0;
    font-family: 'Arial', sans-serif;
    background: #debf9eff; 
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
}

.container {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 15px 35px rgba(0,0,0,0.2);
    width: 100%;
    max-width: 500px;
    padding: 40px;
    margin: 20px;
}

h1 {
    text-align: center;
    color: #333;
    margin-bottom: 20px;
}

label {
    font-weight: 600;
    margin-top: 15px;
    display: block;
    color: #555;
}

select, input, button {
    width: 100%;
    padding: 12px 15px;
    margin-top: 8px;
    margin-bottom: 15px;
    border-radius: 10px;
    border: 1px solid #ccc;
    font-size: 16px;
    box-sizing: border-box;
    transition: all 0.3s ease;
}

select:focus, input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

button {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: #fff;
    border: none;
    cursor: pointer;
    font-weight: bold;
    font-size: 16px;
    transition: all 0.3s ease;
}

button:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

button:active {
    transform: translateY(0);
}

.hidden { display: none; }

.user-type-label {
    font-weight: bold;
    margin-bottom: 5px;
    display: block;
    color: #333;
    text-align: center;
}

.login-link {
    text-align: center;
    margin-top: 20px;
}

.login-link a {
    color: #667eea;
    text-decoration: none;
    font-weight: bold;
}

.login-link a:hover {
    text-decoration: underline;
}

/* Additional form styling */
.password-hint {
    font-size: 12px;
    color: #666;
    margin-top: -10px;
    margin-bottom: 15px;
}

.required::after {
    content: " *";
    color: #e74c3c;
}
</style>

</head>
<body>
<div class="container">
    <h1>Sign Up</h1>

    <label class="user-type-label" for="user_type_select">Select User Type</label>
    <select id="user_type_select">
        <option value="">--Select--</option>
        <option value="user">User</option>
        <option value="vet">Vet</option>
        <option value="admin">Admin</option>
    </select>

    <!-- Regular User Form -->
    <form id="user_form" class="hidden" method="POST" action="signup.php">
        <input type="hidden" name="user_type" value="user">
        
        <label for="name" class="required">Full Name</label>
        <input type="text" id="name" name="name" placeholder="Enter your full name" required>
        
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

    <!-- Admin Form -->
    <form id="admin_form" class="hidden" method="POST" action="signup.php">
        <input type="hidden" name="user_type" value="admin">
        
        <label for="admin_name" class="required">Admin Username</label>
        <input type="text" id="admin_name" name="admin_name" placeholder="Enter admin username" required>
        
        <label for="admin_password" class="required">Password</label>
        <input type="password" id="admin_password" name="password" placeholder="Enter password" required minlength="6">
        <div class="password-hint">Password must be at least 6 characters long</div>
        
        <label for="admin_confirm" class="required">Confirm Password</label>
        <input type="password" id="admin_confirm" name="confirm_password" placeholder="Confirm password" required>
        
        <button type="submit">Create Admin Account</button>
    </form>

    <div class="login-link">
        <p>Already have an account? <a href="../login/Login.php">Login here</a></p>
    </div>
</div>

<script>
const userTypeSelect = document.getElementById('user_type_select');
const userForm = document.getElementById('user_form');
const adminForm = document.getElementById('admin_form');

userTypeSelect.addEventListener('change', () => {
    const type = userTypeSelect.value;
    if (type === 'admin') {
        adminForm.classList.remove('hidden');
        userForm.classList.add('hidden');
        adminForm.querySelector('input[name="user_type"]').value = 'admin';
    } else if (type) {
        userForm.classList.remove('hidden');
        adminForm.classList.add('hidden');
        userForm.querySelector('input[name="user_type"]').value = type;
    } else {
        userForm.classList.add('hidden');
        adminForm.classList.add('hidden');
    }
});

// Add form validation
document.getElementById('user_form').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (password.length < 6) {
        e.preventDefault();
        alert('Password must be at least 6 characters long.');
        return;
    }
    
    if (password !== confirmPassword) {
        e.preventDefault();
        alert('Passwords do not match.');
        return;
    }
});

document.getElementById('admin_form').addEventListener('submit', function(e) {
    const password = document.getElementById('admin_password').value;
    const confirmPassword = document.getElementById('admin_confirm').value;
    
    if (password.length < 6) {
        e.preventDefault();
        alert('Password must be at least 6 characters long.');
        return;
    }
    
    if (password !== confirmPassword) {
        e.preventDefault();
        alert('Passwords do not match.');
        return;
    }
});

// Real-time password validation
function setupPasswordValidation(formId, passwordId, confirmId) {
    const form = document.getElementById(formId);
    const password = document.getElementById(passwordId);
    const confirm = document.getElementById(confirmId);
    
    if (password && confirm) {
        confirm.addEventListener('input', function() {
            if (password.value && confirm.value) {
                if (password.value !== confirm.value) {
                    confirm.style.borderColor = '#e74c3c';
                } else {
                    confirm.style.borderColor = '#10b981';
                }
            }
        });
    }
}

// Initialize password validation for both forms
setupPasswordValidation('user_form', 'password', 'confirm_password');
setupPasswordValidation('admin_form', 'admin_password', 'admin_confirm');
</script>
</body>
</html>