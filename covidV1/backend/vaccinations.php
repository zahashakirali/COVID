
<?php
include 'db.php';

include 'header.php';

$errors = [];

// Fetch related data for dropdowns
$patients = mysqli_query($conn, "SELECT patient_id, name FROM patients ORDER BY name ASC");
$hospitals = mysqli_query($conn, "SELECT hospital_id, name FROM hospitals WHERE is_approved = 'Approved' ORDER BY name ASC");
$vaccines = mysqli_query($conn, "SELECT vaccine_id, name FROM vaccines WHERE available = 'Yes' ORDER BY name ASC");

// ================== ADD NEW VACCINATION ==================
if (isset($_POST['newvaccination'])) {
    $patient_id = $_POST['patient_id'];
    $hospital_id = $_POST['hospital_id'];
    $vaccine_id = $_POST['vaccine_id'];
    $vaccination_date = $_POST['vaccination_date'];
    $status = $_POST['status'];
    $dose_number = $_POST['dose_number'];

    // Validation
    if ($status == 'Completed' && empty($vaccination_date)) {
        $errors['vaccination_date'] = "Vaccination date is required for completed vaccinations.";
    }
    
    if (!is_numeric($dose_number) || $dose_number < 1) {
        $errors['dose_number'] = "Dose number must be at least 1.";
    }

    if (empty($errors)) {
        $stmt = mysqli_prepare($conn, "INSERT INTO vaccinations 
            (patient_id, hospital_id, vaccine_id, vaccination_date, status, dose_number) 
            VALUES (?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iiissi", 
            $patient_id, $hospital_id, $vaccine_id, $vaccination_date, $status, $dose_number
        );
        
        if (mysqli_stmt_execute($stmt)) {
            echo "<script>
                document.addEventListener('DOMContentLoaded', () => {
                    Swal.fire({icon:'success', title:'Vaccination Record Added Successfully', showConfirmButton:false, timer:2500});
                });
            </script>";
        } else {
            $errors['database'] = "Error adding vaccination record: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
}

// ================== DELETE VACCINATION ==================
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    mysqli_query($conn, "DELETE FROM vaccinations WHERE vaccination_id = $id");
    header("Location: vaccinations.php");
    exit();
}

// ================== UPDATE VACCINATION ==================
if (isset($_POST['updateVaccination'])) {
    $id = $_POST['edit_id'];
    $patient_id = $_POST['edit_patient_id'];
    $hospital_id = $_POST['edit_hospital_id'];
    $vaccine_id = $_POST['edit_vaccine_id'];
    $vaccination_date = $_POST['edit_vaccination_date'];
    $status = $_POST['edit_status'];
    $dose_number = $_POST['edit_dose_number'];

    // Validation
    if ($status == 'Completed' && empty($vaccination_date)) {
        $errors['edit_vaccination_date'] = "Vaccination date is required for completed vaccinations.";
    }
    
    if (!is_numeric($dose_number) || $dose_number < 1) {
        $errors['edit_dose_number'] = "Dose number must be at least 1.";
    }

    if (empty($errors)) {
        $stmt = mysqli_prepare($conn, "UPDATE vaccinations 
            SET patient_id=?, hospital_id=?, vaccine_id=?, vaccination_date=?, status=?, dose_number=?
            WHERE vaccination_id=?");
        mysqli_stmt_bind_param($stmt, "iiissii",
            $patient_id, $hospital_id, $vaccine_id, $vaccination_date, $status, $dose_number, $id
        );
        
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        echo "<script>
            document.addEventListener('DOMContentLoaded', () => {
                Swal.fire({icon:'success', title:'Vaccination Record Updated Successfully', showConfirmButton:false, timer:2500});
                setTimeout(() => { window.location.href = 'vaccinations.php'; }, 1000);
            });
        </script>";
    }
}
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Vaccinations Management</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addVaccinationModal">
            <i class="bi bi-plus-circle"></i> Add New Vaccination
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
        <table class="table table-hover align-middle text-center" id="vaccinationsTable">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Patient</th>
                    <th>Hospital</th>
                    <th>Vaccine</th>
                    <th>Vaccination Date</th>
                    <th>Status</th>
                    <th>Dose No.</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = "SELECT v.*, 
                         p.name as patient_name,
                         h.name as hospital_name,
                         vac.name as vaccine_name
                         FROM vaccinations v
                         JOIN patients p ON v.patient_id = p.patient_id
                         JOIN hospitals h ON v.hospital_id = h.hospital_id
                         JOIN vaccines vac ON v.vaccine_id = vac.vaccine_id
                         ORDER BY v.vaccination_id DESC";
                $result = mysqli_query($conn, $query);
                
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $vaccination_date = $row['vaccination_date'] ? date('M j, Y', strtotime($row['vaccination_date'])) : 'Not Scheduled';
                        $created_date = date('M j, Y', strtotime($row['created_at']));
                        
                        // Status badge colors
                        $status_class = '';
                        if ($row['status'] == 'Completed') {
                            $status_class = 'bg-success';
                        } elseif ($row['status'] == 'Pending') {
                            $status_class = 'bg-warning text-dark';
                        } else {
                            $status_class = 'bg-danger';
                        }
                        
                        // Dose badge
                        $dose_class = $row['dose_number'] == 1 ? 'bg-info' : 'bg-primary';
                        
                        echo "<tr>
                            <td>{$row['vaccination_id']}</td>
                            <td>{$row['patient_name']}</td>
                            <td>{$row['hospital_name']}</td>
                            <td>{$row['vaccine_name']}</td>
                            <td>{$vaccination_date}</td>
                            <td>
                                <span class='badge {$status_class}'>{$row['status']}</span>
                            </td>
                            <td>
                                <span class='badge {$dose_class}'>Dose {$row['dose_number']}</span>
                            </td>
                            <td>{$created_date}</td>
                            <td>
                                <button class='btn btn-sm btn-outline-primary editBtn'
                                    data-id='{$row['vaccination_id']}'
                                    data-patient_id='{$row['patient_id']}'
                                    data-hospital_id='{$row['hospital_id']}'
                                    data-vaccine_id='{$row['vaccine_id']}'
                                    data-vaccination_date='{$row['vaccination_date']}'
                                    data-status='{$row['status']}'
                                    data-dose_number='{$row['dose_number']}'>
                                    <i class='bi bi-pencil-square'></i>
                                </button>
                                <button class='btn btn-sm btn-outline-danger deleteBtn' data-id='{$row['vaccination_id']}' data-patient='{$row['patient_name']}'>
                                    <i class='bi bi-trash'></i>
                                </button>
                            </td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='9' class='text-center py-4 text-muted'>No vaccination records found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Vaccination Modal -->
<div class="modal fade" id="addVaccinationModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content p-3">
      <form method="POST">
        <h5 class="text-center mb-3">Add New Vaccination Record</h5>

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Patient *</label>
            <select class="form-select" name="patient_id" required>
              <option value="">-- Select Patient --</option>
              <?php
              mysqli_data_seek($patients, 0);
              while ($p = mysqli_fetch_assoc($patients)) {
                  echo "<option value='{$p['patient_id']}'>{$p['name']}</option>";
              }
              ?>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Hospital *</label>
            <select class="form-select" name="hospital_id" required>
              <option value="">-- Select Hospital --</option>
              <?php
              mysqli_data_seek($hospitals, 0);
              while ($h = mysqli_fetch_assoc($hospitals)) {
                  echo "<option value='{$h['hospital_id']}'>{$h['name']}</option>";
              }
              ?>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Vaccine *</label>
            <select class="form-select" name="vaccine_id" required>
              <option value="">-- Select Vaccine --</option>
              <?php
              mysqli_data_seek($vaccines, 0);
              while ($v = mysqli_fetch_assoc($vaccines)) {
                  echo "<option value='{$v['vaccine_id']}'>{$v['name']}</option>";
              }
              ?>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Status *</label>
            <select class="form-select" name="status" required>
              <option value="Pending">Pending</option>
              <option value="Completed">Completed</option>
              <option value="Cancelled">Cancelled</option>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Vaccination Date</label>
            <input type="date" class="form-control" name="vaccination_date">
            <small class="text-muted">Required for completed vaccinations</small>
          </div>

          <div class="col-md-6">
            <label class="form-label">Dose Number *</label>
            <select class="form-select" name="dose_number" required>
              <option value="1">Dose 1</option>
              <option value="2">Dose 2</option>
              <option value="3">Booster Dose</option>
            </select>
          </div>
        </div>

        <button type="submit" name="newvaccination" class="btn btn-primary mt-3 w-100">Add Vaccination Record</button>
      </form>
    </div>
  </div>
</div>

<!-- Edit Vaccination Modal -->
<div class="modal fade" id="editVaccinationModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content p-3">
      <form method="POST">
        <input type="hidden" name="edit_id" id="edit_id">
        
        <h5 class="text-center mb-3">Edit Vaccination Record</h5>

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Patient *</label>
            <select class="form-select" id="edit_patient_id" name="edit_patient_id" required>
              <option value="">-- Select Patient --</option>
              <?php
              mysqli_data_seek($patients, 0);
              while ($p = mysqli_fetch_assoc($patients)) {
                  echo "<option value='{$p['patient_id']}'>{$p['name']}</option>";
              }
              ?>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Hospital *</label>
            <select class="form-select" id="edit_hospital_id" name="edit_hospital_id" required>
              <option value="">-- Select Hospital --</option>
              <?php
              mysqli_data_seek($hospitals, 0);
              while ($h = mysqli_fetch_assoc($hospitals)) {
                  echo "<option value='{$h['hospital_id']}'>{$h['name']}</option>";
              }
              ?>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Vaccine *</label>
            <select class="form-select" id="edit_vaccine_id" name="edit_vaccine_id" required>
              <option value="">-- Select Vaccine --</option>
              <?php
              mysqli_data_seek($vaccines, 0);
              while ($v = mysqli_fetch_assoc($vaccines)) {
                  echo "<option value='{$v['vaccine_id']}'>{$v['name']}</option>";
              }
              ?>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Status *</label>
            <select class="form-select" id="edit_status" name="edit_status" required>
              <option value="Pending">Pending</option>
              <option value="Completed">Completed</option>
              <option value="Cancelled">Cancelled</option>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Vaccination Date</label>
            <input type="date" class="form-control" id="edit_vaccination_date" name="edit_vaccination_date">
            <small class="text-muted">Required for completed vaccinations</small>
          </div>

          <div class="col-md-6">
            <label class="form-label">Dose Number *</label>
            <select class="form-select" id="edit_dose_number" name="edit_dose_number" required>
              <option value="1">Dose 1</option>
              <option value="2">Dose 2</option>
              <option value="3">Booster Dose</option>
            </select>
          </div>
        </div>

        <button type="submit" name="updateVaccination" class="btn btn-success mt-3 w-100">Update Vaccination Record</button>
      </form>
    </div>
  </div>
</div>

<script>
// DELETE CONFIRMATION
document.querySelectorAll('.deleteBtn').forEach(btn => {
  btn.addEventListener('click', function() {
    const id = this.getAttribute('data-id');
    const patient = this.getAttribute('data-patient');
    
    Swal.fire({
      title: 'Are you sure?',
      html: `You are about to delete vaccination record for: <strong>${patient}</strong>`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Yes, delete it!'
    }).then(result => {
      if (result.isConfirmed) {
        window.location.href = 'vaccinations.php?delete=' + id;
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
    
    // Show modal
    new bootstrap.Modal(document.getElementById('editVaccinationModal')).show();
  });
});

// Search functionality
document.addEventListener('DOMContentLoaded', function() {
  // Simple table search
  const searchInput = document.createElement('input');
  searchInput.type = 'text';
  searchInput.placeholder = 'Search vaccinations...';
  searchInput.className = 'form-control mb-3';
  searchInput.style.maxWidth = '300px';
  
  searchInput.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('#vaccinationsTable tbody tr');
    
    rows.forEach(row => {
      const text = row.textContent.toLowerCase();
      row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
  });
  
  // Insert search box before the table
  const tableContainer = document.querySelector('.table-responsive');
  tableContainer.parentNode.insertBefore(searchInput, tableContainer);
});

// Dynamic form validation based on status
document.addEventListener('DOMContentLoaded', function() {
  const statusSelect = document.getElementById('edit_status');
  const dateInput = document.getElementById('edit_vaccination_date');
  
  if (statusSelect && dateInput) {
    statusSelect.addEventListener('change', function() {
      if (this.value === 'Completed') {
        dateInput.required = true;
      } else {
        dateInput.required = false;
      }
    });
  }
  
  // Also for add modal
  const addStatusSelect = document.querySelector('select[name="status"]');
  const addDateInput = document.querySelector('input[name="vaccination_date"]');
  
  if (addStatusSelect && addDateInput) {
    addStatusSelect.addEventListener('change', function() {
      if (this.value === 'Completed') {
        addDateInput.required = true;
      } else {
        addDateInput.required = false;
      }
    });
  }
});
</script>

<?php include 'footer.php'; ?>

