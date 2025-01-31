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

// ✅ Ensure `$doctor_name` is properly set
$doctor_id = $doctor_data['doctor_id'];
$doctor_name = isset($doctor_data['first_name']) && isset($doctor_data['last_name'])
    ? $doctor_data['first_name'] . " " . $doctor_data['last_name']
    : "Unknown Doctor"; // Prevents errors if data is missing


$doctor_id = $doctor_data['doctor_id'];

// Fetch today's date
$todays_date = date("Y-m-d");

// Fetch today's appointments
$todays_appointments_query = "SELECT a.*, p.first_name AS patient_first, p.last_name AS patient_last, p.gender, p.date_of_birth, s.status_name
                               FROM Appointments a
                               JOIN Patients p ON a.patient_id = p.patient_id
                               JOIN AppointmentStatuses s ON a.status_id = s.status_id
                               WHERE a.doctor_id = '$doctor_id' AND DATE(a.appointment_date) = '$todays_date'
                               ORDER BY a.appointment_date ASC";
$todays_appointments_result = Database::search($todays_appointments_query);
$todays_appointments = [];
while ($row = $todays_appointments_result->fetch_assoc()) {
    $todays_appointments[] = $row;
}

// Fetch upcoming appointments
$upcoming_appointments_query = "SELECT a.*, p.first_name AS patient_first, p.last_name AS patient_last, p.gender, p.date_of_birth, s.status_name
                                FROM Appointments a
                                JOIN Patients p ON a.patient_id = p.patient_id
                                JOIN AppointmentStatuses s ON a.status_id = s.status_id
                                WHERE a.doctor_id = '$doctor_id' AND a.appointment_date > NOW()
                                ORDER BY a.appointment_date ASC";
$upcoming_appointments_result = Database::search($upcoming_appointments_query);
$upcoming_appointments = [];
while ($row = $upcoming_appointments_result->fetch_assoc()) {
    $upcoming_appointments[] = $row;
}

// Fetch appointment history
$history_query = "SELECT a.*, p.first_name AS patient_first, p.last_name AS patient_last, p.gender, p.date_of_birth, s.status_name
                  FROM Appointments a
                  JOIN Patients p ON a.patient_id = p.patient_id
                  JOIN AppointmentStatuses s ON a.status_id = s.status_id
                  WHERE a.doctor_id = '$doctor_id' AND a.appointment_date < NOW()
                  ORDER BY a.appointment_date DESC";
$history_result = Database::search($history_query);
$history_appointments = [];
while ($row = $history_result->fetch_assoc()) {
    $history_appointments[] = $row;
}

// Fetch distinct dates for calendar view
$calendar_query = "SELECT DISTINCT DATE(appointment_date) AS appointment_day FROM Appointments WHERE doctor_id = '$doctor_id' ORDER BY appointment_day ASC";
$calendar_result = Database::search($calendar_query);
$calendar_dates = [];
while ($row = $calendar_result->fetch_assoc()) {
    $calendar_dates[] = $row['appointment_day'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments - CareCompass</title>
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

        .appointments-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .date-filter {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .date-picker {
            padding: 0.5rem 1rem;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: white;
        }

        .appointment-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .tab-button {
            padding: 1rem 2rem;
            border: none;
            background: none;
            cursor: pointer;
            color: #64748b;
            position: relative;
            transition: all 0.3s;
        }

        .tab-button.active {
            color: var(--primary);
            font-weight: 600;
        }

        .tab-button.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--primary);
        }

        .appointment-list {
            display: grid;
            gap: 1.5rem;
        }

        .appointment-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            display: grid;
            grid-template-columns: 1fr auto;
            align-items: center;
        }

        .patient-details {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .patient-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #e0f2fe;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--primary);
        }

        .appointment-time {
            text-align: right;
        }

        .appointment-meta {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }

        .badge-primary { background: #dbeafe; color: #1d4ed8; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef3c7; color: #b45309; }
        .badge-danger { background: #fee2e2; color: #b91c1c; }

        .calendar-view {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-top: 2rem;
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.5rem;
        }

        .calendar-day {
            text-align: center;
            padding: 1rem;
            border-radius: 8px;
            background: #f8fafc;
        }

        .calendar-day.active {
            background: var(--primary);
            color: white;
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
                <a href="./doctor-patient.php" class="nav-item">
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

    <!-- Tabs for Appointments -->
    <div class="appointment-tabs">
        <button class="tab-button active" data-tab="today-tab">Today's Schedule (<?= count($todays_appointments) ?>)</button>
        <button class="tab-button" data-tab="upcoming-tab">Upcoming (<?= count($upcoming_appointments) ?>)</button>
        <button class="tab-button" data-tab="history-tab">History (<?= count($history_appointments) ?>)</button>
    </div>

    <!-- Today's Appointments -->
    <div class="appointment-list tab-content" id="today-tab">
        <?php if (empty($todays_appointments)) : ?>
            <p>No appointments for today.</p>
        <?php else : ?>
            <?php foreach ($todays_appointments as $appointment) : ?>
                <div class="appointment-card">
                    <div class="patient-details">
                        <div class="patient-avatar">
                            <i class="fas fa-user-injured"></i>
                        </div>
                        <div>
                            <h3><?= htmlspecialchars($appointment['patient_first'] . " " . $appointment['patient_last']) ?></h3>
                            <p><?= htmlspecialchars($appointment['gender']) ?> • <?= date("Y") - date("Y", strtotime($appointment['date_of_birth'])) ?> years old • #PAT-<?= $appointment['patient_id'] ?></p>
                            <div class="appointment-meta">
                                <span class="badge badge-primary"><?= date("h:i A", strtotime($appointment['appointment_date'])) ?></span>
                                <span class="badge <?= ($appointment['status_name'] == 'Urgent') ? 'badge-danger' : 'badge-warning' ?>">
                                    <?= htmlspecialchars($appointment['status_name']) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="appointment-time">
                        <span class="badge badge-success"><?= htmlspecialchars($appointment['status_name']) ?></span>
                        <p class="text-muted"><?= rand(15, 60) ?> mins</p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Upcoming Appointments -->
    <div class="appointment-list tab-content" id="upcoming-tab" style="display: none;">
        <?php if (empty($upcoming_appointments)) : ?>
            <p>No upcoming appointments.</p>
        <?php else : ?>
            <?php foreach ($upcoming_appointments as $appointment) : ?>
                <div class="appointment-card">
                    <div class="patient-details">
                        <div class="patient-avatar">
                            <i class="fas fa-user-injured"></i>
                        </div>
                        <div>
                            <h3><?= htmlspecialchars($appointment['patient_first'] . " " . $appointment['patient_last']) ?></h3>
                            <p><?= htmlspecialchars($appointment['gender']) ?> • <?= date("Y") - date("Y", strtotime($appointment['date_of_birth'])) ?> years old • #PAT-<?= $appointment['patient_id'] ?></p>
                            <div class="appointment-meta">
                                <span class="badge badge-primary"><?= date("h:i A", strtotime($appointment['appointment_date'])) ?></span>
                                <span class="badge <?= ($appointment['status_name'] == 'Urgent') ? 'badge-danger' : 'badge-warning' ?>">
                                    <?= htmlspecialchars($appointment['status_name']) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="appointment-time">
                        <span class="badge badge-success"><?= htmlspecialchars($appointment['status_name']) ?></span>
                        <p class="text-muted"><?= rand(15, 60) ?> mins</p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Appointment History -->
    <div class="appointment-list tab-content" id="history-tab" style="display: none;">
        <?php if (empty($history_appointments)) : ?>
            <p>No past appointments.</p>
        <?php else : ?>
            <?php foreach ($history_appointments as $appointment) : ?>
                <div class="appointment-card">
                    <div class="patient-details">
                        <div class="patient-avatar">
                            <i class="fas fa-user-injured"></i>
                        </div>
                        <div>
                            <h3><?= htmlspecialchars($appointment['patient_first'] . " " . $appointment['patient_last']) ?></h3>
                            <p><?= htmlspecialchars($appointment['gender']) ?> • <?= date("Y") - date("Y", strtotime($appointment['date_of_birth'])) ?> years old • #PAT-<?= $appointment['patient_id'] ?></p>
                            <div class="appointment-meta">
                                <span class="badge badge-primary"><?= date("h:i A", strtotime($appointment['appointment_date'])) ?></span>
                                <span class="badge <?= ($appointment['status_name'] == 'Urgent') ? 'badge-danger' : 'badge-warning' ?>">
                                    <?= htmlspecialchars($appointment['status_name']) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="appointment-time">
                        <span class="badge badge-success"><?= htmlspecialchars($appointment['status_name']) ?></span>
                        <p class="text-muted"><?= rand(15, 60) ?> mins</p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Calendar View -->
    <div class="calendar-view">
        <h3>Upcoming Appointment Days</h3>
        <div class="calendar-grid">
            <?php foreach ($calendar_dates as $day) : ?>
                <div class="calendar-day"><?= date("M j", strtotime($day)) ?></div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<script>
    // Tab Switching Functionality
    document.querySelectorAll('.tab-button').forEach(button => {
        button.addEventListener('click', () => {
            // Remove active class from all buttons
            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');

            // Hide all appointment lists
            document.querySelectorAll('.tab-content').forEach(list => list.style.display = 'none');

            // Show selected tab
            document.getElementById(button.getAttribute('data-tab')).style.display = 'block';
        });
    });
</script>
</body>
</html>
