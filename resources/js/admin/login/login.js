// Toggle password visibility
document.addEventListener('DOMContentLoaded', function () {
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#loginPassword');

    if (togglePassword && password) {
        togglePassword.addEventListener('click', function (e) {
            // Switch input type between password and text
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            // Toggle eye icon
            this.classList.toggle('fa-eye-slash');
        });
    }
});

// Display authentication error using SweetAlert2
document.addEventListener('DOMContentLoaded', function () {
    const errorEl = document.getElementById('authError');
    if (errorEl) {
        Swal.fire({
            icon: 'error',
            title: 'Acceso denegado',
            text: errorEl.dataset.message,   // Error message from data attribute
            confirmButtonText: 'Aceptar',
            confirmButtonColor: '#e53e3e',
        });
    }
});