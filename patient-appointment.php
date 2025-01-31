<?php
session_start();
include 'connection.php';

// Ensure database connection is established
Database::setUpConnection();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    header("Location: login.php");
    exit();
}

// Get logged-in user ID
$user_id = $_SESSION['user_id'];

// Retrieve patient ID from Patients table
$patient_query = "SELECT patient_id FROM Patients WHERE user_id = '$user_id'";
$patient_result = Database::search($patient_query);
$patient_data = $patient_result->fetch_assoc();
$patient_id = $patient_data['patient_id'] ?? null;

if (!$patient_id) {
    echo "<script>alert('You need to complete your profile before booking an appointment.'); window.location.href = 'patient-profile.php';</script>";
    exit();
}

// Fetch doctors for selection
$doctor_query = "SELECT doctor_id, first_name, last_name, specialty_id FROM Doctors";
$doctor_result = Database::search($doctor_query);
$doctors = [];
while ($row = $doctor_result->fetch_assoc()) {
    $doctors[] = $row;
}

// Fetch statuses
$status_query = "SELECT status_id, status_name FROM AppointmentStatuses WHERE status_name = 'Scheduled'";
$status_result = Database::search($status_query);
$status_data = $status_result->fetch_assoc();
$scheduled_status_id = $status_data['status_id'];

// Fetch patient's booked appointments
$appointments_query = "SELECT a.*, d.first_name AS doctor_first, d.last_name AS doctor_last, s.status_name
                       FROM Appointments a
                       JOIN Doctors d ON a.doctor_id = d.doctor_id
                       JOIN AppointmentStatuses s ON a.status_id = s.status_id
                       WHERE a.patient_id = '$patient_id'
                       ORDER BY a.appointment_date DESC";
$appointments_result = Database::search($appointments_query);
$appointments = [];
while ($row = $appointments_result->fetch_assoc()) {
    $appointments[] = $row;
}

// Handle New Appointment Booking
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $doctor_id = mysqli_real_escape_string(Database::$connection, $_POST['doctor_id']);
    $appointment_date = mysqli_real_escape_string(Database::$connection, $_POST['appointment_date']);
    $appointment_reason = mysqli_real_escape_string(Database::$connection, $_POST['appointment_reason']);

    // Validate form fields
    if (!$doctor_id || !$appointment_date || !$appointment_reason) {
        echo "<script>alert('All fields are required.'); window.location.href = 'patient-appointment.php';</script>";
        exit();
    }

    // Insert new appointment
    $insert_appointment_query = "INSERT INTO Appointments (booked_by, patient_id, doctor_id, appointment_date, status_id, appointment_reason)
                                 VALUES ('$user_id', '$patient_id', '$doctor_id', '$appointment_date', '$scheduled_status_id', '$appointment_reason')";
    Database::iud($insert_appointment_query);

    echo "<script>alert('Appointment booked successfully!'); window.location.href = 'patient-appointment.php';</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - CareCompass</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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
        
        @media (max-width: 768px) {
            .dashboard-container {
                grid-template-columns: 1fr;
            }

            .sidebar {
                height: auto;
                position: relative;
            }

        }

        /* ======= Main Content ======= */
        .main-content {
            padding: 2rem 2rem 4rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Appointments List */
        .appointments-list {
            margin-bottom: 3rem;
        }

        .appointment-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .appointment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .appointment-status {
            padding: 0.3rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }

        .status-scheduled { background: #dbeafe; color: #1d4ed8; }
        .status-completed { background: #dcfce7; color: #166534; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }

        /* Booking Form */
        .booking-form {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--dark);
            font-weight: 500;
        }

        input, select, textarea {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            outline: none;
        }

        .btn {
            padding: 0.8rem 2rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: #1d4ed8;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .dashboard-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="hospital-logo.png" alt="CareCompass">
                <h3>Patient Dashboard</h3>
            </div>
            
            <nav>
                <a href="./patient-dashboard.html" class="nav-item">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="./patient-appointment.php" class="nav-item">
                    <i class="fas fa-calendar-check"></i>
                    <span>Appointments</span>
                </a>
                <a href="./patient-medicalRecord.php" class="nav-item">
                    <i class="fas fa-file-medical"></i>
                    <span>Medical Records</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-credit-card"></i>
                    <span>Payments</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-user-edit"></i>
                    <span>Profile Settings</span>
                </a>
            </nav>
        </aside>

         <!-- Main Content -->
         <main class="main-content">
            <div class="appointments-list">
                <h2>My Appointments</h2>
                <?php if (empty($appointments)) : ?>
                    <p>No appointments booked yet.</p>
                <?php else : ?>
                    <?php foreach ($appointments as $appointment) : ?>
                        <div class="appointment-card">
                            <div class="appointment-header">
                                <div>
                                    <h3>Dr. <?= htmlspecialchars($appointment['doctor_first'] . " " . $appointment['doctor_last']) ?></h3>
                                    <p><?= htmlspecialchars($appointment['appointment_reason']) ?></p>
                                </div>
                                <span class="appointment-status <?= ($appointment['status_name'] == 'Scheduled') ? 'status-scheduled' : ($appointment['status_name'] == 'Completed' ? 'status-completed' : 'status-cancelled') ?>">
                                    <?= htmlspecialchars($appointment['status_name']) ?>
                                </span>
                            </div>
                            <div class="appointment-details">
                                <p><i class="fas fa-calendar-alt"></i> <?= date("F j, Y", strtotime($appointment['appointment_date'])) ?></p>
                                <p><i class="fas fa-clock"></i> <?= date("h:i A", strtotime($appointment['appointment_date'])) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="booking-form">
                <h2>Book New Appointment</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Select Doctor</label>
                        <select name="doctor_id" required>
                            <option value="">Choose a doctor</option>
                            <?php foreach ($doctors as $doctor) : ?>
                                <option value="<?= $doctor['doctor_id'] ?>">
                                    <?= htmlspecialchars($doctor['first_name'] . " " . $doctor['last_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Appointment Date & Time</label>
                        <input type="datetime-local" name="appointment_date" required>
                    </div>

                    <div class="form-group">
                        <label>Reason for Appointment</label>
                        <textarea name="appointment_reason" rows="4" required></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-calendar-check"></i> Book Appointment
                    </button>
                </form>
            </div>
        </main>
       
    </div>
</body>
</html>