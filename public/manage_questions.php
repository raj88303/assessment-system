<?php
session_start();
require_once '../config/config.php';
require_once '../includes/db.php';
require_once '../models/Question.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Supervisor') {
    header("Location: login.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

$question = new Question($db);

$selectedDesignation = '';
$questions = [];

// Handle bulk deletion
if (isset($_POST['bulk_delete'])) {
    $questionIds = $_POST['question_ids'];
    if (!empty($questionIds)) {
        //print_r($questionIds); // Debugging line
        foreach ($questionIds as $questionId) {
            $question->deleteQuestion($questionId);
        }
        $_SESSION['message'] = "Selected questions deleted successfully!";
    } else {
        $_SESSION['message'] = "No questions were selected for deletion.";
    }
    header("Location: manage_questions.php");
    exit;
}

if(isset($_GET['delid'])){
    $delid= intval($_GET['delid']);
    if($question->deleteQuestion($delid))
        $_SESSION['message'] = "Questions deleted successfully!";
    else
        $_SESSION['message'] = "No questions were selected for deletion.";
}

// Fetch all designations from the users table for the dropdown
$designations = $question->getAllDesignationsFromUsers(); // Directly from the users table

// Fetch questions based on selected designation
if (isset($_GET['designation']) && $_GET['designation'] != '') {
    $selectedDesignation = $_GET['designation'];
    $questions = $question->getQuestionsByDesignation($selectedDesignation);
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Questions</title>
    <link rel="stylesheet" href="../styles/main.css">
    <style>
        /* Your existing styles */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 90%;
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
            padding: 12px;
            text-align: left;
        }

        th {
            background-color: #f8f8f8;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        .button {
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
            border: none;
            cursor: pointer;
            display: inline-block;
        }

        .button:hover {
            background-color: #218838;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            font-size: 16px;
            color: #007bff;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .back-link:hover {
            color: #0056b3;
        }

        .message {
            color: green;
            font-weight: bold;
            margin-top: 20px;
        }

        .filter-form {
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <?php include('header.php');?>
        <h1>Manage Questions</h1>

        <?php if (isset($_SESSION['message'])): ?>
            <p class="message"><?php echo $_SESSION['message']; ?></p>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <h2>Filter Questions by Designation</h2>
        <form method="get" action="manage_questions.php" class="filter-form">
            <label for="filter_designation">Choose Designation:</label>
            <select id="filter_designation" name="designation">
                <option value="">Select Designation</option>
                <?php while ($row = $designations->fetch(PDO::FETCH_ASSOC)): ?>
                    <option value="<?php echo htmlspecialchars($row['designation']); ?>" <?php if ($row['designation'] == $selectedDesignation) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($row['designation']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <input type="submit" value="Filter" class="button">
        </form>

        <h2>Existing Questions</h2>
        <form method="post" action="manage_questions.php">
            <table>
                <thead>
                    <tr>
                        <th><input type="checkbox" id="select_all"></th>
                        <th>Designation</th>
                        <th>Question</th>
                        <th>Period</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($questions)): ?>
                        <?php while ($row = $questions->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <td><input type="checkbox" name="question_ids[]" value="<?php echo $row['question_id']; ?>"></td>
                                <td><?php echo htmlspecialchars($row['designation']); ?></td>
                                <td><?php echo htmlspecialchars($row['question_text']); ?></td>
                                <td><?php echo date('M Y',strtotime($row['period'])); ?></td>
                                <td>
                                    <a href="add_questions.php?edit=<?php echo $row['question_id']; ?>" class="button">Edit</a>
                                    <a href="manage_questions.php?&designation=<?=$_GET['designation'];?>&delid=<?php echo $row['question_id']; ?>" class="button" onclick="return confirm('Are you sure you want to delete this question?');">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">No questions found for the selected designation.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Add the bulk delete button -->
            <input type="submit" name="bulk_delete" value="Delete Selected" class="button" onclick="return confirm('Are you sure you want to delete the selected questions?');">
        </form>

        <a href="supervisor_dashboard.php" class="back-link">Back to Dashboard</a>
    </div>
    <script>
        document.getElementById('select_all').onclick = function() {
            var checkboxes = document.querySelectorAll('input[name="question_ids[]"]');
            for (var checkbox of checkboxes) {
                checkbox.checked = this.checked;
            }
        }
    </script>

</body>

</html>