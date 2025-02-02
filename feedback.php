<?php
session_start();
include 'connection.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle Feedback Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $user_id = $_SESSION['user_id'];
    $feedback_type = mysqli_real_escape_string(Database::$connection, $_POST['feedback_type']);
    $feedback_text = mysqli_real_escape_string(Database::$connection, $_POST['feedback_text']);

    Database::iud("
        INSERT INTO Feedbacks (user_id, feedback_type_id, feedback_text, submitted_at, is_resolved)
        VALUES ($user_id, '$feedback_type', '$feedback_text', NOW(), 0)
    ");

    $_SESSION['message'] = "Feedback submitted successfully!";
    header("Location: feedback.php");
    exit();
}

// Fetch Previous Feedback
$feedbacks = Database::search("
    SELECT f.*, ft.feedback_name, u.username
    FROM Feedbacks f
    JOIN FeedbackTypes ft ON f.feedback_type_id = ft.feedback_type_id
    JOIN Users u ON f.user_id = u.user_id
    WHERE f.user_id = {$_SESSION['user_id']}
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Submit Feedback</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
        .container { max-width: 700px; margin: auto; background: white; padding: 20px; border-radius: 8px; }
        .form-group { margin-bottom: 15px; }
        label { font-weight: bold; }
        .btn { padding: 10px; border: none; cursor: pointer; width: 100%; margin-top: 10px; }
        .btn-primary { background: #2563eb; color: white; }
        .btn-danger { background: #ef4444; color: white; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ddd; }
        .alert { color: green; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Submit Feedback</h2>
        <?php if (isset($_SESSION['message'])): ?>
            <p class="alert"><?= $_SESSION['message']; unset($_SESSION['message']); ?></p>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Feedback Type:</label>
                <select name="feedback_type" required>
                    <option value="1">Complaint</option>
                    <option value="2">Suggestion</option>
                    <option value="3">Query</option>
                </select>
            </div>
            <div class="form-group">
                <label>Your Feedback:</label>
                <textarea name="feedback_text" rows="4" required></textarea>
            </div>
            <button type="submit" name="submit_feedback" class="btn btn-primary">Submit</button>
        </form>

        <h3>Previous Feedback</h3>
        <table>
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Feedback</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($feedbacks as $feedback): ?>
                <tr>
                    <td><?= $feedback['feedback_name'] ?></td>
                    <td><?= $feedback['feedback_text'] ?></td>
                    <td><?= $feedback['is_resolved'] ? "Resolved" : "Pending" ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
