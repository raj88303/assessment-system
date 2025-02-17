<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

require_once '../config/config.php';
require_once '../includes/db.php';
require_once '../models/EmployeeAnswer.php';
require_once '../models/Question.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'Employee' && $_SESSION['role'] != 'Supervisor' && $_SESSION['role'] != 'HR')) {
    header("Location: login.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

$employeeAnswer = new EmployeeAnswer($db);
$question = new Question($db);

$month = $_GET['month'];
$year = $_GET['year'];

// Check if the employee has already submitted the form for this period
if ($employeeAnswer->checkAssessmentExists($user_id, $month, $year)) {
    $_SESSION['error'] = "You have already submitted your assessment for this month.";
} else {
    
    $questions = $question->getAssessmentQuestion();
    
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        
        // Calculate the total score from the submitted ratings
        foreach ($_POST['ratings'] as $question_id => $rating) {
            $self_score += $rating;
        }
        $self_score = $self_score / count($_POST['ratings']);
        // Create or update the assessment entry with the calculated total score
        $note = $_POST['note'];
        
        $assessment_id = $employeeAnswer->createAssessment($user_id, $self_score, $month, $year, $note);
        if($assessment_id > 0)
        {
            foreach ($_POST['ratings'] as $question_id => $rating) {
                $employeeAnswer->createDetail($assessment_id, $question_id, $rating);
            }
            $_SESSION['success'] = "Assessment submitted successfully!";
            header("Location: dashboard.php");
            exit;
        }
        else
            $_SESSION['error'] = "Error submitting assessment at this time.";
            
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Assessment</title>
</head>

<body>
    <div class="container">
        <?php include('header.php');?>
        <h3 class="my-3">Fill Assessment for <?php echo date('M, Y', strtotime($year.'-'.$month)); ?></h3>
        <?php if (isset($_SESSION['success'])): ?>
            <p class="text-success"><?php echo $_SESSION['success']; ?></p>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <p class="text-danger"><?php echo $_SESSION['error']; ?></p>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if(count($questions) > 0):?>
        <div class="card">
            <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="month" value="<?= $month; ?>">
                <input type="hidden" name="year" value="<?= $year; ?>">
                <ul class="list-group list-group-flush">
                    <?php foreach($questions as $row): 
                    $question_id = intval($row['question_id']);
                    ?>
                    <li id="question_<?= $question_id; ?>" class="list-group-item py-5">
                        <label class="mb-2 fs-5" for="question_<?= $question_id; ?>"><b><?php echo $row['question_text']; ?></b></label>    
                        <div class="d-flex gap-2">
                            <div class="form-check">
                                <input class="form-check-input" id="rate_<?= $question_id; ?>_1" type="radio" name="ratings[<?= $question_id; ?>]" value="1" checked> 
                                <label class="form-check-label" for="rate_<?= $question_id; ?>_1">1 - Needs Improvement</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" id="rate_<?= $question_id; ?>_2" type="radio" name="ratings[<?= $question_id; ?>]" value="2"> 
                                <label class="form-check-label" for="rate_<?= $question_id; ?>_2">2 - Below Expectations</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" id="rate_<?= $question_id; ?>_3" type="radio" name="ratings[<?= $question_id; ?>]" value="3"> 
                                <label class="form-check-label" for="rate_<?= $question_id; ?>_3">3 - Meeting Expectations</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" id="rate_<?= $question_id; ?>_4" type="radio" name="ratings[<?= $question_id; ?>]" value="4"> 
                                <label class="form-check-label" for="rate_<?= $question_id; ?>_4">4 - Exceeding Expectations</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" id="rate_<?= $question_id; ?>_5" type="radio" name="ratings[<?= $question_id; ?>]" value="5"> 
                                <label class="form-check-label" for="rate_<?= $question_id; ?>_5">5 - Outstanding</label>
                            </div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                    <label class="form-label fw-bold" for="note">Additional Note</label>
                    <textarea class="form-control" name="note" id="note" rows="8" placeholder="Enter any additional details you would like to include that the questions do not cover."></textarea>
                    <input type="submit" value="Submit" class="my-3 btn btn-secondary">
            </form>
            </div>
        </div>
        <?php else: ?>
            <p>No questions available for now.</p>
        <?php endif; ?>
    </div>
</body>

</html>