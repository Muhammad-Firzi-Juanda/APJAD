// assets/js/app.js

document.addEventListener('DOMContentLoaded', function() {
    // Escape key closes modals
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeAllModals();
        }
    });

    // Close modal when clicking backdrop
    document.querySelectorAll('.modal-backdrop').forEach(function(backdrop) {
        backdrop.addEventListener('click', function(e) {
            if (e.target === backdrop) {
                backdrop.classList.remove('show');
            }
        });
    });
});

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
    }
}

function closeAllModals() {
    document.querySelectorAll('.modal-backdrop').forEach(function(modal) {
        modal.classList.remove('show');
    });
}

function openEditModal(userData) {
    document.getElementById('edit_user_id').value = userData.id || '';
    document.getElementById('edit_nama').value = userData.nama || '';
    document.getElementById('edit_email').value = userData.email || '';
    document.getElementById('edit_role').value = userData.role || 'Jurusan';
    document.getElementById('edit_password').value = '';
    openModal('modalEdit');
}

function openDeleteModal(userId) {
    document.getElementById('delete_user_id').value = userId;
    openModal('modalHapus');
}

function closeErrorPopup() {
    const popup = document.getElementById('loginErrorModal');
    if (popup) {
        popup.style.display = 'none';
    }
}
