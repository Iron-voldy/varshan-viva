<?php
// Include the database connection file
include 'connection.php';

// Ensure database connection is established
Database::setUpConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form input values and sanitize them
    $first_name = mysqli_real_escape_string(Database::$connection, $_POST['first_name']);
    $last_name = mysqli_real_escape_string(Database::$connection, $_POST['last_name']);
    $username = mysqli_real_escape_string(Database::$connection, $_POST['username']);
    $email = mysqli_real_escape_string(Database::$connection, $_POST['email']);
    $phone = mysqli_real_escape_string(Database::$connection, $_POST['phone']);
    $dob = mysqli_real_escape_string(Database::$connection, $_POST['dob']);
    $password = mysqli_real_escape_string(Database::$connection, $_POST['password']);
    $hashed_password = password_hash($password, PASSWORD_DEFAULT); // Secure password hashing

    // Check if the email already exists
    $email_check_query = "SELECT * FROM Users WHERE email = '$email'";
    $result = Database::search($email_check_query);

    if ($result->num_rows > 0) {
        echo "<script>alert('Email already exists. Please use a different email.'); window.location.href = 'register.php';</script>";
        exit();
    }

    // Insert into the Users table ONLY
    $insert_user_query = "
        INSERT INTO users (username, password, email, phone, role_id, status, created_at, updated_at) 
        VALUES ('$username', '$hashed_password', '$email', '$phone', 3, 'Active', NOW(), NOW())";
    Database::iud($insert_user_query);

    // Success message
    echo "<script>alert('Registration successful! You can now log in.'); window.location.href = 'login.html';</script>";
    exit();
} else {
    header('Location: register.html');
    exit();
}

?>
