// @ts-nocheck
import '../../shared/ajax-pagination';
import { cf4Confirm, cf4Toast, cf4Warning, cf4Error, escapeHtml } from '../shared/swal';

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

    const toast = (icon, title) => cf4Toast({ icon, title, timer: 2800 });

    const handleDuplicate = async (data, inputNameValue) => {
        if (data.exact) {
            await cf4Warning(
                `La marca "${data.existing.name}" ya está registrada.`,
                'Marca ya existente'
            );
        } else {
            const result = await cf4Confirm({
                title: 'Marca similar encontrada',
                html: `La marca que escribes (<strong>${escapeHtml(inputNameValue)}</strong>) ya está registrada como <strong>${escapeHtml(data.existing.name)}</strong>.<br><br>¿Deseas editarla?`,
                icon: 'info',
                confirmButtonText: 'Sí, editar',
                cancelButtonText: 'No, cancelar',
            });
            if (result.isConfirmed) {
                inputId.value          = data.existing.id;
                inputName.value        = data.existing.name;
                modalTitle.textContent = 'Editar Marca';
            }
        }
    };

    const openModal = () => {
        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');
        inputName.focus();
    };
    const closeModal = () => {
        modal.classList.remove('active');
        modal.setAttribute('aria-hidden', 'true');
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
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            closeModal();
        }
    });

    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', () => {
            inputId.value          = btn.dataset.id;
            inputName.value        = btn.dataset.name;
            modalTitle.textContent = 'Editar Marca';
            openModal();
        });
    });

    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', async () => {
            const result = await cf4Confirm({
                title: '¿Eliminar marca?',
                text: `"${btn.dataset.name}" será eliminada permanentemente.`,
                icon: 'warning',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                danger: true,
            });
            if (!result.isConfirmed) return;

            let data;
            try {
                const res = await fetch(`/brands/${btn.dataset.id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                });
                data = await res.json();
            } catch {
                await toast('error', 'Error de conexión al intentar eliminar la marca.');
                return;
            }

            if (data.success) {
                await toast('success', data.message);
                location.reload();
            } else if (data.blocked) {
                await cf4Warning(
                    `${data.message}\n\nPara eliminarla primero debes desvincularla de todos los productos asociados.`,
                    'No se puede eliminar'
                );
            } else {
                await cf4Error(data.message || 'Ocurrió un error inesperado.', 'No se pudo eliminar');
            }
        });
    });

    formMarca.addEventListener('submit', async (e) => {
        e.preventDefault();
        errorName.innerHTML = '';

        const id     = inputId.value;
        const url    = id ? `/brands/${id}` : '/brands';
        const method = id ? 'PUT' : 'POST';

        try {
            const res  = await fetch(url, {
                method,
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ name: inputName.value }),
            });
            const data = await res.json().catch(() => ({}));

            if (data.success) {
                closeModal();
                await toast('success', data.message);
                location.reload();
            } else if (data.duplicate) {
                await handleDuplicate(data, inputName.value);
            } else if (data.errors) {
                errorName.textContent = data.errors.name?.[0] ?? '';
            } else {
                await toast('error', data.message || 'Ocurrió un error inesperado.');
            }
        } catch {
            errorName.textContent = 'Error de conexión. Verificá tu red e intentá de nuevo.';
            await toast('error', 'Error de conexión. Verificá tu red e intentá de nuevo.');
        }
    });
});
