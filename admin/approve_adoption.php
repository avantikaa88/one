<?php
session_start();
include(__DIR__ . '/../db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login/login.php");
    exit;
}


if (!isset($_GET['id']) || !isset($_GET['action'])) {
    header("Location: admin_dashboard.php?message=Invalid request&type=error");
    exit;
}

$adoption_id = intval($_GET['id']);
$action = $_GET['action'];


if (!in_array($action, ['approve', 'reject'])) {
    header("Location: admin_dashboard.php?message=Invalid action&type=error");
    exit;
}


$conn->begin_transaction();

try {
    $stmt = $conn->prepare("SELECT pet_id, user_id FROM adoption_application WHERE adoption_id = ?");
    $stmt->bind_param("i", $adoption_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Adoption application not found");
    }
    
    $app_data = $result->fetch_assoc();
    $pet_id = $app_data['pet_id'];
    $user_id = $app_data['user_id'];
    $stmt->close();
    
    // Update adoption application status
    $new_status = ($action === 'approve') ? 'Approved' : 'Rejected';
    $stmt = $conn->prepare("UPDATE adoption_application SET status = ? WHERE adoption_id = ?");
    $stmt->bind_param("si", $new_status, $adoption_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update adoption application");
    }
    $stmt->close();
    
    
    if ($action === 'approve') {
        
        $stmt = $conn->prepare("UPDATE pet SET status = 'Adopted' WHERE pet_id = ?");
        $stmt->bind_param("i", $pet_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update pet status");
        }
        $stmt->close();
        
        
        $stmt = $conn->prepare("
            UPDATE adoption_application 
            SET status = 'Rejected' 
            WHERE pet_id = ? 
            AND status = 'Pending' 
            AND adoption_id != ?
        ");
        $stmt->bind_param("ii", $pet_id, $adoption_id);
        $stmt->execute();
        $stmt->close();
        
        
        $message = "Your adoption application has been approved! The pet is now yours.";
        
    } else {
        
        $stmt = $conn->prepare("
            SELECT COUNT(*) as approved_count 
            FROM adoption_application 
            WHERE pet_id = ? AND status = 'Approved'
        ");
        $stmt->bind_param("i", $pet_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $approved_count = $result->fetch_assoc()['approved_count'];
        $stmt->close();
        
        
        if ($approved_count == 0) {
            $stmt = $conn->prepare("UPDATE pet SET status = 'Available' WHERE pet_id = ?");
            $stmt->bind_param("i", $pet_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update pet status");
            }
            $stmt->close();
        }
        
        
        $message = "Your adoption application has been rejected.";
    }
    
    
    $conn->commit();
    
    
    $success_message = ($action === 'approve') 
        ? "Adoption application approved successfully!" 
        : "Adoption application rejected successfully!";
    
    header("Location: admin_dashboard.php?message=" . urlencode($success_message) . "&type=success");
    exit;
    
} catch (Exception $e) {
    
    $conn->rollback();
    header("Location: admin_dashboard.php?message=" . urlencode($e->getMessage()) . "&type=error");
    exit;
}
?>