
<?php
include 'header.php';
include 'db.php';
?>
<br><br>
<?php
// 1. Total Patients
$patients = $conn->query("SELECT COUNT(*) AS total FROM patients");
$patients_count = $patients->fetch_assoc()['total'];

// 2. Total Hospitals
$hospitals = $conn->query("SELECT COUNT(*) AS total FROM hospitals WHERE is_approved='Approved'");
$hospitals_count = $hospitals->fetch_assoc()['total'];

// 3. Total Vaccinations (Completed doses only)
$vaccinations = $conn->query("SELECT COUNT(*) AS total FROM vaccinations WHERE status='Completed'");
$vaccinations_count = $vaccinations->fetch_assoc()['total'];

// 4. Pending Covid Tests
$pendingTests = $conn->query("SELECT COUNT(*) AS total FROM covid_tests WHERE result='Pending'");
$pendingTests_count = $pendingTests->fetch_assoc()['total'];
?>

<!-- Dashboard Content -->
<div class="content">
    <div class="page-header">
        <h1 class="page-title">Dashboard</h1>
        <!-- <button class="btn btn-primary" id="addNewBtn" onclick="openModal()"> -->
            <!-- <i class="fas fa-plus"></i> Add New -->
        </button>
    </div>

    <!-- Stats Cards -->
    <div class="cards">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Total Patients</h3>
                <div class="card-icon bg-primary">
                    <i class="fas fa-user-injured"></i>
                </div>
            </div>
            <div class="card-value"><?php echo number_format($patients_count); ?>
</div>
            <div class="card-text">+12% from last month</div>
        </div>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Hospitals</h3>
                <div class="card-icon bg-success">
                    <i class="fas fa-hospital"></i>
                </div>
            </div>
            <div class="card-value"><?php echo number_format($hospitals_count); ?>
</div>
            <div class="card-text">+3 new this month</div>
        </div>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Vaccinations</h3>
                <div class="card-icon bg-warning">
                    <i class="fas fa-syringe"></i>
                </div>
            </div>
            <div class="card-value"><?php echo number_format($vaccinations_count); ?>
</div>
            <div class="card-text">65% of population</div>
        </div>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Pending Tests</h3>
                <div class="card-icon bg-danger">
                    <i class="fas fa-vial"></i>
                </div>
            </div>
            <div class="card-value"><?php echo number_format($pendingTests_count); ?>
</div>
            <div class="card-text">-5% from last week</div>
        </div>
    </div>

    <!-- Recent Appointments Table -->
    <div class="table-container">
        <div class="table-header">
            <h3>Recent Appointments</h3>
            <div class="table-actions">
                <!-- <button class="btn btn-primary">
                    <i class="fas fa-download"></i> Export -->
                </button>
            </div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Patient</th>
                    <th>Hospital</th>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>#001</td>
                    <td>Hamza Ali</td>
                    <td>Aga Khan Hospital</td>
                    <td>2025-10-05</td>
                    <td>Vaccination</td>
                    <td><span class="status status-approved">Approved</span></td>
                    <td>
                        <div class="action-buttons">
                            <div class="action-btn edit-btn" onclick="openModal()">
                                <i class="fas fa-edit"></i>
                            </div>
                            <div class="action-btn delete-btn" onclick="openDeleteModal()">
                                <i class="fas fa-trash"></i>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>#002</td>
                    <td>Ayesha Noor</td>
                    <td>Liaquat National</td>
                    <td>2025-10-06</td>
                    <td>Covid Test</td>
                    <td><span class="status status-pending">Pending</span></td>
                    <td>
                        <div class="action-buttons">
                            <div class="action-btn edit-btn" onclick="openModal()">
                                <i class="fas fa-edit"></i>
                            </div>
                            <div class="action-btn delete-btn" onclick="openDeleteModal()">
                                <i class="fas fa-trash"></i>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>#003</td>
                    <td>Ahmed Khan</td>
                    <td>Dow University</td>
                    <td>2025-10-07</td>
                    <td>Vaccination</td>
                    <td><span class="status status-rejected">Rejected</span></td>
                    <td>
                        <div class="action-buttons">
                            <div class="action-btn edit-btn" onclick="openModal()">
                                <i class="fas fa-edit"></i>
                            </div>
                            <div class="action-btn delete-btn" onclick="openDeleteModal()">
                                <i class="fas fa-trash"></i>
                            </div>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal" id="crudModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="modalTitle">Add New Record</h3>
            <button class="close-btn" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="crudForm">
                <div class="form-group">
                    <label class="form-label" for="name">Name</label>
                    <input type="text" class="form-control" id="name" placeholder="Enter name">
                </div>
                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" class="form-control" id="email" placeholder="Enter email">
                </div>
                <div class="form-group">
                    <label class="form-label" for="phone">Phone</label>
                    <input type="text" class="form-control" id="phone" placeholder="Enter phone">
                </div>
                <div class="form-group">
                    <label class="form-label" for="status">Status</label>
                    <select class="form-control" id="status">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                        <option value="Pending">Pending</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn" onclick="closeModal()">Cancel</button>
            <button class="btn btn-primary" id="saveBtn">Save</button>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal" id="deleteModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Confirm Delete</h3>
            <button class="close-btn" onclick="closeDeleteModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete this record? This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
            <button class="btn" onclick="closeDeleteModal()">Cancel</button>
            <button class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
        </div>
    </div>
</div>

<!-- Dashboard CSS -->
<style>
:root {
  --background-color: #ffffff;
  --default-color: #444444;
  --heading-color: #555555;
  --accent-color: #3fbbc0;
}

body, html {
    margin:0; padding:0;
    font-family: "Roboto", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif;
    background-color: var(--background-color);
    color: var(--default-color);
}
h1,h2,h3,h4,h5,h6 { font-family:"Raleway",sans-serif; color: var(--heading-color); margin:0; }
button { cursor:pointer; border:none; outline:none; font-family:inherit; }

/* Page Header */
.page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:25px; }
.page-header h1 { font-size:28px; }
.page-header .btn { padding:10px 18px; background-color: var(--accent-color); color:#fff; border-radius:5px; font-weight:500; transition:0.3s; }
.page-header .btn:hover { background-color:#36aeb3; }

/* Cards */
.cards { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:20px; margin-bottom:30px; }
.card { background-color:#fff; border-radius:10px; box-shadow:0 4px 10px rgba(0,0,0,0.08); padding:20px; display:flex; flex-direction:column; transition: transform 0.3s, box-shadow 0.3s; }
.card:hover { transform:translateY(-5px); box-shadow:0 8px 20px rgba(0,0,0,0.12); }
.card-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; }
.card-title { font-size:16px; font-weight:600; color: var(--heading-color); }
.card-icon { width:40px; height:40px; border-radius:50%; display:flex; justify-content:center; align-items:center; color:#fff; font-size:18px; }
.bg-primary { background-color: var(--accent-color); }
.bg-success { background-color:#28a745; }
.bg-warning { background-color:#ffc107; color:#333; }
.bg-danger  { background-color:#dc3545; }
.card-value { font-size:24px; font-weight:700; margin-bottom:5px; }
.card-text { font-size:14px; color:#777; }

/* Table */
.table-container { background-color:#fff; padding:20px; border-radius:10px; box-shadow:0 4px 10px rgba(0,0,0,0.08); }
.table-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; }
.table-header h3 { margin:0; font-size:18px; font-weight:600; }
.table-actions .btn { padding:8px 14px; background-color: var(--accent-color); color:#fff; border-radius:5px; transition:0.3s; }
.table-actions .btn:hover { background-color:#36aeb3; }
table { width:100%; border-collapse:collapse; }
thead { background-color:#f9f9f9; }
th, td { padding:12px 15px; text-align:left; font-size:14px; border-bottom:1px solid #e0e0e0; }
th { font-weight:600; }
.status { padding:5px 10px; border-radius:12px; font-size:12px; font-weight:600; color:#fff; display:inline-block; }
.status-approved { background-color:#28a745; }
.status-pending  { background-color:#ffc107; color:#333; }
.status-rejected { background-color:#dc3545; }
.action-buttons { display:flex; gap:10px; }
.action-btn { width:30px; height:30px; border-radius:5px; display:flex; justify-content:center; align-items:center; transition:0.3s; }
.edit-btn { background-color: var(--accent-color); color:#fff; }
.edit-btn:hover { background-color:#36aeb3; }
.delete-btn { background-color:#dc3545; color:#fff; }
.delete-btn:hover { background-color:#c82333; }

/* Modals */
.modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:1000; }
.modal-content { background-color:#fff; padding:25px; border-radius:10px; width:400px; max-width:90%; box-shadow:0 8px 20px rgba(0,0,0,0.15); }
.modal-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; }
.modal-title { font-size:18px; font-weight:600; }
.close-btn { background:transparent; font-size:22px; line-height:1; cursor:pointer; color: var(--default-color); }
.modal-body { margin-bottom:15px; }
.form-group { margin-bottom:15px; }
.form-label { display:block; margin-bottom:5px; font-weight:500; }
.form-control { width:100%; padding:8px 12px; border-radius:5px; border:1px solid #ccc; font-size:14px; }
.modal-footer { display:flex; justify-content:flex-end; gap:10px; }
.modal-footer .btn { padding:8px 14px; border-radius:5px; }
.modal-footer .btn-primary { background-color: var(--accent-color); color:#fff; }
.modal-footer .btn-primary:hover { background-color:#36aeb3; }
.modal-footer .btn-danger { background-color:#dc3545; color:#fff; }
.modal-footer .btn-danger:hover { background-color:#c82333; }

/* Responsive */
@media(max-width:768px) {
    .cards { grid-template-columns: repeat(auto-fit, minmax(180px,1fr)); }
    table th, table td { font-size:12px; padding:10px 8px; }
}
</style>

<!-- JS for modals -->
<script>
function openModal(){ document.getElementById('crudModal').style.display='flex'; }
function closeModal(){ document.getElementById('crudModal').style.display='none'; }
function openDeleteModal(){ document.getElementById('deleteModal').style.display='flex'; }
function closeDeleteModal(){ document.getElementById('deleteModal').style.display='none'; }
</script>

<?php
include 'footer.php';
?>
