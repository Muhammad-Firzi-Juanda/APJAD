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

    // Intercept Login Form submission to show pop-up toasts
    const loginForm = document.querySelector('.login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = loginForm.querySelector('.btn-login');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.75';
            }

            const formData = new FormData(loginForm);

            fetch(loginForm.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: formData
            })
            .then(async response => {
                const data = await response.json().catch(() => ({}));
                return { ok: response.ok, status: response.status, data: data };
            })
            .then(result => {
                if (result.ok && result.data.success) {
                    // Close error popup if opened
                    closeErrorPopup();

                    // Show Success Toast Popup (Matching Image 2)
                    let toast = document.getElementById('loginSuccessToast');
                    if (!toast) {
                        toast = document.createElement('div');
                        toast.id = 'loginSuccessToast';
                        toast.className = 'login-success-toast';
                        toast.innerHTML = `
                            <span>Login Berhasil</span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                        `;
                        document.body.appendChild(toast);
                    }
                    toast.style.display = 'flex';

                    // Redirect smoothly after user sees the "Login Berhasil âœ“" popup
                    setTimeout(() => {
                        window.location.href = result.data.redirect || '/dashboard';
                    }, 800);
                } else {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.style.opacity = '1';
                    }
                    showErrorPopup(result.data.message || 'Email atau Password Anda Salah');
                }
            })
            .catch(err => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.style.opacity = '1';
                }
                showErrorPopup('Email atau Password Anda Salah');
            });
        });
    }
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
    const form = document.getElementById('formEditUser');
    if (form && userData.id) {
        const baseUrl = form.getAttribute('data-base-url');
        if (baseUrl) {
            form.action = baseUrl.replace(/\/$/, '') + '/' + userData.id;
        }
    }
    const idField = document.getElementById('edit_user_id');
    if (idField) idField.value = userData.id || '';
    
    const namaField = document.getElementById('edit_nama');
    if (namaField) namaField.value = userData.nama || '';

    const emailField = document.getElementById('edit_email');
    if (emailField) emailField.value = userData.email || '';

    const roleField = document.getElementById('edit_role');
    if (roleField) {
        roleField.value = userData.role || 'Fakultas';
    }

    const pwField = document.getElementById('edit_password');
    if (pwField) pwField.value = '';

    openModal('modalEdit');
}

function openDeleteModal(userId) {
    const form = document.getElementById('formDeleteUser');
    if (form && userId) {
        const baseUrl = form.getAttribute('data-base-url');
        if (baseUrl) {
            form.action = baseUrl.replace(/\/$/, '') + '/' + userId;
        }
    }
    const input = document.getElementById('delete_user_id');
    if (input) {
        input.value = userId;
    }
    openModal('modalHapus');
}

function showErrorPopup(message) {
    let modal = document.getElementById('loginErrorModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.className = 'error-popup-backdrop';
        modal.id = 'loginErrorModal';
        modal.innerHTML = `
            <div class="error-popup-card">
                <div class="error-popup-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </div>
                <p class="error-message">${message}</p>
                <button type="button" class="btn-popup-ok" onclick="closeErrorPopup()">OK</button>
            </div>
        `;
        document.body.appendChild(modal);
    } else {
        const msgEl = modal.querySelector('.error-message');
        if (msgEl) msgEl.textContent = message;
        modal.style.display = 'flex';
    }
}

function closeErrorPopup() {
    const popup = document.getElementById('loginErrorModal');
    if (popup) {
        popup.style.display = 'none';
    }
}

function togglePasswordVisibilityModal(fieldId, button) {
    const passwordInput = document.getElementById(fieldId);
    if (!passwordInput) return;
    const eyeIcon = document.getElementById(fieldId + '_eye') || (button ? button.querySelector('svg') : null);
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        if (eyeIcon) {
            eyeIcon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
        }
    } else {
        passwordInput.type = 'password';
        if (eyeIcon) {
            eyeIcon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
        }
    }
}



/* ---------------------------------------------------------------
   Flash Alert — auto-dismiss
   Semua elemen .flash-alert akan otomatis menghilang setelah
   beberapa detik dengan animasi smooth dan progress-bar countdown.
--------------------------------------------------------------- */
(function () {
    var DELAY_SUCCESS = 4000;
    var DELAY_ERROR   = 6000;

    function dismissAlert(el) {
        el.style.maxHeight = el.offsetHeight + 'px';
        el.getBoundingClientRect();
        el.classList.add('flash-alert--hiding');
        el.addEventListener('transitionend', function handler() {
            el.removeEventListener('transitionend', handler);
            if (el.parentNode) { el.parentNode.removeChild(el); }
        });
    }

    function initAlert(el) {
        var isError = el.classList.contains('error');
        var delay   = isError ? DELAY_ERROR : DELAY_SUCCESS;

        el.style.maxHeight = el.scrollHeight + 'px';

        var bar = document.createElement('div');
        bar.className = 'flash-alert__progress';
        bar.style.setProperty('--flash-duration', (delay / 1000) + 's');
        el.appendChild(bar);

        var timer;
        var btn = document.createElement('button');
        btn.className = 'flash-alert__close';
        btn.setAttribute('aria-label', 'Tutup notifikasi');
        btn.innerHTML = '&times;';
        btn.addEventListener('click', function () {
            clearTimeout(timer);
            dismissAlert(el);
        });
        el.appendChild(btn);

        timer = setTimeout(function () {
            dismissAlert(el);
        }, delay);
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.flash-alert').forEach(function (el) {
            initAlert(el);
        });
    });
})();
