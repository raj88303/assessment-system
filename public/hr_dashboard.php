<?php
session_start();
require_once '../config/config.php';
require_once '../includes/db.php';
require_once '../models/EmployeeAnswer.php';
require_once '../models/User.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'HR') {
    header("Location: login.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

$employeeAnswer = new EmployeeAnswer($db);
$userModel = new User($db);

$year = 2025;
$selmonth = $_GET['month'];
if($selmonth == '')
    $selmonth = date('n');
$selyear = $_GET['year'];
if($selyear == '')
    $selyear = date('Y');

$employees = $employeeAnswer->getEmployeesByPeriod($selmonth, $selyear);
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



?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Dashboard</title>
</head>

<body>
    <div class="container">
        <?php include('header.php');?>
        <h3 class="my-3">HR Dashboard</h3>

        <!-- Period Selector -->
        <div class="card my-3">
            <div class="card-body">
                <form method="GET" action="hr_dashboard.php" class="">
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
                            <a href="hr_dashboard.php?month=<?= date('n');?>&year=<?= date('Y');?>" class="btn btn-outline-secondary btn-sm ms-2">Current Month</a>
                        </div>
                </form>
            </div>
        </div>
        
        <div class="card">
                <div class="card-header fw-bold">Assessments</div>
                <div class="card-body">
                    <table class="table table-striped text-center">
                        <thead>
                            <tr>
                                <th class="text-start">Employee Name</th>
                                <th>Employee</th>
                                <th>Supervisor</th>
                                <th>HR</th>
                                <th>Score</th>
                                <th>#</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($employees->rowCount() > 0): ?>
                                <?php while ($row = $employees->fetch(PDO::FETCH_ASSOC)): ?>
                                    <tr>
                                        <td class="text-start"><?php echo htmlspecialchars($row['name']); ?> <span class="form-text ms-2">(<?php echo $row['designation']; ?>)</span></td>
                                        <td><?php echo htmlspecialchars($row['self_score']); // Employee score 
                                            ?></td>
                                        <td><?= ($row['supervisor_score'])?$row['supervisor_score']:'<span class="text-danger">Pending</span>'; ?></td>
                                        <td><?= ($row['hr_score'])?$row['hr_score']:'<span class="text-danger">Pending</span>'; ?></td>
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
                                            <?php if(empty($row['hr_score'])):?>
                                            <a href="hr_review.php?assessment_id=<?php echo $row['assessment_id']; ?>" class="btn btn-outline-secondary btn-sm">Review</a>
                                            <?php endif;?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7">No assessments found for the current period.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
    </div>
</body>

</html>