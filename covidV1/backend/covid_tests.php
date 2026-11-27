
<?php
include 'db.php';

include 'header.php';

$errors = [];

// Fetch related data for dropdowns
$patients = mysqli_query($conn, "SELECT patient_id, name FROM patients ORDER BY name ASC");
$hospitals = mysqli_query($conn, "SELECT hospital_id, name FROM hospitals WHERE is_approved = 'Approved' ORDER BY name ASC");

// ================== ADD NEW COVID TEST ==================
if (isset($_POST['newcovidtest'])) {
    $patient_id = $_POST['patient_id'];
    $hospital_id = $_POST['hospital_id'];
    $test_date = $_POST['test_date'];
    $result = $_POST['result'];
    $remarks = trim($_POST['remarks']);

    // Validation
    if (empty($test_date)) {
        $errors['test_date'] = "Test date is required.";
    }
    
    if (strtotime($test_date) > strtotime('today')) {
        $errors['test_date'] = "Test date cannot be in the future.";
    }

    if (empty($errors)) {
        $stmt = mysqli_prepare($conn, "INSERT INTO covid_tests 
            (patient_id, hospital_id, test_date, result, remarks) 
            VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iisss", 
            $patient_id, $hospital_id, $test_date, $result, $remarks
        );
        
        if (mysqli_stmt_execute($stmt)) {
            echo "<script>
                document.addEventListener('DOMContentLoaded', () => {
                    Swal.fire({icon:'success', title:'COVID Test Record Added Successfully', showConfirmButton:false, timer:2500});
                });
            </script>";
        } else {
            $errors['database'] = "Error adding COVID test record: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
}

// ================== DELETE COVID TEST ==================
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    mysqli_query($conn, "DELETE FROM covid_tests WHERE test_id = $id");
    header("Location: covid_tests.php");
    exit();
}

// ================== UPDATE COVID TEST ==================
if (isset($_POST['updateCovidTest'])) {
    $id = $_POST['edit_id'];
    $patient_id = $_POST['edit_patient_id'];
    $hospital_id = $_POST['edit_hospital_id'];
    $test_date = $_POST['edit_test_date'];
    $result = $_POST['edit_result'];
    $remarks = trim($_POST['edit_remarks']);

    // Validation
    if (empty($test_date)) {
        $errors['edit_test_date'] = "Test date is required.";
    }
    
    if (strtotime($test_date) > strtotime('today')) {
        $errors['edit_test_date'] = "Test date cannot be in the future.";
    }

    if (empty($errors)) {
        $stmt = mysqli_prepare($conn, "UPDATE covid_tests 
            SET patient_id=?, hospital_id=?, test_date=?, result=?, remarks=?
            WHERE test_id=?");
        mysqli_stmt_bind_param($stmt, "iisssi",
            $patient_id, $hospital_id, $test_date, $result, $remarks, $id
        );
        
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        echo "<script>
            document.addEventListener('DOMContentLoaded', () => {
                Swal.fire({icon:'success', title:'COVID Test Record Updated Successfully', showConfirmButton:false, timer:2500});
                setTimeout(() => { window.location.href = 'covid_tests.php'; }, 1000);
            });
        </script>";
    }
}
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>COVID Tests Management</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCovidTestModal">
            <i class="bi bi-plus-circle"></i> Add New COVID Test
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
        <table class="table table-hover align-middle text-center" id="covidTestsTable">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Patient</th>
                    <th>Hospital</th>
                    <th>Test Date</th>
                    <th>Result</th>
                    <th>Remarks</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = "SELECT ct.*, 
                         p.name as patient_name,
                         h.name as hospital_name
                         FROM covid_tests ct
                         JOIN patients p ON ct.patient_id = p.patient_id
                         JOIN hospitals h ON ct.hospital_id = h.hospital_id
                         ORDER BY ct.test_id DESC";
                $result = mysqli_query($conn, $query);
                
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $test_date = date('M j, Y', strtotime($row['test_date']));
                        $created_date = date('M j, Y', strtotime($row['created_at']));
                        $short_remarks = strlen($row['remarks']) > 50 ? substr($row['remarks'], 0, 50) . '...' : $row['remarks'];
                        
                        // Result badge colors
                        $result_class = '';
                        if ($row['result'] == 'Negative') {
                            $result_class = 'bg-success';
                        } elseif ($row['result'] == 'Positive') {
                            $result_class = 'bg-danger';
                        } else {
                            $result_class = 'bg-warning text-dark';
                        }
                        
                        echo "<tr>
                            <td>{$row['test_id']}</td>
                            <td>{$row['patient_name']}</td>
                            <td>{$row['hospital_name']}</td>
                            <td>{$test_date}</td>
                            <td>
                                <span class='badge {$result_class}'>{$row['result']}</span>
                            </td>
                            <td class='text-start' title='{$row['remarks']}'>
                                <small>{$short_remarks}</small>
                            </td>
                            <td>{$created_date}</td>
                            <td>
                                <button class='btn btn-sm btn-outline-primary editBtn'
                                    data-id='{$row['test_id']}'
                                    data-patient_id='{$row['patient_id']}'
                                    data-hospital_id='{$row['hospital_id']}'
                                    data-test_date='{$row['test_date']}'
                                    data-result='{$row['result']}'
                                    data-remarks='{$row['remarks']}'>
                                    <i class='bi bi-pencil-square'></i>
                                </button>
                                <button class='btn btn-sm btn-outline-danger deleteBtn' data-id='{$row['test_id']}' data-patient='{$row['patient_name']}'>
                                    <i class='bi bi-trash'></i>
                                </button>
                            </td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='8' class='text-center py-4 text-muted'>No COVID test records found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add COVID Test Modal -->
<div class="modal fade" id="addCovidTestModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content p-3">
      <form method="POST">
        <h5 class="text-center mb-3">Add New COVID Test Record</h5>

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
            <label class="form-label">Test Date *</label>
            <input type="date" class="form-control" name="test_date" required max="<?php echo date('Y-m-d'); ?>">
          </div>

          <div class="col-md-6">
            <label class="form-label">Result *</label>
            <select class="form-select" name="result" required>
              <option value="Pending">Pending</option>
              <option value="Positive">Positive</option>
              <option value="Negative">Negative</option>
            </select>
          </div>

          <div class="col-12">
            <label class="form-label">Remarks</label>
            <textarea class="form-control" name="remarks" rows="3" placeholder="Enter test remarks, symptoms, or recommendations..."></textarea>
          </div>
        </div>

        <button type="submit" name="newcovidtest" class="btn btn-primary mt-3 w-100">Add COVID Test Record</button>
      </form>
    </div>
  </div>
</div>

<!-- Edit COVID Test Modal -->
<div class="modal fade" id="editCovidTestModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content p-3">
      <form method="POST">
        <input type="hidden" name="edit_id" id="edit_id">
        
        <h5 class="text-center mb-3">Edit COVID Test Record</h5>

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
            <label class="form-label">Test Date *</label>
            <input type="date" class="form-control" id="edit_test_date" name="edit_test_date" required max="<?php echo date('Y-m-d'); ?>">
          </div>

          <div class="col-md-6">
            <label class="form-label">Result *</label>
            <select class="form-select" id="edit_result" name="edit_result" required>
              <option value="Pending">Pending</option>
              <option value="Positive">Positive</option>
              <option value="Negative">Negative</option>
            </select>
          </div>

          <div class="col-12">
            <label class="form-label">Remarks</label>
            <textarea class="form-control" id="edit_remarks" name="edit_remarks" rows="3"></textarea>
          </div>
        </div>

        <button type="submit" name="updateCovidTest" class="btn btn-success mt-3 w-100">Update COVID Test Record</button>
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
      html: `You are about to delete COVID test record for: <strong>${patient}</strong>`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Yes, delete it!'
    }).then(result => {
      if (result.isConfirmed) {
        window.location.href = 'covid_tests.php?delete=' + id;
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
    new bootstrap.Modal(document.getElementById('editCovidTestModal')).show();
  });
});

// Search functionality
document.addEventListener('DOMContentLoaded', function() {
  // Simple table search
  const searchInput = document.createElement('input');
  searchInput.type = 'text';
  searchInput.placeholder = 'Search COVID tests...';
  searchInput.className = 'form-control mb-3';
  searchInput.style.maxWidth = '300px';
  
  searchInput.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('#covidTestsTable tbody tr');
    
    rows.forEach(row => {
      const text = row.textContent.toLowerCase();
      row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
  });
  
  // Insert search box before the table
  const tableContainer = document.querySelector('.table-responsive');
  tableContainer.parentNode.insertBefore(searchInput, tableContainer);
});

// Date validation to prevent future dates
document.addEventListener('DOMContentLoaded', function() {
  const today = new Date().toISOString().split('T')[0];
  
  // Set max date for all date inputs
  const dateInputs = document.querySelectorAll('input[type="date"]');
  dateInputs.forEach(input => {
    input.setAttribute('max', today);
  });
});
</script>


<?php
include 'footer.php';
?>
