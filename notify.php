<?php
include 'connection.php';


$merchant_id = $_POST['merchant_id'];
$order_id = $_POST['order_id'];
$payhere_amount = $_POST['payhere_amount'];
$payhere_currency = $_POST['payhere_currency'];
$status_code = $_POST['status_code'];
$md5sig = $_POST['md5sig'];


$merchant_secret = "MTE2NDA2OTQ3MjI3MDA1MzY4Mzg3NTgxMjYyMzYzNjQ0OTk1NzI4";


$local_md5sig = strtoupper(
    md5(
        $merchant_id .
        $order_id .
        $payhere_amount .
        $payhere_currency .
        $status_code .
        strtoupper(md5($merchant_secret))
    )
);


if ($local_md5sig === $md5sig) {
    if ($status_code == 2) {
    
        $parts = explode('_', $order_id);
        if (count($parts) >= 2) {
            $appointment_id = $parts[1];
            $amount = $payhere_amount;

          
            $payment_insert_query = "INSERT INTO Payments (user_id, amount, payment_method, payment_status, appointment_id)
                                    VALUES (?, ?, 'PayHere', 'Completed', ?)
                                    ON DUPLICATE KEY UPDATE payment_status = 'Completed'";
            $stmt = Database::$connection->prepare($payment_insert_query);
            $stmt->bind_param("ids", $user_id, $amount, $appointment_id);
            $stmt->execute();
        }
    }
} else {

    http_response_code(400);
}
?>