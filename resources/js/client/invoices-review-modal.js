import { cf4DialogDefaults, cf4Toast, getSwal } from './swal.js';

(function () {
    const config = window.__cf4InvoiceReview;
    if (!config) return;

    const { tab, pendingProducts, postUrl } = config;
    if (tab !== 'historial' || !Array.isArray(pendingProducts) || pendingProducts.length === 0) {
        return;
    }

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const selectedRatings = {};

    function renderRows() {
        return pendingProducts.map((product) => {
            const pid = Number(product.product_id);
            const stars = [1, 2, 3, 4, 5].map((value) => {
                return '<button type="button" class="cf4-review-star-btn" data-product-id="' + pid + '" data-star="' + value + '" aria-label="' + value + ' estrellas">★</button>';
            }).join('');

            return '<div class="cf4-review-modal-row">' +
                '<div class="cf4-review-modal-product">' + product.name + '</div>' +
                '<div class="cf4-review-stars">' + stars + '</div>' +
                '</div>';
        }).join('');
    }

    function paintStars(modal, productId, value) {
        modal.querySelectorAll('.cf4-review-star-btn[data-product-id="' + productId + '"]').forEach((btn) => {
            btn.classList.toggle('is-active', Number(btn.dataset.star) <= value);
        });
    }

    void (async () => {
        const Swal = await getSwal();

        const result = await Swal.fire({
            ...cf4DialogDefaults(),
            title: 'Tu pedido fue confirmado',
            html:
                '<p>Por favor denos una calificación de la satisfacción con el producto.</p>' +
                '<div class="cf4-review-modal-list">' + renderRows() + '</div>' +
                '<p style="margin-top:0.65rem;font-size:0.86rem;color:#666;">Este mensaje seguirá apareciendo mientras tengas productos sin reseñar.</p>',
            icon: 'info',
            confirmButtonText: 'Guardar mi reseña',
            showCancelButton: false,
            showCloseButton: false,
            allowOutsideClick: false,
            allowEscapeKey: false,
            focusConfirm: false,
            didOpen: (modal) => {
                modal.querySelectorAll('.cf4-review-star-btn').forEach((btn) => {
                    btn.addEventListener('click', () => {
                        const productId = Number(btn.dataset.productId);
                        const star = Number(btn.dataset.star);
                        selectedRatings[productId] = star;
                        paintStars(modal, productId, star);
                    });
                });
            },
            preConfirm: async () => {
                const payload = Object.entries(selectedRatings).map(([productId, stars]) => ({
                    product_id: Number(productId),
                    stars: Number(stars),
                }));

                if (payload.length === 0) {
                    Swal.showValidationMessage('Selecciona al menos una calificación antes de guardar.');
                    return false;
                }

                try {
                    const response = await fetch(postUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ reviews: payload }),
                    });

                    const data = await response.json();
                    if (!response.ok) {
                        throw new Error(data.message || 'No se pudo guardar la reseña.');
                    }

                    return data;
                } catch (error) {
                    Swal.showValidationMessage(error.message);
                    return false;
                }
            },
        });

        if (result.isConfirmed) {
            await cf4Toast({
                icon: 'success',
                title: 'Reseña guardada',
                text: 'Gracias por calificar tus productos.',
                timer: 1800,
            });
            window.location.reload();
        }
    })();
})();
