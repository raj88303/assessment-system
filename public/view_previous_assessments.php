<?php
session_start();
require_once '../config/config.php';
require_once '../includes/db.php';
require_once '../models/EmployeeAnswer.php';
require_once '../models/Question.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Employee') {
    header("Location: login.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

$employeeAnswer = new EmployeeAnswer($db);
$question = new Question($db);

$user_id = $_SESSION['user_id'];
$selectedPeriod = isset($_GET['period']) ? $_GET['period'] : date('Y-m-01');

// Fetch all periods where the employee has completed an assessment
$completedPeriods = $employeeAnswer->getCompletedPeriods($user_id);

// Fetch the assessment details for the selected period
$questions = $question->readByDesignationAndPeriod($_SESSION['designation'], $selectedPeriod);
$ratings = $employeeAnswer->getRatingsForEmployee($user_id, $selectedPeriod);
$supervisorRatings = $employeeAnswer->getSupervisorRatingsForUser($user_id, $selectedPeriod);

// Fetch the HR score for the selected period
$hrScore = $employeeAnswer->getHrScoreForUser($user_id, $selectedPeriod);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Previous Assessments</title>
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

        h2 {
            color: #555;
            margin-top: 30px;
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
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #f8f8f8;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .back-link:hover {
            background-color: #0056b3;
        }

        .month-selector {
            margin-bottom: 20px;
            text-align: center;
        }

        .month-selector select {
            padding: 10px;
            font-size: 16px;
        }

        .hr-score {
            margin-top: 20px;
            font-weight: bold;
            color: #333;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="container">
        <?php include('header.php');?>
        <h1>Previous Assessment Details</h1>

        <!-- Month/Year Selector -->
        <div class="month-selector">
            <form method="get" action="view_previous_assessments.php">
                <label for="period">Select Period:</label>
                <select id="period" name="period" onchange="this.form.submit()">
                    <?php
                    foreach ($completedPeriods as $period) {
                        $formattedPeriod = date("F Y", strtotime($period['period']));
                        echo "<option value='{$period['period']}'" . ($period['period'] == $selectedPeriod ? " selected" : "") . ">$formattedPeriod</option>";
                    }
                    ?>
                </select>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Question</th>
                    <th>Your Rating</th>
                    <th>Supervisor Rating</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($questions->rowCount() > 0) {
                    while ($row = $questions->fetch(PDO::FETCH_ASSOC)) {
                        $question_id = $row['question_id'];
                        $employeeRating = $ratings[$question_id] ?? 'Not Rated';
                        $supervisorRating = $supervisorRatings[$question_id] ?? 'Not Rated';

                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['question_text']) . "</td>";
                        echo "<td>" . htmlspecialchars($employeeRating) . "</td>";
                        echo "<td>" . htmlspecialchars($supervisorRating) . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='3' class='message'>No questions found for this period.</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <div class="hr-score">
            <p>HR Score: <?php echo htmlspecialchars($hrScore ?? 'Not Scored'); ?></p>
        </div>

        <a href="dashboard.php" class="back-link">Back to Dashboard</a>
    </div>
</body>

</html>