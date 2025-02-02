<?php
session_start();
require 'connection.php';

if (isset($_GET['id'])) {
    $patient_id = $_GET['id'];
    
    // Get user_id first
    $result = Database::search("SELECT user_id FROM Patients WHERE patient_id = $patient_id");
    $user_id = $result->fetch_assoc()['user_id'];
    
    // Delete patient
    Database::iud("DELETE FROM Patients WHERE patient_id = $patient_id");
    
    // Delete user
    Database::iud("DELETE FROM Users WHERE user_id = $user_id");

    $_SESSION['message'] = "Patient deleted successfully";
    header("Location: patients.php");
    exit();
}