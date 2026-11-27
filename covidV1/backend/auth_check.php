<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

// Role-based access control
$current_page = basename($_SERVER['PHP_SELF']);
$allowed_pages = [];

// Define allowed pages for each role
switch ($_SESSION['role']) {
    case 'admin':
        $allowed_pages = [
            'dashboard.php', 'admin.php', 'patients.php', 'hospitals.php', 
            'vaccines.php', 'vaccinations.php', 'covid_tests.php', 
            'appointments.php', 'booking_details.php', 'logout.php'
        ];
        break;
        
    case 'hospital':
        // $allowed_pages = [
        //     'dashboard.php', 'patients.php', 'vaccinations.php', 
        //     'covid_tests.php', 'appointments.php', 'booking_details.php', 'logout.php'
        // ];
        // // Check if hospital is approved
        // if (!isset($_SESSION['is_approved']) || $_SESSION['is_approved'] != 'Approved') {
        //     header("Location: login.php");
        //     exit();
        // }
       
        header("Location: ../frontend/patient.php");
        break;
        
    case 'patient':
        // $allowed_pages = [
        //     'dashboard.php', 'appointments.php', 'vaccinations.php', 
        //     'covid_tests.php', 'logout.php'
        // ];
  
        header("Location: ../frontend/patient.php");
        break;
        
    default:
        header("Location: login.php");
        exit();
}

// Check if current page is allowed for user's role
if (!in_array($current_page, $allowed_pages)) {
    header("Location: unauthorized.php");
    exit();
}

// Check session timeout (30 minutes)
$session_timeout = 30 * 60; // 30 minutes in seconds
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
    session_unset();
    session_destroy();
    header("Location: login.php?timeout=1");
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Function to check specific role
function checkRole($allowed_roles) {
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        header("Location: unauthorized.php");
        exit();
    }
}

// Function to get user info
function getUserInfo() {
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['name'],
        'email' => $_SESSION['email'],
        'role' => $_SESSION['role']
    ];
}
?>