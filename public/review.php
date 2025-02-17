<?php
session_start();
require_once '../config/config.php';
require_once '../includes/db.php';
require_once '../models/EmployeeAnswer.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Supervisor') {
    header("Location: login.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

$employeeAnswer = new EmployeeAnswer($db);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id'];
    $period = $_POST['period'];

    foreach ($_POST['ratings'] as $question_id => $rating) {
        $query = "UPDATE employee_answers SET rating = :rating WHERE user_id = :user_id AND question_id = :question_id AND period = :period";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':rating', $rating);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':question_id', $question_id);
        $stmt->bindParam(':period', $period);
        $stmt->execute();
    }
    $success = "Ratings updated successfully!";
}

// Get user_id and period from GET parameters or other means
$user_id = $_GET['user_id'];
$period = date('Y-m-01');

$results = $employeeAnswer->readByUserAndPeriod($user_id, $period);
?>

<!DOCTYPE html>
<html>

<head>
    <title>Review Assessment</title>
    <link rel="stylesheet" href="../styles/main.css">
</head>

<body>
    <div class="container">
        <h1>Review Assessment</h1>
        <form method="post" action="review.php">
            <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
            <input type="hidden" name="period" value="<?php echo $period; ?>">
            <?php
            while ($row = $results->fetch(PDO::FETCH_ASSOC)) {
                echo "<label for='question_" . $row['question_id'] . "'>" . $row['question_text'] . ":</label>";
                echo "<select id='question_" . $row['question_id'] . "' name='ratings[" . $row['question_id'] . "]'>";
                echo "<option value='1'" . ($row['rating'] == 1 ? " selected" : "") . ">1 - Needs Improvement</option>";
                echo "<option value='2'" . ($row['rating'] == 2 ? " selected" : "") . ">2 - Below Expectations</option>";
                echo "<option value='3'" . ($row['rating'] == 3 ? " selected" : "") . ">3 - Meeting Expectations</option>";
                echo "<option value='4'" . ($row['rating'] == 4 ? " selected" : "") . ">4 - Exceeding Expectations</option>";
                echo "<option value='5'" . ($row['rating'] == 5 ? " selected" : "") . ">5 - Outstanding</option>";
                echo "</select>";
            }
            ?>
            <input type="submit" value="Update Ratings">
            <?php if (isset($success)) echo "<p class='success'>$success</p>"; ?>
        </form>
    </div>
</body>

</html>