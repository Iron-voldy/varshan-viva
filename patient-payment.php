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

        /* Payments List */
        .payments-list {
            margin-bottom: 3rem;
        }

        .payment-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .payment-status {
            padding: 0.3rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }

        .status-pending { background: #fef3c7; color: #854d0e; }
        .status-paid { background: #dcfce7; color: #166534; }

        /* Payment Form */
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

        input:focus, select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            outline: none;
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

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .dashboard-container {
                grid-template-columns: 1fr;
            }
            
            .payment-card {
                flex-direction: column;
                align-items: start;
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
        <main class="main-content">
            <!-- Pending Payments -->
            <div class="payments-list">
                <h2 class="section-title">Pending Payments</h2>
                
                <!-- Payment Card 1 -->
                <div class="payment-card">
                    <div>
                        <h3>Appointment with Dr. Sarah Johnson</h3>
                        <p><i class="fas fa-calendar-alt"></i> October 25, 2023</p>
                        <p><i class="fas fa-clock"></i> 10:00 AM - 10:30 AM</p>
                    </div>
                    <div class="payment-details">
                        <p class="amount">$150.00</p>
                        <span class="payment-status status-pending">Pending</span>
                        <p class="due-date">Due: November 1, 2023</p>
                    </div>
                </div>

                <!-- Payment Card 2 -->
                <div class="payment-card">
                    <div>
                        <h3>Lab Test - Complete Blood Count</h3>
                        <p><i class="fas fa-flask"></i> October 20, 2023</p>
                    </div>
                    <div class="payment-details">
                        <p class="amount">$75.50</p>
                        <span class="payment-status status-pending">Pending</span>
                        <p class="due-date">Due: October 30, 2023</p>
                    </div>
                </div>
            </div>

            <!-- Payment Form -->
            <div class="payment-form">
                <h2 class="section-title">Make a Payment</h2>
                <form>
                    <div class="payment-methods">
                        <div class="method-card active">
                            <i class="fab fa-cc-visa fa-2x"></i>
                            <p>Credit/Debit Card</p>
                        </div>
                        <div class="method-card">
                            <i class="fab fa-paypal fa-2x"></i>
                            <p>PayPal</p>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Card Number</label>
                            <div class="card-input">
                                <input type="text" placeholder="4242 4242 4242 4242" required>
                                <i class="fas fa-credit-card card-icon"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Expiration Date</label>
                            <input type="month" required>
                        </div>

                        <div class="form-group">
                            <label>CVC</label>
                            <div class="card-input">
                                <input type="text" placeholder="123" required>
                                <i class="fas fa-lock card-icon"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Amount to Pay</label>
                            <input type="number" value="150.00" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-lock"></i> Pay Now
                    </button>
                </form>
            </div>

            <!-- Payment History -->
            <div class="payment-history">
                <h2 class="section-title">Payment History</h2>
                <div class="payment-card status-paid">
                    <div>
                        <h3>Appointment with Dr. Michael Chen</h3>
                        <p><i class="fas fa-calendar-alt"></i> September 15, 2023</p>
                    </div>
                    <div class="payment-details">
                        <p class="amount">$120.00</p>
                        <span class="payment-status status-paid">Paid</span>
                        <p class="paid-date">Paid: September 15, 2023</p>
                    </div>
                </div>
            </div>
        </main>
       
    </div>
</body>
</html>