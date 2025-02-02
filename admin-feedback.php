<?php
session_start();
include 'connection.php';

// Ensure only admin has access
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: login.php");
    exit();
}

// Handle Mark as Resolved
if (isset($_GET['resolve'])) {
    $feedback_id = $_GET['resolve'];
    Database::iud("UPDATE Feedbacks SET is_resolved = 1 WHERE feedback_id = $feedback_id");
    $_SESSION['message'] = "Feedback marked as resolved!";
    header("Location: admin-feedback.php");
    exit();
}

// Fetch all feedback
$feedbacks = Database::search("
    SELECT f.*, ft.feedback_name, u.username, u.email
    FROM Feedbacks f
    JOIN FeedbackTypes ft ON f.feedback_type_id = ft.feedback_type_id
    JOIN Users u ON f.user_id = u.user_id
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Manage Feedback - Admin</title>
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
 
    body { 
        font-family: Arial, sans-serif; 
        background: #f4f4f4; 
        padding: 20px; 
    }
    .container { 
        max-width: 900px; 
        margin: auto; 
        background: white; 
        padding: 20px; 
        border-radius: 8px; 
        box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
    }
    .btn { 
        display: inline-flex; 
        align-items: center; 
        justify-content: center; 
        padding: 10px 14px; 
        font-size: 14px; 
        font-weight: bold; 
        border: none; 
        border-radius: 6px; 
        cursor: pointer; 
        transition: background 0.3s ease, transform 0.2s ease; 
        text-decoration: none; 
    }
    .btn i {
        margin-right: 6px; /* Space between icon and text */
    }
    .btn-primary { 
        background: #2563eb; 
        color: white; 
    }
    .btn-primary:hover { 
        background: #1d4ed8; 
        transform: scale(1.05);
    }
    .btn-danger { 
        background: #ef4444; 
        color: white; 
    }
    .btn-danger:hover { 
        background: #dc2626; 
        transform: scale(1.05);
    }
    table { 
        width: 100%; 
        border-collapse: collapse; 
        margin-top: 20px; 
        background: white; 
    }
    th, td { 
        padding: 12px; 
        border: 1px solid #ddd; 
        text-align: left; 
    }
    th { 
        background: #f3f4f6; 
    }
    .alert { 
        color: green; 
        font-weight: bold; 
    }
</style>

    </style>
</head>
<body>

<div class="dashboard-container">
    
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



    <div class="container">
        
        <h2>Manage Feedback</h2>
        <?php if (isset($_SESSION['message'])): ?>
            <p class="alert"><?= $_SESSION['message']; unset($_SESSION['message']); ?></p>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Type</th>
                    <th>Feedback</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($feedbacks as $feedback): ?>
                <tr>
                    <td><?= $feedback['username'] ?></td>
                    <td><?= $feedback['email'] ?></td>
                    <td><?= $feedback['feedback_name'] ?></td>
                    <td><?= $feedback['feedback_text'] ?></td>
                    <td><?= $feedback['is_resolved'] ? "Resolved" : "Pending" ?></td>
                    <td>
    <?php if (!$feedback['is_resolved']): ?>
        <a href="?resolve=<?= $feedback['feedback_id'] ?>" class="btn btn-primary">
            <i class="fas fa-check-circle"></i> Mark Resolved
        </a>
    <?php else: ?>
        <span style="color: green; font-weight: bold;">
            <i class="fas fa-check"></i> Resolved
        </span>
    <?php endif; ?>
</td>

                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    </div>
</body>
</html>
