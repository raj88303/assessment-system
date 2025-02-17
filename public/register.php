<?php
session_start();
require_once '../config/config.php';
require_once '../includes/db.php';
require_once '../models/User.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: login.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

$user = new User($db);
$user_id = intval($_GET['user_id']);
$row = $user->getUserById($user_id);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user->name = $_POST['name'];
    $user->email = $_POST['email'];
    $user->password = $_POST['password'];
    $user->role = $_POST['role'];
    $user->department = $_POST['department'];
    $user->designation = $_POST['designation'];
    $user->supervisor_id = intval($_POST['supervisor_id']);
    $user->user_id = intval($_POST['user_id']);
    
    if($user->user_id > 0 && $user->updateUser()){
        $_SESSION['success'] = 'User updated successfully';
        header('location:register.php?user_id='.$user_id);
        exit;
    }
    elseif($user->register()){
        $_SESSION['success'] = "User added successfully.";
        header('location:register.php');
        exit;
    }
    else
        $_SESSION['error'] = "Error occured. Cannot add / update user.";
    
}
$supervisors = $user->getSupervisors();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Add User</title>
</head>

<body>
    <div class="container">
        <?php include('header.php');?>
        <h3 class="my-3">Add User</h3>
        <?php if (isset($_SESSION['success'])) echo "<p class='text-success'>".$_SESSION['success']."</p>"; unset($_SESSION['success']); ?>
        <?php if (isset($_SESSION['error'])) echo "<p class='text-danger'>".$_SESSION['error']."</p>";  unset($_SESSION['error']);?>
        <div class="card">
            <div class="card-body">
                <form method="post" action="">
                    <label for="name">Full Name:</label>
                    <input class="form-control mb-3" type="text" name="name" placeholder="Name" value="<?= $row['name'];?>" required>
                    
                    <label for="email">Email:</label>
                    <input class="form-control mb-3" type="<?= ($user_id)?'hidden':'email';?>" name="email" placeholder="Email" value="<?= $row['email'];?>" required>
                    
                    <label for="password">Password:</label>
                    <input class="form-control mb-3" type="password" name="password" placeholder="Password" <?= ($user_id > 0)?'':'required';?>>
                    
                    <label class="form-label">Role:</label><br>
                    <div class="mb-3 d-flex gap-3">
                        <div class="form-check">
                            <input type="radio" class="form-check-input role-radio" id="admin" name="role" value="Admin" <?= ($row['role'] == 'Admin')?'checked':''; ?> required>
                            <label class="form-check-label" for="admin">Admin</label>
                        </div>
                        <div class="form-check">
                            <input type="radio" class="form-check-input role-radio" id="supervisor" name="role" value="Supervisor" <?= ($row['role'] == 'Supervisor')?'checked':''; ?> required>
                            <label class="form-check-label" for="supervisor">Supervisor</label>
                        </div>
                        <div class="form-check">
                            <input type="radio" class="form-check-input role-radio" id="hr" name="role" value="HR" <?= ($row['role'] == 'HR')?'checked':''; ?> required>
                            <label class="form-check-label" for="hr">HR</label>
                        </div>
                        <div class="form-check">
                            <input type="radio" class="form-check-input role-radio" id="employee" name="role" value="Employee" <?= ($row['role'] == 'Employee')?'checked':''; ?> required>
                            <label class="form-check-label" for="employee">Employee</label>
                        </div>
                    </div>
                    
                    <div id="supervisorDiv" <?= ($row['role']=='Admin')?'style="display: none;"':'';?> class="mb-3">
                        <label for="supervisor_id">Select Manager:</label>
                        <select class="form-select mb-3 w-auto" id="supervisor_id" name="supervisor_id">
                            <option value=""></option>
                            <?php
                                foreach($supervisors as $item)
                                {
                                    echo '<option value="'.$item['user_id'].'" '.(($item['user_id'] == $row['supervisor_id'])?'selected':'').'>'.$item['name'].'</option>';
                                }
                            ?>
                        </select>
                    </div>
                    <div class="d-flex mb-3">
                        <div class="me-3">
                            <label for="department">Department:</label>
                            <select class="form-select" id="department" name="department" required>
                                <option value="Management" <?= ($row['department'] == 'Management')?'selected':''; ?>>Management</option>
                                <option value="Sales" <?= ($row['department'] == 'Sales')?'selected':''; ?>>Sales</option>
                                <option value="Marketing" <?= ($row['department'] == 'Marketing')?'selected':''; ?>>Marketing</option>
                                <option value="HR" <?= ($row['department'] == 'HR')?'selected':''; ?>>HR</option>
                                <option value="Finance" <?= ($row['department'] == 'Finance')?'selected':''; ?>>Finance</option>
                                <option value="Operation" <?= ($row['department'] == 'Operation')?'selected':''; ?>>Operation</option>
                                <option value="Packaging" <?= ($row['department'] == 'Packaging')?'selected':''; ?>>Packaging</option>
                                <option value="Logistic" <?= ($row['department'] == 'Logistic')?'selected':''; ?>>Logistic</option>
                                <!-- Add other departments as needed -->
                            </select>
                        </div>
                        <div>
                            <label for="designation">Designation:</label>
                            <input class="form-control" type="text" id="designation" name="designation" placeholder="Designation" value="<?= $row['designation'];?>" required>
                        </div>
                    </div>
                    <?= ($user_id>0)?'<input type="hidden" name="user_id" value="'.$user_id.'">':'';?>
                    <input type="submit" value="Save User" class="btn btn-secondary my-3">
                </form>
            </div>
        </div>
    </div>
</body>
<script type="text/javascript">
    document.querySelectorAll('.role-radio').forEach((radio) => {
        radio.addEventListener('change', function () {
            const supervisorDiv = document.getElementById('supervisorDiv');
            const supervisorSelect = document.getElementById('supervisor_id');
            
            if (this.value !== 'Admin') {
                supervisorDiv.style.display = 'block';
                supervisorSelect.disabled = false; // Enable the select
                supervisorSelect.required = true; // Make it required when enabled

            } else {
                supervisorDiv.style.display = 'none';
                supervisorSelect.disabled = true; // Disable the select
                supervisorSelect.required = false; // Make it required when enabled
            }
        });
    });
</script>

</html>