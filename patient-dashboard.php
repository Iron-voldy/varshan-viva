<?php
session_start();
include 'connection.php';

// Ensure user is logged in as a patient
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch Patient Details
$patient_query = "SELECT first_name, last_name FROM Patients WHERE user_id = ?";
$stmt = Database::$connection->prepare($patient_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$patient_result = $stmt->get_result();

$patient = $patient_result->fetch_assoc() ?? ['first_name' => 'Guest', 'last_name' => ''];

// Fetch Upcoming Appointments
$appointments_query = "SELECT COUNT(*) AS total FROM Appointments WHERE patient_id = (SELECT patient_id FROM Patients WHERE user_id = ?) AND appointment_date >= NOW()";
$stmt = Database::$connection->prepare($appointments_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$appointments_result = $stmt->get_result();
$appointments = $appointments_result->fetch_assoc();

// Fetch Pending Payments
$payments_query = "SELECT SUM(amount) AS total FROM Payments WHERE user_id = ? AND payment_status = 'Pending'";
$stmt = Database::$connection->prepare($payments_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$payments_result = $stmt->get_result();
$payments = $payments_result->fetch_assoc();

// Fetch Active Prescriptions
$prescriptions_query = "SELECT COUNT(*) AS total FROM Prescriptions WHERE record_id IN (SELECT record_id FROM MedicalRecords WHERE patient_id = (SELECT patient_id FROM Patients WHERE user_id = ?))";
$stmt = Database::$connection->prepare($prescriptions_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$prescriptions_result = $stmt->get_result();
$prescriptions = $prescriptions_result->fetch_assoc();

// Fetch Available Doctors
$doctors_query = "SELECT d.first_name, d.last_name, d.hospital_branch, d.working_hours, s.specialty_name 
                  FROM Doctors d JOIN Specialties s ON d.specialty_id = s.specialty_id WHERE d.working_hours IS NOT NULL";
$doctors_result = Database::search($doctors_query);
$doctors = $doctors_result->fetch_all(MYSQLI_ASSOC);

// Fetch Emergency Contacts
$emergency_query = "SELECT * FROM EmergencyContacts";
$emergency_result = Database::search($emergency_query);
$emergency_contacts = $emergency_result->fetch_all(MYSQLI_ASSOC);

// Fetch Latest Health Updates (Using Feedbacks as News Data)
$news_query = "SELECT feedback_text, submitted_at FROM Feedbacks ORDER BY submitted_at DESC LIMIT 3";
$news_result = Database::search($news_query);
$news_updates = $news_result->fetch_all(MYSQLI_ASSOC);
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

        /* ======= Main Content ======= */
        .main-content {
            padding: 2rem 2rem 4rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Welcome Banner */
        .welcome-banner {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .welcome-text h1 {
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
        }

        /* Quick Actions Grid */
        .quick-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .quick-card {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }

        .quick-card:hover {
            transform: translateY(-3px);
        }

        /* Doctors Section */
        .doctors-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .doctor-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            position: relative;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .doctor-header {
            display: flex;
            gap: 1.5rem;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .doctor-image {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary);
        }

        .availability-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: var(--accent);
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }

        /* Emergency Section */
        .emergency-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .emergency-card {
            background: #fee2e2;
            padding: 1.5rem;
            border-radius: 15px;
            text-align: center;
        }

        .emergency-icon {
            font-size: 2rem;
            color: var(--danger);
            margin-bottom: 1rem;
        }

        /* Health News */
        .news-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .news-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }

        .news-card:hover {
            transform: translateY(-3px);
        }

        .news-image {
            height: 180px;
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .news-date {
            position: absolute;
            bottom: 0;
            left: 0;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }

        .news-content {
            padding: 1.5rem;
        }

        /* Buttons */
        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: #1d4ed8;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        @media (max-width: 768px) {
            .dashboard-container {
                grid-template-columns: 1fr;
            }

            .sidebar {
                height: auto;
                position: relative;
            }

            .welcome-banner {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
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
                <a href="#" class="nav-item">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-calendar-check"></i>
                    <span>Appointments</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-file-medical"></i>
                    <span>Medical Records</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-credit-card"></i>
                    <span>Payments</span>
                </a>
                <a href="patient-profile.php" class="nav-item">
                    <i class="fas fa-user-edit"></i>
                    <span>Profile Settings</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Welcome Banner -->
            <div class="welcome-banner">
                <div class="welcome-text">
                <?php 
                if (!$patient) {
                    echo "<h1>Welcome Back, Guest!</h1>";
                } else {
                    echo "<h1>Welcome Back, " . htmlspecialchars($patient['first_name']) . "!</h1>";
                }
                ?>                
                    <p id="last-login">Last login: Today at 09:30 AM</p>
                </div>
                <button class="btn btn-primary">
                    <i class="fas fa-plus"></i> New Appointment
                </button>
            </div>

            <!-- Quick Actions -->
            <div class="quick-grid">
                <div class="quick-card">
                    <div class="quick-icon">
                        <i class="fas fa-calendar-alt fa-2x text-primary"></i>
                    </div>
                    <div>
                        <h3>Upcoming Appointments</h3>
                        <p class="stat-number"><?= $appointments['total'] ?> Appointments</p>
                    </div>
                </div>
                <div class="quick-card">
                    <div class="quick-icon">
                        <i class="fas fa-file-invoice-dollar fa-2x text-success"></i>
                    </div>
                    <div>
                        <h3>Pending Payments</h3>
                        <p class="stat-number">$<?= $payments['total'] ?: '0.00' ?></p>
                    </div>
                </div>
                <div class="quick-card">
                    <div class="quick-icon">
                        <i class="fas fa-prescription fa-2x text-warning"></i>
                    </div>
                    <div>
                        <h3>Active Prescriptions</h3>
                        <p class="stat-number"><?= $prescriptions['total'] ?> Medications</p>
                    </div>
                </div>
            </div>

            <!-- Available Doctors -->
            <h2 class="section-title">Available Specialists</h2>
            <div class="doctors-grid">
                <!-- Doctor 1 -->
                <div class="doctors-grid">
                <?php foreach ($doctors as $doctor): ?>
                <div class="doctor-card">
                    <div class="doctor-header">
                        <div>
                            <h3>Dr. <?= htmlspecialchars($doctor['first_name']) ?> <?= htmlspecialchars($doctor['last_name']) ?></h3>
                            <p><?= htmlspecialchars($doctor['specialty_name']) ?></p>
                        </div>
                    </div>
                    <p><i class="fas fa-hospital"></i> <?= htmlspecialchars($doctor['hospital_branch']) ?></p>
                    <p><i class="fas fa-clock"></i> <?= htmlspecialchars($doctor['working_hours']) ?></p>
                </div>
                <?php endforeach; ?>
            </div>
                <!-- Add more doctor cards -->
            </div>

            <!-- Emergency Contacts -->
            <h2 class="section-title">Emergency Services</h2>
            <div class="emergency-grid">
                <div class="emergency-card">
                    <i class="fas fa-ambulance emergency-icon"></i>
                    <h4>24/7 Emergency</h4>
                    <p class="text-danger">123-456-7890</p>
                </div>
                <div class="emergency-card">
                    <i class="fas fa-first-aid emergency-icon"></i>
                    <h4>Ambulance Service</h4>
                    <p class="text-danger">111-222-3333</p>
                </div>
                <div class="emergency-card">
                    <i class="fas fa-biohazard emergency-icon"></i>
                    <h4>Poison Control</h4>
                    <p class="text-danger">999-888-7777</p>
                </div>
            </div>

            <!-- Health News -->
            <h2 class="section-title">Latest Health Updates</h2>
            <div class="news-grid">
                <div class="news-card">
                    <div class="news-image" 
                         style="background-image: url('https://images.unsplash.com/photo-1584036561566-baf8f5f1b144?auto=format&fit=crop&w=600')">
                        <span class="news-date">Oct 15, 2023</span>
                    </div>
                    <div class="news-content">
                        <h4>Breakthrough in Cancer Treatment</h4>
                        <p class="text-muted">New immunotherapy treatment shows promising results in clinical trials...</p>
                        <button class="btn btn-primary mt-2">
                            Read More <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
                <!-- Add more news cards -->
            </div>
        </main>
    </div>

    <script>
        // Real-time last login update
        const formatTime = (date) => {
            return date.toLocaleTimeString('en-US', { 
                hour: 'numeric', 
                minute: '2-digit', 
                hour12: true 
            });
        };

        document.getElementById('last-login').textContent = 
            `Last login: Today at ${formatTime(new Date())}`;
    </script>
</body>
</html>