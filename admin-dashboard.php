<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CareCompass</title>
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

        /* ======= Main Content ======= */
        .main-content {
            padding: 2rem 2rem 4rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        /* Data Tables */
        .data-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        th {
            background: #f8fafc;
            font-weight: 600;
        }

        .action-btns {
            display: flex;
            gap: 0.5rem;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background: white;
            width: 90%;
            max-width: 600px;
            padding: 2rem;
            border-radius: 12px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-top: 1.5rem;
        }

        @media (max-width: 768px) {
            .dashboard-container {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            table thead {
                display: none;
            }
            
            td {
                display: block;
                text-align: right;
            }
            
            td::before {
                content: attr(data-label);
                float: left;
                font-weight: 600;
                color: var(--primary);
            }
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
                <a href="#" class="nav-item active">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="#" class="nav-item">
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
            <!-- Quick Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Patients</h3>
                    <p class="stat-number">1,245</p>
                </div>
                <div class="stat-card">
                    <h3>Active Doctors</h3>
                    <p class="stat-number">45</p>
                </div>
                <div class="stat-card">
                    <h3>Today's Appointments</h3>
                    <p class="stat-number">89</p>
                </div>
                <div class="stat-card">
                    <h3>Pending Payments</h3>
                    <p class="stat-number">23</p>
                </div>
            </div>

            <!-- Doctors Table -->
            <div class="data-table">
                <div class="table-header">
                    <h3>Recent Doctors</h3>
                    <button class="btn btn-primary" onclick="openDoctorModal()">
                        <i class="fas fa-plus"></i> Add Doctor
                    </button>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Specialty</th>
                            <th>Hospital Branch</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td data-label="Name">Dr. Sarah Johnson</td>
                            <td data-label="Specialty">Cardiology</td>
                            <td data-label="Branch">City General</td>
                            <td data-label="Status"><span class="status-active">Active</span></td>
                            <td data-label="Actions">
                                <div class="action-btns">
                                    <button class="btn btn-icon edit-btn">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-icon danger-btn">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <!-- More rows -->
                    </tbody>
                </table>
            </div>

            <!-- Patients Table -->
            <div class="data-table">
                <h3>Recent Patients</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Age</th>
                            <th>Gender</th>
                            <th>Last Visit</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td data-label="Name">John Doe</td>
                            <td data-label="Age">35</td>
                            <td data-label="Gender">Male</td>
                            <td data-label="Last Visit">2023-11-05</td>
                            <td data-label="Actions">
                                <button class="btn btn-icon view-btn">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        <!-- More rows -->
                    </tbody>
                </table>
            </div>

            <!-- Add Doctor Modal -->
            <div class="modal" id="doctorModal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Add New Doctor</h2>
                        <button class="btn btn-icon" onclick="closeDoctorModal()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <form class="form-grid">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" required>
                        </div>
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" required>
                        </div>
                        <div class="form-group">
                            <label>Specialty</label>
                            <select required>
                                <option>Cardiology</option>
                                <option>Pediatrics</option>
                                <!-- More options -->
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Hospital Branch</label>
                            <input type="text" required>
                        </div>
                        <div class="form-group">
                            <label>Contact Number</label>
                            <input type="tel" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" required>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" onclick="closeDoctorModal()">
                                Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
                                Save Doctor
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Modal Handling
        function openDoctorModal() {
            document.getElementById('doctorModal').style.display = 'flex';
        }

        function closeDoctorModal() {
            document.getElementById('doctorModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>