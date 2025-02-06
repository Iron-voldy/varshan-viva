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

// Check if record ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Invalid report ID.");
}

$record_id = $_GET['id'];

// Fetch medical report for editing
$report_query = "SELECT m.record_id, m.patient_id, p.first_name, p.last_name, m.diagnosis, m.treatment_plan, 
                 m.prescribed_medications, m.follow_up_date, m.visit_date 
                 FROM MedicalRecords m 
                 JOIN Patients p ON m.patient_id = p.patient_id 
                 WHERE m.record_id = ? AND m.doctor_id = ?";

$stmt = Database::$connection->prepare($report_query);
$stmt->bind_param("ii", $record_id, $doctor_id);
$stmt->execute();
$report_result = $stmt->get_result();
$report = $report_result->fetch_assoc();

// If no record found or doctor doesn't have permission
if (!$report) {
    die("Medical report not found or unauthorized access.");
}

// Handle form submission for updating the report
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_report'])) {
    $diagnosis = mysqli_real_escape_string(Database::$connection, $_POST['diagnosis']);
    $treatment_plan = mysqli_real_escape_string(Database::$connection, $_POST['treatment_plan']);
    $prescribed_medications = mysqli_real_escape_string(Database::$connection, $_POST['prescribed_medications']);
    $follow_up_date = !empty($_POST['follow_up_date']) ? $_POST['follow_up_date'] : NULL;

    $update_query = "UPDATE MedicalRecords 
                     SET diagnosis = ?, treatment_plan = ?, prescribed_medications = ?, follow_up_date = ? 
                     WHERE record_id = ? AND doctor_id = ?";
    $stmt = Database::$connection->prepare($update_query);
    $stmt->bind_param("ssssii", $diagnosis, $treatment_plan, $prescribed_medications, $follow_up_date, $record_id, $doctor_id);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Medical report updated successfully!";
        header("Location: doctor-medical-reports.php");
        exit();
    } else {
        $error_message = "Error updating report. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Medical Report</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; background: #f1f5f9; margin: 0; padding: 20px; }
        .container { max-width: 700px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1 { color: #2563eb; }
        form { margin-top: 20px; }
        input, textarea, select { width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px; }
        .form-group { margin-bottom: 15px; }
        .btn { padding: 10px 15px; border: none; cursor: pointer; border-radius: 4px; text-decoration: none; display: inline-block; }
        .btn-primary { background: #2563eb; color: white; }
        .btn-danger { background: red; color: white; }
        .btn:hover { opacity: 0.8; }
        .success-message { color: green; font-weight: bold; }
        .error-message { color: red; font-weight: bold; }
    </style>
</head>
<body>

<div class="container">
    <h1>Edit Medical Report</h1>

    <?php if (isset($error_message)): ?>
        <p class="error-message"><?= htmlspecialchars($error_message) ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label>Patient</label>
            <input type="text" value="<?= htmlspecialchars($report['first_name'] . ' ' . $report['last_name']) ?>" disabled>
        </div>
        <div class="form-group">
            <label>Diagnosis</label>
            <textarea name="diagnosis" required><?= htmlspecialchars($report['diagnosis']) ?></textarea>
        </div>
        <div class="form-group">
            <label>Treatment Plan</label>
            <textarea name="treatment_plan" required><?= htmlspecialchars($report['treatment_plan']) ?></textarea>
        </div>
        <div class="form-group">
            <label>Prescribed Medications</label>
            <textarea name="prescribed_medications"><?= htmlspecialchars($report['prescribed_medications']) ?></textarea>
        </div>
        <div class="form-group">
            <label>Follow-Up Date</label>
            <input type="date" name="follow_up_date" value="<?= $report['follow_up_date'] ? date('Y-m-d', strtotime($report['follow_up_date'])) : '' ?>">
        </div>
        <button type="submit" name="update_report" class="btn btn-primary">Update Report</button>
        <a href="doctor-medical-reports.php" class="btn btn-danger">Cancel</a>
    </form>
</div>

</body>
</html>
