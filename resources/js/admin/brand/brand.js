import '../../shared/ajax-pagination.js';

// Toggle sidebar collapse on click
document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.querySelector('.toggle-sidebar');
    const aside  = document.querySelector('.admin-sidebar');
    if (toggle && aside) {
        toggle.addEventListener('click', () => aside.classList.toggle('collapsed'));
    }
});

document.addEventListener('DOMContentLoaded', () => {
    const csrfToken  = document.querySelector('meta[name="csrf-token"]').content;
    const modal      = document.getElementById('modal-marca');
    const formMarca  = document.getElementById('form-marca');
    const inputId    = document.getElementById('marca-id');
    const inputName  = document.getElementById('marca-nombre');
    const errorName  = document.getElementById('error-nombre');
    const modalTitle = document.getElementById('modal-titulo');

    // ── Toast helper ────────────────────────────────────────────
    const toast = (icon, title) => Swal.fire({
        toast: true,
        position: 'top-end',
        icon,
        title,
        showConfirmButton: false,
        timer: 2800,
        timerProgressBar: true,
    });

    // ── Duplicate error handler ─────────────────────────────────
    const handleDuplicate = async (data, inputNameValue) => {
        if (data.exact) {
            // Exact same name → simple alert
            Swal.fire({
                icon: 'warning',
                title: 'Marca ya existente',
                text: `La marca "${data.existing.name}" ya está registrada.`,
                confirmButtonColor: '#235347',
            });
        } else {
            // Different capitalization → offer to edit
            const result = await Swal.fire({
                icon: 'info',
                title: 'Marca similar encontrada',
                html: `La marca que escribes (<strong>${inputNameValue}</strong>) ya está registrada como <strong>${data.existing.name}</strong>.<br><br>¿Deseas editarla?`,
                showCancelButton: true,
                confirmButtonText: 'Sí, editar',
                cancelButtonText: 'No, cancelar',
                confirmButtonColor: '#235347',
                cancelButtonColor: '#455a64',
            });
            if (result.isConfirmed) {
                inputId.value          = data.existing.id;
                inputName.value        = data.existing.name;
                modalTitle.textContent = 'Editar Marca';
            }
        }
    };

    // ── Modal open / close ──────────────────────────────────────
    const openModal = () => { modal.style.display = 'flex'; inputName.focus(); };
    const closeModal = () => {
        modal.style.display   = 'none';
        formMarca.reset();
        inputId.value         = '';
        errorName.innerHTML   = '';
        modalTitle.textContent = 'Nueva Marca';
    };

    document.getElementById('btn-nueva-marca').addEventListener('click', () => {
        inputId.value = '';
        openModal();
    });
    document.getElementById('btn-cerrar-modal').addEventListener('click', closeModal);
    document.getElementById('btn-cancelar').addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal(); });

    // ── Edit buttons ────────────────────────────────────────────
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', () => {
            inputId.value          = btn.dataset.id;
            inputName.value        = btn.dataset.name;
            modalTitle.textContent = 'Editar Marca';
            openModal();
        });
    });

    // ── Delete buttons ──────────────────────────────────────────
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', async () => {
            const { isConfirmed } = await Swal.fire({
                title: '¿Eliminar marca?',
                text: `"${btn.dataset.name}" será eliminada permanentemente.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#d32f2f',
                cancelButtonColor: '#455a64',
            });
            if (!isConfirmed) return;

            let data;
            try {
                const res = await fetch(`/brands/${btn.dataset.id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                });
                data = await res.json();
            } catch (err) {
                toast('error', 'Error de conexión al intentar eliminar la marca.');
                return;
            }

            if (data.success) {
                await toast('success', data.message);
                location.reload();
            } else if (data.blocked) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No se puede eliminar',
                    html: `<p style="margin:0 0 0.5rem">${data.message}</p>
                           <p style="margin:0;font-size:0.875rem;color:#6b7280">Para eliminarla primero debes desvincularla de todos los productos asociados.</p>`,
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#235347',
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'No se pudo eliminar',
                    text: data.message || 'Ocurrió un error inesperado.',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#235347',
                });
            }
        });
    });

    // ── Form submit (create / update) ───────────────────────────
    formMarca.addEventListener('submit', async (e) => {
        e.preventDefault();
        errorName.innerHTML = '';

        const id     = inputId.value;
        const url    = id ? `/brands/${id}` : '/brands';
        const method = id ? 'PUT' : 'POST';

        const res  = await fetch(url, {
            method,
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ name: inputName.value }),
        });
        const data = await res.json();

        if (data.success) {
            closeModal();
            await toast('success', data.message);
            location.reload();
        } else if (data.duplicate) {
            await handleDuplicate(data, inputName.value);
        } else if (data.errors) {
            errorName.textContent = data.errors.name?.[0] ?? '';
        } else {
            toast('error', 'Ocurrió un error inesperado.');
        }
    });
});
