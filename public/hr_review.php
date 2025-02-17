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

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'HR') {
    header("Location: login.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

$employeeAnswer = new EmployeeAnswer($db);
$id = intval($_GET['assessment_id']);
$assessment = $employeeAnswer->getAssessment($id);
$hrQuestion = $employeeAnswer->getHrQuestions();
$hrReview = $employeeAnswer->getHrReview($id);
$update = 0;
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if(count($hrReview) > 0)
    {
        if($employeeAnswer->updateHrReview($id))
            $update = 1;
    }
    else
    {
        if($employeeAnswer->setHrReview($id))
            $update = 1;
    }
    if($update)
    {
        $_SESSION['success'] = "HR ratings and feedback updated successfully!";
        header("Location: hr_review.php?assessment_id=".$id);
        exit;
    }
    else
        $_SESSION['error'] = 'Error adding the review';
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Review</title>
</head>

<body>
    <div class="container">
        <?php include('header.php');?>
        <h3 class="my-3">HR Review for <?php echo htmlspecialchars($assessment['user']); ?> (<?php echo date('F Y', strtotime($assessment['year'].'-'.$assessment['month'])); ?>)</h3>
        <?php if (isset($_SESSION['success'])): ?>
            <p class="text-success"><?php echo $_SESSION['success']; ?></p>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <p class="text-error"><?php echo $_SESSION['error']; ?></p>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        <div class="card">
            <div class="card-body">
                <form method="post" action="">
                    <div class="table-container">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Criteria</th>
                                    <th>Rating</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                                foreach($hrQuestion as $row)
                                {
                                    ?>
                                    <tr>
                                    <td><?= $row['question'];?></td>
                                    <td>
                                        <select name="question[<?= $row['id_hr_question'];?>]" class="form-select w-auto">
                                            <option value="5" <?= ($hrReview[$row['id_hr_question']]['rating'] == 5)?'selected':'';?>>5 - Excellent</option>
                                            <option value="4" <?= ($hrReview[$row['id_hr_question']]['rating'] == 4)?'selected':'';?>>4 - Good</option>
                                            <option value="3" <?= ($hrReview[$row['id_hr_question']]['rating'] == 3)?'selected':'';?>>3 - Satisfactory</option>
                                            <option value="2" <?= ($hrReview[$row['id_hr_question']]['rating'] == 2)?'selected':'';?>>2 - Below Expectations</option>
                                            <option value="1" <?= ($hrReview[$row['id_hr_question']]['rating'] == 1)?'selected':'';?>>1 - Needs Improvement</option>
                                        </select>
                                    </td>
                                    </tr>
                            <?php
                                }
                            ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Feedback Textbox -->
                    <div class="feedback-container mt-3">
                        <label for="feedback" class="form-label mb-2">HR Feedback:</label>
                        <textarea id="feedback" name="feedback" class="form-control"><?= $assessment['hr_note']; ?></textarea>
                    </div>

                    <input type="submit" value="Submit" class="btn btn-secondary my-3">
                </form>
            </div>
        </div>
    </div>
</body>

</html>