<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL && ~E_WARNING && ~E_NOTICE);

session_start();
require_once '../config/config.php';
require_once '../includes/db.php';
require_once '../models/Question.php';
require_once '../models/User.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: login.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

$question = new Question($db);
$user = new User($db);

// Fetch distinct designations from the users table dynamically
$departments = $question->getDepartments();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_question'])) {
        $question_text = $_POST['question_text'];
        $department = $_POST['department'];
        $specificUser = $_POST['specific_user'];
        $status = intval($_POST['status']);
        $question_id = $_POST['question_id'];
        
        if($department == '' && $specificUser == '')
            $_SESSION['error'] = 'Please chose atleast one department or specific user';
        elseif($department != '' && $specificUser != '')
            $_SESSION['error'] = 'You cannot chose both department and specific user.';
        elseif($_POST['add_question'] == 'Add Question')
        {
            if ($question->createQuestion($question_text, $department, $specificUser, $status)) {
                $_SESSION['success'] = "Question added successfully!";
            } else {
                $_SESSION['error'] = "Failed to add question.";
            }
        }
        elseif($_POST['add_question'] == 'Update Question')
        {
            if ($question->updateQuestion($question_id, $question_text, $department, $specificUser, $status)) {
                $_SESSION['success'] = "Question added successfully!";
            } else {
                $_SESSION['error'] = "Failed to add question.";
            }
        }
        
    }
}

// Retrieve success or error messages
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}
if($_GET['edit'] > 0)
{
    $questionRow = $question->loadQuestion($_GET['edit']);
}
$specificUsers = $user->readAll();
$groupedUsers = array();
foreach ($specificUsers as $k=>$item) {
    $groupedUsers[$item['department']][$k] = $item;
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
        <h3 class="my-3">Add Questions</h3>
        <?php if (isset($success)) echo "<p class='text-success'>$success</p>"; ?>
        <?php if (isset($error)) echo "<p class='text-danger'>$error</p>"; ?>
        <div class="card">
            <div class="card-body">
                <form method="post" action="add_questions.php<?= ($_GET['edit'])?'?edit='.$_GET['edit']:'';?> ">
                    <div class="mb-3">
                        <label for="question_text" class="form-label fw-bold">Question Text:</label>
                        <textarea id="question_text" name="question_text" class="form-control" required><?= $questionRow['question_text'];?></textarea>
                    </div>
                    <p class="fw-bold">Assign question to Department / Specific User</p>
                    <div class="d-flex">
                        <div class="mb-3">
                            <label for="department" class="form-label fw-bold">Department:</label>
                            <select id="department" name="department" class="form-select w-auto">
                                <option value=""></option>
                                <?php foreach ($departments as $department): ?>
                                    <option value="<?php echo htmlspecialchars($department['department']); ?>" <?= ($questionRow['department'] == $department['department'])?'selected':'';?>>
                                        <?php echo htmlspecialchars($department['department']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="ms-3">
                            <label for="specific_user" class="form-label fw-bold">Specific User</label>
                            <select name="specific_user" id="specific_user" class="form-select">
                                <option value=""></option>
                                <?php foreach ($groupedUsers as $department => $users): ?>
                                    <optgroup label="<?= htmlspecialchars($department) ?>">
                                        <?php foreach ($users as $user): ?>
                                            <option value="<?= htmlspecialchars($user['user_id']) ?>" <?= ($user['user_id'] == $questionRow['specific_user'])?'selected':''; ?>>
                                                <?= htmlspecialchars($user['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status:</label>
                        <div class="form-check form-switch">
                            <input value="1" name="status" class="form-check-input" type="checkbox" role="switch" id="flexSwitchCheckChecked" <?= (!isset($questionRow['status']) || $questionRow['status'] == 1) ? 'checked' : ''; ?>>
                        </div>
                    </div>
                    <input name="question_id" value="<?= $_GET['edit'];?>" type="hidden">
                    <input type="submit" name="add_question" value="<?= ($_GET['edit'])?'Update':'Add';?> Question" class="btn btn-secondary my-3">
                </form>
            </div>
        </div>
    </div>
</body>

</html>