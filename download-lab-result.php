<?php
session_start();
include 'connection.php';

// Ensure database connection is established
Database::setUpConnection();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    die("Unauthorized access.");
}

// Get logged-in user ID
$user_id = $_SESSION['user_id'];

// Retrieve patient ID from Patients table
$patient_query = "SELECT patient_id FROM Patients WHERE user_id = '$user_id'";
$patient_result = Database::search($patient_query);
$patient_data = $patient_result->fetch_assoc();
$patient_id = $patient_data['patient_id'] ?? null;

if (!$patient_id) {
    die("You do not have permission to view this file.");
}

// Get lab test ID from request
if (!isset($_GET['test_id']) || empty($_GET['test_id'])) {
    die("Invalid request.");
}

$lab_test_id = mysqli_real_escape_string(Database::$connection, $_GET['test_id']);

// Fetch lab test details
$lab_test_query = "SELECT result, status FROM LabTests WHERE lab_test_id = '$lab_test_id' AND record_id IN 
                  (SELECT record_id FROM MedicalRecords WHERE patient_id = '$patient_id')";
$lab_test_result = Database::search($lab_test_query);
$lab_test_data = $lab_test_result->fetch_assoc();

if (!$lab_test_data) {
    die("No lab test result found.");
}

// Check if test is completed and result is available
if ($lab_test_data['status'] !== 'Completed' || empty($lab_test_data['result'])) {
    die("Lab test result is not yet available.");
}

// Generate a file for download (for demonstration purposes)
$result_content = "Lab Test Result\n\nTest ID: $lab_test_id\n\n" . $lab_test_data['result'];
$file_name = "Lab_Test_Result_$lab_test_id.txt";

// Set headers for download
header('Content-Type: text/plain');
header('Content-Disposition: attachment; filename="' . $file_name . '"');
header('Content-Length: ' . strlen($result_content));

// Output file content
echo $result_content;
exit();
?>
