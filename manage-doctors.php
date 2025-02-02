<?php
session_start();
include 'connection.php';

Database::setUpConnection();

// Fetch Specialties for Dropdown
$specialty_query = "SELECT * FROM Specialties";
$specialty_result = Database::search($specialty_query);
$specialties = $specialty_result->fetch_all(MYSQLI_ASSOC);

// Fetch Doctors from Database
$query = "SELECT d.doctor_id, d.first_name, d.last_name, d.contact_number, d.hospital_branch, d.qualifications, d.working_hours, s.specialty_id, s.specialty_name, u.email, u.status 
          FROM Doctors d
          JOIN Specialties s ON d.specialty_id = s.specialty_id
          JOIN Users u ON d.user_id = u.user_id";
$result = Database::search($query);
$doctors = $result->fetch_all(MYSQLI_ASSOC);

// Handle Add/Edit Doctor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_doctor'])) {
    $first_name = mysqli_real_escape_string(Database::$connection, $_POST['first_name']);
    $last_name = mysqli_real_escape_string(Database::$connection, $_POST['last_name']);
    $specialty_id = (int)$_POST['specialty_id'];
    $hospital_branch = mysqli_real_escape_string(Database::$connection, $_POST['hospital_branch']);
    $contact_number = mysqli_real_escape_string(Database::$connection, $_POST['contact_number']);
    $email = mysqli_real_escape_string(Database::$connection, $_POST['email']);
    $qualifications = mysqli_real_escape_string(Database::$connection, $_POST['qualifications']);
    $working_hours = mysqli_real_escape_string(Database::$connection, $_POST['working_hours']);
    
    // Retrieve or create user
    $user_query = "SELECT user_id FROM Users WHERE email = ? LIMIT 1";
    $stmt = Database::$connection->prepare($user_query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user_result = $stmt->get_result();
    
    if ($user_result->num_rows === 0) {
        // Create new user if email not found
        $role_id = 2; // Assuming role_id 2 is for doctors
        $password = password_hash("defaultpassword", PASSWORD_DEFAULT);
        $insert_user = "INSERT INTO Users (username, password, email, role_id, status) VALUES (?, ?, ?, ?, 'Active')";
        $stmt = Database::$connection->prepare($insert_user);
        $stmt->bind_param("sssi", $first_name, $password, $email, $role_id);
        $stmt->execute();
        $user_id = $stmt->insert_id;
    } else {
        $user = $user_result->fetch_assoc();
        $user_id = $user['user_id'];
    }
    
    if (!empty($_POST['doctor_id'])) {
        $doctor_id = (int)$_POST['doctor_id'];
        $update_query = "UPDATE Doctors SET first_name=?, last_name=?, specialty_id=?, hospital_branch=?, contact_number=?, qualifications=?, working_hours=? WHERE doctor_id=?";
        $stmt = Database::$connection->prepare($update_query);
        $stmt->bind_param("ssiisssi", $first_name, $last_name, $specialty_id, $hospital_branch, $contact_number, $qualifications, $working_hours, $doctor_id);
        $stmt->execute();
    } else {
        $insert_query = "INSERT INTO Doctors (user_id, first_name, last_name, specialty_id, hospital_branch, contact_number, qualifications, working_hours) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = Database::$connection->prepare($insert_query);
        $stmt->bind_param("ississss", $user_id, $first_name, $last_name, $specialty_id, $hospital_branch, $contact_number, $qualifications, $working_hours);
        $stmt->execute();
    }
    header("Location: manage-doctors.php");
    exit();
}

// Handle Delete Doctor
if (isset($_GET['delete'])) {
    $doctor_id = (int)$_GET['delete'];
    $delete_query = "DELETE FROM Doctors WHERE doctor_id = ?";
    $stmt = Database::$connection->prepare($delete_query);
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    header("Location: manage-doctors.php");
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Doctors - CareCompass</title>
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


        .manage-doctors {
            padding: 2rem;
        }

        .action-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .search-filter {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .doctor-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .status-indicator {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }

        .status-active { background: #dcfce7; color: #166534; }
        .status-inactive { background: #fef3c7; color: #b45309; }

        .avatar-sm {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .action-btns {
            display: flex;
            gap: 0.5rem;
        }

        /* Enhanced Modal Styles */
        .doctor-modal .form-grid {
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
            align-items: center;
        }

        .btn {
            padding: 10px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease-in-out;
            border: none;
        }
        .btn-primary { background: #2563eb; color: white; }
        .btn-danger { background: #ef4444; color: white; }
        .btn:hover { opacity: 0.8; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar (Same as previous admin dashboard) -->
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
                <a href="#" class="nav-item">
                    <i class="fas fa-user-injured"></i>
                    <span>Manage Patients</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-calendar-check"></i>
                    <span>Appointments</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-credit-card"></i>
                    <span>Payments</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-comments"></i>
                    <span>Feedback</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="manage-doctors">
                <h1>Manage Doctors</h1>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Specialty</th>
                            <th>Contact</th>
                            <th>Hospital Branch</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($doctors as $doctor): ?>
                        <tr>
                            <td><?= htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']) ?></td>
                            <td><?= htmlspecialchars($doctor['specialty_name']) ?></td>
                            <td><?= htmlspecialchars($doctor['contact_number']) ?></td>
                            <td><?= htmlspecialchars($doctor['hospital_branch']) ?></td>
                            <td><?= $doctor['status'] == 'Active' ? 'Active' : 'Inactive' ?></td>
                            <td>
                                <button class="btn btn-primary" onclick="editDoctor(
                                    <?= $doctor['doctor_id'] ?>, 
                                    '<?= htmlspecialchars($doctor['first_name']) ?>', 
                                    '<?= htmlspecialchars($doctor['last_name']) ?>', 
                                    <?= $doctor['specialty_id'] ?>, 
                                    '<?= htmlspecialchars($doctor['hospital_branch']) ?>', 
                                    '<?= htmlspecialchars($doctor['contact_number']) ?>',
                                    '<?= htmlspecialchars($doctor['qualifications']) ?>',
                                    '<?= htmlspecialchars($doctor['working_hours']) ?>'
                                )">Edit</button>
                                <a href="?delete=<?= $doctor['doctor_id'] ?>" class="btn btn-danger">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Add/Edit Doctor Form -->
                <h2 id="formTitle">Add New Doctor</h2>
                <form method="POST" action="">
                    <input type="hidden" id="doctor_id" name="doctor_id">
                    <input type="text" id="first_name" name="first_name" placeholder="First Name" required>
                    <input type="text" id="last_name" name="last_name" placeholder="Last Name" required>
                    <select id="specialty_id" name="specialty_id" required>
                        <?php foreach ($specialties as $spec): ?>
                            <option value="<?= $spec['specialty_id'] ?>"><?= $spec['specialty_name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" id="hospital_branch" name="hospital_branch" placeholder="Hospital Branch" required>
                    <input type="text" id="contact_number" name="contact_number" placeholder="Contact Number" required>
                    <input type="text" id="qualifications" name="qualifications" placeholder="Qualifications" required>
                    <input type="text" id="working_hours" name="working_hours" placeholder="Working Hours" required>
                    <button type="submit" name="save_doctor" class="btn btn-primary">Save Doctor</button>
                </form>
            </div>
        </main>
    </div>

    <script>
        function editDoctor(id, firstName, lastName, specialty, branch, contact, qualifications, workingHours) {
            document.getElementById('doctor_id').value = id;
            document.getElementById('first_name').value = firstName;
            document.getElementById('last_name').value = lastName;
            document.getElementById('specialty_id').value = specialty;
            document.getElementById('hospital_branch').value = branch;
            document.getElementById('contact_number').value = contact;
            document.getElementById('qualifications').value = qualifications;
            document.getElementById('working_hours').value = workingHours;
            document.getElementById('formTitle').innerText = 'Edit Doctor';
        }
    </script>
</body>
</html>