<?php
require_once 'database.php';
requireLogin();

$database = new Database();
$conn = $database->connect();

$research_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$faculty_id = isset($_GET['faculty_id']) ? intval($_GET['faculty_id']) : 0;
$return_page = isset($_GET['return']) ? $_GET['return'] : 'faculty_detail';

if ($research_id <= 0) {
    $_SESSION['error'] = "Invalid research ID.";
    header("Location: view_faculty.php");
    exit();
}

try {
    // Get the research record to verify faculty_id and permissions
    $stmt = $conn->prepare("SELECT faculty_id FROM research WHERE research_id = ?");
    $stmt->execute([$research_id]);
    $research = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$research) {
        $_SESSION['error'] = "Research record not found.";
        if ($faculty_id > 0) {
            header("Location: view_faculty_detail.php?id=$faculty_id");
        } else {
            header("Location: view_research.php");
        }
        exit();
    }
    
    // Use the faculty_id from the research record if not provided
    if ($faculty_id <= 0) {
        $faculty_id = $research['faculty_id'];
    }
    
    // Verify the faculty_id matches
    if ($research['faculty_id'] != $faculty_id) {
        $_SESSION['error'] = "Invalid research record.";
        header("Location: view_faculty.php");
        exit();
    }
    
    // Permission check
    if (!canEditFaculty($faculty_id)) {
        $_SESSION['error'] = "You don't have permission to delete this research record.";
        header("Location: view_faculty_detail.php?id=$faculty_id");
        exit();
    }
    
    // Delete the research record
    $stmt = $conn->prepare("DELETE FROM research WHERE research_id = ?");
    $stmt->execute([$research_id]);
    
    $_SESSION['success'] = "Research record deleted successfully.";
    
    // Redirect based on return parameter
    if ($return_page === 'view_research') {
        header("Location: view_research.php");
    } else {
        header("Location: view_faculty_detail.php?id=$faculty_id");
    }
    exit();
    
} catch (PDOException $e) {
    error_log("Error deleting research: " . $e->getMessage());
    $_SESSION['error'] = "Error deleting research record.";
    
    if ($faculty_id > 0) {
        header("Location: view_faculty_detail.php?id=$faculty_id");
    } else {
        header("Location: view_research.php");
    }
    exit();
}
?>
