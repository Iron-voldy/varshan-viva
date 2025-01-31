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
    echo "<script>alert('You need to complete your profile before accessing medical records.'); window.location.href = 'patient-profile.php';</script>";
    exit();
}

// Fetch patient's medical records
$medical_records_query = "SELECT mr.*, d.first_name AS doctor_first, d.last_name AS doctor_last
                          FROM MedicalRecords mr
                          JOIN Doctors d ON mr.doctor_id = d.doctor_id
                          WHERE mr.patient_id = '$patient_id'
                          ORDER BY mr.visit_date DESC";
$medical_records_result = Database::search($medical_records_query);
$medical_records = [];
while ($row = $medical_records_result->fetch_assoc()) {
    $medical_records[] = $row;
}

// Fetch prescriptions
$prescriptions_query = "SELECT p.*, mr.visit_date
                        FROM Prescriptions p
                        JOIN MedicalRecords mr ON p.record_id = mr.record_id
                        WHERE mr.patient_id = '$patient_id'
                        ORDER BY p.issued_date DESC";
$prescriptions_result = Database::search($prescriptions_query);
$prescriptions = [];
while ($row = $prescriptions_result->fetch_assoc()) {
    $prescriptions[] = $row;
}

// Fetch lab tests
$lab_tests_query = "SELECT lt.*, mr.visit_date, d.first_name AS doctor_first, d.last_name AS doctor_last
                    FROM LabTests lt
                    JOIN MedicalRecords mr ON lt.record_id = mr.record_id
                    JOIN Doctors d ON lt.doctor_id = d.doctor_id
                    WHERE mr.patient_id = '$patient_id'
                    ORDER BY lt.prescribed_date DESC";
$lab_tests_result = Database::search($lab_tests_query);
$lab_tests = [];
while ($row = $lab_tests_result->fetch_assoc()) {
    $lab_tests[] = $row;
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

        /* Medical Records Timeline */
        .timeline {
            position: relative;
            padding-left: 2rem;
            border-left: 3px solid var(--primary);
            margin-left: 1rem;
        }

        .record-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            position: relative;
            transition: transform 0.3s;
        }

        .record-card:hover {
            transform: translateX(10px);
        }

        .record-card::before {
            content: '';
            position: absolute;
            left: -2.3rem;
            top: 2rem;
            width: 16px;
            height: 16px;
            background: var(--primary);
            border-radius: 50%;
        }

        .record-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1.5rem;
        }

        .doctor-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .doctor-image {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary);
        }

        .record-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .detail-item {
            padding: 1rem;
            background: var(--light);
            border-radius: 10px;
        }

        .prescriptions-list, .labtests-list {
            margin-top: 1.5rem;
        }

        .medication-card, .labtest-card {
            background: white;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            border-left: 4px solid var(--accent);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
            display: inline-block;
        }

        .status-completed { background: #dcfce7; color: #166534; }
        .status-pending { background: #fef9c3; color: #854d0e; }

        @media (max-width: 768px) {
            .dashboard-container {
                grid-template-columns: 1fr;
            }
            
            .record-header {
                flex-direction: column;
                gap: 1rem;
            }
        }   </style>
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
                <a href="./patient-payment.php" class="nav-item">
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
            <h1>My Medical Records</h1>

            <div class="timeline">
                <?php if (empty($medical_records)) : ?>
                    <p>No medical records found.</p>
                <?php else : ?>
                    <?php foreach ($medical_records as $record) : ?>
                        <div class="record-card">
                            <div class="record-header">
                                <div class="doctor-info">
                                    <img src="./assets/images/doctor.png" class="doctor-image">
                                    <div>
                                        <h3><?= htmlspecialchars($record['doctor_first'] . " " . $record['doctor_last']) ?></h3>
                                        <p>Visit Date: <?= date("F j, Y", strtotime($record['visit_date'])) ?></p>
                                    </div>
                                </div>
                                <span class="status-badge status-completed">Completed</span>
                            </div>

                            <div class="record-details">
                                <div class="detail-item">
                                    <h4>Diagnosis</h4>
                                    <p><?= htmlspecialchars($record['diagnosis']) ?></p>
                                </div>
                                <div class="detail-item">
                                    <h4>Treatment Plan</h4>
                                    <p><?= htmlspecialchars($record['treatment_plan']) ?></p>
                                </div>
                                <div class="detail-item">
                                    <h4>Follow-up Date</h4>
                                    <p><?= $record['follow_up_date'] ? date("F j, Y", strtotime($record['follow_up_date'])) : 'No follow-up needed' ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <h2>Prescribed Medications</h2>
            <div class="prescriptions-list">
                <?php if (empty($prescriptions)) : ?>
                    <p>No prescriptions found.</p>
                <?php else : ?>
                    <?php foreach ($prescriptions as $prescription) : ?>
                        <div class="medication-card">
                            <div>
                                <h5><?= htmlspecialchars($prescription['medication_name']) ?></h5>
                                <p><?= htmlspecialchars($prescription['dosage']) ?> | <?= htmlspecialchars($prescription['duration']) ?></p>
                            </div>
                            <p><i class="fas fa-calendar-alt"></i> Issued on <?= date("F j, Y", strtotime($prescription['issued_date'])) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <h2>Lab Tests</h2>
            <div class="labtests-list">
                <?php if (empty($lab_tests)) : ?>
                    <p>No lab tests found.</p>
                <?php else : ?>
                    <?php foreach ($lab_tests as $lab_test) : ?>
                        <div class="labtest-card">
                            <div>
                                <h5><?= htmlspecialchars($lab_test['test_name']) ?></h5>
                                <p>Ordered by: Dr. <?= htmlspecialchars($lab_test['doctor_first'] . " " . $lab_test['doctor_last']) ?></p>
                            </div>
                            <p><i class="fas fa-calendar-alt"></i> Prescribed: <?= date("F j, Y", strtotime($lab_test['prescribed_date'])) ?></p>
                            <p class="status-badge <?= $lab_test['status'] == 'Completed' ? 'status-completed' : 'status-pending' ?>">
                                <?= htmlspecialchars($lab_test['status']) ?>
                            </p>
                            <?php if ($lab_test['status'] == 'Completed' && !empty($lab_test['result'])) : ?>
                                <a href="download-lab-result.php?test_id=<?= $lab_test['lab_test_id'] ?>" class="text-primary">
                                    <i class="fas fa-file-pdf"></i> Download Results
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>

    </div>
</body>
</html>