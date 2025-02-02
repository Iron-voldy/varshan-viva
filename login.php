<?php
session_start();
include 'connection.php'; // Database connection

Database::setUpConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mysqli_real_escape_string(Database::$connection, $_POST['username']);
    $password = mysqli_real_escape_string(Database::$connection, $_POST['password']);

    // Fetch user by username or email
    $query = "SELECT * FROM Users WHERE username='$username' OR email='$username'";
    $result = Database::search($query);

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // ✅ Verify hashed password
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['status'] = $user['status'];

            // Prevent inactive/suspended users from logging in
            if ($user['status'] === 'Inactive' || $user['status'] === 'Suspended') {
                echo "<script>alert('Your account is inactive or suspended. Contact admin.'); window.location.href = 'admin-login.php';</script>";
                exit();
            }

            // ✅ Role-based redirection
            if ($user['role_id'] == 1) {
                header('Location: admin-dashboard.php'); // ✅ Admin Dashboard
                exit();
            } elseif ($user['role_id'] == 2) {
                header('Location: doctor-dashboard.php'); // Doctor Dashboard
                exit();
            } elseif ($user['role_id'] == 3) {
                header('Location: patient-dashboard.php'); // Patient Dashboard
                exit();
            } else {
                echo "<script>alert('Invalid login credentials.'); window.location.href = 'login.html';</script>";
                exit();
            }
        } else {
            echo "<script>alert('Incorrect password. Try again.'); window.location.href = 'login.html';</script>";
            exit();
        }
    } else {
        echo "<script>alert('User not found.'); window.location.href = 'login.html';</script>";
        exit();
    }
} else {
    header('Location: login.html'); // Redirect to login page if accessed directly
    exit();
}
?>
