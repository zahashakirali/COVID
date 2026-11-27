
<?php
include 'db.php';

include 'header.php';

$errors = [];

// Fetch roles for dropdown (only admin role)
$roles = mysqli_query($conn, "SELECT role_id, role_name FROM roles WHERE role_name = 'admin' ORDER BY role_name ASC");

// ================== ADD NEW ADMIN ==================
if (isset($_POST['newadmin'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $phone = trim($_POST['phone']);
    $role_id = $_POST['role_id'];

    // Validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Please enter a valid email address.";
    }
    
    // Check if email already exists
    $check_email = mysqli_query($conn, "SELECT admin_id FROM admins WHERE email = '$email'");
    if (mysqli_num_rows($check_email) > 0) {
        $errors['email'] = "Email already exists in the system.";
    }
    
    if (strlen($password) < 6) {
        $errors['password'] = "Password must be at least 6 characters long.";
    }

    if (empty($errors)) {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Handle file upload
        $image = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $target_dir = "uploads/admins/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }
            $image_name = time() . '_' . basename($_FILES["image"]["name"]);
            $target_file = $target_dir . $image_name;
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            
            // Check if image file is actual image
            $check = getimagesize($_FILES["image"]["tmp_name"]);
            if ($check !== false) {
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    $image = $image_name;
                }
            }
        }

        $stmt = mysqli_prepare($conn, "INSERT INTO admins 
            (role_id, name, email, password, phone, image) 
            VALUES (?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "isssss", 
            $role_id, $name, $email, $hashed_password, $phone, $image
        );
        
        if (mysqli_stmt_execute($stmt)) {
            echo "<script>
                document.addEventListener('DOMContentLoaded', () => {
                    Swal.fire({icon:'success', title:'Admin Added Successfully', showConfirmButton:false, timer:2500});
                });
            </script>";
        } else {
            $errors['database'] = "Error adding admin: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
}

// ================== DELETE ADMIN ==================
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Prevent deleting the last admin
    $count_admins = mysqli_query($conn, "SELECT COUNT(*) as count FROM admins");
    $result = mysqli_fetch_assoc($count_admins);
    
    if ($result['count'] <= 1) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', () => {
                Swal.fire({icon:'error', title:'Cannot Delete', text:'Cannot delete the last admin account.', showConfirmButton:true});
            });
        </script>";
    } else {
        mysqli_query($conn, "DELETE FROM admins WHERE admin_id = $id");
        header("Location: admin.php");
        exit();
    }
}

// ================== UPDATE ADMIN ==================
if (isset($_POST['updateAdmin'])) {
    $id = $_POST['edit_id'];
    $name = trim($_POST['edit_name']);
    $email = trim($_POST['edit_email']);
    $phone = trim($_POST['edit_phone']);
    $role_id = $_POST['edit_role_id'];

    // Check if email already exists (excluding current admin)
    $check_email = mysqli_query($conn, "SELECT admin_id FROM admins WHERE email = '$email' AND admin_id != $id");
    if (mysqli_num_rows($check_email) > 0) {
        $errors['edit_email'] = "Email already exists in the system.";
    }

    if (empty($errors)) {
        // Handle file upload
        $image = $_POST['current_image'];
        if (isset($_FILES['edit_image']) && $_FILES['edit_image']['error'] == 0) {
            $target_dir = "uploads/admins/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }
            $image_name = time() . '_' . basename($_FILES["edit_image"]["name"]);
            $target_file = $target_dir . $image_name;
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            
            $check = getimagesize($_FILES["edit_image"]["tmp_name"]);
            if ($check !== false) {
                if (move_uploaded_file($_FILES["edit_image"]["tmp_name"], $target_file)) {
                    // Delete old image if exists
                    if ($image && file_exists($target_dir . $image)) {
                        unlink($target_dir . $image);
                    }
                    $image = $image_name;
                }
            }
        }

        // Handle password update
        $password_update = "";
        if (!empty($_POST['edit_password'])) {
            $hashed_password = password_hash($_POST['edit_password'], PASSWORD_DEFAULT);
            $password_update = ", password = ?";
        }

        if (!empty($_POST['edit_password'])) {
            $stmt = mysqli_prepare($conn, "UPDATE admins 
                SET role_id=?, name=?, email=?, phone=?, image=? $password_update
                WHERE admin_id=?");
            mysqli_stmt_bind_param($stmt, "issssi",
                $role_id, $name, $email, $phone, $image, $hashed_password, $id
            );
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE admins 
                SET role_id=?, name=?, email=?, phone=?, image=?
                WHERE admin_id=?");
            mysqli_stmt_bind_param($stmt, "issssi",
                $role_id, $name, $email, $phone, $image, $id
            );
        }
        
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        echo "<script>
            document.addEventListener('DOMContentLoaded', () => {
                Swal.fire({icon:'success', title:'Admin Updated Successfully', showConfirmButton:false, timer:2500});
                setTimeout(() => { window.location.href = 'admin.php'; }, 1000);
            });
        </script>";
    }
}
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Admins Management</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAdminModal">
            <i class="bi bi-plus-circle"></i> Add New Admin
        </button>
    </div>

    <!-- Display Errors -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="table-responsive bg-white shadow-sm p-3 rounded">
        <table class="table table-hover align-middle text-center" id="adminsTable">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Registered</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = "SELECT a.*, r.role_name 
                          FROM admins a 
                          JOIN roles r ON a.role_id = r.role_id 
                          ORDER BY a.admin_id DESC";
                $result = mysqli_query($conn, $query);
                
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $image_path = $row['image'] ? "uploads/admins/{$row['image']}" : "https://ui-avatars.com/api/?name=" . urlencode($row['name']) . "&size=100&background=random";
                        $created_date = date('M j, Y', strtotime($row['created_at']));
                        
                        echo "<tr>
                            <td>{$row['admin_id']}</td>
                            <td>
                                <img src='{$image_path}' alt='{$row['name']}' class='rounded-circle' width='40' height='40' style='object-fit: cover;'>
                            </td>
                            <td>{$row['name']}</td>
                            <td>{$row['email']}</td>
                            <td>{$row['phone']}</td>
                            <td>
                                <span class='badge bg-primary'>{$row['role_name']}</span>
                            </td>
                            <td>{$created_date}</td>
                            <td>
                                <button class='btn btn-sm btn-outline-primary editBtn'
                                    data-id='{$row['admin_id']}'
                                    data-role_id='{$row['role_id']}'
                                    data-name='{$row['name']}'
                                    data-email='{$row['email']}'
                                    data-phone='{$row['phone']}'
                                    data-image='{$row['image']}'>
                                    <i class='bi bi-pencil-square'></i>
                                </button>
                                <button class='btn btn-sm btn-outline-danger deleteBtn' data-id='{$row['admin_id']}' data-name='{$row['name']}'>
                                    <i class='bi bi-trash'></i>
                                </button>
                            </td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='8' class='text-center py-4 text-muted'>No admins found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Admin Modal -->
<div class="modal fade" id="addAdminModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content p-3">
      <form method="POST" enctype="multipart/form-data">
        <h5 class="text-center mb-3">Add New Admin</h5>

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Role</label>
            <select class="form-select" name="role_id" required>
              <option value="">-- Select Role --</option>
              <?php
              mysqli_data_seek($roles, 0);
              while ($r = mysqli_fetch_assoc($roles)) {
                  echo "<option value='{$r['role_id']}'>{$r['role_name']}</option>";
              }
              ?>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Profile Image</label>
            <input type="file" class="form-control" name="image" accept="image/*">
          </div>

          <div class="col-md-6">
            <label class="form-label">Full Name *</label>
            <input type="text" class="form-control" name="name" required>
          </div>

          <div class="col-md-6">
            <label class="form-label">Email *</label>
            <input type="email" class="form-control" name="email" required>
          </div>

          <div class="col-md-6">
            <label class="form-label">Password *</label>
            <input type="password" class="form-control" name="password" required>
          </div>

          <div class="col-md-6">
            <label class="form-label">Phone</label>
            <input type="text" class="form-control" name="phone">
          </div>
        </div>

        <button type="submit" name="newadmin" class="btn btn-primary mt-3 w-100">Add Admin</button>
      </form>
    </div>
  </div>
</div>

<!-- Edit Admin Modal -->
<div class="modal fade" id="editAdminModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content p-3">
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="edit_id" id="edit_id">
        <input type="hidden" name="current_image" id="current_image">
        
        <h5 class="text-center mb-3">Edit Admin</h5>

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Role</label>
            <select class="form-select" id="edit_role_id" name="edit_role_id" required>
              <option value="">-- Select Role --</option>
              <?php
              mysqli_data_seek($roles, 0);
              while ($r = mysqli_fetch_assoc($roles)) {
                  echo "<option value='{$r['role_id']}'>{$r['role_name']}</option>";
              }
              ?>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Profile Image</label>
            <input type="file" class="form-control" name="edit_image" accept="image/*">
            <small class="text-muted">Current: <span id="current_image_name"></span></small>
          </div>

          <div class="col-md-6">
            <label class="form-label">Full Name *</label>
            <input type="text" class="form-control" id="edit_name" name="edit_name" required>
          </div>

          <div class="col-md-6">
            <label class="form-label">Email *</label>
            <input type="email" class="form-control" id="edit_email" name="edit_email" required>
          </div>

          <div class="col-md-6">
            <label class="form-label">New Password</label>
            <input type="password" class="form-control" name="edit_password" placeholder="Leave blank to keep current">
          </div>

          <div class="col-md-6">
            <label class="form-label">Phone</label>
            <input type="text" class="form-control" id="edit_phone" name="edit_phone">
          </div>
        </div>

        <button type="submit" name="updateAdmin" class="btn btn-success mt-3 w-100">Update Admin</button>
      </form>
    </div>
  </div>
</div>

<script>
// DELETE CONFIRMATION
document.querySelectorAll('.deleteBtn').forEach(btn => {
  btn.addEventListener('click', function() {
    const id = this.getAttribute('data-id');
    const name = this.getAttribute('data-name');
    
    Swal.fire({
      title: 'Are you sure?',
      html: `You are about to delete admin: <strong>${name}</strong>`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Yes, delete it!'
    }).then(result => {
      if (result.isConfirmed) {
        window.location.href = 'admin.php?delete=' + id;
      }
    });
  });
});

// EDIT BUTTON POPULATE
document.querySelectorAll('.editBtn').forEach(btn => {
  btn.addEventListener('click', function() {
    const data = this.dataset;
    
    // Populate all fields
    for (const key in data) {
      const el = document.getElementById('edit_' + key);
      if (el) {
        if (el.type === 'select-one') {
          el.value = data[key];
        } else {
          el.value = data[key];
        }
      }
    }
    
    // Handle image display
    const currentImageName = document.getElementById('current_image_name');
    if (data.image) {
      currentImageName.textContent = data.image;
    } else {
      currentImageName.textContent = 'No image';
    }
    
    // Show modal
    new bootstrap.Modal(document.getElementById('editAdminModal')).show();
  });
});

// Search functionality
document.addEventListener('DOMContentLoaded', function() {
  // Simple table search
  const searchInput = document.createElement('input');
  searchInput.type = 'text';
  searchInput.placeholder = 'Search admins...';
  searchInput.className = 'form-control mb-3';
  searchInput.style.maxWidth = '300px';
  
  searchInput.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('#adminsTable tbody tr');
    
    rows.forEach(row => {
      const text = row.textContent.toLowerCase();
      row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
  });
  
  // Insert search box before the table
  const tableContainer = document.querySelector('.table-responsive');
  tableContainer.parentNode.insertBefore(searchInput, tableContainer);
});
</script>

<?php include 'footer.php'; ?>

