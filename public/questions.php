<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL && ~E_WARNING && ~E_NOTICE);

session_start();
require_once '../config/config.php';
require_once '../includes/db.php';
require_once '../models/Question.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: login.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

$question = new Question($db);

if(isset($_GET['delid'])){
    $delid= intval($_GET['delid']);
    if($question->deleteQuestion($delid))
        $_SESSION['success'] = "Questions deleted successfully!";
    else
        $_SESSION['error'] = "Cannot delete this question.";
}
if($_GET['action'] == 'changeStatus')
{
    $question_id = intval($_GET['question_id']);
    $status = intval($_GET['status']);
    $question->changeStatus($question_id, $status);
}
// Fetch all designations from the users table for the dropdown
$questions = $question->readAll(); // Directly from the users table
$departments = $question->getDepartments();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Questions</title>
</head>

<body>
    <div class="container">
        <?php include('header.php');?>
        <h3 class="my-3">Manage Questions</h3>
        <?php if (isset($_SESSION['success'])) echo "<p class='text-success'>".$_SESSION['success']."</p>";  unset($_SESSION['success']);?>
        <?php if (isset($_SESSION['error'])) echo "<p class='text-danger'>".$_SESSION['error']."</p>"; unset($_SESSION['error']);?>
        <div class="card my-3">
            <div class="card-body">
                <form method="GET" onchange="this.submit()">
                    <input type="hidden" name="action" value="filter">
                    <label for="department" class="form-label">Filter Questions:</label>
                    <div class="d-flex">
                        <select id="department" name="department" class="form-select w-auto">
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $department): ?>
                                <option value="<?php echo htmlspecialchars($department['department']); ?>" <?= ($_GET['department'] == $department['department'] && $_GET['action']=='filter')?'selected':''; ?>>
                                    <?php echo htmlspecialchars($department['department']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select class="form-select w-auto ms-2" name="status">
                            <option value="all" <?= ($_GET['status']== 'all' && $_GET['action']=='filter')?'selected':''; ?>>All Status</option>
                            <option value="1" <?= ($_GET['status']== '1' && $_GET['action']=='filter')?'selected':''; ?>>Active</option>
                            <option value="0" <?= ($_GET['status'] == '0' && $_GET['action']=='filter')?'selected':''; ?>>Inactive</option>
                        </select>
                        <input type="submit" class="btn btn-outline-primary btn-sm ms-2" value="Filter">
                        <button type="button" onclick="window.location.href='questions.php';" class="btn btn-outline-secondary btn-sm ms-2">Reset</button>
                    </div>
                </form>
            </div>
        </div>
        <?php /*
        <div class="card">
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Question</th>
                            <th>Department/User</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($questions)): ?>
                            <?php foreach($questions as $row): ?>
                                <tr>
                                    <td><a class="link-dark link-underline-opacity-0" href="add_questions.php?edit=<?= $row['question_id'];?>"><?php echo substr($row['question_text'], 0,85); echo (strlen($row['question_text'])>85)?'...':''; ?></a></td>
                                    <td><?= $row['department']; ?><?= $row['name'];?></td>
                                    <td>
                                        <div class="form-check form-switch">
                                          <input onchange="changeStatus('questions.php?question_id=<?= $row['question_id']; ?>&status=<?= ($row['status'])?'0':'1';?>&action=changeStatus')" value="1" name="status" class="form-check-input" type="checkbox" role="switch" id="flexSwitchCheckChecked" <?= ($row['status'])?'checked':'';?>>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="add_questions.php?edit=<?php echo $row['question_id']; ?>" class="btn btn-outline-secondary btn-sm">Edit</a>
                                        <a href="questions.php?delid=<?php echo $row['question_id']; ?><?= '&action='.$_GET['action'].'&department='.$_GET['department'].'&status='.$_GET['status']; ?>" class="ms-2 btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to delete this question?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">No questions found for the selected department.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div> */?>
        <?php
        foreach($questions as $row)
        {
            $nq[$row['department'].$row['name']][] = $row;
        }
        ?>
        <?php foreach ($nq as $for=>$question): ?>
        <div class="card mb-3">
            <div class="card-header bg-secondary text-white fw-bold"><?= $for;?></div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>SN</th>
                            <th>Question</th>
                            <th>Department/User</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                <?php $cnt = 0; foreach($question as $row):?>
                        <tr>
                            <td><?= ++$cnt; ?></td>
                            <td><a class="link-dark link-underline-opacity-0" href="add_questions.php?edit=<?= $row['question_id'];?>"><?php echo substr($row['question_text'], 0,85); echo (strlen($row['question_text'])>85)?'...':''; ?></a></td>
                            <td><?= $row['department']; ?><?= $row['name'];?></td>
                            <td>
                                <div class="form-check form-switch">
                                    <input onchange="changeStatus('questions.php?question_id=<?= $row['question_id']; ?>&status=<?= ($row['status'])?'0':'1';?>&action=changeStatus')" value="1" name="status" class="form-check-input" type="checkbox" role="switch" id="flexSwitchCheckChecked" <?= ($row['status'])?'checked':'';?>>
                                </div>
                            </td>
                            <td>
                                <a href="add_questions.php?edit=<?php echo $row['question_id']; ?>" class="btn btn-outline-secondary btn-sm">Edit</a>
                                <a href="questions.php?delid=<?php echo $row['question_id']; ?><?= '&action='.$_GET['action'].'&department='.$_GET['department'].'&status='.$_GET['status']; ?>" class="ms-2 btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to delete this question?');">Delete</a>
                            </td>
                        </tr>
                <?php endforeach;?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <script type="text/javascript">
        function changeStatus(url) {
            window.location.href = url;
        }
    </script>
</body>

</html>