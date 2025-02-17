<?php
session_start();
require_once '../config/config.php';
require_once '../includes/db.php';
require_once '../models/Assessment.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'HR') {
    header("Location: login.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

$assessment = new Assessment($db);
$results = $assessment->getAllAssessments();

?>

<!DOCTYPE html>
<html>

<head>
    <title>Advanced Reporting</title>
    <link rel="stylesheet" href="../styles/main.css">
</head>

<body>
    <div class="container">
        <?php include('header.php');?>
        <h1>Advanced Reporting</h1>
        <table>
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Period</th>
                    <th>Total Score</th>
                    <th>Supervisor Score</th>
                    <th>HR Score</th>
                </tr>
            </thead>
            <tbody>
                <?php
                while ($row = $results->fetch(PDO::FETCH_ASSOC)) {
                    echo "<tr>";
                    echo "<td>" . $row['name'] . "</td>";
                    echo "<td>" . $row['period'] . "</td>";
                    echo "<td>" . $row['total_score'] . "</td>";
                    echo "<td>" . $row['supervisor_score'] . "</td>";
                    echo "<td>" . $row['hr_score'] . "</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>

</html>