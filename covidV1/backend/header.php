<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COVID Management System - Admin Dashboard</title>
  
    <!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<!-- SweetAlert2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <link rel="stylesheet" href="./css/style.css">


    <!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h3>COVID Admin</h3>
                <button class="toggle-btn" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            <div class="sidebar-menu">
                <ul>
                    <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
                    <li><a href="admin.php"><i class="fas fa-user-shield"></i> <span>Admins</span></a></li>
                    <li><a href="hospitals.php"><i class="fas fa-hospital"></i> <span>Hospitals</span></a></li>
                    <li><a href="patients.php"><i class="fas fa-user-injured"></i> <span>Patients</span></a></li>
                    <li><a href="vaccines.php"><i class="fas fa-syringe"></i> <span>Vaccines</span></a></li>
                    <li><a href="vaccinations.php"><i class="fas fa-shield-virus"></i> <span>Vaccinations</span></a></li>
                    <li><a href="covid_tests.php"><i class="fas fa-vial"></i> <span>Covid Tests</span></a></li>
                    <li><a href="appointments.php"><i class="fas fa-calendar-check"></i> <span>Appointments</span></a></li>
                    <li><a href="booking_details.php"><i class="fas fa-clipboard-list"></i> <span>Booking Details</span></a></li>
                </ul>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content" id="mainContent">
            <!-- Topbar -->
            <div class="topbar">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search...">
                </div>
                <div class="user-info">
                    <div class=" ms-3">
                        <button class="btn btn-outline-danger"><a href="logout.php">Logout</a></button>
                    </div>
                    <img src="https://ui-avatars.com/api/?name=Admin+User&background=random" alt="Admin User">
                    <span>Admin Panel</span>
                </div>
            </div>