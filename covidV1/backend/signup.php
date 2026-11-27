<?php
session_start();
include 'db.php';

$errors = [];
$success = '';

if (isset($_POST['signup'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = trim($_POST['phone']);
    $role = 'admin'; // Only admin role

    // Validation
    if (empty($name) || empty($email) || empty($password)) {
        $errors[] = "All fields are required.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    // Check if email already exists in admins table
    $check_email = mysqli_query($conn, "SELECT email FROM admins WHERE email = '$email'");
    if (mysqli_num_rows($check_email) > 0) {
        $errors[] = "Email already exists in the system.";
    }

    if (empty($errors)) {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert into admins table
        $role_id = 1; // admin role_id
        $query = "INSERT INTO admins (role_id, name, email, password, phone) 
                  VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "issss", $role_id, $name, $email, $hashed_password, $phone);

        if (mysqli_stmt_execute($stmt)) {
            $success = "Admin account created successfully! You can now login.";
            
            // Clear form
            $_POST = array();
        } else {
            $errors[] = "Error creating account: " . mysqli_error($conn);
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
    <title>Sign Up - COVID Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
    body {
        background-color: #f8f9fa; /* Light greyish background for consistency with the dashboard */
        min-height: 100vh;
        display: flex;
        align-items: center;
    }
    .signup-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }
    .signup-header {
        background: linear-gradient(135deg, #3fbbc0, #36aeb3); /* Blue-green gradient similar to dashboard */
        color: white;
        padding: 2rem;
        text-align: center;
    }
    .signup-header i {
        font-size: 3rem;
        margin-bottom: 10px;
    }
    .form-control {
        border: 1px solid #36aeb3; /* Accent green border for inputs */
        box-shadow: 0 0 5px rgba(63, 187, 192, 0.3); /* Soft shadow effect */
    }
    .btn-primary {
        background-color: #3fbbc0; /* Fresh green button for consistency */
        border-color: #3fbbc0;
    }
    .btn-primary:hover {
        background-color: #36aeb3; /* Darker green-blue for hover */
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
        color: #555555; /* Darker grey for form labels */
    }
</style>

</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="signup-card">
                    <div class="signup-header">
                        <h1><i class="bi bi-person-plus"></i></h1>
                        <h2>Create Admin Account</h2>
                        <p class="mb-0">Join COVID Management System</p>
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

                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <?php echo $success; ?>
                                <br>
                                <a href="login.php" class="alert-link">Click here to login</a>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" name="name" value="<?php echo $_POST['name'] ?? ''; ?>" required>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" name="email" value="<?php echo $_POST['email'] ?? ''; ?>" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Password *</label>
                                    <input type="password" class="form-control" name="password" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Confirm Password *</label>
                                    <input type="password" class="form-control" name="confirm_password" required>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Phone Number</label>
                                    <input type="text" class="form-control" name="phone" value="<?php echo $_POST['phone'] ?? ''; ?>">
                                </div>
                            </div>

                            <button type="submit" name="signup" class="btn btn-primary w-100 py-2 mt-4 mb-3">
                                <i class="bi bi-person-plus"></i> Create Admin Account
                            </button>

                            <div class="text-center">
                                <p class="mb-0">Already have an account? 
                                    <a href="login.php" class="text-decoration-none">Sign in here</a>
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
