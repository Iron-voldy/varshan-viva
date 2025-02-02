<?php
session_start();
include 'connection.php';

// Ensure only admin can add doctors
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $specialty_id = $_POST['specialty_id'];
    $hospital_branch = $_POST['hospital_branch'];
    $contact_number = $_POST['contact_number'];
    $email = $_POST['email'];
    
    // Insert into users table first
    $user_insert = "INSERT INTO Users (username, password, email, phone, role_id) VALUES (?, ?, ?, ?, 2)";
    $stmt = Database::$connection->prepare($user_insert);
    $password_hash = password_hash('doctor123', PASSWORD_DEFAULT); // Default password
    $stmt->bind_param("ssss", $email, $password_hash, $email, $contact_number);
    $stmt->execute();
    $user_id = $stmt->insert_id;

    // Insert into doctors table
    $doctor_insert = "INSERT INTO Doctors (user_id, first_name, last_name, specialty_id, hospital_branch, contact_number) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = Database::$connection->prepare($doctor_insert);
    $stmt->bind_param("ississ", $user_id, $first_name, $last_name, $specialty_id, $hospital_branch, $contact_number);
    $stmt->execute();

    header("Location: admin-dashboard.php");
    exit();
}
?>
