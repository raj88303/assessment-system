<?php
session_start();
require_once '../config/config.php';
require_once '../includes/db.php';
require_once '../models/User.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $database = new Database();
    $db = $database->getConnection();

    $user = new User($db);
    $user->email = $_POST['email'];
    $user->password = $_POST['password'];

    if ($user->login()) {
        $_SESSION['user_id'] = $user->user_id;
        $_SESSION['name'] = $user->name;
        $_SESSION['role'] = $user->role;
        $_SESSION['department'] = $user->department;
        header("Location: index.php");
    } else {
        $error = "Invalid login credentials.";
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Login</title>
    <link rel="stylesheet" href="../styles/main.css">
</head>

<body>
    <div class="container" style="max-width:400px;">
        <h1>Login</h1>
        <form method="post" action="login.php">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="submit" value="Login">
            <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        </form>
    </div>
</body>

</html>