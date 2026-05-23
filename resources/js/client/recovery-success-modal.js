import { cf4Success } from './swal.js';

document.addEventListener('DOMContentLoaded', () => {
    const payload = window.__cf4RecoverySuccess;
    if (!payload) return;

    void cf4Success(payload, 'Contraseña actualizada').then(() => {
        if (window.__cf4RecoverySuccessRedirect) {
            window.location.href = window.__cf4RecoverySuccessRedirect;
        }
    });
});
