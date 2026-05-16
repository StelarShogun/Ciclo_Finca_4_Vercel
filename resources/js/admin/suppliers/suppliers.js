// Validates all fields in a supplier form; returns { valid, errors[] }
function validateForm(form, type) {
    const errors = [];

    const name = form.querySelector('#name');
    const primaryContact = form.querySelector('#primary_contact');
    const phone = form.querySelector('#phone');
    const email = form.querySelector('#email');
    const address = form.querySelector('#address');
    const deliveryTime = form.querySelector('#delivery_time');
    const rating = form.querySelector('#rating');

    if (!name.value.trim()) {
        errors.push('El nombre del proveedor es obligatorio');
    }

    if (!primaryContact.value.trim()) {
        errors.push('El contacto principal es obligatorio');
    }

    if (!phone.value.trim()) {
        errors.push('El teléfono es obligatorio');
    } else if (!/^[0-9+\s-]{8,}$/.test(phone.value)) {
        // Validate phone: at least 8 characters, digits, +, spaces, hyphens allowed
        errors.push('El teléfono debe tener al menos 8 dígitos');
    }

    if (!email.value.trim()) {
        errors.push('El correo electrónico es obligatorio');
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
        errors.push('El correo electrónico no es válido');
    }

    if (!address.value.trim()) {
        errors.push('La dirección es obligatoria');
    }

    if (!deliveryTime.value) {
        errors.push('El tiempo de entrega es obligatorio');
    } else {
        const time = parseInt(deliveryTime.value);
        // Delivery time must be a whole number between 1 and 365 days
        if (isNaN(time) || time < 1 || time > 365) {
            errors.push('El tiempo de entrega debe ser entre 1 y 365 días');
        }
    }

    // Rating is optional; only validate if a value was provided
    if (rating && rating.value) {
        const ratingValue = parseFloat(rating.value);
        if (isNaN(ratingValue) || ratingValue < 0 || ratingValue > 5) {
            errors.push('La evaluación debe estar entre 0 y 5');
        }
    }

    return {
        valid: errors.length === 0,
        errors: errors
    };
}

// Displays a feedback message inside a DOM element or falls back to a SweetAlert dialog
function showMessage(message, type, elementId) {
    const element = document.getElementById(elementId);

    // No target element found — use SweetAlert as fallback
    if (!element) {
        Swal.fire({
            icon: type === 'success' ? 'success' : 'error',
            title: type === 'success' ? 'Éxito' : 'Error',
            text: message.replace(/[✅❌]/g, '').trim(),
            confirmButtonText: 'Entendido'
        });
        return;
    }

    const messageText = element.querySelector('span');
    const icon = element.querySelector('i');

    // Reset previous state before applying the new one
    element.classList.remove('hidden', 'success', 'error', 'warning');
    element.classList.add(type);

    // Swap icon class to match the message type
    if (icon) {
        icon.className = type === 'success'
            ? 'fas fa-check-circle'
            : type === 'error'
                ? 'fas fa-exclamation-circle'
                : 'fas fa-info-circle';
    }

    if (messageText) {
        messageText.textContent = message;
    } else {
        element.textContent = message;
    }

    element.classList.remove('hidden');

    // Auto-hide success messages after 5 seconds
    if (type === 'success') {
        setTimeout(() => element.classList.add('hidden'), 5000);
    }
}

// Handles form submission for the dedicated "create supplier" page
async function registerSupplier(e) {
    e.preventDefault();
    const form = document.getElementById('formRegistro');

    const validation = validateForm(form, 'register');
    if (!validation.valid) {
        Swal.fire({
            icon: 'error',
            title: 'Errores de validación',
            html: validation.errors.map(err => `• ${err}`).join('<br>'),
            confirmButtonText: 'Entendido'
        });
        return;
    }

    // Grab button elements to show a loading state while the request is in flight
    const btnSubmit = document.getElementById('btnRegistroSubmit');
    const btnText = document.getElementById('btnRegistroTexto');
    const btnLoading = document.getElementById('btnRegistroCargando');
    const message = document.getElementById('mensajeFeedbackRegistro');

    btnSubmit.disabled = true;
    btnText.classList.add('hidden');
    btnLoading.classList.remove('hidden');
    if (message) message.classList.add('hidden');

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form)
        });

        const data = await response.json();

        if (data.success) {
            await Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: 'Proveedor registrado exitosamente.',
                confirmButtonText: 'Aceptar'
            });
            // Redirect to the suppliers list (or the URL returned by the server)
            window.location.href = data.redirect || '/suppliers';
        } else {
            // Flatten server-side validation errors into a single string
            const errorMessage = data.errors
                ? Object.values(data.errors).flat().join(', ')
                : data.message || 'Error al registrar el proveedor';
            showMessage('❌ ' + errorMessage, 'error', 'mensajeFeedbackRegistro');
        }
    } catch (error) {
        console.error('Error registering supplier:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error de conexión',
            text: 'No se pudo conectar con el servidor. Intenta nuevamente.',
            confirmButtonText: 'Entendido'
        });
    } finally {
        // Always restore the button to its original state
        btnSubmit.disabled = false;
        btnText.classList.remove('hidden');
        btnLoading.classList.add('hidden');
    }
}

// Handles form submission for the dedicated "edit supplier" page
async function editSupplier(e) {
    e.preventDefault();
    const form = document.getElementById('formEdicion');

    const validation = validateForm(form, 'edit');
    if (!validation.valid) {
        Swal.fire({
            icon: 'error',
            title: 'Errores de validación',
            html: validation.errors.map(err => `• ${err}`).join('<br>'),
            confirmButtonText: 'Entendido'
        });
        return;
    }

    const btnSubmit = document.getElementById('btnSubmit');
    const btnText = document.getElementById('btnTexto');
    const btnLoading = document.getElementById('btnCargando');
    const message = document.getElementById('mensajeFeedbackEditar');

    btnSubmit.disabled = true;
    btnText.classList.add('hidden');
    btnLoading.classList.remove('hidden');
    if (message) message.classList.add('hidden');

    const formData = new FormData(form);
    // Laravel requires _method=PUT to route the request to the update controller
    formData.append('_method', 'PUT');

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            await Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: 'Proveedor actualizado exitosamente.',
                confirmButtonText: 'Aceptar'
            });
            window.location.href = data.redirect || '/suppliers';
        } else {
            const errorMessage = data.errors
                ? Object.values(data.errors).flat().join(', ')
                : data.message || 'Error al actualizar el proveedor';
            showMessage('❌ ' + errorMessage, 'error', 'mensajeFeedbackEditar');
        }
    } catch (error) {
        console.error('Error editing supplier:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error de conexión',
            text: 'No se pudo conectar con el servidor. Intenta nuevamente.',
            confirmButtonText: 'Entendido'
        });
    } finally {
        btnSubmit.disabled = false;
        btnText.classList.remove('hidden');
        btnLoading.classList.add('hidden');
    }
}

// Fetches a single supplier by ID and populates the read-only detail modal
async function viewSupplierDetail(id) {
    try {
        const response = await fetch(`/suppliers/${id}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
            },
        });

        const data = await response.json().catch(() => null);

        if (!response.ok || !data?.success) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data?.message || 'No se pudieron obtener los datos del proveedor',
            });
            return;
        }

        const s = data.data;

        document.getElementById('modalProveedorNombre').textContent       = s.name        || 'N/A';
        document.getElementById('modalProveedorEmail').textContent        = s.email       || 'N/A';
        document.getElementById('modalProveedorTelefono').textContent     = s.phone       || 'N/A';
        document.getElementById('modalProveedorDireccion').textContent    = s.address     || 'N/A';
        document.getElementById('modalProveedorEvaluacion').textContent   = s.rating      || '0';
        document.getElementById('modalProveedorEstado').textContent       = s.status      || 'N/A';
        document.getElementById('modalProveedorFechaRegistro').textContent = s.created_at
            ? new Date(s.created_at).toLocaleDateString()
            : 'N/A';

        document.getElementById('modalDetalleProveedor').classList.add('active');
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión al obtener los datos del proveedor' });
    }
}

// Hides the supplier detail modal
function closeModal() {
    document.getElementById('modalDetalleProveedor').classList.remove('active');
}

// Centralized delete confirmation for supplier forms.
document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;
    if (!form.classList.contains('js-supplier-delete-form')) return;

    event.preventDefault();
    event.stopPropagation();

    const actionUrl = form.getAttribute('action');
    if (!actionUrl) return;

    const supplierName = form.dataset.supplierName || 'este proveedor';

    const result = await Swal.fire({
        title: '¿Deseas eliminar este proveedor?',
        text: `Se eliminará el proveedor “${supplierName}”. Esta acción no se puede revertir fácilmente. ¿Deseas continuar?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#b91c1c',
        confirmButtonText: 'Sí, continuar',
        cancelButtonText: 'No, cancelar',
        focusCancel: true,
    });

    if (!result.isConfirmed) return;

    try {
        const response = await fetch(actionUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
            },
            body: new URLSearchParams({ _method: 'DELETE' }),
        });

        const data = await response.json().catch(() => null);
        if (data?.success) {
            Swal.fire({
                icon: 'success',
                title: 'Proveedor eliminado',
                text: data.message || 'Proveedor eliminado correctamente.',
                confirmButtonText: 'Entendido',
            }).then(() => location.reload());
            return;
        }

        Swal.fire({
            icon: 'error',
            title: 'No se pudo eliminar',
            text: (data && data.message) ? data.message : 'No se pudo completar la acción. Inténtalo nuevamente.',
            confirmButtonText: 'Cerrar',
        });
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error de conexión',
            text: 'No se pudo conectar con el servidor. Intenta nuevamente.',
            confirmButtonText: 'Cerrar',
        });
    }
});

// Opens the "new supplier" modal and resets its form to a blank state
function openNewSupplierModal() {
    const modal = document.getElementById('new-supplier-modal');
    if (!modal) return;
    modal.classList.add('active');
    document.getElementById('new-supplier-form').reset();
    // Clear any lingering validation error messages from a previous attempt
    document.querySelectorAll('#new-supplier-form .error-message').forEach(el => {
        el.textContent = '';
        el.style.display = 'none';
    });
}

// Hides the "new supplier" modal
function closeNewSupplierModal() {
    document.getElementById('new-supplier-modal')?.classList.remove('active');
}

// Fetches supplier data by ID and pre-fills the edit modal form
async function loadSupplierForEdit(id) {
    try {
        const editModal = document.getElementById('edit-supplier-modal');
        if (!editModal) return;

        editModal.classList.add('active');

        const response = await fetch(`/suppliers/${id}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
            },
        });

        const data = await response.json().catch(() => null);

        if (!response.ok || !data?.success) {
            editModal.classList.remove('active');
            const message = data?.message || 'No se pudieron obtener los datos del proveedor';
            Swal.fire({ icon: 'error', title: 'Error', text: message });
            return;
        }

        const s = data.data;

        document.getElementById('edit-supplier-id').value        = s.supplier_id   || '';
        document.getElementById('edit-supplier-nombre').value    = s.name          || '';
        document.getElementById('edit-supplier-contacto').value  = s.primary_contact || '';
        document.getElementById('edit-supplier-telefono').value  = s.phone         || '';
        document.getElementById('edit-supplier-email').value     = s.email         || '';
        document.getElementById('edit-supplier-direccion').value = s.address       || '';
        document.getElementById('edit-supplier-tiempo').value    = s.delivery_time || '';
        document.getElementById('edit-supplier-evaluacion').value = s.rating       || '';

        document.querySelectorAll('#edit-supplier-form .error-message').forEach(el => {
            el.textContent = '';
            el.style.display = 'none';
        });
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('edit-supplier-modal')?.classList.remove('active');
        Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión al obtener los datos del proveedor' });
    }
}

// Hides the "edit supplier" modal
function closeEditSupplierModal() {
    document.getElementById('edit-supplier-modal')?.classList.remove('active');
}

// Event listeners for DOM elements that exist on page load
document.addEventListener('DOMContentLoaded', function () {

    // New supplier modal 
    document.getElementById('open-new-supplier-modal')?.addEventListener('click', openNewSupplierModal);
    document.getElementById('close-new-supplier-modal')?.addEventListener('click', closeNewSupplierModal);
    document.getElementById('cancel-new-supplier')?.addEventListener('click', closeNewSupplierModal);

    // POST to /suppliers to create a new record
    document.getElementById('save-new-supplier')?.addEventListener('click', async function () {
        const form = document.getElementById('new-supplier-form');
        const formData = new FormData(form);
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        // Clear existing field-level errors before submitting
        document.querySelectorAll('#new-supplier-form .error-message').forEach(el => {
            el.textContent = '';
            el.style.display = 'none';
        });

        try {
            const response = await fetch('/suppliers', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': csrfToken || '',
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (data.success) {
                Swal.fire({ icon: 'success', title: 'Éxito', text: data.message || 'Proveedor creado exitosamente' })
                    .then(() => { closeNewSupplierModal(); location.reload(); });
            } else if (data.errors) {
                // Display each server-side validation error next to the relevant field
                Object.keys(data.errors).forEach(key => {
                    const errorEl = document.getElementById(`error-new-${key}`);
                    if (errorEl) { errorEl.textContent = data.errors[key][0]; errorEl.style.display = 'flex'; }
                });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Error al crear el proveedor' });
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión' });
        }
    });

    // Edit supplier modal 
    document.getElementById('close-edit-supplier-modal')?.addEventListener('click', closeEditSupplierModal);
    document.getElementById('cancel-edit-supplier')?.addEventListener('click', closeEditSupplierModal);

    // PUT to /suppliers/:id to update an existing record
    document.getElementById('save-edit-supplier')?.addEventListener('click', async function () {
        const form = document.getElementById('edit-supplier-form');
        const supplierId = document.getElementById('edit-supplier-id').value;
        const formData = new FormData(form);
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        // Clear field-level errors before re-submitting
        document.querySelectorAll('#edit-supplier-form .error-message').forEach(el => {
            el.textContent = '';
            el.style.display = 'none';
        });

        // Spoof the HTTP method since browsers only support GET/POST in forms
        formData.append('_method', 'PUT');

        try {
            const response = await fetch(`/suppliers/${supplierId}`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': csrfToken || '',
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (data.success) {
                Swal.fire({ icon: 'success', title: 'Éxito', text: data.message || 'Proveedor actualizado exitosamente' })
                    .then(() => { closeEditSupplierModal(); location.reload(); });
            } else if (data.errors) {
                // Show field-specific errors returned by the server
                Object.keys(data.errors).forEach(key => {
                    const errorEl = document.getElementById(`error-edit-${key}`);
                    if (errorEl) { errorEl.textContent = data.errors[key][0]; errorEl.style.display = 'flex'; }
                });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Error al actualizar el proveedor' });
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión' });
        }
    });

    // Close any modal when the user clicks outside it (on the backdrop)
    document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
        backdrop.addEventListener('click', function (e) {
            if (e.target === this) {
                this.closest('.edit-modal')?.classList.remove('active');
            }
        });
    });

    document.querySelectorAll('.modal-overlay').forEach((overlay) => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                overlay.classList.remove('active');
            }
        });
    });

    // Reads filter inputs and reloads the page with updated query params
    function applyFilters() {
        const name    = document.getElementById('buscarNombre')?.value.trim()   || '';
        const contact = document.getElementById('buscarContacto')?.value.trim() || '';

        const url = new URL(window.location.href);
        url.searchParams.set('page', '1'); // Reset to first page whenever filters change

        name    ? url.searchParams.set('name', name)       : url.searchParams.delete('name');
        contact ? url.searchParams.set('contact', contact) : url.searchParams.delete('contact');

        window.location.href = url.toString();
    }

    document.getElementById('btnBuscar')?.addEventListener('click', applyFilters);

    // Allow pressing Enter in either search field to trigger the filter
    ['buscarNombre', 'buscarContacto'].forEach(id => {
        document.getElementById(id)?.addEventListener('keydown', e => {
            if (e.key === 'Enter') applyFilters();
        });
    });

    // Removes all active filters and returns to page 1
    document.getElementById('limpiarFiltros')?.addEventListener('click', function () {
        const url = new URL(window.location.href);
        url.searchParams.delete('name');
        url.searchParams.delete('contact');
        url.searchParams.set('page', '1');
        window.location.href = url.toString();
    });

});

// Expose public functions to global scope for inline event handlers
window.registerSupplier = registerSupplier;
window.editSupplier = editSupplier;
window.viewSupplierDetail = viewSupplierDetail;
window.loadSupplierForEdit = loadSupplierForEdit;
window.closeModal = closeModal;
window.openNewSupplierModal = openNewSupplierModal;
window.closeNewSupplierModal = closeNewSupplierModal;
window.closeEditSupplierModal = closeEditSupplierModal;
window.validateForm = validateForm;
window.showMessage = showMessage;