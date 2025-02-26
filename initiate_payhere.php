<?php
session_start();
include 'connection.php';


if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];


$amount = "1000";
$appointment_id = "101";

//$user_query = "SELECT first_name, last_name, email, phone, address, city, country FROM Users WHERE user_id = ?";/
//$stmt = Database::$connection->prepare($user_query);
//$stmt->bind_param("i", $user_id);
//$stmt->execute();
//$user_result = $stmt->get_result();
//$user = $user_result->fetch_assoc();


$order_id = "PAY_" . $appointment_id . "_" . time();


$merchant_id = "1221688";
$merchant_secret = "MTE2NDA2OTQ3MjI3MDA1MzY4Mzg3NTgxMjYyMzYzNjQ0OTk1NzI4";
$currency = "LKR";


$hash = strtoupper(
    md5(
        $merchant_id .
        $order_id .
        number_format($amount, 2, '.', '') .
        $currency .
        strtoupper(md5($merchant_secret))
    )
);


$payment = [
    "sandbox" => true, 
    "merchant_id" => $merchant_id,
    "return_url" => "http://localhost/viva/payment-success.php",
    "cancel_url" => "http://localhost/viva/patient-payment.php",
    "notify_url" => "http://localhost/viva/notify.php",
    "order_id" => $order_id,
    "items" => "Appointment Payment",
    "amount" => number_format($amount, 2, '.', ''),
    "currency" => $currency,
    "hash" => $hash,
    "first_name" => "Test",
    "last_name" => "User",
    "email" =>"test@example.com",
    "phone" => "0712767300",
    "address" => "No.1, Test Road",
    "city" => "Colombo",
    "country" => "Sri Lanka",
    "delivery_address" => "No.1, Test Road",
    "delivery_city" => "Colombo",
    "delivery_country" => "Sri Lanka",
    "custom_1" => "",
    "custom_2" => ""
];

echo json_encode($payment);
?>