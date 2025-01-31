<?php
session_start();
include 'connection.php';

// Ensure database connection is established
Database::setUpConnection();

// Check if user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) { // Assuming role_id 2 is for doctors
    header("Location: login.php");
    exit();
}

// Get logged-in doctor ID
$user_id = $_SESSION['user_id'];

// Fetch doctor details
$doctor_query = "SELECT doctor_id, first_name, last_name FROM Doctors WHERE user_id = '$user_id'";
$doctor_result = Database::search($doctor_query);
$doctor_data = $doctor_result->fetch_assoc();

if (!$doctor_data) {
    echo "<script>alert('No doctor profile found. Please contact the admin.'); window.location.href = 'login.php';</script>";
    exit();
}

$doctor_id = $doctor_data['doctor_id'];
$doctor_name = $doctor_data['first_name'] . " " . $doctor_data['last_name'];

// Fetch today's appointments
$todays_date = date("Y-m-d");
$appointments_query = "SELECT a.*, p.first_name AS patient_first, p.last_name AS patient_last, p.gender, p.date_of_birth, s.status_name
                       FROM appointments a
                       JOIN Patients p ON a.patient_id = p.patient_id
                       JOIN AppointmentStatuses s ON a.status_id = s.status_id
                       WHERE a.doctor_id = '$doctor_id' AND DATE(a.appointment_date) = '$todays_date'
                       ORDER BY a.appointment_date ASC";
$appointments_result = Database::search($appointments_query);
$appointments = [];
while ($row = $appointments_result->fetch_assoc()) {
    $appointments[] = $row;
}

// Fetch recent patients
$recent_patients_query = "SELECT DISTINCT p.patient_id, p.first_name, p.last_name, p.date_of_birth, p.gender, MAX(mr.visit_date) AS last_visit
                          FROM MedicalRecords mr
                          JOIN Patients p ON mr.patient_id = p.patient_id
                          WHERE mr.doctor_id = '$doctor_id'
                          GROUP BY p.patient_id
                          ORDER BY last_visit DESC LIMIT 5";
$recent_patients_result = Database::search($recent_patients_query);
$recent_patients = [];
while ($row = $recent_patients_result->fetch_assoc()) {
    $recent_patients[] = $row;
}

// Fetch pending lab results
$lab_tests_query = "SELECT lt.*, p.first_name AS patient_first, p.last_name AS patient_last, lt.prescribed_date
                    FROM LabTests lt
                    JOIN MedicalRecords mr ON lt.record_id = mr.record_id
                    JOIN Patients p ON mr.patient_id = p.patient_id
                    WHERE mr.doctor_id = '$doctor_id' AND lt.status = 'Pending'
                    ORDER BY lt.prescribed_date DESC";
$lab_tests_result = Database::search($lab_tests_query);
$pending_lab_tests = [];
while ($row = $lab_tests_result->fetch_assoc()) {
    $pending_lab_tests[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - CareCompass</title>
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

        /* ======= Main Content ======= */
        .main-content {
            padding: 2rem 2rem 4rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        /* Appointments Timeline */
        .timeline {
            position: relative;
            padding-left: 2rem;
            border-left: 3px solid var(--primary);
            margin-left: 1rem;
        }

        .appointment-card {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
            position: relative;
        }

        .appointment-card::before {
            content: '';
            position: absolute;
            left: -2.3rem;
            top: 1.5rem;
            width: 16px;
            height: 16px;
            background: var(--primary);
            border-radius: 50%;
        }

        .patient-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .patient-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--light);
            display: grid;
            place-items: center;
        }

        .status-badge {
            padding: 0.3rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            display: inline-block;
        }

        .status-scheduled { background: #dbeafe; color: #1d4ed8; }
        .status-completed { background: #dcfce7; color: #166534; }

        /* Patients List */
        .patients-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .patient-card {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        /* Lab Results */
        .lab-results {
            margin-top: 2rem;
        }

        .lab-card {
            background: white;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            border-left: 4px solid var(--accent);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        @media (max-width: 768px) {
            .dashboard-container {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
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

        <!-- Main Content -->
        <main class="main-content">
            <h1>Welcome, Dr. <?= htmlspecialchars($doctor_name) ?></h1>
            <p>Today's Schedule: <?= count($appointments) ?> Appointments</p>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Today's Appointments</h3>
                    <p class="stat-number"><?= count($appointments) ?></p>
                </div>
                <div class="stat-card">
                    <h3>Pending Lab Results</h3>
                    <p class="stat-number"><?= count($pending_lab_tests) ?></p>
                </div>
                <div class="stat-card">
                    <h3>Active Patients</h3>
                    <p class="stat-number"><?= count($recent_patients) ?></p>
                </div>
            </div>

            <h2>Upcoming Appointments</h2>
            <div class="timeline">
                <?php if (empty($appointments)) : ?>
                    <p>No appointments for today.</p>
                <?php else : ?>
                    <?php foreach ($appointments as $appointment) : ?>
                        <div class="appointment-card">
                            <div class="patient-info">
                                <div class="patient-avatar">
                                    <i class="fas fa-user-injured"></i>
                                </div>
                                <div>
                                    <h3><?= htmlspecialchars($appointment['patient_first'] . " " . $appointment['patient_last']) ?></h3>
                                    <p><?= htmlspecialchars($appointment['gender']) ?> | DOB: <?= date("F j, Y", strtotime($appointment['date_of_birth'])) ?></p>
                                </div>
                            </div>
                            <div class="appointment-details">
                                <p><i class="fas fa-clock"></i> <?= date("h:i A", strtotime($appointment['appointment_date'])) ?></p>
                                <p class="appointment-reason"><?= htmlspecialchars($appointment['appointment_reason']) ?></p>
                                <span class="status-badge <?= ($appointment['status_name'] == 'Scheduled') ? 'status-scheduled' : 'status-completed' ?>">
                                    <?= htmlspecialchars($appointment['status_name']) ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <h2>Recent Patients</h2>
            <div class="patients-grid">
                <?php if (empty($recent_patients)) : ?>
                    <p>No recent patients.</p>
                <?php else : ?>
                    <?php foreach ($recent_patients as $patient) : ?>
                        <div class="patient-card">
                            <div class="patient-info">
                                <div class="patient-avatar">
                                    <i class="fas fa-user-injured"></i>
                                </div>
                                <div>
                                    <h3><?= htmlspecialchars($patient['first_name'] . " " . $patient['last_name']) ?></h3>
                                    <p>Last Visit: <?= date("F j, Y", strtotime($patient['last_visit'])) ?></p>
                                </div>
                            </div>
                            <div class="patient-details">
                                <p>DOB: <?= date("F j, Y", strtotime($patient['date_of_birth'])) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <h2>Pending Lab Results</h2>
            <div class="lab-results">
                <?php if (empty($pending_lab_tests)) : ?>
                    <p>No pending lab results.</p>
                <?php else : ?>
                    <?php foreach ($pending_lab_tests as $lab_test) : ?>
                        <div class="lab-card">
                            <div class="flex justify-between items-center">
                                <div>
                                    <h4><?= htmlspecialchars($lab_test['test_name']) ?></h4>
                                    <p>Patient: <?= htmlspecialchars($lab_test['patient_first'] . " " . $lab_test['patient_last']) ?></p>
                                    <p>Ordered: <?= date("F j, Y", strtotime($lab_test['prescribed_date'])) ?></p>
                                </div>
                                <span class="status-badge status-scheduled">Pending</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>

    </div>
</body>
</html>