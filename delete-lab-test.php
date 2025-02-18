<?php
session_start();
include 'connection.php';

// Ensure doctor is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header("Location: login.php");
    exit();
}

// Get the lab test record_id from the URL
if (isset($_GET['id'])) {
    $record_id = $_GET['id'];

    // Delete the lab test record from the database
    $delete_query = "DELETE FROM LabTests WHERE record_id = ?";
    $stmt = Database::$connection->prepare($delete_query);
    $stmt->bind_param("i", $record_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo "Lab test record deleted successfully.";
        header("Location: doctor-manage-labTest.php");
        exit();
    } else {
        echo "Error deleting lab test record.";
    }
} else {
    echo "No lab test record specified.";
}
?>
