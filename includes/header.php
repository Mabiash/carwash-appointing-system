<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
ensure_session_started();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SparkleWash - Car Wash Appointment System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="./assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container">
            <a class="navbar-brand" href="/index.php">
                <i class="fas fa-car-wash me-2"></i>SparkleWash
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/carwash-appoinment/index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/carwash-appoinment/services.php">Services</a>
                    </li>
                 
                    <?php if (is_logged_in() && !is_admin()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/carwash-appoinment/user/dashboard.php">My Appointment</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/carwash-appoinment/user/book.php">Book Appointment</a>
                    </li>
                    <?php endif; ?>
                    <?php if (is_admin()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            Admin
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/carwash-appoinment/admin/dashboard.php">Dashboard</a></li>
                            <li><a class="dropdown-item" href="/carwash-appoinment/admin/appointments.php">Appointments</a></li>
                            <li><a class="dropdown-item" href="/carwash-appoinment/admin/services.php">Services</a></li>
                            <li><a class="dropdown-item" href="/carwash-appoinment/admin/users.php">Users</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
                <div class="navbar-nav">
                    <?php if (is_logged_in()): ?>
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i><?php echo $_SESSION['user_name']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="/carwash-appoinment/user/profile.php">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/carwash-appoinment/auth/logout.php">Logout</a></li>
                        </ul>
                    </div>
                    <?php else: ?>
                    <a class="nav-link" href="/carwash-appoinment/auth/login.php">Login</a>
                    <a class="nav-link" href="/carwash-appoinment/auth/register.php">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <?php display_message(); ?>