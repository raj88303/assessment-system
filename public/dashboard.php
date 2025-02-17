<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

require_once '../config/config.php';
require_once '../includes/db.php';
require_once '../models/EmployeeAnswer.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'Employee' && $_SESSION['role'] != 'Supervisor' && $_SESSION['role'] != 'HR')) {
    header("Location: login.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

$employeeAnswer = new EmployeeAnswer($db);
$user_id = intval($_SESSION['user_id']);


$year = $employeeAnswer->baseYear;
$selmonth = $_GET['month'];
if($selmonth == '')
    $selmonth = date('n');
$selyear = $_GET['year'];
if($selyear == '')
    $selyear = date('Y');

$months = [
    1 => "January",
    2 => "February",
    3 => "March",
    4 => "April",
    5 => "May",
    6 => "June",
    7 => "July",
    8 => "August",
    9 => "September",
    10 => "October",
    11 => "November",
    12 => "December"
];


// Fetch all completed assessments for the user, ordered by period
$results = $employeeAnswer->readByUser($user_id);

$empResults = $employeeAnswer->getAssessmentByPeriod($selmonth, $selyear);

$fillassessment = $employeeAnswer->getFillAssessment($user_id);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
</head>

<body>
    <div class="container">
        <?php include('header.php');?>
        <h3 class="my-3">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></h3>
        <div class="text-success"><?php echo $_SESSION['success']; $_SESSION['success'] = '';?></div>
        <div class="text-error"><?php echo $_SESSION['error']; $_SESSION['error'] = '';?></div>
        <!-- Show Fill Assessment Button for the Next Available Period -->
        <?php if(count($fillassessment) > 0):?>
        <p class="fw-bold">Fill Assessment for : 
        <?php foreach($fillassessment as $item):?>
            <a class="btn btn-outline-danger btn-sm me-2" href="assessment.php?month=<?= $item['month'];?>&year=<?= $item['year'];?>"><?= date('M, Y',strtotime($item['year'].'-'.$item['month']));?></a>
        <?php endforeach;?>
        </p>
        <?php endif;?>
        
        <div class="card">
                <div class="card-header fw-bold bg-success text-light">Your Recent Assessments</div>
                <div class="card-body">
                    <table class="table table-striped text-center">
                        <thead>
                            <tr>
                                <th class="text-start">Period</th>
                                <th>Employee</th>
                                <th>Supervisor</th>
                                <th>HR</th>
                                <th>Score</th>
                                <th>#</th>
                            </tr>
                        </thead>
                        <tbody>
                                <?php foreach($results as $row): ?>
                                    <tr>
                                        <td class="text-start"><?= date('M, Y', strtotime($row['year'].'-'.$row['month']));?></td>
                                        <td><?php echo round($row['self_score'],2); // Employee score 
                                            ?></td>
                                        <td><?= ($row['supervisor_score'])?round($row['supervisor_score'],2):'<span class="text-danger">Pending</span>'; ?></td>
                                        <td><?= ($row['hr_score'])?round($row['hr_score'],2):'<span class="text-danger">Pending</span>'; ?></td>
                                        <td>
                                            <?php

                                            if(empty($row['supervisor_score']) || empty($row['hr_score']))
                                                $total_score = -1;
                                            else
                                                $total_score = round(($row['supervisor_score']/5)*7 + ($row['hr_score']/5)*3,2);

                                            if($total_score <= 5 && $total_score >=0)
                                                $total_score = '<span class="alert alert-danger p-1">'.$total_score.'</span>';
                                            elseif($total_score > 5 && $total_score <8)
                                                $total_score = '<span class="alert alert-info p-1">'.$total_score.'</span>';
                                            elseif($total_score >=8 && $total_score <= 10)
                                                $total_score = '<span class="alert alert-success p-1">'.$total_score.'</span>';
                                            else
                                                $total_score = '<span class="text-danger">NA</span>';
                                            echo $total_score;
                                            ?>
                                        </td>
                                        <td>
                                            <a class="btn btn-outline-primary btn-sm me-2" href="assessment_details.php?assessment_id=<?= $row['assessment_id']; ?>">Details</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        
        <?php if($_SESSION['role'] == 'Supervisor' || $_SESSION['role'] == 'HR'):?>
        
        <div class="card mt-3">
            <div class="card-header fw-bold bg-primary text-light">Your Employee Assessments</div>
                <div class="card-body">
                    <form method="GET" action="" class="" onchange="this.submit()">
                        <label for="period">Filter:</label>
                        <div class="d-flex">
                            <select name="month" class="form-select w-auto form-control-sm">
                                <?php 
                                foreach($months as $k => $month):
                                ?>
                                <option value="<?= $k;?>" <?= ($k == $selmonth)?'selected':'';?>><?= $month; ?></option>
                                <?php endforeach;?>
                            </select>
                            <select name="year" class="form-select w-auto form-control-sm mx-2">
                                <?php
                                for($i=$year; $i<= date('Y'); $i++)
                                {?>
                                <option value="<?= $i;?>" <?= ($i == $selyear)?'selected':'';?>><?= $i;?></option>
                                <?php } ?>

                            </select>
                            <input type="submit" value="Filter" class="btn btn-outline-primary btn-sm">
                            <button 
                                type="button"
                                onclick="window.location.href = 'dashboard.php?month=<?= date('n'); ?>&year=<?= date('Y'); ?>';" 
                                class="btn btn-outline-secondary btn-sm ms-2">
                                Current Month
                            </button>
                        </div>
                    </form>
                <hr />
                    <table class="table table-striped text-center">
                        <thead>
                            <tr>
                                <th class="text-start">Employee Name</th>
                                <th class="text-start">Designation</th>
                                <th>Employee</th>
                                <th>Supervisor</th>
                                <th>HR</th>
                                <th>Score</th>
                                <th class="text-end">#</th>
                            </tr>
                        </thead>
                        <tbody>
                                <?php foreach($empResults as $row): ?>
                                    <tr>
                                        <td class="text-start"><?php echo $row['name']; ?></td>
                                        <td class="text-start"><small class="alert alert-info ms-2 p-1"><?php echo $row['designation']; ?></small></td>
                                        <td><?php echo round($row['self_score'],2); ?></td>
                                        <td><?= ($row['supervisor_score'])?round($row['supervisor_score'],2):'<span class="text-danger">Pending</span>'; ?></td>
                                        <td><?= ($row['hr_score'])?round($row['hr_score'],2):'<span class="text-danger">Pending</span>'; ?></td>
                                        <td>
                                            <?php

                                            if(empty($row['supervisor_score']) || empty($row['hr_score']))
                                                $total_score = -1;
                                            else
                                                $total_score = round(($row['supervisor_score']/5)*7 + ($row['hr_score']/5)*3,2);

                                            if($total_score <= 5 && $total_score >=0)
                                                $total_score = '<span class="alert alert-danger p-1">'.$total_score.'</span>';
                                            elseif($total_score > 5 && $total_score <8)
                                                $total_score = '<span class="alert alert-info p-1">'.$total_score.'</span>';
                                            elseif($total_score >=8 && $total_score <= 10)
                                                $total_score = '<span class="alert alert-success p-1">'.$total_score.'</span>';
                                            else
                                                $total_score = '<span class="text-danger">NA</span>';
                                            echo $total_score;
                                            ?>
                                        </td>
                                        <td class="text-end">
                                            <a class="btn btn-outline-primary btn-sm me-2" href="assessment_details.php?assessment_id=<?= $row['assessment_id']; ?>">Details</a>
                                            <?php 
                                            $emp = $row['user_id'];
                                            
                                            if(empty($row['supervisor_score']) && $_SESSION['user_id'] == $row['supervisor_id']):
                                            ?>
                                                <a href="supervisor_review.php?assessment_id=<?php echo $row['assessment_id']; ?>" class="btn btn-outline-secondary btn-sm me-2">Review (Supv.)</a>
                                            <?php endif; if(empty($row['hr_score']) && $_SESSION['role'] == 'HR'):?>
                                                <a href="hr_review.php?assessment_id=<?php echo $row['assessment_id']; ?>" class="btn btn-outline-secondary btn-sm">Review (HR)</a>
                                            <?php endif;?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?= (count($empResults)<=0)?'<div class="d-block p-3 text-danger">No records found</div>':'';?>
                </div>
            </div>
        <?php endif;?>
    </div>
</body>

</html>