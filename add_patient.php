<?php
session_start();
require 'connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate and sanitize inputs
    $firstName = Database::$connection->real_escape_string($_POST['first_name']);
    $lastName = Database::$connection->real_escape_string($_POST['last_name']);
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $bloodType = $_POST['blood_type'];
    $email = Database::$connection->real_escape_string($_POST['email']);
    $phone = Database::$connection->real_escape_string($_POST['phone']);
    $address = Database::$connection->real_escape_string($_POST['address']);

    // Create user first
    $password = password_hash('defaultpassword', PASSWORD_DEFAULT);
    Database::iud("
        INSERT INTO Users (username, password, email, phone, role_id, status)
        VALUES ('$email', '$password', '$email', '$phone', 3, 'Active')
    ");
    
    $user_id = Database::$connection->insert_id;
    
    // Create patient
    Database::iud("
        INSERT INTO Patients (user_id, first_name, last_name, date_of_birth, gender, blood_type, address)
        VALUES ($user_id, '$firstName', '$lastName', '$dob', '$gender', '$bloodType', '$address')
    ");

    $_SESSION['message'] = "Patient added successfully";
    header("Location: patients.php");
    exit();
}