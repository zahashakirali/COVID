
// Sidebar Toggle
document.getElementById('sidebarToggle').addEventListener('click', function() {
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.getElementById('mainContent');
    
    sidebar.classList.toggle('collapsed');
    mainContent.classList.toggle('expanded');
});

// Modal Functions
function openModal() {
    document.getElementById('crudModal').classList.add('show');
}

function closeModal() {
    document.getElementById('crudModal').classList.remove('show');
}

function openEditModal() {
    document.getElementById('modalTitle').textContent = 'Edit Record';
    document.getElementById('crudModal').classList.add('show');
}

function openDeleteModal() {
    document.getElementById('deleteModal').classList.add('show');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('show');
}

// Event Listeners
document.getElementById('addNewBtn').addEventListener('click', function() {
    document.getElementById('modalTitle').textContent = 'Add New Record';
    document.getElementById('crudForm').reset();
    openModal();
});

document.getElementById('saveBtn').addEventListener('click', function() {
    // In a real application, you would submit the form data to the server here
    alert('Record saved successfully!');
    closeModal();
});

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    // In a real application, you would send a delete request to the server here
    alert('Record deleted successfully!');
    closeDeleteModal();
});

// Close modals when clicking outside
window.addEventListener('click', function(event) {
    const crudModal = document.getElementById('crudModal');
    const deleteModal = document.getElementById('deleteModal');
    
    if (event.target === crudModal) {
        closeModal();
    }
    
    if (event.target === deleteModal) {
        closeDeleteModal();
    }
});
