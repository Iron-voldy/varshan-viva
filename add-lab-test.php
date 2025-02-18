<?php
session_start();
include 'connection.php';

// Ensure doctor is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch the corresponding `doctor_id` using the logged-in `user_id`
$doctor_query = "SELECT doctor_id FROM Doctors WHERE user_id = ?";
$stmt = Database::$connection->prepare($doctor_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$doctor_result = $stmt->get_result();
$doctor = $doctor_result->fetch_assoc();

if (!$doctor) {
    die("Doctor record not found.");
}

$doctor_id = $doctor['doctor_id'];

// Fetch all patients
$patients_query = "SELECT patient_id, first_name, last_name FROM Patients";
$patients_result = Database::search($patients_query);
$patients = $patients_result->fetch_all(MYSQLI_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $patient_id = $_POST['patient_id'];
    $test_name = $_POST['test_name'];
    $prescribed_date = $_POST['prescribed_date'];
    
    // Check if optional fields exist and set defaults
    $result_date = isset($_POST['result_date']) ? $_POST['result_date'] : NULL;
    $result = isset($_POST['result']) ? $_POST['result'] : NULL;
    $status = isset($_POST['status']) ? $_POST['status'] : 'Pending';

    // Insert a new medical record for the patient if none exists
    $insert_medical_record = "INSERT INTO MedicalRecords (patient_id, doctor_id, visit_date) VALUES (?, ?, ?)";
    $stmt = Database::$connection->prepare($insert_medical_record);
    $visit_date = date('Y-m-d H:i:s'); // Set current visit date for the medical record
    $stmt->bind_param("iis", $patient_id, $doctor_id, $visit_date);
    $stmt->execute();

    // Get the record_id of the newly inserted medical record
    $record_id = Database::$connection->insert_id;

    // Insert the new lab test record into the database
    $insert_query = "INSERT INTO LabTests (doctor_id, record_id, test_name, prescribed_date, result_date, result, status) 
                     VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = Database::$connection->prepare($insert_query);
    $stmt->bind_param("iisssss", $doctor_id, $record_id, $test_name, $prescribed_date, $result_date, $result, $status);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo "Lab test record added successfully.";
        header("Location: doctor-manage-labTest.php");
        exit();
    } else {
        echo "Error adding lab test record.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Lab Test - Doctor Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; background: #f1f5f9; margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1 { color: #2563eb; }
        .form-group { margin-bottom: 15px; }
        input, textarea, select { width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px; }
        .btn { padding: 8px 12px; border: none; cursor: pointer; border-radius: 4px; text-decoration: none; display: inline-block; }
        .btn-primary { background: #2563eb; color: white; }
        .btn:hover { opacity: 0.8; }
    </style>
</head>
<body>

<div class="container">
    <h1>Add Lab Test</h1>

    <form method="POST">
        <div class="form-group">
            <label>Patient</label>
            <select name="patient_id" required>
                <option value="">Select Patient</option>
                <?php foreach ($patients as $patient): ?>
                    <option value="<?= $patient['patient_id'] ?>"><?= $patient['first_name'] . ' ' . $patient['last_name'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Test Name</label>
            <input type="text" name="test_name" required>
        </div>
        <div class="form-group">
            <label>Prescribed Date</label>
            <input type="datetime-local" name="prescribed_date" required>
        </div>
        <div class="form-group">
            <label>Result Date</label>
            <input type="datetime-local" name="result_date">
        </div>
        <div class="form-group">
            <label>Result</label>
            <textarea name="result"></textarea>
        </div>
        <div class="form-group">
            <label>Status</label>
            <select name="status" required>
                <option value="Completed">Completed</option>
                <option value="Pending">Pending</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Add Lab Test</button>
    </form>
</div>

</body>
</html>
