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

    // Fetch the lab test record from the database
    $lab_test_query = "SELECT l.record_id, p.first_name, p.last_name, l.test_name, l.prescribed_date, l.result_date, l.result, l.status 
                       FROM LabTests l
                       JOIN MedicalRecords m ON l.record_id = m.record_id 
                       JOIN Patients p ON m.patient_id = p.patient_id
                       WHERE l.record_id = ?";
    $stmt = Database::$connection->prepare($lab_test_query);
    $stmt->bind_param("i", $record_id);
    $stmt->execute();
    $lab_test_result = $stmt->get_result();

    if ($lab_test_result->num_rows > 0) {
        $lab_test = $lab_test_result->fetch_assoc();
    } else {
        die("Lab test record not found.");
    }
} else {
    die("No lab test record specified.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $test_name = $_POST['test_name'];
    $prescribed_date = $_POST['prescribed_date'];
    $result_date = $_POST['result_date'];
    $result = $_POST['result'];
    $status = $_POST['status'];

    // Update the lab test record in the database
    $update_query = "UPDATE LabTests SET test_name = ?, prescribed_date = ?, result_date = ?, result = ?, status = ? WHERE record_id = ?";
    $stmt = Database::$connection->prepare($update_query);
    $stmt->bind_param("sssssi", $test_name, $prescribed_date, $result_date, $result, $status, $record_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo "Lab test record updated successfully.";
        header("Location: doctor-manage-labTest.php");
        exit();
    } else {
        echo "Error updating lab test record.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Lab Test - Doctor Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Reuse CSS */
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
    <h1>Edit Lab Test</h1>

    <form method="POST">
        <div class="form-group">
            <label>Test Name</label>
            <input type="text" name="test_name" value="<?= htmlspecialchars($lab_test['test_name']) ?>" required>
        </div>
        <div class="form-group">
            <label>Prescribed Date</label>
            <input type="datetime-local" name="prescribed_date" value="<?= date('Y-m-d\TH:i', strtotime($lab_test['prescribed_date'])) ?>" required>
        </div>
        <div class="form-group">
            <label>Result Date</label>
            <input type="datetime-local" name="result_date" value="<?= $lab_test['result_date'] ? date('Y-m-d\TH:i', strtotime($lab_test['result_date'])) : '' ?>">
        </div>
        <div class="form-group">
            <label>Result</label>
            <textarea name="result"><?= htmlspecialchars($lab_test['result']) ?></textarea>
        </div>
        <div class="form-group">
            <label>Status</label>
            <select name="status" required>
                <option value="Completed" <?= $lab_test['status'] == 'Completed' ? 'selected' : '' ?>>Completed</option>
                <option value="Pending" <?= $lab_test['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Update Lab Test</button>
    </form>
</div>

</body>
</html>
