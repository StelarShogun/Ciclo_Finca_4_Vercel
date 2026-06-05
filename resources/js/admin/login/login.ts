import { cf4Error } from '../shared/swal';

document.addEventListener('DOMContentLoaded', () => {
    const togglePassword = document.querySelector<HTMLElement>('#togglePassword');
    const password = document.querySelector<HTMLInputElement>('#loginPassword');

    if (togglePassword && password) {
        togglePassword.addEventListener('click', function (this: HTMLElement) {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });
    }
});

document.addEventListener('DOMContentLoaded', () => {
    const errorEl = document.getElementById('authError');
    if (errorEl?.dataset.message) {
        void cf4Error(errorEl.dataset.message, 'Acceso denegado');
    }
});
