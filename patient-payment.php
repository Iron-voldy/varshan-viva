<?php
session_start();
include 'connection.php';


error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_error.log'); 


if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    echo json_encode(['error' => 'Unauthorized access.']);
    exit();
}

$user_id = $_SESSION['user_id'];


$pending_payments = [];
$completed_payments = [];


$pending_query = "SELECT p.payment_id, p.amount, p.payment_status, p.payment_method, a.appointment_date, s.service_name
                  FROM Payments p
                  JOIN Appointments a ON p.appointment_id = a.appointment_id
                  LEFT JOIN Services s ON a.appointment_reason = s.service_name
                  WHERE p.user_id = ? AND p.payment_status = 'Pending'";

$stmt = Database::$connection->prepare($pending_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pending_result = $stmt->get_result();
if ($pending_result->num_rows > 0) {
    $pending_payments = $pending_result->fetch_all(MYSQLI_ASSOC);
}


$completed_query = "SELECT p.payment_id, p.amount, p.payment_status, p.payment_method, a.appointment_date, s.service_name
                    FROM Payments p
                    JOIN Appointments a ON p.appointment_id = a.appointment_id
                    LEFT JOIN Services s ON a.appointment_reason = s.service_name
                    WHERE p.user_id = ? AND p.payment_status = 'Completed'";

$stmt = Database::$connection->prepare($completed_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$completed_result = $stmt->get_result();
if ($completed_result->num_rows > 0) {
    $completed_payments = $completed_result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - CareCompass</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta http-equiv="Content-Security-Policy" content="
        default-src 'self';
        script-src 'self' 'unsafe-inline' https://www.payhere.lk https://sandbox.payhere.lk;
        style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com;
        img-src 'self' data:;
        font-src 'self' https://cdnjs.cloudflare.com;
        connect-src 'self' https://sandbox.payhere.lk;
        frame-src https://www.payhere.lk https://sandbox.payhere.lk;
    ">
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

        .payment-form {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--dark);
            font-weight: 500;
        }

        input {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .btn {
            padding: 0.8rem 2rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: #1d4ed8;
        }

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
                <a href="./patient-medicalRecord.php" class="nav-item">
                    <i class="fas fa-file-medical"></i>
                    <span>Medical Records</span>
                </a>
                <a href="./patient-payment.php" class="nav-item">
                    <i class="fas fa-credit-card"></i>
                    <span>Payments</span>
                </a>
                <a href="./patient-profile.php" class="nav-item">
                    <i class="fas fa-user-edit"></i>
                    <span>Profile Settings</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Pending Payments -->
            <div class="payments-list">
                <h2 class="section-title">Pending Payments</h2>
                <?php foreach ($pending_payments as $payment): ?>
                    <div class="payment-card">
                        <div>
                            <h3>Appointment with Dr. <?= htmlspecialchars($payment['first_name']) ?> <?= htmlspecialchars($payment['last_name']) ?></h3>
                            <p><i class="fas fa-calendar-alt"></i> <?= htmlspecialchars($payment['appointment_date']) ?></p>
                            <p><i class="fas fa-clock"></i> <?= htmlspecialchars($payment['service_name']) ?></p>
                        </div>
                        <div class="payment-details">
                            <p class="amount"><?= htmlspecialchars($payment['amount']) ?></p>
                            <span class="payment-status status-pending">Pending</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Payment Form -->
            <div class="payment-form">
                <h2 class="section-title">Make a Payment</h2>
                <form id="payment-form">
                    <div class="form-group">
                        <label>Amount to Pay (LKR)</label>
                        <input type="number" name="amount" value="150.00" required>
                    </div>
                    <input type="hidden" name="appointment_id" value="12345"> <!-- Replace with dynamic value in production -->
                    <button type="submit" class="btn btn-primary">Pay Now with PayHere</button>
                </form>
            </div>

            <!-- Completed Payments -->
            <div class="payments-list">
                <h2 class="section-title">Completed Payments</h2>
                <?php foreach ($completed_payments as $payment): ?>
                    <div class="payment-card">
                        <div>
                            <h3>Appointment with Dr. <?= htmlspecialchars($payment['first_name']) ?> <?= htmlspecialchars($payment['last_name']) ?></h3>
                            <p><i class="fas fa-calendar-alt"></i> <?= htmlspecialchars($payment['appointment_date']) ?></p>
                            <p><i class="fas fa-clock"></i> <?= htmlspecialchars($payment['service_name']) ?></p>
                        </div>
                        <div class="payment-details">
                            <p class="amount"><?= htmlspecialchars($payment['amount']) ?></p>
                            <span class="payment-status status-paid">Paid</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <script type="text/javascript" src="https://www.payhere.lk/lib/payhere.js"></script>
    <script>
        document.getElementById('payment-form').addEventListener('submit', function(event) {
            event.preventDefault();

            var amount = document.querySelector('input[name="amount"]').value;
            var appointment_id = document.querySelector('input[name="appointment_id"]').value;

            fetch('initiate_payhere.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'amount=' + encodeURIComponent(amount) + '&appointment_id=' + encodeURIComponent(appointment_id)
            })
            .then(response => response.json())
            .then(payment => {
                if (payment.error) {
                    alert(payment.error);
                    return;
                }

                payhere.onCompleted = function(orderId) {
                    console.log("Payment completed. OrderID: " + orderId);
                    alert("Payment completed. OrderID: " + orderId);
                    window.location.reload();
                };

                payhere.onDismissed = function() {
                    console.log("Payment dismissed");
                    alert("Payment dismissed");
                };

                payhere.onError = function(error) {
                    console.log("Error: " + error);
                    alert("Error: " + error);
                };

                payhere.startPayment(payment);
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to initiate payment');
            });
        });
    </script>
</body>
</html>