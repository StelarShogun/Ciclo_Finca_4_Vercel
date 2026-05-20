import '../../shared/ajax-pagination.js';

// Toggle sidebar collapse on click
document.addEventListener("DOMContentLoaded", () => {
    const toggle = document.querySelector(".toggle-sidebar");
    const aside = document.querySelector(".admin-sidebar");
    if (toggle && aside) {
        toggle.addEventListener("click", () => aside.classList.toggle("collapsed"));
    }
});

// Ban / Unban user actions
document.addEventListener('DOMContentLoaded', function () {
    // Use event delegation to handle dynamically added buttons
    document.addEventListener('click', function (e) {
        const banBtn = e.target.closest('[data-action="ban"]');
        const unbanBtn = e.target.closest('[data-action="unban"]');

        if (!banBtn && !unbanBtn) return;

        const btn = banBtn || unbanBtn;
        const action = btn.dataset.action;
        const id = btn.dataset.id;
        const name = btn.dataset.name;
        const email = btn.dataset.email;

        const isBan = action === 'ban';

        // Show confirmation dialog with SweetAlert2
        Swal.fire({
            icon: isBan ? 'warning' : 'question',
            title: isBan ? '¿Banear usuario?' : '¿Activar usuario?',
            html: isBan
                ? `¿Seguro que desea banear al usuario <strong>${name}</strong> (${email})?`
                : `¿Seguro que desea activar al usuario <strong>${name}</strong> (${email})?`,
            showCancelButton: true,
            confirmButtonText: isBan ? 'Sí, banear' : 'Sí, activar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: isBan ? '#ef4444' : '#10b981',
            cancelButtonColor: '#6b7280',
        }).then((result) => {
            if (!result.isConfirmed) return;

            // Send PATCH request to ban or unban endpoint
            const url = isBan ? `/clientes/${id}/ban` : `/clientes/${id}/unban`;

            fetch(url, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) throw new Error('Error del servidor');

                // Update UI: status badge and button in the table row
                const row = document.getElementById(`client-row-${id}`);
                const badge = row.querySelector('.status-badge');
                const td = row.querySelector('td:last-child');

                if (isBan) {
                    badge.textContent = 'Baneado';
                    badge.className = 'status-badge status-banned';
                    td.innerHTML = `<button class="btn btn-success btn-sm" data-id="${id}" data-name="${name}" data-email="${email}" data-action="unban">
                        <i class="fas fa-check-circle"></i> Activar
                    </button>`;
                } else {
                    badge.textContent = 'Activo';
                    badge.className = 'status-badge status-active';
                    td.innerHTML = `<button class="btn btn-danger btn-sm" data-id="${id}" data-name="${name}" data-email="${email}" data-action="ban">
                        <i class="fas fa-ban"></i> Banear
                    </button>`;
                }

                // Notify success with auto‑close toast
                Swal.fire({
                    icon: 'success',
                    title: isBan ? 'Usuario baneado' : 'Usuario activado',
                    timer: 1800,
                    showConfirmButton: false,
                });
            })
            .catch(() => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo completar la operación. Intenta nuevamente.',
                });
            });
        });
    });
});