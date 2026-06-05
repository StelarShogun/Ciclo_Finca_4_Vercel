// @ts-nocheck
import { cf4Confirm, cf4Warning } from '../shared/swal';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('xml-apply-form');
    if (!form) return;

    const checkboxes = () => [...document.querySelectorAll('.xml-update-checkbox')];
    const badge = document.getElementById('selected-count-badge');
    const label = document.getElementById('selected-count-label');

    function updateCount() {
        const checked = checkboxes().filter(c => c.checked).length;

        if (badge) {
            badge.textContent = checked > 0 ? `(${checked})` : '';
        }

        if (label) {
            label.textContent = checked > 0
                ? `${checked} producto(s) seleccionado(s) para actualizar`
                : 'Ningún producto seleccionado — no se realizarán cambios.';
        }
    }

    updateCount();

    document.addEventListener('change', (e) => {
        if (e.target.classList.contains('xml-update-checkbox')) {
            updateCount();
        }
    });

    document.getElementById('btn-select-deviations')?.addEventListener('click', () => {
        checkboxes().forEach(cb => {
            cb.checked = cb.closest('tr')?.classList.contains('row-deviation') || false;
        });
        updateCount();
    });

    document.getElementById('btn-select-all')?.addEventListener('click', () => {
        checkboxes().forEach(cb => cb.checked = true);
        updateCount();
    });

    document.getElementById('btn-deselect-all')?.addEventListener('click', () => {
        checkboxes().forEach(cb => cb.checked = false);
        updateCount();
    });

    document.addEventListener('input', (e) => {
        const input = e.target;
        if (!input.classList.contains('sale-price-input')) return;

        const suggested = parseFloat(input.dataset.suggested);
        const current = parseFloat(input.value);

        if (input.value === '') {
            input.classList.remove('is-modified');
        } else if (Math.abs(current - suggested) > 0.001) {
            input.classList.add('is-modified');
        } else {
            input.classList.remove('is-modified');
        }
    });

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.sale-price-clear');
        if (!btn) return;

        const productId = btn.dataset.target;
        const input = document.querySelector(`.sale-price-input[data-product-id="${productId}"]`);

        if (input) {
            input.value = '';
            input.classList.remove('is-modified');
            input.focus();
        }
    });

    form.addEventListener('submit', async (e) => {
        const selected = checkboxes().filter(c => c.checked).length;

        if (selected === 0) {
            e.preventDefault();
            await cf4Warning('Seleccione al menos un producto para aplicar cambios.', 'Sin productos seleccionados');
            return;
        }

        e.preventDefault();

        const saleChanges = [...document.querySelectorAll('.sale-price-input')]
            .filter(i => i.value !== '').length;

        let html = `<p>Se actualizará el precio de compra de <strong>${selected}</strong> producto(s).</p>`;

        if (saleChanges > 0) {
            html += `<p>Además, se actualizará el precio de <strong>venta</strong> de <strong>${saleChanges}</strong> producto(s).</p>`;
        }

        html += '<p>Esta acción quedará registrada en el historial.</p>';

        const result = await cf4Confirm({
            title: '¿Aplicar cambios del XML?',
            html,
            icon: 'warning',
            confirmButtonText: 'Sí, aplicar cambios',
            cancelButtonText: 'Cancelar',
            danger: false,
        });

        if (!result.isConfirmed) return;

        const btn = document.getElementById('xml-apply-btn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Aplicando…';
        }

        form.submit();
    });
});
