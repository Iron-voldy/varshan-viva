<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $record_id = $_GET['id'];
    Database::iud("DELETE FROM MedicalRecords WHERE record_id = $record_id");
}

header("Location: doctor-medical-reports.php");
exit();
?>
