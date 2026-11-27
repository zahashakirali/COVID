<?php
include 'db.php';

include 'header.php';

$errors = [];

// Fetch appointments for dropdown
$appointments = mysqli_query($conn, "SELECT a.appointment_id, a.appointment_type, p.name as patient_name, h.name as hospital_name
                                    FROM appointments a
                                    JOIN patients p ON a.patient_id = p.patient_id
                                    JOIN hospitals h ON a.hospital_id = h.hospital_id
                                    ORDER BY a.appointment_id DESC");

// ================== ADD NEW BOOKING DETAIL ==================
if (isset($_POST['newbookingdetail'])) {
    $appointment_id = $_POST['appointment_id'];
    $remarks = trim($_POST['remarks']);

    // Validation
    if (empty($appointment_id)) {
        $errors['appointment_id'] = "Appointment is required.";
    }
    
    // Check if booking detail already exists for this appointment
    $check_booking = mysqli_query($conn, "SELECT booking_id FROM booking_details WHERE appointment_id = '$appointment_id'");
    if (mysqli_num_rows($check_booking) > 0) {
        $errors['appointment_id'] = "Booking details already exist for this appointment.";
    }

    if (empty($errors)) {
        // Handle file upload
        $report_file = '';
        if (isset($_FILES['report_file']) && $_FILES['report_file']['error'] == 0) {
            $target_dir = "uploads/reports/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }
            $file_name = time() . '_' . basename($_FILES["report_file"]["name"]);
            $target_file = $target_dir . $file_name;
            $fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            
            // Allow only specific file types
            $allowed_types = array('pdf', 'doc', 'docx', 'txt');
            if (in_array($fileType, $allowed_types)) {
                if (move_uploaded_file($_FILES["report_file"]["tmp_name"], $target_file)) {
                    $report_file = $file_name;
                }
            } else {
                $errors['report_file'] = "Only PDF, DOC, DOCX, and TXT files are allowed.";
            }
        }

        if (empty($errors)) {
            $stmt = mysqli_prepare($conn, "INSERT INTO booking_details 
                (appointment_id, remarks, report_file) 
                VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "iss", 
                $appointment_id, $remarks, $report_file
            );
            
            if (mysqli_stmt_execute($stmt)) {
                echo "<script>
                    document.addEventListener('DOMContentLoaded', () => {
                        Swal.fire({icon:'success', title:'Booking Detail Added Successfully', showConfirmButton:false, timer:2500});
                    });
                </script>";
            } else {
                $errors['database'] = "Error adding booking detail: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// ================== DELETE BOOKING DETAIL ==================
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Get file name before deletion to remove from server
    $file_query = mysqli_query($conn, "SELECT report_file FROM booking_details WHERE booking_id = $id");
    $file_data = mysqli_fetch_assoc($file_query);
    
    if ($file_data['report_file']) {
        $file_path = "uploads/reports/" . $file_data['report_file'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    
    mysqli_query($conn, "DELETE FROM booking_details WHERE booking_id = $id");
    header("Location: booking_details.php");
    exit();
}

// ================== UPDATE BOOKING DETAIL ==================
if (isset($_POST['updateBookingDetail'])) {
    $id = $_POST['edit_id'];
    $appointment_id = $_POST['edit_appointment_id'];
    $remarks = trim($_POST['edit_remarks']);

    // Check if booking detail already exists for this appointment (excluding current)
    $check_booking = mysqli_query($conn, "SELECT booking_id FROM booking_details WHERE appointment_id = '$appointment_id' AND booking_id != $id");
    if (mysqli_num_rows($check_booking) > 0) {
        $errors['edit_appointment_id'] = "Booking details already exist for this appointment.";
    }

    if (empty($errors)) {
        // Handle file upload
        $report_file = $_POST['current_report_file'];
        if (isset($_FILES['edit_report_file']) && $_FILES['edit_report_file']['error'] == 0) {
            $target_dir = "uploads/reports/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }
            $file_name = time() . '_' . basename($_FILES["edit_report_file"]["name"]);
            $target_file = $target_dir . $file_name;
            $fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            
            // Allow only specific file types
            $allowed_types = array('pdf', 'doc', 'docx', 'txt');
            if (in_array($fileType, $allowed_types)) {
                if (move_uploaded_file($_FILES["edit_report_file"]["tmp_name"], $target_file)) {
                    // Delete old file if exists
                    if ($report_file && file_exists($target_dir . $report_file)) {
                        unlink($target_dir . $report_file);
                    }
                    $report_file = $file_name;
                }
            } else {
                $errors['edit_report_file'] = "Only PDF, DOC, DOCX, and TXT files are allowed.";
            }
        }

        if (empty($errors)) {
            $stmt = mysqli_prepare($conn, "UPDATE booking_details 
                SET appointment_id=?, remarks=?, report_file=?
                WHERE booking_id=?");
            mysqli_stmt_bind_param($stmt, "issi",
                $appointment_id, $remarks, $report_file, $id
            );
            
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            
            echo "<script>
                document.addEventListener('DOMContentLoaded', () => {
                    Swal.fire({icon:'success', title:'Booking Detail Updated Successfully', showConfirmButton:false, timer:2500});
                    setTimeout(() => { window.location.href = 'booking_details.php'; }, 1000);
                });
            </script>";
        }
    }
}
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Booking Details Management</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBookingDetailModal">
            <i class="bi bi-plus-circle"></i> Add New Booking Detail
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
        <table class="table table-hover align-middle text-center" id="bookingDetailsTable">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Appointment ID</th>
                    <th>Patient</th>
                    <th>Hospital</th>
                    <th>Appointment Type</th>
                    <th>Remarks</th>
                    <th>Report File</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = "SELECT bd.*, 
                         a.appointment_type,
                         p.name as patient_name,
                         h.name as hospital_name
                         FROM booking_details bd
                         JOIN appointments a ON bd.appointment_id = a.appointment_id
                         JOIN patients p ON a.patient_id = p.patient_id
                         JOIN hospitals h ON a.hospital_id = h.hospital_id
                         ORDER BY bd.booking_id DESC";
                $result = mysqli_query($conn, $query);
                
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $created_date = date('M j, Y', strtotime($row['created_at']));
                        $short_remarks = strlen($row['remarks']) > 50 ? substr($row['remarks'], 0, 50) . '...' : $row['remarks'];
                        
                        // Appointment type badge
                        $type_class = $row['appointment_type'] == 'Vaccination' ? 'bg-info' : 'bg-secondary';
                        
                        echo "<tr>
                            <td>{$row['booking_id']}</td>
                            <td>
                                <span class='badge bg-primary'>#{$row['appointment_id']}</span>
                            </td>
                            <td>{$row['patient_name']}</td>
                            <td>{$row['hospital_name']}</td>
                            <td>
                                <span class='badge {$type_class}'>{$row['appointment_type']}</span>
                            </td>
                            <td class='text-start' title='{$row['remarks']}'>
                                <small>{$short_remarks}</small>
                            </td>
                            <td>";
                            
                        if ($row['report_file']) {
                            $file_path = "uploads/reports/" . $row['report_file'];
                            $file_icon = pathinfo($row['report_file'], PATHINFO_EXTENSION) == 'pdf' ? 'bi-file-earmark-pdf' : 'bi-file-earmark-text';
                            echo "<a href='{$file_path}' target='_blank' class='btn btn-sm btn-outline-success'>
                                    <i class='bi {$file_icon}'></i> Download
                                  </a>";
                        } else {
                            echo "<span class='text-muted'>No file</span>";
                        }
                        
                        echo "</td>
                            <td>{$created_date}</td>
                            <td>
                                <button class='btn btn-sm btn-outline-primary editBtn'
                                    data-id='{$row['booking_id']}'
                                    data-appointment_id='{$row['appointment_id']}'
                                    data-remarks='{$row['remarks']}'
                                    data-report_file='{$row['report_file']}'>
                                    <i class='bi bi-pencil-square'></i>
                                </button>
                                <button class='btn btn-sm btn-outline-danger deleteBtn' data-id='{$row['booking_id']}' data-patient='{$row['patient_name']}'>
                                    <i class='bi bi-trash'></i>
                                </button>
                            </td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='9' class='text-center py-4 text-muted'>No booking details found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Booking Detail Modal -->
<div class="modal fade" id="addBookingDetailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content p-3">
      <form method="POST" enctype="multipart/form-data">
        <h5 class="text-center mb-3">Add New Booking Detail</h5>

        <div class="row g-3">
          <div class="col-12">
            <label class="form-label">Appointment *</label>
            <select class="form-select" name="appointment_id" required>
              <option value="">-- Select Appointment --</option>
              <?php
              mysqli_data_seek($appointments, 0);
              while ($a = mysqli_fetch_assoc($appointments)) {
                  $display_text = "#{$a['appointment_id']} - {$a['patient_name']} - {$a['hospital_name']} ({$a['appointment_type']})";
                  echo "<option value='{$a['appointment_id']}'>{$display_text}</option>";
              }
              ?>
            </select>
          </div>

          <div class="col-12">
            <label class="form-label">Remarks</label>
            <textarea class="form-control" name="remarks" rows="3" placeholder="Enter booking remarks, instructions, or notes..."></textarea>
          </div>

          <div class="col-12">
            <label class="form-label">Report File</label>
            <input type="file" class="form-control" name="report_file" accept=".pdf,.doc,.docx,.txt">
            <small class="text-muted">Allowed files: PDF, DOC, DOCX, TXT (Max: 5MB)</small>
          </div>
        </div>

        <button type="submit" name="newbookingdetail" class="btn btn-primary mt-3 w-100">Add Booking Detail</button>
      </form>
    </div>
  </div>
</div>

<!-- Edit Booking Detail Modal -->
<div class="modal fade" id="editBookingDetailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content p-3">
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="edit_id" id="edit_id">
        <input type="hidden" name="current_report_file" id="current_report_file">
        
        <h5 class="text-center mb-3">Edit Booking Detail</h5>

        <div class="row g-3">
          <div class="col-12">
            <label class="form-label">Appointment *</label>
            <select class="form-select" id="edit_appointment_id" name="edit_appointment_id" required>
              <option value="">-- Select Appointment --</option>
              <?php
              mysqli_data_seek($appointments, 0);
              while ($a = mysqli_fetch_assoc($appointments)) {
                  $display_text = "#{$a['appointment_id']} - {$a['patient_name']} - {$a['hospital_name']} ({$a['appointment_type']})";
                  echo "<option value='{$a['appointment_id']}'>{$display_text}</option>";
              }
              ?>
            </select>
          </div>

          <div class="col-12">
            <label class="form-label">Remarks</label>
            <textarea class="form-control" id="edit_remarks" name="edit_remarks" rows="3"></textarea>
          </div>

          <div class="col-12">
            <label class="form-label">Report File</label>
            <input type="file" class="form-control" name="edit_report_file" accept=".pdf,.doc,.docx,.txt">
            <small class="text-muted">Current: <span id="current_report_file_name"></span></small>
            <small class="text-muted d-block">Allowed files: PDF, DOC, DOCX, TXT (Max: 5MB)</small>
          </div>
        </div>

        <button type="submit" name="updateBookingDetail" class="btn btn-success mt-3 w-100">Update Booking Detail</button>
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
      html: `You are about to delete booking details for: <strong>${patient}</strong>`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Yes, delete it!'
    }).then(result => {
      if (result.isConfirmed) {
        window.location.href = 'booking_details.php?delete=' + id;
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
    
    // Handle file display
    const currentFileName = document.getElementById('current_report_file_name');
    if (data.report_file) {
      currentFileName.textContent = data.report_file;
    } else {
      currentFileName.textContent = 'No file';
    }
    
    // Show modal
    new bootstrap.Modal(document.getElementById('editBookingDetailModal')).show();
  });
});

// Search functionality
document.addEventListener('DOMContentLoaded', function() {
  // Simple table search
  const searchInput = document.createElement('input');
  searchInput.type = 'text';
  searchInput.placeholder = 'Search booking details...';
  searchInput.className = 'form-control mb-3';
  searchInput.style.maxWidth = '300px';
  
  searchInput.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('#bookingDetailsTable tbody tr');
    
    rows.forEach(row => {
      const text = row.textContent.toLowerCase();
      row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
  });
  
  // Insert search box before the table
  const tableContainer = document.querySelector('.table-responsive');
  tableContainer.parentNode.insertBefore(searchInput, tableContainer);
});

// File size validation
document.addEventListener('DOMContentLoaded', function() {
  const fileInputs = document.querySelectorAll('input[type="file"]');
  const maxSize = 5 * 1024 * 1024; // 5MB in bytes
  
  fileInputs.forEach(input => {
    input.addEventListener('change', function() {
      if (this.files[0] && this.files[0].size > maxSize) {
        alert('File size must be less than 5MB');
        this.value = '';
      }
    });
  });
});
</script>

<?php include 'footer.php'; ?>
