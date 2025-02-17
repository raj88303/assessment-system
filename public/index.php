<?php

session_start();
require_once '../config/config.php';
require_once '../includes/db.php';


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

/* 
Need to update here 
1. Add the navigation bar for ease of authentication
2. Logout feature
*/

$role = $_SESSION['role'];

if($role == 'Admin')
    header("Location: admin_dashboard.php");
elseif ($role == 'Employee' || $role == 'Supervisor' || $role == 'HR')
    header("Location: dashboard.php");
 else {
    echo "Invalid role";
    session_destroy();
}
