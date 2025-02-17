<?php
session_start();
require_once '../config/config.php';
require_once '../includes/db.php';
require_once '../models/Question.php';
require_once '../models/EmployeeAnswer.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

$question = new Question($db);
$employeeAnswer = new EmployeeAnswer($db);
$assessment_id = intval($_GET['assessment_id']);
$assessment = $employeeAnswer->getAssessment($assessment_id);
$hrquestions = $employeeAnswer->getHrReview($assessment_id);


if($_SESSION['role'] != 'Admin' && $_SESSION['user_id'] != $assessment['user_id'] && $_SESSION['user_id'] != $assessment['supervisor_id'])
{
    echo 'Assessment details not available.';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Questions</title>
</head>

<body>
    <div class="container">
        <?php include('header.php');?>
        <h3 class="my-3">Assessment Details</h3>
        <h3 class="alert alert-info"><?= $assessment['user']; ?> - <?= date('M, Y', strtotime($assessment['year'].'-'.$assessment['month'])) ?></h3>
        <div class="card">
            <div class="card-header"><span class="fw-bold">Assessment Questions</span></div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Question</th>
                            <th>Employee</th>
                            <th>Supervisor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assessment['questions'] as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['question']); ?></td>
                            <td><?= $row['self_rating'];?></td>
                            <td><?= ($row['supervisor_rating'])?$row['supervisor_rating']:'<span class="text-danger">Pending</span>';?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card my-3">
            <div class="card-header"><span class="fw-bold">HR Questions</span></div>
            <div class="card-body">
                <table class="table table-striped mb-3">
                    <thead>
                        <tr>
                            <th>Question</th>
                            <th>Rating</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($hrquestions as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['question']); ?></td>
                            <td><?= $row['rating'];?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?= (count($hrquestions) <= 0)?'<span class="text-danger">Pending HR ratings</span>':'';?>
            </div>
        </div>
        <div class="card">
            <div class="card-header">Notes</div>
            <div class="card-body">
            <?php 
                if($assessment['self_note'])
                    $feedback .= '<div class="d-block"><span class="badge alert alert-info p-1 me-2 mb-2">Employee</span><br>'.$assessment['self_note'].'<hr class="my-3">';
                
                if($assessment['supervisor_note'])
                    $feedback .= '<div class="d-block"><span class="badge alert alert-success me-2 p-1 mb-0">Supervisor</span><br>'.$assessment['supervisor_note'].'<hr class="my-3">';
                
                if($assessment['hr_note'])
                    $feedback .= '<div class="d-block"><span class="badge alert alert-primary p-1 me-2 mb-2">HR</span><br>'.$assessment['hr_note'];
                echo $feedback;
                ?>
            </div>
        </div>
    </div>
</body>

</html>