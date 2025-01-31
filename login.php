<?php
session_start();
include 'connection.php'; // Include database connection

// Ensure database connection is established
Database::setUpConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve user input and sanitize
    $username = mysqli_real_escape_string(Database::$connection, $_POST['username']);
    $password = mysqli_real_escape_string(Database::$connection, $_POST['password']);

    // Query the database for user credentials
    $query = "SELECT * FROM users WHERE username='$username' OR email='$username'";
    $result = Database::search($query);

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verify password
        if (password_verify($password, $user['password'])) {
            // Store user details in session
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['status'] = $user['status'];

            // Check user status (prevent inactive/suspended users from logging in)
            if ($user['status'] === 'Inactive' || $user['status'] === 'Suspended') {
                echo "<script>alert('Your account is currently inactive or suspended. Please contact the hospital administration.'); window.location.href = 'login.php';</script>";
                exit();
            }

            // Redirect based on role
            if ($user['role_id'] == 3) {
                header('Location: patient-dashboard.html'); // Redirect Patient to Dashboard
                exit();
            } else {
                echo "<script>alert('Invalid login for patient account.'); window.location.href = 'login.html';</script>";
                exit();
            }
        } else {
            echo "<script>alert('Incorrect password. Please try again.'); window.location.href = 'login.html';</script>";
            exit();
        }
    } else {
        echo "<script>alert('User not found. Please check your username/email or register.'); window.location.href = 'login.html';</script>";
        exit();
    }
} else {
    // Redirect to login page if accessed directly
    header('Location: login.html');
    exit();
}
?>
