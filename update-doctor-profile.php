<?php
session_start();
include 'connection.php';

Database::setUpConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $contact_number = $_POST['contact_number'];
    $hospital_branch = $_POST['hospital_branch'];
    $qualifications = $_POST['qualifications'];

    $query = "UPDATE Doctors SET first_name=?, last_name=?, contact_number=?, hospital_branch=?, qualifications=? WHERE user_id=?";
    $stmt = Database::$connection->prepare($query);
    $stmt->bind_param("sssssi", $first_name, $last_name, $contact_number, $hospital_branch, $qualifications, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false]);
    }
}
?>
