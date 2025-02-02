<?php
session_start();
require 'connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $patient_id = $_POST['patient_id'];
    $firstName = Database::$connection->real_escape_string($_POST['first_name']);
    $lastName = Database::$connection->real_escape_string($_POST['last_name']);
    // Add other fields

    // Update patient
    Database::iud("
        UPDATE Patients SET
        first_name = '$firstName',
        last_name = '$lastName'
        WHERE patient_id = $patient_id
    ");

    $_SESSION['message'] = "Patient updated successfully";
    header("Location: patients.php");
    exit();
}