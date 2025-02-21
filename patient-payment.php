<?php
session_start();
include 'connection.php';
require_once('./vendor/stripe/stripe-php/init.php'); // Include Stripe PHP SDK

// Stripe API keys
\Stripe\Stripe::setApiKey('sk_test_51OPJDzBo3hvX4AIQGNRRUtUjlR0fQJ8eC2YF4pl9qJ6CGdpdjExDqQER6hthd3QxqD1amzk1yEBRWSqgpOu4BoUd00gkEydCyo'); // Replace with your actual Stripe secret key

// Ensure the patient is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    echo json_encode(['error' => 'Unauthorized access.']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch patient details
$patient_query = "SELECT p.first_name, p.last_name FROM Patients p WHERE p.user_id = ?";
$stmt = Database::$connection->prepare($patient_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$patient_result = $stmt->get_result();
$patient = $patient_result->fetch_assoc();

if (!$patient) {
    echo json_encode(['error' => 'Patient record not found.']);
    exit();
}

$first_name = $patient['first_name'];
$last_name = $patient['last_name'];

// Initialize variables for pending and completed payments as empty arrays
$pending_payments = [];
$completed_payments = [];

// Fetch all pending payments
$payments_query = "SELECT p.payment_id, p.amount, p.payment_status, p.payment_method, a.appointment_date, s.service_name 
                   FROM Payments p 
                   JOIN Appointments a ON p.appointment_id = a.appointment_id
                   LEFT JOIN Services s ON a.appointment_reason = s.service_name
                   WHERE p.user_id = ? AND p.payment_status = 'Pending'";
$stmt = Database::$connection->prepare($payments_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$payments_result = $stmt->get_result();

// Check if there are pending payments and assign the result
if ($payments_result->num_rows > 0) {
    $pending_payments = $payments_result->fetch_all(MYSQLI_ASSOC);
}

// Fetch completed payments (if any)
$completed_payments_query = "SELECT p.payment_id, p.amount, p.payment_status, p.payment_method, a.appointment_date, s.service_name 
                             FROM Payments p 
                             JOIN Appointments a ON p.appointment_id = a.appointment_id
                             LEFT JOIN Services s ON a.appointment_reason = s.service_name
                             WHERE p.user_id = ? AND p.payment_status = 'Completed'";
$stmt = Database::$connection->prepare($completed_payments_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$completed_payments_result = $stmt->get_result();

// Check if there are completed payments and assign the result
if ($completed_payments_result->num_rows > 0) {
    $completed_payments = $completed_payments_result->fetch_all(MYSQLI_ASSOC);
}

// Handle Stripe payment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount = $_POST['amount'] * 100; // Convert to cents (Stripe expects amount in cents)
    $payment_method_id = $_POST['payment_method_id']; // This will be the token ID sent from the frontend
    $appointment_id = $_POST['appointment_id']; // Appointment ID passed from frontend

    try {
        // Create the payment intent using Stripe's API
        $paymentIntent = \Stripe\PaymentIntent::create([
            'amount' => $amount,
            'currency' => 'usd',
            'payment_method' => $payment_method_id, // Use the payment method (token) received from frontend
            'confirmation_method' => 'manual',
            'confirm' => true,
        ]);

        // Handle successful payment
        if ($paymentIntent->status === 'succeeded') {
            // Save the payment record in the database
            $payment_insert_query = "INSERT INTO Payments (user_id, amount, payment_method, payment_status, appointment_id) 
                                     VALUES (?, ?, ?, 'Completed', ?)";
            $stmt = Database::$connection->prepare($payment_insert_query);
            $stmt->bind_param("idssi", $user_id, $amount, 'Credit Card', $appointment_id); // Assuming appointment_id is passed
            $stmt->execute();

            echo json_encode(['message' => 'Payment processed successfully!']);
        } else {
            echo json_encode(['error' => 'Payment failed.']);
        }
    } catch (\Stripe\Exception\CardException $e) {
        echo json_encode(['error' => 'Error: ' . $e->getError()->message]);
    }
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

        .payment-form {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 1.5rem;
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

        input, select {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .card-input {
            position: relative;
        }

        .card-icon {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
        }

        .payment-methods {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .method-card {
            flex: 1;
            padding: 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .method-card.active {
            border-color: var(--primary);
            background: #eff6ff;
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
                            <p class="due-date">Due: <?= date("Y-m-d", strtotime($payment['due_date'])) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Payment Form -->
            <div class="payment-form">
                <h2 class="section-title">Make a Payment</h2>
                <form id="payment-form" method="POST">
                    <div class="form-group">
                        <label>Amount to Pay</label>
                        <input type="number" name="amount" value="150.00" required>
                    </div>

                    <div class="form-group">
                        <label>Payment Method</label>
                        <div id="card-element">
                            <!-- A Stripe Element will be inserted here. -->
                        </div>
                        <!-- Used to display form errors. -->
                        <div id="card-errors" role="alert"></div>
                    </div>

                    <input type="hidden" name="appointment_id" value="12345"> <!-- Example appointment ID, replace dynamically -->
                    <button type="submit" id="submit" class="btn btn-primary">Pay Now</button>
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
                            <p class="paid-date">Paid: <?= date("Y-m-d", strtotime($payment['paid_date'])) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <script src="https://js.stripe.com/v3/"></script>
<script>
    var stripe = Stripe('pk_test_51OPJDzBo3hvX4AIQc7qVZ6w2Zb2aduI0C2UfSRFW7n2NMhQ42P7tj7xhA3TfIcQNKL8z4AnCc03SWHAxdww8PqWh00V3haSh5i'); // Replace with your public key
    var elements = stripe.elements();
    
    // Create an instance of the card Element
    var card = elements.create('card');
    card.mount('#card-element'); // This mounts the card input field

    // Handle form submission
    var form = document.getElementById('payment-form');
    form.addEventListener('submit', async function(event) {
        event.preventDefault();

        // Create a token using the card Element
        const {token, error} = await stripe.createToken(card);

        if (error) {
            // Display any error that occurs during token creation
            document.getElementById('card-errors').textContent = error.message;
        } else {
            // If no error, send the token to the backend for further processing
            var formData = new FormData(form);
            formData.append("payment_method_id", token.id);

            // Send the token to the backend
            fetch('patient-payment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert(data.error); // Display the error from the backend if any
                } else {
                    alert(data.message); // Display the success message received from backend
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Payment failed. Please try again later.');
            });
        }
    });
</script>


</body>
</html>
