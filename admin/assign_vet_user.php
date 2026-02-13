<?php
session_start();
include(__DIR__ . '/../db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login/login.php");
    exit;
}


if (isset($_POST['appointment_id'], $_POST['vet_id'])) {
    $appointment_id = (int)$_POST['appointment_id'];
    $vet_id = (int)$_POST['vet_id'];

    $stmt = $conn->prepare("SELECT status FROM vet_appointments WHERE id=?");
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if($res->num_rows > 0){
        $row = $res->fetch_assoc();
        if($row['status'] === 'Pending'){
            
            $stmt2 = $conn->prepare("UPDATE vet_appointments SET vet_id=? WHERE id=?");
            $stmt2->bind_param("ii", $vet_id, $appointment_id);
            $stmt2->execute();
            $stmt2->close();
        }
    }
    $stmt->close();
}

header("Location: admin_appointments.php");
exit;
?>
