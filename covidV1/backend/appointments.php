<?php
include 'db.php';

include 'header.php';

$errors = [];

// Fetch related data for dropdowns
$patients = mysqli_query($conn, "SELECT patient_id, name FROM patients ORDER BY name ASC");
$hospitals = mysqli_query($conn, "SELECT hospital_id, name FROM hospitals WHERE is_approved = 'Approved' ORDER BY name ASC");

// ================== ADD NEW APPOINTMENT ==================
if (isset($_POST['newappointment'])) {
    $patient_id = $_POST['patient_id'];
    $hospital_id = $_POST['hospital_id'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_type = $_POST['appointment_type'];
    $status = $_POST['status'];

    // Validation
    if (empty($appointment_date)) {
        $errors['appointment_date'] = "Appointment date and time is required.";
    }
    
    if (strtotime($appointment_date) < strtotime('now')) {
        $errors['appointment_date'] = "Appointment date cannot be in the past.";
    }

    if (empty($errors)) {
        $stmt = mysqli_prepare($conn, "INSERT INTO appointments 
            (patient_id, hospital_id, appointment_date, appointment_type, status) 
            VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iisss", 
            $patient_id, $hospital_id, $appointment_date, $appointment_type, $status
        );
        
        if (mysqli_stmt_execute($stmt)) {
            echo "<script>
                document.addEventListener('DOMContentLoaded', () => {
                    Swal.fire({icon:'success', title:'Appointment Added Successfully', showConfirmButton:false, timer:2500});
                });
            </script>";
        } else {
            $errors['database'] = "Error adding appointment: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
}

// ================== DELETE APPOINTMENT ==================
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Check if appointment has related records in booking_details
    $check_records = mysqli_query($conn, "SELECT COUNT(*) as count FROM booking_details WHERE appointment_id = $id");
    $result = mysqli_fetch_assoc($check_records);
    
    if ($result['count'] > 0) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', () => {
                Swal.fire({icon:'error', title:'Cannot Delete', text:'Appointment has related records in booking details.', showConfirmButton:true});
            });
        </script>";
    } else {
        mysqli_query($conn, "DELETE FROM appointments WHERE appointment_id = $id");
        header("Location: appointments.php");
        exit();
    }
}

// ================== UPDATE APPOINTMENT ==================
if (isset($_POST['updateAppointment'])) {
    $id = $_POST['edit_id'];
    $patient_id = $_POST['edit_patient_id'];
    $hospital_id = $_POST['edit_hospital_id'];
    $appointment_date = $_POST['edit_appointment_date'];
    $appointment_type = $_POST['edit_appointment_type'];
    $status = $_POST['edit_status'];

    // Validation
    if (empty($appointment_date)) {
        $errors['edit_appointment_date'] = "Appointment date and time is required.";
    }
    
    if (strtotime($appointment_date) < strtotime('now')) {
        $errors['edit_appointment_date'] = "Appointment date cannot be in the past.";
    }

    if (empty($errors)) {
        $stmt = mysqli_prepare($conn, "UPDATE appointments 
            SET patient_id=?, hospital_id=?, appointment_date=?, appointment_type=?, status=?
            WHERE appointment_id=?");
        mysqli_stmt_bind_param($stmt, "iisssi",
            $patient_id, $hospital_id, $appointment_date, $appointment_type, $status, $id
        );
        
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        echo "<script>
            document.addEventListener('DOMContentLoaded', () => {
                Swal.fire({icon:'success', title:'Appointment Updated Successfully', showConfirmButton:false, timer:2500});
                setTimeout(() => { window.location.href = 'appointments.php'; }, 1000);
            });
        </script>";
    }
}
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Appointments Management</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAppointmentModal">
            <i class="bi bi-plus-circle"></i> Add New Appointment
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
        <table class="table table-hover align-middle text-center" id="appointmentsTable">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Patient</th>
                    <th>Hospital</th>
                    <th>Appointment Date & Time</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = "SELECT a.*, 
                         p.name as patient_name,
                         h.name as hospital_name
                         FROM appointments a
                         JOIN patients p ON a.patient_id = p.patient_id
                         JOIN hospitals h ON a.hospital_id = h.hospital_id
                         ORDER BY a.appointment_id DESC";
                $result = mysqli_query($conn, $query);
                
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $appointment_datetime = date('M j, Y g:i A', strtotime($row['appointment_date']));
                        $created_date = date('M j, Y', strtotime($row['created_at']));
                        
                        // Status badge colors
                        $status_class = '';
                        if ($row['status'] == 'Completed') {
                            $status_class = 'bg-success';
                        } elseif ($row['status'] == 'Approved') {
                            $status_class = 'bg-primary';
                        } elseif ($row['status'] == 'Pending') {
                            $status_class = 'bg-warning text-dark';
                        } else {
                            $status_class = 'bg-danger';
                        }
                        
                        // Type badge colors
                        $type_class = $row['appointment_type'] == 'Vaccination' ? 'bg-info' : 'bg-secondary';
                        
                        echo "<tr>
                            <td>{$row['appointment_id']}</td>
                            <td>{$row['patient_name']}</td>
                            <td>{$row['hospital_name']}</td>
                            <td>{$appointment_datetime}</td>
                            <td>
                                <span class='badge {$type_class}'>{$row['appointment_type']}</span>
                            </td>
                            <td>
                                <span class='badge {$status_class}'>{$row['status']}</span>
                            </td>
                            <td>{$created_date}</td>
                            <td>
                                <button class='btn btn-sm btn-outline-primary editBtn'
                                    data-id='{$row['appointment_id']}'
                                    data-patient_id='{$row['patient_id']}'
                                    data-hospital_id='{$row['hospital_id']}'
                                    data-appointment_date='{$row['appointment_date']}'
                                    data-appointment_type='{$row['appointment_type']}'
                                    data-status='{$row['status']}'>
                                    <i class='bi bi-pencil-square'></i>
                                </button>
                                <button class='btn btn-sm btn-outline-danger deleteBtn' data-id='{$row['appointment_id']}' data-patient='{$row['patient_name']}'>
                                    <i class='bi bi-trash'></i>
                                </button>
                            </td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='8' class='text-center py-4 text-muted'>No appointments found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Appointment Modal -->
<div class="modal fade" id="addAppointmentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content p-3">
      <form method="POST">
        <h5 class="text-center mb-3">Add New Appointment</h5>

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
            <label class="form-label">Appointment Date & Time *</label>
            <input type="datetime-local" class="form-control" name="appointment_date" required min="<?php echo date('Y-m-d\TH:i'); ?>">
          </div>

          <div class="col-md-6">
            <label class="form-label">Appointment Type *</label>
            <select class="form-select" name="appointment_type" required>
              <option value="Covid Test">COVID Test</option>
              <option value="Vaccination">Vaccination</option>
            </select>
          </div>

          <div class="col-12">
            <label class="form-label">Status *</label>
            <select class="form-select" name="status" required>
              <option value="Pending">Pending</option>
              <option value="Approved">Approved</option>
              <option value="Rejected">Rejected</option>
              <option value="Completed">Completed</option>
            </select>
          </div>
        </div>

        <button type="submit" name="newappointment" class="btn btn-primary mt-3 w-100">Add Appointment</button>
      </form>
    </div>
  </div>
</div>

<!-- Edit Appointment Modal -->
<div class="modal fade" id="editAppointmentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content p-3">
      <form method="POST">
        <input type="hidden" name="edit_id" id="edit_id">
        
        <h5 class="text-center mb-3">Edit Appointment</h5>

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
            <label class="form-label">Appointment Date & Time *</label>
            <input type="datetime-local" class="form-control" id="edit_appointment_date" name="edit_appointment_date" required>
          </div>

          <div class="col-md-6">
            <label class="form-label">Appointment Type *</label>
            <select class="form-select" id="edit_appointment_type" name="edit_appointment_type" required>
              <option value="Covid Test">COVID Test</option>
              <option value="Vaccination">Vaccination</option>
            </select>
          </div>

          <div class="col-12">
            <label class="form-label">Status *</label>
            <select class="form-select" id="edit_status" name="edit_status" required>
              <option value="Pending">Pending</option>
              <option value="Approved">Approved</option>
              <option value="Rejected">Rejected</option>
              <option value="Completed">Completed</option>
            </select>
          </div>
        </div>

        <button type="submit" name="updateAppointment" class="btn btn-success mt-3 w-100">Update Appointment</button>
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
      html: `You are about to delete appointment for: <strong>${patient}</strong>`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Yes, delete it!'
    }).then(result => {
      if (result.isConfirmed) {
        window.location.href = 'appointments.php?delete=' + id;
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
    new bootstrap.Modal(document.getElementById('editAppointmentModal')).show();
  });
});

// Search functionality
document.addEventListener('DOMContentLoaded', function() {
  // Simple table search
  const searchInput = document.createElement('input');
  searchInput.type = 'text';
  searchInput.placeholder = 'Search appointments...';
  searchInput.className = 'form-control mb-3';
  searchInput.style.maxWidth = '300px';
  
  searchInput.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('#appointmentsTable tbody tr');
    
    rows.forEach(row => {
      const text = row.textContent.toLowerCase();
      row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
  });
  
  // Insert search box before the table
  const tableContainer = document.querySelector('.table-responsive');
  tableContainer.parentNode.insertBefore(searchInput, tableContainer);
});

// Date validation to prevent past dates
document.addEventListener('DOMContentLoaded', function() {
  const now = new Date();
  const currentDateTime = now.toISOString().slice(0, 16);
  
  // Set min datetime for all datetime inputs
  const datetimeInputs = document.querySelectorAll('input[type="datetime-local"]');
  datetimeInputs.forEach(input => {
    if (input.name === 'appointment_date') {
      input.setAttribute('min', currentDateTime);
    }
  });
});
</script>

<?php include 'footer.php'; ?>
