<?php
session_start();
include 'connection.php';

// Ensure doctor is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header("Location: login.php");
    exit();
}

// Get Doctor ID from the Doctors Table using user_id
$user_id = $_SESSION['user_id'];
$doctor_query = "SELECT doctor_id FROM Doctors WHERE user_id = ?";
$stmt = Database::$connection->prepare($doctor_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$doctor_result = $stmt->get_result();
$doctor = $doctor_result->fetch_assoc();

// If no matching doctor found, stop execution
if (!$doctor) {
    die("Error: Doctor profile not found.");
}

$doctor_id = $doctor['doctor_id']; // Now we have the correct doctor_id

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = $_POST['patient_id'];
    $diagnosis = mysqli_real_escape_string(Database::$connection, $_POST['diagnosis']);
    $treatment_plan = mysqli_real_escape_string(Database::$connection, $_POST['treatment_plan']);
    $prescribed_medications = mysqli_real_escape_string(Database::$connection, $_POST['prescribed_medications']);
    $follow_up_date = !empty($_POST['follow_up_date']) ? $_POST['follow_up_date'] : NULL;

    // Insert into MedicalRecords table
    $query = "INSERT INTO MedicalRecords (patient_id, doctor_id, visit_date, diagnosis, treatment_plan, prescribed_medications, follow_up_date) 
              VALUES (?, ?, NOW(), ?, ?, ?, ?)";
    $stmt = Database::$connection->prepare($query);
    $stmt->bind_param("iissss", $patient_id, $doctor_id, $diagnosis, $treatment_plan, $prescribed_medications, $follow_up_date);

    if ($stmt->execute()) {
        header("Location: doctor-medical-reports.php");
        exit();
    } else {
        die("Error inserting medical record: " . $stmt->error);
    }
}
?>
