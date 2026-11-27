<?php
session_start();
include 'db.php';

$errors = [];

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = 'admin'; // Only admin login now

    // Validation
    if (empty($email) || empty($password)) {
        $errors[] = "Email and password are required.";
    }

    if (empty($errors)) {
        $table = 'admins';
        $id_field = 'admin_id';
        $name_field = 'name';

        $query = "SELECT * FROM $table WHERE email = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if ($user) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user[$id_field];
                $_SESSION['email'] = $user['email'];
                $_SESSION['name'] = $user[$name_field];
                $_SESSION['role'] = $role;

                header("Location: dashboard.php");
                exit();
            } else {
                $errors[] = "Invalid password.";
            }
        } else {
            $errors[] = "No user found with this email.";
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login - COVID Management System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<style>
    body {
        background-color: #f8f9fa; /* Light background color matching dashboard */
        min-height: 100vh;
        display: flex;
        align-items: center;
    }
    .login-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }
    .login-header {
        background: linear-gradient(135deg, #3fbbc0, #36aeb3); /* Blue to green gradient matching the dashboard */
        color: white;
        padding: 2rem;
        text-align: center;
    }
    .login-header i {
        font-size: 3rem;
        margin-bottom: 10px;
    }
    .form-control {
        border: 1px solid #36aeb3; /* Blue-green border matching the dashboard accent color */
        box-shadow: 0 0 5px rgba(63, 187, 192, 0.3); /* Subtle shadow effect */
    }
    .btn-primary {
        background-color: #3fbbc0; /* Blue-green button color matching the dashboard */
        border-color: #3fbbc0;
    }
    .btn-primary:hover {
        background-color: #36aeb3; /* Slightly darker green-blue for hover effect */
        border-color: #36aeb3;
    }
    .text-center a {
        color: #3fbbc0; /* Accent color for links */
    }
    .alert-danger {
        background-color: #f8d7da; /* Soft red background for errors */
        color: #721c24;
    }
    .form-label {
        color: #555555; /* Subtle grey color for labels */
    }
</style>

</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="login-card">
                <div class="login-header">
                    <i class="bi bi-shield-check"></i>
                    <h2>Admin Login</h2>
                    <p class="mb-0">Sign in to your admin account</p>
                </div>
                
                <div class="card-body p-4">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <!-- Email -->
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" name="email" placeholder="Enter your email" required>
                            </div>
                        </div>

                        <!-- Password -->
                        <div class="mb-4">
                            <label class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" name="password" placeholder="Enter your password" required>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" name="login" class="btn btn-primary w-100 py-2 mb-3">
                            <i class="bi bi-box-arrow-in-right"></i> Log In
                        </button>

                        <!-- Links -->
                        <div class="text-center">
                            <p class="mb-2">
                                Don't have an admin account? 
                                <a href="signup.php" class="text-decoration-none">Sign Up</a>
                            </p>

                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
