<?php
session_start();
include 'connection.php';

// Ensure only admin has access
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: login.php");
    exit();
}

// Handle Payment Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_payment'])) {
    $payment_id = $_POST['payment_id'];
    $new_status = $_POST['status'];

    Database::iud("UPDATE Payments SET payment_status = '$new_status' WHERE payment_id = $payment_id");

    $_SESSION['message'] = "Payment status updated!";
    header("Location: admin-payments.php");
    exit();
}

// Fetch all payments
$payments = Database::search("
    SELECT p.*, u.username, a.appointment_id 
    FROM Payments p
    JOIN Users u ON p.user_id = u.user_id
    JOIN Appointments a ON p.appointment_id = a.appointment_id
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Manage Payments - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
        .container { max-width: 1000px; margin: auto; background: white; padding: 20px; border-radius: 8px; }
        .btn { padding: 10px; border: none; cursor: pointer; }
        .btn-primary { background: #2563eb; color: white; }
        .btn-danger { background: #ef4444; color: white; }
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: center; }
        .alert { color: green; font-weight: bold; }

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
        <h2>Manage Payments</h2>
        <?php if (isset($_SESSION['message'])): ?>
            <p class="alert"><?= $_SESSION['message']; unset($_SESSION['message']); ?></p>
        <?php endif; ?>

        <table class="table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Transaction ID</th>
                    <th>Status</th>
                    <th>Payment Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $payment): ?>
                <tr>
                    <td><?= $payment['username'] ?></td>
                    <td>$<?= number_format($payment['amount'], 2) ?></td>
                    <td><?= $payment['payment_method'] ?></td>
                    <td><?= $payment['transaction_id'] ?></td>
                    <td><?= ucfirst($payment['payment_status']) ?></td>
                    <td><?= date("Y-m-d", strtotime($payment['payment_date'])) ?></td>
                    <td>
                        <button class="btn btn-primary" onclick="openEditModal(
                            <?= $payment['payment_id'] ?>, 
                            '<?= $payment['payment_status'] ?>'
                        )">Update</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Edit Payment Modal -->
    <div class="modal" id="editModal" style="display: none;">
        <div class="modal-content">
            <h2>Update Payment Status</h2>
            <form method="POST">
                <input type="hidden" id="payment_id" name="payment_id">
                <label for="status">Status:</label>
                <select name="status" id="status" required>
                    <option value="Pending">Pending</option>
                    <option value="Completed">Completed</option>
                    <option value="Failed">Failed</option>
                    <option value="Refunded">Refunded</option>
                </select>
                <button type="submit" name="update_payment" class="btn btn-primary">Save</button>
                <button type="button" class="btn btn-danger" onclick="closeEditModal()">Cancel</button>
            </form>
        </div>
    </div>

    </div>
    <script>
        function openEditModal(paymentId, status) {
            document.getElementById("payment_id").value = paymentId;
            document.getElementById("status").value = status;
            document.getElementById("editModal").style.display = "flex";
        }

        function closeEditModal() {
            document.getElementById("editModal").style.display = "none";
        }
    </script>
</body>
</html>
