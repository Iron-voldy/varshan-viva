<?php
session_start();
include 'connection.php';

// Ensure only admin has access
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: login.php");
    exit();
}


// Handle Add Patient
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_patient'])) {
    $first_name = mysqli_real_escape_string(Database::$connection, $_POST['first_name']);
    $last_name = mysqli_real_escape_string(Database::$connection, $_POST['last_name']);
    $dob = mysqli_real_escape_string(Database::$connection, $_POST['dob']);
    $gender = mysqli_real_escape_string(Database::$connection, $_POST['gender']);
    $blood_type = mysqli_real_escape_string(Database::$connection, $_POST['blood_type']);
    $email = mysqli_real_escape_string(Database::$connection, $_POST['email']);
    $phone = mysqli_real_escape_string(Database::$connection, $_POST['phone']);
    $address = mysqli_real_escape_string(Database::$connection, $_POST['address']);

    // Create new user
    $password = password_hash('TempPassword123', PASSWORD_DEFAULT);
    Database::iud("
        INSERT INTO Users (username, password, email, phone, role_id, status)
        VALUES ('$email', '$password', '$email', '$phone', 3, 'Active')
    ");
    $user_id = Database::$connection->insert_id;

    // Insert patient details
    Database::iud("
        INSERT INTO Patients (user_id, first_name, last_name, date_of_birth, gender, blood_type, address)
        VALUES ($user_id, '$first_name', '$last_name', '$dob', '$gender', '$blood_type', '$address')
    ");

    $_SESSION['message'] = "Patient added successfully!";
    header("Location: manage-patient.php");
    exit();
}

// Handle Edit Patient
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_patient'])) {
    $patient_id = $_POST['patient_id'];
    $user_id = $_POST['user_id'];
    $first_name = mysqli_real_escape_string(Database::$connection, $_POST['first_name']);
    $last_name = mysqli_real_escape_string(Database::$connection, $_POST['last_name']);
    $dob = mysqli_real_escape_string(Database::$connection, $_POST['dob']);
    $gender = mysqli_real_escape_string(Database::$connection, $_POST['gender']);
    $blood_type = mysqli_real_escape_string(Database::$connection, $_POST['blood_type']);
    $email = mysqli_real_escape_string(Database::$connection, $_POST['email']);
    $phone = mysqli_real_escape_string(Database::$connection, $_POST['phone']);
    $address = mysqli_real_escape_string(Database::$connection, $_POST['address']);

    // Update patient details
    Database::iud("
        UPDATE Patients SET
        first_name = '$first_name',
        last_name = '$last_name',
        date_of_birth = '$dob',
        gender = '$gender',
        blood_type = '$blood_type',
        address = '$address'
        WHERE patient_id = $patient_id
    ");

    // Update user details
    Database::iud("
        UPDATE Users SET
        email = '$email',
        phone = '$phone'
        WHERE user_id = $user_id
    ");

    $_SESSION['message'] = "Patient details updated!";
    header("Location: manage-patient.php");
    exit();
}

// Handle Delete Patient
if (isset($_GET['delete'])) {
    $patient_id = $_GET['delete'];
    $result = Database::search("SELECT user_id FROM Patients WHERE patient_id = $patient_id");
    $user_id = $result->fetch_assoc()['user_id'];

    Database::iud("DELETE FROM Patients WHERE patient_id = $patient_id");
    Database::iud("DELETE FROM Users WHERE user_id = $user_id");

    $_SESSION['message'] = "Patient deleted successfully!";
    header("Location: manage-patient.php");
    exit();
}

// Fetch all patients
$patients = [];
$result = Database::search("
    SELECT p.*, u.email, u.phone, u.user_id 
    FROM Patients p
    JOIN Users u ON p.user_id = u.user_id
");
while ($row = $result->fetch_assoc()) {
    $patients[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Patients - CareCompass</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f8f9fa; padding: 20px; }
        .container { max-width: 900px; margin: auto; background: white; padding: 20px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
        .btn { padding: 10px; border: none; cursor: pointer; }
        .btn-primary { background: #2563eb; color: white; }
        .btn-danger { background: #ef4444; color: white; }
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table th, .table td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; }
        .modal-content { background: white; padding: 20px; width: 400px; }

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

    <div class="container">
        <h2>Manage Patients</h2>
        <?php if (isset($_SESSION['message'])): ?>
            <p style="color: green;"><?= $_SESSION['message']; unset($_SESSION['message']); ?></p>
        <?php endif; ?>
        <button class="btn btn-primary" onclick="document.getElementById('addModal').style.display='flex'">Add Patient</button>

        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($patients as $patient): ?>
                <tr>
                    <td><?= $patient['first_name'] ?> <?= $patient['last_name'] ?></td>
                    <td><?= $patient['email'] ?></td>
                    <td><?= $patient['phone'] ?></td>
                    <td>
                        <a href="?edit=<?= $patient['patient_id'] ?>" class="btn btn-primary">Edit</a>
                        <a href="?delete=<?= $patient['patient_id'] ?>" class="btn btn-danger">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Add Patient Modal -->
    <div class="modal" id="addModal">
        <div class="modal-content">
            <h2>Add Patient</h2>
            <form method="POST">
                <input type="text" name="first_name" placeholder="First Name" required>
                <input type="text" name="last_name" placeholder="Last Name" required>
                <input type="date" name="dob" required>
                <input type="text" name="gender" required>
                <input type="text" name="blood_type" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="text" name="phone" placeholder="Phone" required>
                <input type="text" name="address" placeholder="Address" required>
                <button type="submit" name="add_patient" class="btn btn-primary">Save</button>
            </form>
        </div>
    </div>
    </div>
</body>
</html>
