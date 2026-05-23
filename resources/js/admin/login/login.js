import { cf4Error } from '../shared/swal.js';

// Toggle password visibility
document.addEventListener('DOMContentLoaded', function () {
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#loginPassword');

    if (togglePassword && password) {
        togglePassword.addEventListener('click', function () {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });
    }
});

// Display authentication error using SweetAlert2
document.addEventListener('DOMContentLoaded', function () {
    const errorEl = document.getElementById('authError');
    if (errorEl) {
        void cf4Error(errorEl.dataset.message, 'Acceso denegado');
    }
});
