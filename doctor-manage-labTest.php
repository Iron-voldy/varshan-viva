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

// Fetch lab tests for this doctor
$lab_tests_query = "SELECT l.record_id, p.first_name, p.last_name, l.test_name, l.prescribed_date, l.result_date, l.result, l.status 
                    FROM LabTests l
                    JOIN MedicalRecords m ON l.record_id = m.record_id 
                    JOIN Patients p ON m.patient_id = p.patient_id
                    WHERE l.doctor_id = ?";
$stmt = Database::$connection->prepare($lab_tests_query);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$lab_tests_result = $stmt->get_result();
$lab_tests = $lab_tests_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Lab Tests - Doctor Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Same CSS as before (reused for lab test management page) */
        body { font-family: Arial, sans-serif; background: #f1f5f9; margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1 { color: #2563eb; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #2563eb; color: white; }
        .btn { padding: 8px 12px; border: none; cursor: pointer; border-radius: 4px; text-decoration: none; display: inline-block; }
        .btn-primary { background: #2563eb; color: white; }
        .btn-danger { background: red; color: white; }
        .btn:hover { opacity: 0.8; }
        form { margin-top: 20px; }
        input, textarea, select { width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px; }
        .form-group { margin-bottom: 15px; }

        
        :root {
            --primary: #2563eb;
            --secondary: #3b82f6;
            --accent: #10b981;
            --light: #f8fafc;
            --dark: #1e293b;
            --danger: #ef4444;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        body {
            background: #f1f5f9;
        }

        .dashboard-container {
            display: grid;
            grid-template-columns: 280px 1fr;
            min-height: 100vh;
            gap: 2rem;
        }

        /* ======= Sidebar ======= */
        .sidebar {
            background: white;
            padding: 2rem 1.5rem;
            box-shadow: 2px 0 15px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            height: 100vh;
        }

        .sidebar-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .sidebar-header img {
            width: 140px;
            margin-bottom: 1rem;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            margin: 0.5rem 0;
            border-radius: 10px;
            color: #64748b;
            transition: all 0.3s;
            text-decoration: none;
        }

        .nav-item:hover {
            background: #e0f2fe;
            color: var(--primary);
        }


    </style>
</head>
<body>

<div class="dashboard-container">
    <!-- Sidebar (same as before) -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="hospital-logo.png" alt="CareCompass">
            <h3>Doctor Dashboard</h3>
        </div>
        
        <nav>
            <a href="#" class="nav-item active">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="./doctor-appoinment.php" class="nav-item">
                <i class="fas fa-calendar-check"></i>
                <span>Appointments</span>
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-user-injured"></i>
                <span>Patients</span>
            </a>
            <a href="doctor-manage-labTest.php" class="nav-item">
                <i class="fas fa-flask"></i>
                <span>Lab Tests</span>
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-file-medical"></i>
                <span>Medical Records</span>
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-user-edit"></i>
                <span>Profile Settings</span>
            </a>
        </nav>
    </aside>

    <div class="container">
        <h1>Manage Lab Tests</h1>

        <h3>Order New Lab Test</h3>
        <form method="POST" action="add-lab-test.php">
            <input type="hidden" name="doctor_id" value="<?= $doctor_id ?>">
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
            <button type="submit" class="btn btn-primary">Order Test</button>
        </form>

        <h3>Existing Lab Tests</h3>
        <table>
            <tr>
                <th>Patient</th>
                <th>Test Name</th>
                <th>Prescribed Date</th>
                <th>Result Date</th>
                <th>Result</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($lab_tests as $lab_test): ?>
            <tr>
                <td><?= htmlspecialchars($lab_test['first_name'] . ' ' . $lab_test['last_name']) ?></td>
                <td><?= htmlspecialchars($lab_test['test_name']) ?></td>
                <td><?= $lab_test['prescribed_date'] ? date('Y-m-d H:i', strtotime($lab_test['prescribed_date'])) : 'N/A' ?></td>
                <td><?= $lab_test['result_date'] ? date('Y-m-d H:i', strtotime($lab_test['result_date'])) : 'N/A' ?></td>
                <td><?= htmlspecialchars($lab_test['result']) ?></td>
                <td><?= htmlspecialchars($lab_test['status']) ?></td>
                <td>
                    <a href="edit-lab-test.php?id=<?= $lab_test['record_id'] ?>" class="btn btn-primary">Edit</a>
                    <a href="delete-lab-test.php?id=<?= $lab_test['record_id'] ?>" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

</body>
</html>
