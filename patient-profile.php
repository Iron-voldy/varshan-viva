<?php
session_start();
include 'connection.php';

// Ensure database connection is established
Database::setUpConnection();

// Check if user is logged in as a patient
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    header("Location: login.php");
    exit();
}

// Get logged-in user ID
$user_id = $_SESSION['user_id'];

// Fetch patient details if they exist
$query = "SELECT * FROM Patients WHERE user_id = '$user_id'";
$result = Database::search($query);
$patient = $result->fetch_assoc();

// If form is submitted, update or insert patient details
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = mysqli_real_escape_string(Database::$connection, $_POST['first_name']);
    $last_name = mysqli_real_escape_string(Database::$connection, $_POST['last_name']);
    $date_of_birth = mysqli_real_escape_string(Database::$connection, $_POST['date_of_birth']);
    $gender = mysqli_real_escape_string(Database::$connection, $_POST['gender']);
    $blood_type = mysqli_real_escape_string(Database::$connection, $_POST['blood_type']);
    $address = mysqli_real_escape_string(Database::$connection, $_POST['address']);
    $emergency_contact = mysqli_real_escape_string(Database::$connection, $_POST['emergency_contact']);

    if ($patient) {
        // Update existing patient record
        $update_query = "UPDATE Patients SET 
            first_name = '$first_name',
            last_name = '$last_name',
            date_of_birth = '$date_of_birth',
            gender = '$gender',
            blood_type = '$blood_type',
            address = '$address',
            emergency_contact = '$emergency_contact'
            WHERE user_id = '$user_id'";
        Database::iud($update_query);

        echo "<script>alert('Profile updated successfully!'); window.location.href = 'patient-profile.php';</script>";
    } else {
        // Insert new patient record
        $insert_query = "INSERT INTO Patients (user_id, first_name, last_name, date_of_birth, gender, blood_type, address, emergency_contact)
            VALUES ('$user_id', '$first_name', '$last_name', '$date_of_birth', '$gender', '$blood_type', '$address', '$emergency_contact')";
        Database::iud($insert_query);

        echo "<script>alert('Profile created successfully!'); window.location.href = 'patient-profile.php';</script>";
    }
}

// Set default values if patient record does not exist
$first_name = $patient['first_name'] ?? "";
$last_name = $patient['last_name'] ?? "";
$date_of_birth = $patient['date_of_birth'] ?? "";
$gender = $patient['gender'] ?? "";
$blood_type = $patient['blood_type'] ?? "";
$address = $patient['address'] ?? "";
$emergency_contact = $patient['emergency_contact'] ?? "";
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
        :root {
            --primary: #2563eb;
            --secondary: #3b82f6;
            --light: #f8fafc;
            --dark: #1e293b;
            --success: #10b981;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        

        .profile-container {
            background: white;
            max-width: 800px;
            width: 100%;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 2.5rem;
        }

        .profile-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .profile-header h1 {
            color: var(--dark);
            margin-bottom: 0.5rem;
            font-size: 2rem;
        }

        .profile-header p {
            color: #64748b;
        }

        .profile-form {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }

        .form-group {
            position: relative;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
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

        .input-icon {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }

        .form-actions {
            grid-column: 1 / -1;
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
        }

        .btn {
            padding: 0.8rem 2rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
        }

        .btn-save {
            background: var(--primary);
            color: white;
        }

        .btn-save:hover {
            background: #1d4ed8;
        }

        .btn-back {
            background: #e2e8f0;
            color: var(--dark);
        }

        .btn-back:hover {
            background: #cbd5e1;
        }

        @media (max-width: 768px) {
            .profile-form {
                grid-template-columns: 1fr;
            }
            
            body {
                padding: 1rem;
            }
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
                <a href="#" class="nav-item">
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
        <div class="profile-container">
            <div class="profile-header">
                <h1>Profile Settings</h1>
                <p>Manage your personal information and emergency contacts</p>
            </div>
    
            <form class="profile-form" method="POST">
            <div class="form-group">
                <label>First Name</label>
                <input type="text" name="first_name" value="<?= htmlspecialchars($first_name) ?>" required>
            </div>

            <div class="form-group">
                <label>Last Name</label>
                <input type="text" name="last_name" value="<?= htmlspecialchars($last_name) ?>" required>
            </div>

            <div class="form-group">
                <label>Date of Birth</label>
                <input type="date" name="date_of_birth" value="<?= htmlspecialchars($date_of_birth) ?>" required>
            </div>

            <div class="form-group">
                <label>Gender</label>
                <select name="gender" required>
                    <option value="Male" <?= ($gender == 'Male') ? 'selected' : '' ?>>Male</option>
                    <option value="Female" <?= ($gender == 'Female') ? 'selected' : '' ?>>Female</option>
                    <option value="Other" <?= ($gender == 'Other') ? 'selected' : '' ?>>Other</option>
                </select>
            </div>

            <div class="form-group">
                <label>Blood Type</label>
                <select name="blood_type" required>
                    <option value="A+" <?= ($blood_type == 'A+') ? 'selected' : '' ?>>A+</option>
                    <option value="A-" <?= ($blood_type == 'A-') ? 'selected' : '' ?>>A-</option>
                    <option value="B+" <?= ($blood_type == 'B+') ? 'selected' : '' ?>>B+</option>
                    <option value="B-" <?= ($blood_type == 'B-') ? 'selected' : '' ?>>B-</option>
                    <option value="O+" <?= ($blood_type == 'O+') ? 'selected' : '' ?>>O+</option>
                    <option value="O-" <?= ($blood_type == 'O-') ? 'selected' : '' ?>>O-</option>
                    <option value="AB+" <?= ($blood_type == 'AB+') ? 'selected' : '' ?>>AB+</option>
                    <option value="AB-" <?= ($blood_type == 'AB-') ? 'selected' : '' ?>>AB-</option>
                </select>
            </div>

            <div class="form-group full-width">
                <label>Address</label>
                <textarea name="address" required><?= htmlspecialchars($address) ?></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-save">Save Changes</button>
            </div>
        </form>
        </div>
    
    </div>
</body>
</html>