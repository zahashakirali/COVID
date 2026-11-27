
<?php
include 'db.php';

include 'header.php';

$errors = [];

// Fetch roles for dropdown (only hospital role)
$roles = mysqli_query($conn, "SELECT role_id, role_name FROM roles WHERE role_name = 'hospital' ORDER BY role_name ASC");

// ================== ADD NEW HOSPITAL ==================
if (isset($_POST['newhospital'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $location = trim($_POST['location']);
    $is_approved = $_POST['is_approved'];
    $role_id = $_POST['role_id'];

    // Validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Please enter a valid email address.";
    }
    
    // Check if email already exists
    $check_email = mysqli_query($conn, "SELECT hospital_id FROM hospitals WHERE email = '$email'");
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
            $target_dir = "uploads/hospitals/";
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

        $stmt = mysqli_prepare($conn, "INSERT INTO hospitals 
            (role_id, name, email, password, phone, address, city, location, image, is_approved) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "isssssssss", 
            $role_id, $name, $email, $hashed_password, $phone, $address, $city, $location, $image, $is_approved
        );
        
        if (mysqli_stmt_execute($stmt)) {
            echo "<script>
                document.addEventListener('DOMContentLoaded', () => {
                    Swal.fire({icon:'success', title:'Hospital Added Successfully', showConfirmButton:false, timer:2500});
                });
            </script>";
        } else {
            $errors['database'] = "Error adding hospital: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
}

// ================== DELETE HOSPITAL ==================
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Check if hospital has related records
    $check_records = mysqli_query($conn, "SELECT COUNT(*) as count FROM covid_tests WHERE hospital_id = $id");
    $result = mysqli_fetch_assoc($check_records);
    
    if ($result['count'] > 0) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', () => {
                Swal.fire({icon:'error', title:'Cannot Delete', text:'Hospital has related records in the system.', showConfirmButton:true});
            });
        </script>";
    } else {
        mysqli_query($conn, "DELETE FROM hospitals WHERE hospital_id = $id");
        header("Location: hospitals.php");
        exit();
    }
}

// ================== UPDATE HOSPITAL ==================
if (isset($_POST['updateHospital'])) {
    $id = $_POST['edit_id'];
    $name = trim($_POST['edit_name']);
    $email = trim($_POST['edit_email']);
    $phone = trim($_POST['edit_phone']);
    $address = trim($_POST['edit_address']);
    $city = trim($_POST['edit_city']);
    $location = trim($_POST['edit_location']);
    $is_approved = $_POST['edit_is_approved'];
    $role_id = $_POST['edit_role_id'];

    // Check if email already exists (excluding current hospital)
    $check_email = mysqli_query($conn, "SELECT hospital_id FROM hospitals WHERE email = '$email' AND hospital_id != $id");
    if (mysqli_num_rows($check_email) > 0) {
        $errors['edit_email'] = "Email already exists in the system.";
    }

    if (empty($errors)) {
        // Handle file upload
        $image = $_POST['current_image'];
        if (isset($_FILES['edit_image']) && $_FILES['edit_image']['error'] == 0) {
            $target_dir = "uploads/hospitals/";
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
        if (!empty($_POST['edit_password'])) {
            $hashed_password = password_hash($_POST['edit_password'], PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, "UPDATE hospitals 
                SET role_id=?, name=?, email=?, phone=?, address=?, city=?, location=?, image=?, is_approved=?, password=?
                WHERE hospital_id=?");
            mysqli_stmt_bind_param($stmt, "isssssssssi",
                $role_id, $name, $email, $phone, $address, $city, $location, $image, $is_approved, $hashed_password, $id
            );
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE hospitals 
                SET role_id=?, name=?, email=?, phone=?, address=?, city=?, location=?, image=?, is_approved=?
                WHERE hospital_id=?");
            mysqli_stmt_bind_param($stmt, "issssssssi",
                $role_id, $name, $email, $phone, $address, $city, $location, $image, $is_approved, $id
            );
        }
        
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        echo "<script>
            document.addEventListener('DOMContentLoaded', () => {
                Swal.fire({icon:'success', title:'Hospital Updated Successfully', showConfirmButton:false, timer:2500});
                setTimeout(() => { window.location.href = 'hospitals.php'; }, 1000);
            });
        </script>";
    }
}
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Hospitals Management</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addHospitalModal">
            <i class="bi bi-plus-circle"></i> Add New Hospital
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
        <table class="table table-hover align-middle text-center" id="hospitalsTable">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>City</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th>Registered</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = "SELECT h.*, r.role_name 
                          FROM hospitals h 
                          JOIN roles r ON h.role_id = r.role_id 
                          ORDER BY h.hospital_id DESC";
                $result = mysqli_query($conn, $query);
                
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $image_path = $row['image'] ? "uploads/hospitals/{$row['image']}" : "https://ui-avatars.com/api/?name=" . urlencode($row['name']) . "&size=100&background=random";
                        $created_date = date('M j, Y', strtotime($row['created_at']));
                        
                        // Status badge colors
                        $status_class = '';
                        if ($row['is_approved'] == 'Approved') {
                            $status_class = 'bg-success';
                        } elseif ($row['is_approved'] == 'Pending') {
                            $status_class = 'bg-warning text-dark';
                        } else {
                            $status_class = 'bg-danger';
                        }
                        
                        echo "<tr>
                            <td>{$row['hospital_id']}</td>
                            <td>
                                <img src='{$image_path}' alt='{$row['name']}' class='rounded-circle' width='40' height='40' style='object-fit: cover;'>
                            </td>
                            <td>{$row['name']}</td>
                            <td>{$row['email']}</td>
                            <td>{$row['phone']}</td>
                            <td class='text-start'><small>{$row['address']}</small></td>
                            <td>{$row['city']}</td>
                            <td>{$row['location']}</td>
                            <td>
                                <span class='badge {$status_class}'>{$row['is_approved']}</span>
                            </td>
                            <td>{$created_date}</td>
                            <td>
                                <button class='btn btn-sm btn-outline-primary editBtn'
                                    data-id='{$row['hospital_id']}'
                                    data-role_id='{$row['role_id']}'
                                    data-name='{$row['name']}'
                                    data-email='{$row['email']}'
                                    data-phone='{$row['phone']}'
                                    data-address='{$row['address']}'
                                    data-city='{$row['city']}'
                                    data-location='{$row['location']}'
                                    data-is_approved='{$row['is_approved']}'
                                    data-image='{$row['image']}'>
                                    <i class='bi bi-pencil-square'></i>
                                </button>
                                <button class='btn btn-sm btn-outline-danger deleteBtn' data-id='{$row['hospital_id']}' data-name='{$row['name']}'>
                                    <i class='bi bi-trash'></i>
                                </button>
                            </td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='11' class='text-center py-4 text-muted'>No hospitals found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Hospital Modal -->
<div class="modal fade" id="addHospitalModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content p-3">
      <form method="POST" enctype="multipart/form-data">
        <h5 class="text-center mb-3">Add New Hospital</h5>

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
            <label class="form-label">Approval Status</label>
            <select class="form-select" name="is_approved" required>
              <option value="Pending">Pending</option>
              <option value="Approved">Approved</option>
              <option value="Rejected">Rejected</option>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Hospital Image</label>
            <input type="file" class="form-control" name="image" accept="image/*">
          </div>

          <div class="col-md-6">
            <label class="form-label">Hospital Name *</label>
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

          <div class="col-md-6">
            <label class="form-label">City</label>
            <input type="text" class="form-control" name="city">
          </div>

          <div class="col-12">
            <label class="form-label">Address *</label>
            <textarea class="form-control" name="address" rows="2" required></textarea>
          </div>

          <div class="col-12">
            <label class="form-label">Location</label>
            <input type="text" class="form-control" name="location" placeholder="e.g., Stadium Road, Gulshan-e-Iqbal">
          </div>
        </div>

        <button type="submit" name="newhospital" class="btn btn-primary mt-3 w-100">Add Hospital</button>
      </form>
    </div>
  </div>
</div>

<!-- Edit Hospital Modal -->
<div class="modal fade" id="editHospitalModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content p-3">
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="edit_id" id="edit_id">
        <input type="hidden" name="current_image" id="current_image">
        
        <h5 class="text-center mb-3">Edit Hospital</h5>

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
            <label class="form-label">Approval Status</label>
            <select class="form-select" id="edit_is_approved" name="edit_is_approved" required>
              <option value="Pending">Pending</option>
              <option value="Approved">Approved</option>
              <option value="Rejected">Rejected</option>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Hospital Image</label>
            <input type="file" class="form-control" name="edit_image" accept="image/*">
            <small class="text-muted">Current: <span id="current_image_name"></span></small>
          </div>

          <div class="col-md-6">
            <label class="form-label">Hospital Name *</label>
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

          <div class="col-md-6">
            <label class="form-label">City</label>
            <input type="text" class="form-control" id="edit_city" name="edit_city">
          </div>

          <div class="col-12">
            <label class="form-label">Address *</label>
            <textarea class="form-control" id="edit_address" name="edit_address" rows="2" required></textarea>
          </div>

          <div class="col-12">
            <label class="form-label">Location</label>
            <input type="text" class="form-control" id="edit_location" name="edit_location">
          </div>
        </div>

        <button type="submit" name="updateHospital" class="btn btn-success mt-3 w-100">Update Hospital</button>
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
      html: `You are about to delete hospital: <strong>${name}</strong>`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Yes, delete it!'
    }).then(result => {
      if (result.isConfirmed) {
        window.location.href = 'hospitals.php?delete=' + id;
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
    new bootstrap.Modal(document.getElementById('editHospitalModal')).show();
  });
});

// Search functionality
document.addEventListener('DOMContentLoaded', function() {
  // Simple table search
  const searchInput = document.createElement('input');
  searchInput.type = 'text';
  searchInput.placeholder = 'Search hospitals...';
  searchInput.className = 'form-control mb-3';
  searchInput.style.maxWidth = '300px';
  
  searchInput.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('#hospitalsTable tbody tr');
    
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

