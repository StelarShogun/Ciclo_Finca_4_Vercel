// Toggle para ver/ocultar contraseña
document.addEventListener('DOMContentLoaded', function () {
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#loginPassword');

    if (togglePassword && password) {
        togglePassword.addEventListener('click', function (e) {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });
    }
});

// Mostrar error de autenticación con SweetAlert2
document.addEventListener('DOMContentLoaded', function () {
    const errorEl = document.getElementById('authError');
    if (errorEl) {
        Swal.fire({
            icon: 'error',
            title: 'Acceso denegado',
            text: errorEl.dataset.message,
            confirmButtonText: 'Aceptar',
            confirmButtonColor: '#e53e3e',
        });
    }
});