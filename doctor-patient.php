<?php
session_start();
include 'connection.php';

// Ensure database connection is established
Database::setUpConnection();

// Check if user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) { 
    header("Location: login.php");
    exit();
}

// Get logged-in doctor ID
$user_id = $_SESSION['user_id'];

// Fetch doctor details
$doctor_query = "SELECT doctor_id, first_name, last_name FROM Doctors WHERE user_id = ?";
$stmt = Database::$connection->prepare($doctor_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$doctor_result = $stmt->get_result();
$doctor_data = $doctor_result->fetch_assoc();

if (!$doctor_data) {
    echo "<script>alert('No doctor profile found. Please contact the admin.'); window.location.href = 'login.php';</script>";
    exit();
}

$doctor_id = $doctor_data['doctor_id'];
$doctor_name = htmlspecialchars($doctor_data['first_name'] . " " . $doctor_data['last_name']);

// Fetch all patients assigned to the doctor
$patients_query = "SELECT DISTINCT p.patient_id, p.first_name, p.last_name, p.date_of_birth, p.gender, p.blood_type, 
                          (SELECT MAX(visit_date) FROM MedicalRecords WHERE patient_id = p.patient_id) AS last_visit
                   FROM Patients p
                   JOIN MedicalRecords mr ON p.patient_id = mr.patient_id
                   WHERE mr.doctor_id = ?
                   ORDER BY last_visit DESC";
$stmt = Database::$connection->prepare($patients_query);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$patients_result = $stmt->get_result();
$patients = $patients_result->fetch_all(MYSQLI_ASSOC);

// Fetch first patient details if available
$selected_patient = $patients[0] ?? null;
$patient_id = $selected_patient['patient_id'] ?? null;

// Fetch medical history for the selected patient
$history_query = "SELECT visit_date, diagnosis, treatment_plan FROM MedicalRecords WHERE patient_id = ? ORDER BY visit_date DESC";
$stmt = Database::$connection->prepare($history_query);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$history_result = $stmt->get_result();
$medical_history = $history_result->fetch_all(MYSQLI_ASSOC);

// Fetch upcoming appointments for the selected patient
$upcoming_query = "SELECT appointment_date, appointment_reason, status_id 
                   FROM Appointments 
                   WHERE patient_id = ? AND appointment_date > NOW() 
                   ORDER BY appointment_date ASC";
$stmt = Database::$connection->prepare($upcoming_query);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$upcoming_result = $stmt->get_result();
$upcoming_appointments = $upcoming_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patients - CareCompass</title>
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

        .patient-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .search-bar {
            width: 300px;
            padding: 0.8rem 1.5rem;
            border: 1px solid #cbd5e1;
            border-radius: 30px;
            background: white;
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .search-input {
            border: none;
            outline: none;
            width: 100%;
        }

        .patient-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .patient-list {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            height: 75vh;
            overflow-y: auto;
        }

        .patient-list-item {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            padding: 1.5rem;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .patient-list-item:hover {
            background: #f8fafc;
        }

        .patient-list-item.active {
            background: #e0f2fe;
            border-left: 4px solid var(--primary);
        }

        .patient-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #e0f2fe;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: var(--primary);
        }

        .patient-details-sidebar {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .medical-history {
            margin-top: 2rem;
        }

        .history-item {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .medical-tag {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.9rem;
            background: #e2e8f0;
            margin: 0.25rem;
        }

        .vital-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin: 1.5rem 0;
        }

        .vital-card {
            text-align: center;
            padding: 1rem;
            border-radius: 10px;
            background: #f8fafc;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
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

        <!-- Main Content -->
        <main class="main-content">
            <h1>Welcome, Dr. <?= $doctor_name ?></h1>
            <div class="patient-header">
                <h2>Patient Management</h2>
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" id="search-patient" class="search-input" placeholder="Search patients...">
                </div>
            </div>

            <div class="patient-grid">
                <div class="patient-list">
                    <?php foreach ($patients as $index => $patient) : ?>
                        <div class="patient-list-item <?= $index === 0 ? 'active' : '' ?>" onclick="loadPatient(<?= $patient['patient_id'] ?>)">
                            <div class="patient-avatar"><?= strtoupper(substr($patient['first_name'], 0, 1)) . strtoupper(substr($patient['last_name'], 0, 1)) ?></div>
                            <div class="patient-info">
                                <h3><?= htmlspecialchars($patient['first_name'] . " " . $patient['last_name']) ?></h3>
                                <p>Last Visit: <?= $patient['last_visit'] ?? 'N/A' ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="patient-details-sidebar" id="patient-details">
                    <?php if ($selected_patient) : ?>
                        <h2><?= htmlspecialchars($selected_patient['first_name'] . " " . $selected_patient['last_name']) ?></h2>
                        <p>Patient ID: #PAT-<?= $selected_patient['patient_id'] ?></p>
                        <p>Blood Type: <?= $selected_patient['blood_type'] ?></p>

                        <h3>Medical History</h3>
                        <?php if (!empty($medical_history)) : ?>
                            <?php foreach ($medical_history as $history) : ?>
                                <div class="history-item">
                                    <p><?= date("M j, Y", strtotime($history['visit_date'])) ?> - <?= $history['diagnosis'] ?></p>
                                    <p><strong>Treatment:</strong> <?= $history['treatment_plan'] ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <p>No medical history available.</p>
                        <?php endif; ?>

                        <h3>Upcoming Appointments</h3>
                        <?php if (!empty($upcoming_appointments)) : ?>
                            <?php foreach ($upcoming_appointments as $appointment) : ?>
                                <div class="appointment-card">
                                    <p><?= date("M j, Y h:i A", strtotime($appointment['appointment_date'])) ?> - <?= $appointment['appointment_reason'] ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <p>No upcoming appointments.</p>
                        <?php endif; ?>
                    <?php else : ?>
                        <p>No patient selected.</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        function loadPatient(patientId) {
            fetch(`get-patient-data.php?patient_id=${patientId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }

                    document.getElementById('patient-details').innerHTML = `
                        <h2>${data.first_name} ${data.last_name}</h2>
                        <p>Patient ID: #PAT-${data.patient_id}</p>
                        <p>Blood Type: ${data.blood_type}</p>
                        <h3>Medical History</h3>
                        ${data.history.length ? data.history.map(h => `
                            <div class="history-item">
                                <p>${h.visit_date} - ${h.diagnosis}</p>
                                <p><strong>Treatment:</strong> ${h.treatment_plan}</p>
                            </div>
                        `).join('') : '<p>No medical history available.</p>'}
                        <h3>Upcoming Appointments</h3>
                        ${data.upcoming.length ? data.upcoming.map(a => `
                            <div class="appointment-card">
                                <p>${a.appointment_date} - ${a.appointment_reason}</p>
                            </div>
                        `).join('') : '<p>No upcoming appointments.</p>'}
                    `;
                })
                .catch(error => console.error("Error loading patient data:", error));
        }
    </script>
</body>
</html>