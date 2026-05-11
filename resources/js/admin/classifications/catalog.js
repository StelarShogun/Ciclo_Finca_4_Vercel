import Swal from 'sweetalert2';

window.Swal = Swal;

document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-confirm]');
    if (!btn) return;

    const form = btn.closest('form');
    if (!form) return;

    e.preventDefault();
    e.stopPropagation();

    const message = btn.getAttribute('data-confirm');
    const title = btn.getAttribute('data-confirm-title') || '¿Deseas continuar?';

    Swal.fire({
        title,
        text: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, continuar',
        cancelButtonText: 'No, cancelar',
        focusCancel: true,
        confirmButtonColor: '#b91c1c',
    }).then((result) => {
        if (result.isConfirmed) {
            form.submit();
        }
    });
});
