import { qs, qsa, smartFetch, jsonHeaders, syncFeaturedStarButtons, showSubtleNotification, setActionButtonLoading, showSuccessFeedback, showErrorFeedback } from './inventory-shared.js';
import { fireSwal } from '../shared/swal.js';

export async function initInventoryActions() {
    // Swal is lazy-loaded inside fireSwal() on first dialog — no eager warm-up.
    const root = qs('.products-section');
    if (!root) {
        return;
    }

    root.addEventListener('click', (e) => {
        const btn = e.target.closest('.featured-star-btn');
        if (!btn || !root.contains(btn)) {
            return;
        }
        e.preventDefault();
        e.stopPropagation();

        if (btn.getAttribute('aria-busy') === 'true') {
            return;
        }

        const productId = btn.dataset.productId;
        if (!productId) {
            return;
        }

        btn.setAttribute('aria-busy', 'true');
        btn.classList.add('featured-star-btn--busy');

        smartFetch(`/products/${productId}/toggle-featured`, {
            method: 'POST',
            headers: {
                ...jsonHeaders(),
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({}),
        })
            .then(async (response) => {
                let data = {};
                try {
                    data = await response.json();
                } catch {
                    data = {};
                }
                btn.removeAttribute('aria-busy');
                btn.classList.remove('featured-star-btn--busy');

                if (response.ok && data.success) {
                    syncFeaturedStarButtons(productId, data.is_featured);
                    showSubtleNotification(data.message || 'Destacado actualizado', 'success');
                } else {
                    fireSwal({
                        title: 'Error',
                        text: data.message || 'No se pudo actualizar el destacado.',
                        icon: 'error',
                        confirmButtonText: 'Entendido',
                    });
                }
            })
            .catch(() => {
                btn.removeAttribute('aria-busy');
                btn.classList.remove('featured-star-btn--busy');
                fireSwal({
                    title: 'Error',
                    text: 'No se pudo actualizar el destacado.',
                    icon: 'error',
                    confirmButtonText: 'Entendido',
                });
            });
    });

    initProductDeletion();
}

function initProductDeletion() {
    const deleteButtons = qsa('[data-action="delete"]');

    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.productId;
            const productName = this.dataset.productName;

            fireSwal({
                title: `¿Estás seguro de que deseas desactivar el producto "${productName}"?`,
                text: "El producto existirá en la base de datos, pero no contará para el stock del inventario.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, desactivar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    setActionButtonLoading(button, true, 'Eliminando...');
                    const url = `/products/${productId}`;
                    const method = 'DELETE';
                            
                    smartFetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => {
                        if (response.ok) {
                            return response.json();
                        } else {
                            throw new Error('Error en la solicitud');
                        }
                    })
                    .then(data => {
                        setActionButtonLoading(button, false);
                        if (data.success) {
                            showSuccessFeedback(button, '¡Desactivado!');
                            fireSwal({
                                title: '¡Desactivado!',
                                text: 'El producto ha sido desactivado correctamente.',
                                icon: 'success',
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            showErrorFeedback(button, 'Error');
                            fireSwal({
                                title: 'Error',
                                text: data.message || 'Hubo un problema al desactivar el producto.',
                                icon: 'error',
                            });
                        }
                    })
                    .catch(error => {
                        setActionButtonLoading(button, false);
                        showErrorFeedback(button, 'Error');
                        console.error('Error:', error);
                        fireSwal({
                            title: 'Error',
                            text: 'Hubo un problema de conexión o el servidor no respondió correctamente.',
                            icon: 'error',
                        });
                    });
                }
            });
        });
    });
}
