<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

require_once '../config/config.php';
require_once '../includes/db.php';
require_once '../models/EmployeeAnswer.php';
require_once '../models/Question.php';
require_once '../models/User.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'Supervisor' && $_SESSION['role'] != 'Admin' && $_SESSION['role']!='HR')) {
    header("Location: login.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();
$employeeAnswer = new EmployeeAnswer($db);

//$question = new Question($db);
//$userModel = new User($db);
$assessment_id = intval($_GET['assessment_id']);
$assessment = $employeeAnswer->getAssessmentByID($assessment_id);

if(!$employeeAnswer->checkSupervisor($assessment['user_id'])){
    echo 'You are not the supervisor for this assessment. <a href="./">Go back</a>';
    exit;
}

/* Get the period from the URL or default to the current month if not set
$period = isset($_GET['period']) && !empty($_GET['period']) ? $_GET['period'] . '-01' : date('Y-m-01');
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($user_id === 0) {
    echo "Invalid user ID.";
    exit;
}

// Fetch the user's designation
$user = $userModel->getUserById($user_id);
$designation = $user['designation'];
*/

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if($employeeAnswer->updateSupervisorScore($assessment_id))
    {
        $_SESSION['success'] = "Ratings and feedback updated successfully!";
        header('Location: supervisor_review.php?assessment_id='.$assessment_id);
        exit;
    }
    else
        $_SESSION['error'] = 'Error udpating the details.';
}

// Fetch the questions specific to this user's designation and period
//$questions = $question->readByDesignationAndPeriod($designation, $period);

// Fetch the existing feedback
//$existing_feedback = $employeeAnswer->getGeneralFeedback($user_id, $period, 'Supervisor');
//$supervisor_feedback = 

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supervisor Review</title>
</head>

<body>
    <div class="container">
        <?php include('header.php');?>
        <h3 class="my-3">Supervisor Review for <?= htmlspecialchars($assessment['user']); ?> (<?php echo date('F Y', strtotime($assessment['year'] . '-' . $assessment['month'])); ?>)</h3>

        <?php if (isset($_SESSION['success'])): ?>
            <p class="text-success"><?php echo $_SESSION['success']; ?></p>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <p class="text-danger"><?php echo $_SESSION['error']; ?></p>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <form method="post" action="">
            <div class="table-container">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Question</th>
                            <th>Employee Rating</th>
                            <th>Supervisor Rating</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($assessment['questions']) > 0): ?>
                            <?php foreach ($assessment['questions'] as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['question']); ?></td>
                                    <td><?= $row['self_rating'];?></td>
                                    <td>
                                        <select id="question_<?php echo $row['criterion']; ?>" name="ratings[<?php echo $row['detail_id']; ?>]" class="form-select w-auto">
                                            <?php if($assessment['supervisor_score'] > 0):?>
                                            <option value="1" <?php if ($row['supervisor_rating'] == 1) echo "selected"; ?>>1 - Needs Improvement</option>
                                            <option value="2" <?php if ($row['supervisor_rating'] == 2) echo "selected"; ?>>2 - Below Expectations</option>
                                            <option value="3" <?php if ($row['supervisor_rating'] == 3) echo "selected"; ?>>3 - Meeting Expectations</option>
                                            <option value="4" <?php if ($row['supervisor_rating'] == 4) echo "selected"; ?>>4 - Exceeding Expectations</option>
                                            <option value="5" <?php if ($row['supervisor_rating'] == 5) echo "selected"; ?>>5 - Outstanding</option>
                                            <?php else:?>
                                            <option value="1" <?php if ($row['self_rating'] == 1) echo "selected"; ?>>1 - Needs Improvement</option>
                                            <option value="2" <?php if ($row['self_rating'] == 2) echo "selected"; ?>>2 - Below Expectations</option>
                                            <option value="3" <?php if ($row['self_rating'] == 3) echo "selected"; ?>>3 - Meeting Expectations</option>
                                            <option value="4" <?php if ($row['self_rating'] == 4) echo "selected"; ?>>4 - Exceeding Expectations</option>
                                            <option value="5" <?php if ($row['self_rating'] == 5) echo "selected"; ?>>5 - Outstanding</option>
                                            <?php endif;?>
                                        </select>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2">No questions available for this user's designation and period.</td>
                            <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card my-3">
                <div class="card-header bg-danger text-white">Employee Note</div>
                <div class="card-body text-danger">
                    <?= $assessment['self_note']; ?>
                </div>
            </div>
            <!-- Feedback Textbox -->
            <div class="feedback-container mt-3">
                <label for="feedback">Supervisor Feedback:</label>
                <textarea class="form-control mt-2" id="feedback" name="supervisor_note"><?php echo htmlspecialchars($assessment['supervisor_note']); ?></textarea>
            </div>

            <input type="submit" value="Submit" class="btn btn-secondary my-3">
        </form>
    </div>
</body>

</html>