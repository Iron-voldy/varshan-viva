<?php
session_start();
include 'connection.php';

// Ensure database connection
Database::setUpConnection();

// Check if user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header("Location: login.php");
    exit();
}

// Get logged-in doctor ID
$user_id = $_SESSION['user_id'];
$doctor_query = "SELECT d.doctor_id, d.first_name, d.last_name, d.specialty_id, d.qualifications, 
                        d.hospital_branch, d.contact_number, d.profile_image, d.working_hours, 
                        s.specialty_name, u.email
                 FROM Doctors d
                 JOIN Specialties s ON d.specialty_id = s.specialty_id
                 JOIN Users u ON d.user_id = u.user_id
                 WHERE d.user_id = ?";
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
$specialty_name = htmlspecialchars($doctor_data['specialty_name']);
$hospital_branch = htmlspecialchars($doctor_data['hospital_branch']);
$contact_number = htmlspecialchars($doctor_data['contact_number']);
$profile_image = $doctor_data['profile_image'] ?: 'assets/images/doctor.png';
$email = htmlspecialchars($doctor_data['email']);
$qualifications = htmlspecialchars($doctor_data['qualifications']);
$working_hours = json_decode($doctor_data['working_hours'], true) ?: [];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Profile - CareCompass</title>
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


        .profile-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .profile-header {
            display: flex;
            gap: 2rem;
            align-items: center;
            margin-bottom: 2rem;
        }

        .avatar-section {
            position: relative;
            text-align: center;
        }

        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary);
        }

        .avatar-upload {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: white;
            padding: 0.5rem;
            border-radius: 50%;
            cursor: pointer;
        }

        .profile-info {
            flex: 1;
        }

        .profile-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .detail-card {
            padding: 1.5rem;
            background: var(--light);
            border-radius: 8px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .working-hours-editor {
            margin-top: 1rem;
        }

        .hour-entry {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .qualifications-editor textarea {
            width: 100%;
            height: 150px;
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
        }

        .profile-container {
    max-width: 900px;
    margin: 2rem auto;
    padding: 2rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.profile-header {
    display: flex;
    gap: 2rem;
    align-items: center;
    margin-bottom: 2rem;
}

.avatar-section {
    position: relative;
    text-align: center;
}

.profile-avatar {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid var(--primary);
}

.avatar-upload {
    position: absolute;
    bottom: 10px;
    right: 10px;
    background: white;
    padding: 0.5rem;
    border-radius: 50%;
    cursor: pointer;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.profile-info {
    flex: 1;
}

.profile-info h1 {
    font-size: 1.8rem;
    margin-bottom: 0.5rem;
}

.profile-info p {
    margin: 0.3rem 0;
    font-size: 1rem;
    color: #555;
}

.profile-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
}

.detail-card {
    padding: 1.5rem;
    background: var(--light);
    border-radius: 8px;
    border-left: 4px solid var(--primary);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.detail-card h3 {
    margin-bottom: 1rem;
    color: var(--primary);
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
    margin-top: 1.5rem;
}

textarea {
    width: 100%;
    height: 120px;
    padding: 1rem;
    border-radius: 8px;
    border: 1px solid #cbd5e1;
    font-size: 1rem;
    resize: none;
}

.btn {
    padding: 0.8rem 1.5rem;
    border: none;
    border-radius: 6px;
    font-size: 1rem;
    cursor: pointer;
}

.btn-primary {
    background: var(--primary);
    color: white;
}

.btn-save {
    background: var(--accent);
    color: white;
    width: 100%;
    margin-top: 1rem;
}

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    justify-content: center;
    align-items: center;
}

.modal-content {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    width: 400px;
    text-align: center;
}

.modal h2 {
    margin-bottom: 1rem;
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
                <a href="./doct" class="nav-item">
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

         <!-- Main Profile Section -->
         <main class="main-content">
    <div class="profile-container">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="avatar-section">
                <img src="<?= $profile_image ?>" class="profile-avatar" id="avatarPreview">
                <label class="avatar-upload">
                    <i class="fas fa-camera text-primary"></i>
                    <input type="file" id="avatarInput" accept="image/*" style="display: none;">
                </label>
            </div>
            <div class="profile-info">
                <h1 id="doctorName">Dr. <?= $doctor_name ?></h1>
                <p class="text-lg" id="doctorSpecialty"><?= $specialty_name ?></p>
                <p class="text-muted" id="doctorHospital"><?= $hospital_branch ?></p>
                <p><i class="fas fa-phone"></i> <span id="doctorPhone"><?= $contact_number ?></span></p>
                <p><i class="fas fa-envelope"></i> <span id="doctorEmail"><?= $email ?></span></p>
            </div>
        </div>

        <!-- Profile Details -->
        <div class="profile-details">
            <!-- Qualifications -->
            <div class="detail-card">
                <h3>Qualifications</h3>
                <p id="doctorQualifications"><?= nl2br($qualifications) ?></p>
            </div>

            <!-- Working Hours -->
            <div class="detail-card">
                <h3>Working Hours</h3>
                <?php foreach ($working_hours as $day => $hours): ?>
                    <p><strong><?= ucfirst($day) ?>:</strong> <?= $hours ?></p>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Edit Profile Button -->
        <button class="btn btn-primary" onclick="openEditModal()">Edit Profile</button>
    </div>

    <!-- Edit Profile Modal -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <h2>Edit Profile</h2>
            <form id="profileForm">
                <div class="form-grid">
                    <label>First Name</label>
                    <input type="text" id="firstName" value="<?= htmlspecialchars($doctor_data['first_name']) ?>" required>

                    <label>Last Name</label>
                    <input type="text" id="lastName" value="<?= htmlspecialchars($doctor_data['last_name']) ?>" required>

                    <label>Contact Number</label>
                    <input type="tel" id="contactNumber" value="<?= $contact_number ?>" required>

                    <label>Hospital Branch</label>
                    <input type="text" id="hospitalBranch" value="<?= $hospital_branch ?>" required>

                    <label>Qualifications</label>
                    <textarea id="qualifications"><?= $qualifications ?></textarea>
                </div>

                <button type="submit" class="btn btn-save">Save Changes</button>
            </form>
        </div>
    </div>
</main>

    </div>

    <script>
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            e.preventDefault();

            let formData = new FormData();
            formData.append('first_name', document.getElementById('firstName').value);
            formData.append('last_name', document.getElementById('lastName').value);
            formData.append('contact_number', document.getElementById('contactNumber').value);
            formData.append('hospital_branch', document.getElementById('hospitalBranch').value);
            formData.append('qualifications', document.getElementById('qualifications').value);

            fetch('update-doctor-profile.php', {
                method: 'POST',
                body: formData
            }).then(response => response.json()).then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Update failed!');
                }
            });
        });

        function openEditModal() {
            document.getElementById('editModal').style.display = 'block';
        }
    </script>
</body>
</html>