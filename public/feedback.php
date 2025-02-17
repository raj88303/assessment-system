<?php
session_start();
require_once '../config/config.php';
require_once '../includes/db.php';
require_once '../models/Feedback.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

$feedback = new Feedback($db);
$feedbackResults = $feedback->read($_GET['assessment_id']);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback</title>
    <link rel="stylesheet" href="../styles/main.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 80%;
            margin: 50px auto;
            background: #fff;
            padding: 20px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        h1 {
            text-align: center;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table,
        th,
        td {
            border: 1px solid #ddd;
        }

        th,
        td {
            padding: 12px;
            text-align: left;
        }

        th {
            background-color: #f8f8f8;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        td {
            vertical-align: top;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            font-size: 16px;
            color: #007bff;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .back-link:hover {
            color: #0056b3;
        }
    </style>
</head>

<body>
    <div class="container">
        <?php include('header.php');?>
        <h1>Feedback</h1>
        <table>
            <thead>
                <tr>
                    <th>Feedback By</th>
                    <th>Comments</th>
                </tr>
            </thead>
            <tbody>
                <?php
                while ($row = $feedbackResults->fetch(PDO::FETCH_ASSOC)) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['role']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['comments']) . "</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
        <!-- Back to Dashboard Link -->
        <a href="dashboard.php" class="back-link">Back to Dashboard</a>
    </div>
</body>

</html>