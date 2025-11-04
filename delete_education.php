<?php
require_once 'database.php';
requireLogin();

$database = new Database();
$conn = $database->connect();

$education_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$faculty_id = isset($_GET['faculty_id']) ? intval($_GET['faculty_id']) : 0;

if ($education_id <= 0 || $faculty_id <= 0) {
    $_SESSION['error'] = "Invalid parameters.";
    header("Location: view_faculty.php");
    exit();
}

// Get the faculty_id from the education record to verify permissions
try {
    $stmt = $conn->prepare("SELECT faculty_id FROM education WHERE education_id = ?");
    $stmt->execute([$education_id]);
    $education = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$education) {
        $_SESSION['error'] = "Education record not found.";
        header("Location: view_faculty_detail.php?id=$faculty_id");
        exit();
    }
    
    // Verify the faculty_id matches
    if ($education['faculty_id'] != $faculty_id) {
        $_SESSION['error'] = "Invalid education record.";
        header("Location: view_faculty.php");
        exit();
    }
    
    // Permission check
    if (!canEditFaculty($faculty_id)) {
        $_SESSION['error'] = "You don't have permission to delete this education record.";
        header("Location: view_faculty_detail.php?id=$faculty_id");
        exit();
    }
    
    // Delete the education record
    $stmt = $conn->prepare("DELETE FROM education WHERE education_id = ?");
    $stmt->execute([$education_id]);
    
    $_SESSION['success'] = "Education record deleted successfully.";
    header("Location: view_faculty_detail.php?id=$faculty_id");
    exit();
    
} catch (PDOException $e) {
    error_log("Error deleting education: " . $e->getMessage());
    $_SESSION['error'] = "Error deleting education record.";
    header("Location: view_faculty_detail.php?id=$faculty_id");
    exit();
}
?>
