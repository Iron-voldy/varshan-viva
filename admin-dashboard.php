<?php
session_start();
include 'connection.php';

// Ensure only admin has access
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: login.php");
    exit();
}

// Get total counts for dashboard stats
$stats_query = "
    SELECT 
        (SELECT COUNT(*) FROM Patients) AS total_patients,
        (SELECT COUNT(*) FROM Doctors) AS total_doctors,
        (SELECT COUNT(*) FROM Appointments WHERE DATE(appointment_date) = CURDATE()) AS todays_appointments,
        (SELECT COUNT(*) FROM Payments WHERE payment_status = 'Pending') AS pending_payments
";
$stats_result = Database::search($stats_query);
$stats = $stats_result->fetch_assoc();

// Fetch recent doctors
$doctors_query = "
    SELECT d.first_name, d.last_name, s.specialty_name, d.hospital_branch
    FROM Doctors d
    JOIN Specialties s ON d.specialty_id = s.specialty_id
    ORDER BY d.doctor_id DESC LIMIT 5";
$doctors_result = Database::search($doctors_query);

// Fetch recent patients
$patients_query = "
    SELECT p.first_name, p.last_name, p.date_of_birth, p.gender, 
    (SELECT MAX(visit_date) FROM MedicalRecords WHERE patient_id = p.patient_id) AS last_visit
    FROM Patients p ORDER BY p.patient_id DESC LIMIT 5";
$patients_result = Database::search($patients_query);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CareCompass</title>
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

        /* Data Tables */
        .data-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        th {
            background: #f8fafc;
            font-weight: 600;
        }

        .action-btns {
            display: flex;
            gap: 0.5rem;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background: white;
            width: 90%;
            max-width: 600px;
            padding: 2rem;
            border-radius: 12px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-top: 1.5rem;
        }

        @media (max-width: 768px) {
            .dashboard-container {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            table thead {
                display: none;
            }
            
            td {
                display: block;
                text-align: right;
            }
            
            td::before {
                content: attr(data-label);
                float: left;
                font-weight: 600;
                color: var(--primary);
            }
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
                <a href="./admin-payments.php" class="nav-item">
                    <i class="fas fa-credit-card"></i>
                    <span>Payments</span>
                </a>
                <a href="./feedback.php" class="nav-item">
                    <i class="fas fa-comments"></i>
                    <span>Feedback</span>
                </a>
                <a href="./4o4-error.html" class="nav-item">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </nav>
        </aside>

         <!-- Main Content -->
         <main class="main-content">
            <!-- Quick Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Patients</h3>
                    <p class="stat-number"><?= $stats['total_patients'] ?></p>
                </div>
                <div class="stat-card">
                    <h3>Active Doctors</h3>
                    <p class="stat-number"><?= $stats['total_doctors'] ?></p>
                </div>
                <div class="stat-card">
                    <h3>Today's Appointments</h3>
                    <p class="stat-number"><?= $stats['todays_appointments'] ?></p>
                </div>
                <div class="stat-card">
                    <h3>Pending Payments</h3>
                    <p class="stat-number"><?= $stats['pending_payments'] ?></p>
                </div>
            </div>

            <!-- Doctors Table -->
            <div class="data-table">
                <h3>Recent Doctors</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Specialty</th>
                            <th>Hospital Branch</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($doctor = $doctors_result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $doctor['first_name'] . " " . $doctor['last_name'] ?></td>
                                <td><?= $doctor['specialty_name'] ?></td>
                                <td><?= $doctor['hospital_branch'] ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Patients Table -->
            <div class="data-table">
                <h3>Recent Patients</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Age</th>
                            <th>Gender</th>
                            <th>Last Visit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($patient = $patients_result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $patient['first_name'] . " " . $patient['last_name'] ?></td>
                                <td><?= date("Y") - date("Y", strtotime($patient['date_of_birth'])) ?></td>
                                <td><?= $patient['gender'] ?></td>
                                <td><?= $patient['last_visit'] ?? 'N/A' ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        // Modal Handling
        function openDoctorModal() {
            document.getElementById('doctorModal').style.display = 'flex';
        }

        function closeDoctorModal() {
            document.getElementById('doctorModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>