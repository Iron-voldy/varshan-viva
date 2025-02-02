<?php
session_start();
include 'connection.php';

// Ensure only admin has access
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: login.php");
    exit();
}

// Handle appointment deletion
if (isset($_GET['delete'])) {
    $appointment_id = $_GET['delete'];
    Database::iud("DELETE FROM Appointments WHERE appointment_id = $appointment_id");
    $_SESSION['message'] = "Appointment deleted successfully!";
    header("Location: admin-appointment.php");
    exit();
}

// Handle appointment update (e.g., changing status)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_appointment'])) {
    $appointment_id = $_POST['appointment_id'];
    $status_id = $_POST['status_id'];

    Database::iud("UPDATE Appointments SET status_id = $status_id WHERE appointment_id = $appointment_id");

    $_SESSION['message'] = "Appointment updated successfully!";
    header("Location: admin-appointment.php");
    exit();
}

// Fetch all appointments
$appointments = [];
$result = Database::search("
    SELECT a.*, p.first_name, p.last_name, d.first_name AS doctor_fname, d.last_name AS doctor_lname, aps.status_name 
    FROM Appointments a
    JOIN Patients p ON a.patient_id = p.patient_id
    JOIN Doctors d ON a.doctor_id = d.doctor_id
    JOIN AppointmentStatuses aps ON a.status_id = aps.status_id
");
while ($row = $result->fetch_assoc()) {
    $appointments[] = $row;
}

// Fetch appointment statuses for the dropdown
$statuses = [];
$status_result = Database::search("SELECT * FROM AppointmentStatuses");
while ($row = $status_result->fetch_assoc()) {
    $statuses[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Appointments</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f8f9fa; padding: 20px; }
        .container { max-width: 1000px; margin: auto; background: white; padding: 20px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
        .btn { padding: 10px; border: none; cursor: pointer; }
        .btn-primary { background: #2563eb; color: white; }
        .btn-danger { background: #ef4444; color: white; }
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table th, .table td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; }
        .modal-content { background: white; padding: 20px; width: 400px; }

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
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3>Admin Dashboard</h3>
            </div>
            <nav>
                <a href="./admin-dashboard.php" class="nav-item active">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="./manage-doctors.php" class="nav-item">
                    <i class="fas fa-user-md"></i>
                    <span>Manage Doctors</span>
                </a>
                <a href="./manage-patient.php" class="nav-item">
                    <i class="fas fa-user-injured"></i>
                    <span>Manage Patients</span>
                </a>
                <a href="./admin-appointment.php" class="nav-item">
                    <i class="fas fa-calendar-check"></i>
                    <span>Appointments</span>
                </a>
                <a href="./admin-appointment.php" class="nav-item">
                    <i class="fas fa-credit-card"></i>
                    <span>Payments</span>
                </a>
                <a href="./admin-feedback.php" class="nav-item">
                    <i class="fas fa-comments"></i>
                    <span>Feedback</span>
                </a>
                <a href="./4o4-error.html" class="nav-item">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </nav>
        </aside>

    <div class="container">
        <h2>Manage Appointments</h2>
        <?php if (isset($_SESSION['message'])): ?>
            <p style="color: green;"><?= $_SESSION['message']; unset($_SESSION['message']); ?></p>
        <?php endif; ?>

        <table class="table">
            <thead>
                <tr>
                    <th>Patient Name</th>
                    <th>Doctor</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $appointment): ?>
                <tr>
                    <td><?= $appointment['first_name'] . " " . $appointment['last_name'] ?></td>
                    <td>Dr. <?= $appointment['doctor_fname'] . " " . $appointment['doctor_lname'] ?></td>
                    <td><?= date('Y-m-d', strtotime($appointment['appointment_date'])) ?></td>
                    <td><?= date('H:i A', strtotime($appointment['appointment_date'])) ?></td>
                    <td><?= $appointment['status_name'] ?></td>
                    <td>
                        <button class="btn btn-primary" onclick="openEditModal(
                            <?= $appointment['appointment_id'] ?>, 
                            '<?= $appointment['status_id'] ?>'
                        )">Edit</button>
                        <a href="?delete=<?= $appointment['appointment_id'] ?>" class="btn btn-danger">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Edit Appointment Modal -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <h2>Edit Appointment</h2>
            <form method="POST">
                <input type="hidden" id="appointment_id" name="appointment_id">
                <label for="status">Status:</label>
                <select name="status_id" id="status_id">
                    <?php foreach ($statuses as $status): ?>
                        <option value="<?= $status['status_id'] ?>"><?= $status['status_name'] ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="update_appointment" class="btn btn-primary">Update</button>
                <button type="button" onclick="document.getElementById('editModal').style.display='none'" class="btn btn-danger">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(appointmentId, statusId) {
            document.getElementById('appointment_id').value = appointmentId;
            document.getElementById('status_id').value = statusId;
            document.getElementById('editModal').style.display = 'flex';
        }
    </script>
</body>
</html>
