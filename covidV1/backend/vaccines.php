
<?php
include 'db.php';

include 'header.php';

$errors = [];

// ================== ADD NEW VACCINE ==================
if (isset($_POST['newvaccine'])) {
    $name = trim($_POST['name']);
    $manufacturer = trim($_POST['manufacturer']);
    $description = trim($_POST['description']);
    $available = $_POST['available'];

    // Validation
    if (empty($name)) {
        $errors['name'] = "Vaccine name is required.";
    }
    
    // Check if vaccine name already exists
    $check_vaccine = mysqli_query($conn, "SELECT vaccine_id FROM vaccines WHERE name = '$name'");
    if (mysqli_num_rows($check_vaccine) > 0) {
        $errors['name'] = "Vaccine name already exists in the system.";
    }

    if (empty($errors)) {
        // Handle file upload
        $image = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $target_dir = "uploads/vaccines/";
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

        $stmt = mysqli_prepare($conn, "INSERT INTO vaccines 
            (name, manufacturer, description, available, image) 
            VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sssss", 
            $name, $manufacturer, $description, $available, $image
        );
        
        if (mysqli_stmt_execute($stmt)) {
            echo "<script>
                document.addEventListener('DOMContentLoaded', () => {
                    Swal.fire({icon:'success', title:'Vaccine Added Successfully', showConfirmButton:false, timer:2500});
                });
            </script>";
        } else {
            $errors['database'] = "Error adding vaccine: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
}

// ================== DELETE VACCINE ==================
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Check if vaccine has related records in vaccinations table
    $check_records = mysqli_query($conn, "SELECT COUNT(*) as count FROM vaccinations WHERE vaccine_id = $id");
    $result = mysqli_fetch_assoc($check_records);
    
    if ($result['count'] > 0) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', () => {
                Swal.fire({icon:'error', title:'Cannot Delete', text:'Vaccine has related records in vaccinations.', showConfirmButton:true});
            });
        </script>";
    } else {
        mysqli_query($conn, "DELETE FROM vaccines WHERE vaccine_id = $id");
        header("Location: vaccines.php");
        exit();
    }
}

// ================== UPDATE VACCINE ==================
if (isset($_POST['updateVaccine'])) {
    $id = $_POST['edit_id'];
    $name = trim($_POST['edit_name']);
    $manufacturer = trim($_POST['edit_manufacturer']);
    $description = trim($_POST['edit_description']);
    $available = $_POST['edit_available'];

    // Check if vaccine name already exists (excluding current vaccine)
    $check_vaccine = mysqli_query($conn, "SELECT vaccine_id FROM vaccines WHERE name = '$name' AND vaccine_id != $id");
    if (mysqli_num_rows($check_vaccine) > 0) {
        $errors['edit_name'] = "Vaccine name already exists in the system.";
    }

    if (empty($errors)) {
        // Handle file upload
        $image = $_POST['current_image'];
        if (isset($_FILES['edit_image']) && $_FILES['edit_image']['error'] == 0) {
            $target_dir = "uploads/vaccines/";
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

        $stmt = mysqli_prepare($conn, "UPDATE vaccines 
            SET name=?, manufacturer=?, description=?, available=?, image=?
            WHERE vaccine_id=?");
        mysqli_stmt_bind_param($stmt, "sssssi",
            $name, $manufacturer, $description, $available, $image, $id
        );
        
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        echo "<script>
            document.addEventListener('DOMContentLoaded', () => {
                Swal.fire({icon:'success', title:'Vaccine Updated Successfully', showConfirmButton:false, timer:2500});
                setTimeout(() => { window.location.href = 'vaccines.php'; }, 1000);
            });
        </script>";
    }
}
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Vaccines Management</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addVaccineModal">
            <i class="bi bi-plus-circle"></i> Add New Vaccine
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
        <table class="table table-hover align-middle text-center" id="vaccinesTable">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Vaccine Name</th>
                    <th>Manufacturer</th>
                    <th>Description</th>
                    <th>Availability</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = "SELECT * FROM vaccines ORDER BY vaccine_id DESC";
                $result = mysqli_query($conn, $query);
                
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $image_path = $row['image'] ? "uploads/vaccines/{$row['image']}" : "https://via.placeholder.com/100?text=Vaccine";
                        $created_date = date('M j, Y', strtotime($row['created_at']));
                        $short_description = strlen($row['description']) > 100 ? substr($row['description'], 0, 100) . '...' : $row['description'];
                        
                        // Availability badge
                        $availability_class = $row['available'] == 'Yes' ? 'bg-success' : 'bg-danger';
                        
                        echo "<tr>
                            <td>{$row['vaccine_id']}</td>
                            <td>
                                <img src='{$image_path}' alt='{$row['name']}' class='rounded' width='50' height='50' style='object-fit: cover;'>
                            </td>
                            <td class='fw-bold'>{$row['name']}</td>
                            <td>{$row['manufacturer']}</td>
                            <td class='text-start' title='{$row['description']}'>
                                <small>{$short_description}</small>
                            </td>
                            <td>
                                <span class='badge {$availability_class}'>{$row['available']}</span>
                            </td>
                            <td>{$created_date}</td>
                            <td>
                                <button class='btn btn-sm btn-outline-primary editBtn'
                                    data-id='{$row['vaccine_id']}'
                                    data-name='{$row['name']}'
                                    data-manufacturer='{$row['manufacturer']}'
                                    data-description='{$row['description']}'
                                    data-available='{$row['available']}'
                                    data-image='{$row['image']}'>
                                    <i class='bi bi-pencil-square'></i>
                                </button>
                                <button class='btn btn-sm btn-outline-danger deleteBtn' data-id='{$row['vaccine_id']}' data-name='{$row['name']}'>
                                    <i class='bi bi-trash'></i>
                                </button>
                            </td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='8' class='text-center py-4 text-muted'>No vaccines found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Vaccine Modal -->
<div class="modal fade" id="addVaccineModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content p-3">
      <form method="POST" enctype="multipart/form-data">
        <h5 class="text-center mb-3">Add New Vaccine</h5>

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Vaccine Name *</label>
            <input type="text" class="form-control" name="name" placeholder="e.g., Pfizer-BioNTech" required>
          </div>

          <div class="col-md-6">
            <label class="form-label">Manufacturer *</label>
            <input type="text" class="form-control" name="manufacturer" placeholder="e.g., Pfizer Inc." required>
          </div>

          <div class="col-md-6">
            <label class="form-label">Availability</label>
            <select class="form-select" name="available" required>
              <option value="Yes">Available (Yes)</option>
              <option value="No">Not Available (No)</option>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Vaccine Image</label>
            <input type="file" class="form-control" name="image" accept="image/*">
          </div>

          <div class="col-12">
            <label class="form-label">Description *</label>
            <textarea class="form-control" name="description" rows="4" placeholder="Enter vaccine description, type, dosage information..." required></textarea>
          </div>
        </div>

        <button type="submit" name="newvaccine" class="btn btn-primary mt-3 w-100">Add Vaccine</button>
      </form>
    </div>
  </div>
</div>

<!-- Edit Vaccine Modal -->
<div class="modal fade" id="editVaccineModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content p-3">
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="edit_id" id="edit_id">
        <input type="hidden" name="current_image" id="current_image">
        
        <h5 class="text-center mb-3">Edit Vaccine</h5>

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Vaccine Name *</label>
            <input type="text" class="form-control" id="edit_name" name="edit_name" required>
          </div>

          <div class="col-md-6">
            <label class="form-label">Manufacturer *</label>
            <input type="text" class="form-control" id="edit_manufacturer" name="edit_manufacturer" required>
          </div>

          <div class="col-md-6">
            <label class="form-label">Availability</label>
            <select class="form-select" id="edit_available" name="edit_available" required>
              <option value="Yes">Available (Yes)</option>
              <option value="No">Not Available (No)</option>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Vaccine Image</label>
            <input type="file" class="form-control" name="edit_image" accept="image/*">
            <small class="text-muted">Current: <span id="current_image_name"></span></small>
          </div>

          <div class="col-12">
            <label class="form-label">Description *</label>
            <textarea class="form-control" id="edit_description" name="edit_description" rows="4" required></textarea>
          </div>
        </div>

        <button type="submit" name="updateVaccine" class="btn btn-success mt-3 w-100">Update Vaccine</button>
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
      html: `You are about to delete vaccine: <strong>${name}</strong>`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Yes, delete it!'
    }).then(result => {
      if (result.isConfirmed) {
        window.location.href = 'vaccines.php?delete=' + id;
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
    new bootstrap.Modal(document.getElementById('editVaccineModal')).show();
  });
});

// Search functionality
document.addEventListener('DOMContentLoaded', function() {
  // Simple table search
  const searchInput = document.createElement('input');
  searchInput.type = 'text';
  searchInput.placeholder = 'Search vaccines...';
  searchInput.className = 'form-control mb-3';
  searchInput.style.maxWidth = '300px';
  
  searchInput.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('#vaccinesTable tbody tr');
    
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

