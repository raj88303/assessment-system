<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL && ~E_WARNING && ~E_NOTICE);
session_start();
require_once '../config/config.php';
require_once '../includes/db.php';
require_once '../models/User.php';
require_once '../models/Question.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: login.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

$user = new User($db);
$question = new Question($db);

if(isset($_GET['delid'])){
    $delid= intval($_GET['delid']);
    if($user->deleteUser($delid))
        $_SESSION['success'] = "User deleted successfully!";
    else
        $_SESSION['error'] = "Cannot delete user.";
}
// Fetch all designations from the users table for the dropdown
$users = $user->readAll(); // Directly from the users table
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
        <h3 class="my-3">Manage Users</h3>
        <?php if (isset($_SESSION['success'])) echo "<p class='text-success'>".$_SESSION['success']."</p>";  unset($_SESSION['success']);?>
        <?php if (isset($_SESSION['error'])) echo "<p class='text-danger'>".$_SESSION['error']."</p>"; unset($_SESSION['error']);?>
        <div class="card my-3">
            <div class="card-body">
                <form method="GET">
                    <input type="hidden" name="action" value="filter">
                    <label for="department" class="form-label">Filter User:</label>
                    <div class="d-flex">
                        <select id="department" name="department" class="form-select w-auto">
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $department): ?>
                                <option value="<?php echo htmlspecialchars($department['department']); ?>" <?= ($_GET['department'] == $department['department'] && $_GET['action']=='filter')?'selected':''; ?>>
                                    <?php echo htmlspecialchars($department['department']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="submit" class="btn btn-outline-primary btn-sm ms-2" value="Filter">
                        <button type="button" onclick="window.location.href='users.php';" class="btn btn-outline-secondary btn-sm ms-2">Reset</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>SN</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Supervisor</th>
                            <th>Department</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($users)): $cnt= 0; ?>
                            <?php foreach($users as $row): ?>
                                <tr>
                                    <td><?= ++$cnt;?></td>
                                    <td><a class="link-dark link-underline-opacity-0" href="register.php?user_id=<?= $row['user_id'];?>"><?php echo htmlspecialchars($row['name']).'</a> <small class="alert alert-info p-1 ms-2">'.$row['designation'].'</small>'; ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td>
                                        <?php $role = $row['role'];
                                        if($role == 'Admin')
                                            echo '<span class="badge bg-primary">Admin</span>';
                                        elseif($role == 'Supervisor')
                                            echo '<span class="badge bg-danger">Supervisor</span>';
                                        elseif($role == 'HR')
                                            echo '<span class="badge bg-success">HR</span>';
                                        elseif($role == 'Employee')
                                            echo '<span class="badge bg-info">Employee</span>';
                                        ?>
                                        </td>
                                    <td>
                                        <?php
                                            $supervisor_id = intval($row['supervisor_id']);
                                            if($supervisor_id > 0)
                                            {
                                                $supervisor = $user->getUserById($supervisor_id);
                                                echo $supervisor['name'];
                                            }
                                            else
                                                echo '-';
                                            
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['department']); ?></td>
                                    <td>
                                        <a href="register.php?user_id=<?= $row['user_id'];?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                        <a href="users.php?delid=<?php echo $row['user_id'].'&department='.$_GET['department'].'&action=filter'; ?>" class="ms-2 btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">No users found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>