<?php
session_start();
require_once '../config/config.php';
require_once '../includes/db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];

    $database = new Database();
    $db = $database->getConnection();
    $user = new User($db);
    $user->email = $email;

    if ($user->sendPasswordReset()) {
        $message = 'If the email is registered, a reset link has been sent to it.';
    } else {
        $message = 'Unable to send reset link.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
</head>

<body>
    <h1>Forgot Password</h1>
    <form method="POST" action="forgot_password.php">
        <label for="email">Enter your email:</label><br>
        <input type="email" id="email" name="email" required><br><br>
        <input type="submit" value="Submit">
    </form>
    <?php if ($message) echo "<p>$message</p>"; ?>
</body>

</html>