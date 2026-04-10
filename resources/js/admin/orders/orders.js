/**
 * Pedidos admin — modal configuración plazo cancelación automática (CF4-61)
 */
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('order-expiration-modal');
    const openBtn = document.getElementById('btn-open-order-expiration-modal');
    const form = document.getElementById('order-expiration-form');
    const metaUrl = document.querySelector('meta[name="order-expiration-update-url"]');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    if (! modal || ! openBtn || ! form || ! metaUrl) {
        return;
    }

    const url = metaUrl.getAttribute('content');

    const close = () => {
        modal.classList.remove('active');
        modal.setAttribute('aria-hidden', 'true');
    };

    openBtn.addEventListener('click', () => {
        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');
    });

    modal.querySelectorAll('[data-close-order-expiration-modal]').forEach((btn) => {
        btn.addEventListener('click', close);
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const errEl = document.getElementById('order-expiration-form-error');
        errEl.style.display = 'none';
        errEl.textContent = '';

        const input = document.getElementById('order_expiration_days');
        const submitBtn = document.getElementById('order-expiration-submit');
        submitBtn.disabled = true;

        try {
            const res = await fetch(url, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    order_expiration_days: input.value === '' ? null : Number(input.value),
                }),
            });

            const data = await res.json().catch(() => ({}));

            if (res.status === 422 && data.errors) {
                const first = Object.values(data.errors).flat()[0] ?? 'Datos no válidos.';
                errEl.textContent = first;
                errEl.style.display = 'block';

                return;
            }

            if (! res.ok) {
                errEl.textContent = data.message ?? 'No se pudo guardar.';
                errEl.style.display = 'block';

                return;
            }

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: data.message ?? 'Guardado',
                    timer: 2000,
                    showConfirmButton: false,
                });
            }

            close();

            if (data.order_expiration_days != null) {
                input.value = String(data.order_expiration_days);
            }
        } finally {
            submitBtn.disabled = false;
        }
    });
});
