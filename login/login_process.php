<?php
session_start();
include(__DIR__ . '/../db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $user_type = $_POST['user_type'];

    $errors = [];

    if (empty($username)) $errors[] = "Username is required";
    if (empty($password)) $errors[] = "Password is required";
    if (empty($user_type)) $errors[] = "User type is required";

    if (empty($errors)) {
        $sql = "SELECT * FROM users WHERE user_name = ? AND user_type = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $user_type);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_name'] = $user['user_name'];
                $_SESSION['user_type'] = $user['user_type'];

                // Redirect
                switch ($user_type) {
                    case 'admin': header("Location: ../Admin/Admin_dashboard.php"); break;
                    case 'vet': header("Location: ../Vet/Vet_dashboard.php"); break;
                    case 'shelter': header("Location: ../Shelter/Shelter_dashboard.php"); break;
                    default: header("Location: ../User/User_dashboard.php");
                }
                exit;
            } else {
                $errors[] = "Incorrect password";
            }
        } else {
            $errors[] = "No account found with that username and user type";
        }

        $stmt->close();
    }

    $_SESSION['login_errors'] = $errors;
    header("Location: login.php");
    exit;
}

header("Location: login.php");
exit;
?>
