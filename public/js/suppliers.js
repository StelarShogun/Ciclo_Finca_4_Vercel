// ===== SUPPLIERS.JS - SUPPLIER MANAGEMENT FUNCTIONS =====

// Form validation function
function validateForm(form, type) {
    const errors = [];

    // Get form fields
    const name = form.querySelector('#name');
    const primaryContact = form.querySelector('#primary_contact');
    const phone = form.querySelector('#phone');
    const email = form.querySelector('#email');
    const address = form.querySelector('#address');
    const deliveryTime = form.querySelector('#delivery_time');
    const rating = form.querySelector('#rating');

    // Validate name
    if (!name.value.trim()) {
        errors.push('El nombre del proveedor es obligatorio');
    }

    // Validate primary contact
    if (!primaryContact.value.trim()) {
        errors.push('El contacto principal es obligatorio');
    }

    // Validate phone
    if (!phone.value.trim()) {
        errors.push('El teléfono es obligatorio');
    } else if (!/^[0-9+\s-]{8,}$/.test(phone.value)) {
        errors.push('El teléfono debe tener al menos 8 dígitos');
    }

    // Validate email
    if (!email.value.trim()) {
        errors.push('El correo electrónico es obligatorio');
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
        errors.push('El correo electrónico no es válido');
    }

    // Validate address
    if (!address.value.trim()) {
        errors.push('La dirección es obligatoria');
    }

    // Validate delivery time
    if (!deliveryTime.value) {
        errors.push('El tiempo de entrega es obligatorio');
    } else {
        const time = parseInt(deliveryTime.value);
        if (isNaN(time) || time < 1 || time > 365) {
            errors.push('El tiempo de entrega debe ser entre 1 y 365 días');
        }
    }

    // Validate rating if provided
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

// Show message function
function showMessage(message, type, elementId) {
    const element = document.getElementById(elementId);
    if (!element) {
        // Fallback to SweetAlert if element doesn't exist
        const iconType = type === 'success' ? 'success' : 'error';
        Swal.fire({
            icon: iconType,
            title: type === 'success' ? 'Éxito' : 'Error',
            text: message.replace(/[✅❌]/g, '').trim(),
            confirmButtonText: 'Entendido'
        });
        return;
    }

    const messageText = element.querySelector('span');
    const icon = element.querySelector('i');

    // Update CSS classes
    element.classList.remove('hidden', 'success', 'error', 'warning');
    element.classList.add(type);

    // Update icon based on type
    if (icon) {
        if (type === 'success') {
            icon.className = 'fas fa-check-circle';
        } else if (type === 'error') {
            icon.className = 'fas fa-exclamation-circle';
        } else {
            icon.className = 'fas fa-info-circle';
        }
    }

    // Update message text
    if (messageText) {
        messageText.textContent = message;
    } else {
        element.textContent = message;
    }

    // Show the element
    element.classList.remove('hidden');

    // Auto-hide after 5 seconds for success messages
    if (type === 'success') {
        setTimeout(() => {
            element.classList.add('hidden');
        }, 5000);
    }
}

async function registerSupplier(e) {
    e.preventDefault();
    const form = document.getElementById("formRegistro");

    const validation = validateForm(form, "register");
    if (!validation.valid) {
        const errorMessage = validation.errors
            .map((error) => `• ${error}`)
            .join("\n");

        Swal.fire({
            icon: 'error',
            title: 'Errores de validación',
            html: errorMessage.replace(/\n/g, '<br>'),
            confirmButtonText: 'Entendido'
        });
        return;
    }

    const btnSubmit = document.getElementById("btnRegistroSubmit");
    const btnText = document.getElementById("btnRegistroTexto");
    const btnLoading = document.getElementById("btnRegistroCargando");
    const message = document.getElementById("mensajeFeedbackRegistro");

    btnSubmit.disabled = true;
    btnText.classList.add("hidden");
    btnLoading.classList.remove("hidden");
    if (message) message.classList.add("hidden");

    const formData = new FormData(form);

    try {
        const response = await fetch(form.action, {
            method: "POST",
            body: formData,
        });

        const data = await response.json();

        if (data.success) {
            await Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: 'Proveedor registrado exitosamente.',
                confirmButtonText: 'Aceptar'
            });

            window.location.href = data.redirect || "/suppliers";
        } else {
            let errorMessage = data.message || "Error al registrar el proveedor";
            if (data.errors) {
                errorMessage = Object.values(data.errors).flat().join(", ");
            }
            showMessage(
                "❌ " + errorMessage,
                "error",
                "mensajeFeedbackRegistro"
            );
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error de conexión',
            text: 'No se pudo conectar con el servidor. Intenta nuevamente.',
            confirmButtonText: 'Entendido'
        });
        console.error("Error registering supplier:", error);
    } finally {
        btnSubmit.disabled = false;
        btnText.classList.remove("hidden");
        btnLoading.classList.add("hidden");
    }
}

// =============================== EDIT SUPPLIER FUNCTION ================================
async function editSupplier(e) {
    e.preventDefault();

    const form = document.getElementById("formEdicion");

    const validation = validateForm(form, "edit");
    if (!validation.valid) {
        const errorMessage = validation.errors
            .map((error) => `• ${error}`)
            .join("\n");

        Swal.fire({
            icon: 'error',
            title: 'Errores de validación',
            html: errorMessage.replace(/\n/g, '<br>'),
            confirmButtonText: 'Entendido'
        });
        return;
    }

    const btnSubmit = document.getElementById("btnSubmit");
    const btnText = document.getElementById("btnTexto");
    const btnLoading = document.getElementById("btnCargando");
    const message = document.getElementById("mensajeFeedbackEditar");

    btnSubmit.disabled = true;
    btnText.classList.add("hidden");
    btnLoading.classList.remove("hidden");
    if (message) message.classList.add("hidden");

    const formData = new FormData(form);
    formData.append("_method", "PUT");

    try {
        const response = await fetch(form.action, {
            method: "POST",
            body: formData,
        });

        const data = await response.json();

        if (data.success) {
            await Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: 'Proveedor actualizado exitosamente.',
                confirmButtonText: 'Aceptar'
            });

            window.location.href = data.redirect || "/suppliers";
        } else {
            let errorMessage = data.message || "Error al actualizar el proveedor";
            if (data.errors) {
                errorMessage = Object.values(data.errors).flat().join(", ");
            }
            showMessage(
                "❌ " + errorMessage,
                "error",
                "mensajeFeedbackEditar"
            );
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error de conexión',
            text: 'No se pudo conectar con el servidor. Intenta nuevamente.',
            confirmButtonText: 'Entendido'
        });
        console.error("Error editing supplier:", error);
    } finally {
        btnSubmit.disabled = false;
        btnText.classList.remove("hidden");
        btnLoading.classList.add("hidden");
    }
}

// View supplier details
async function viewSupplierDetail(id) {
    try {
        const response = await fetch(`/suppliers/${id}`, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            }
        });

        if (!response.ok) {
            throw new Error('Error fetching supplier data');
        }

        const data = await response.json();

        if (data.success) {
            const supplier = data.data;

            // Fill modal with supplier data
            document.getElementById('modalProveedorNombre').textContent = supplier.name || 'N/A';
            document.getElementById('modalProveedorEmail').textContent = supplier.email || 'N/A';
            document.getElementById('modalProveedorTelefono').textContent = supplier.phone || 'N/A';
            document.getElementById('modalProveedorDireccion').textContent = supplier.address || 'N/A';
            document.getElementById('modalProveedorEvaluacion').textContent = supplier.rating || '0';
            document.getElementById('modalProveedorEstado').textContent = supplier.status || 'N/A';
            document.getElementById('modalProveedorFechaRegistro').textContent = supplier.created_at ? new Date(supplier.created_at).toLocaleDateString() : 'N/A';

            // Show modal
            document.getElementById('modalDetalleProveedor').classList.add('active');
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'No se pudieron obtener los datos del proveedor'
            });
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error de conexión al obtener los datos del proveedor'
        });
    }
}

// Close detail modal
function closeModal() {
    document.getElementById('modalDetalleProveedor').classList.remove('active');
}

// Delete supplier
async function deleteSupplier(event) {
    event.preventDefault();

    const form = event.target;

    // Get supplier ID from form action URL
    const actionUrl = form.getAttribute('action');
    const supplierId = actionUrl.split('/').pop();

    // Get supplier name from the table row
    const row = form.closest('tr');
    const nameCell = row.querySelector('.proveedor-nombre');
    const supplierName = nameCell ? nameCell.textContent.trim() : 'this supplier';

    const result = await Swal.fire({
        title: '¿Estás seguro?',
        text: `¿Deseas eliminar el proveedor "${supplierName}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        try {
            const response = await fetch(actionUrl, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                }
            });

            const data = await response.json();

            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Eliminado',
                    text: 'El proveedor ha sido eliminado correctamente'
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Error al eliminar el proveedor'
                });
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error de conexión al eliminar el proveedor'
            });
        }
    }
}

// Open new supplier modal
function openNewSupplierModal() {
    const modal = document.getElementById('new-supplier-modal');
    if (modal) {
        modal.classList.add('active');
        // Reset form
        document.getElementById('new-supplier-form').reset();
        // Clear error messages
        document.querySelectorAll('#new-supplier-form .error-message').forEach(el => {
            el.textContent = '';
            el.style.display = 'none';
        });
    }
}

// Close new supplier modal
function closeNewSupplierModal() {
    const modal = document.getElementById('new-supplier-modal');
    if (modal) {
        modal.classList.remove('active');
    }
}

// Load supplier data into edit modal
async function loadSupplierForEdit(id) {
    try {
        const response = await fetch(`/suppliers/${id}`, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            }
        });

        if (!response.ok) {
            throw new Error('Error fetching supplier data');
        }

        const data = await response.json();

        if (data.success) {
            const supplier = data.data;

            // Fill form with supplier data
            document.getElementById('edit-supplier-id').value = supplier.supplier_id;
            document.getElementById('edit-supplier-nombre').value = supplier.name || '';
            document.getElementById('edit-supplier-contacto').value = supplier.primary_contact || '';
            document.getElementById('edit-supplier-telefono').value = supplier.phone || '';
            document.getElementById('edit-supplier-email').value = supplier.email || '';
            document.getElementById('edit-supplier-direccion').value = supplier.address || '';
            document.getElementById('edit-supplier-tiempo').value = supplier.delivery_time || '';
            document.getElementById('edit-supplier-evaluacion').value = supplier.rating || '';

            // Clear error messages
            document.querySelectorAll('#edit-supplier-form .error-message').forEach(el => {
                el.textContent = '';
                el.style.display = 'none';
            });

            // Show modal
            document.getElementById('edit-supplier-modal').classList.add('active');
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'No se pudieron obtener los datos del proveedor'
            });
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error de conexión al obtener los datos del proveedor'
        });
    }
}

// Close edit supplier modal
function closeEditSupplierModal() {
    const modal = document.getElementById('edit-supplier-modal');
    if (modal) {
        modal.classList.remove('active');
    }
}

// Event listeners on page load
document.addEventListener('DOMContentLoaded', function() {
    // New supplier modal
    const openNewSupplierBtn = document.getElementById('open-new-supplier-modal');
    const closeNewSupplierBtn = document.getElementById('close-new-supplier-modal');
    const cancelNewSupplierBtn = document.getElementById('cancel-new-supplier');
    const saveNewSupplierBtn = document.getElementById('save-new-supplier');

    if (openNewSupplierBtn) {
        openNewSupplierBtn.addEventListener('click', openNewSupplierModal);
    }

    if (closeNewSupplierBtn) {
        closeNewSupplierBtn.addEventListener('click', closeNewSupplierModal);
    }

    if (cancelNewSupplierBtn) {
        cancelNewSupplierBtn.addEventListener('click', closeNewSupplierModal);
    }

    // Save new supplier
    if (saveNewSupplierBtn) {
        saveNewSupplierBtn.addEventListener('click', async function() {
            const form = document.getElementById('new-supplier-form');
            const formData = new FormData(form);

            // Clear previous errors
            document.querySelectorAll('#new-supplier-form .error-message').forEach(el => {
                el.textContent = '';
                el.style.display = 'none';
            });

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

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
                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: data.message || 'Proveedor creado exitosamente'
                    }).then(() => {
                        closeNewSupplierModal();
                        location.reload();
                    });
                } else {
                    // Show validation errors
                    if (data.errors) {
                        Object.keys(data.errors).forEach(key => {
                            const errorEl = document.getElementById(`error-new-${key}`);
                            if (errorEl) {
                                errorEl.textContent = data.errors[key][0];
                                errorEl.style.display = 'flex';
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Error al crear el proveedor'
                        });
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error de conexión'
                });
            }
        });
    }

    // Edit supplier modal
    const closeEditSupplierBtn = document.getElementById('close-edit-supplier-modal');
    const cancelEditSupplierBtn = document.getElementById('cancel-edit-supplier');
    const saveEditSupplierBtn = document.getElementById('save-edit-supplier');

    if (closeEditSupplierBtn) {
        closeEditSupplierBtn.addEventListener('click', closeEditSupplierModal);
    }

    if (cancelEditSupplierBtn) {
        cancelEditSupplierBtn.addEventListener('click', closeEditSupplierModal);
    }

    // Save supplier changes
    if (saveEditSupplierBtn) {
        saveEditSupplierBtn.addEventListener('click', async function() {
            const form = document.getElementById('edit-supplier-form');
            const formData = new FormData(form);
            const supplierId = document.getElementById('edit-supplier-id').value;

            // Clear previous errors
            document.querySelectorAll('#edit-supplier-form .error-message').forEach(el => {
                el.textContent = '';
                el.style.display = 'none';
            });

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                formData.append('_method', 'PUT');

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
                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: data.message || 'Proveedor actualizado exitosamente'
                    }).then(() => {
                        closeEditSupplierModal();
                        location.reload();
                    });
                } else {
                    // Show validation errors
                    if (data.errors) {
                        Object.keys(data.errors).forEach(key => {
                            const errorEl = document.getElementById(`error-edit-${key}`);
                            if (errorEl) {
                                errorEl.textContent = data.errors[key][0];
                                errorEl.style.display = 'flex';
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Error al actualizar el proveedor'
                        });
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error de conexión'
                });
            }
        });
    }

    // Close modals when clicking the backdrop
    document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
        backdrop.addEventListener('click', function(e) {
            if (e.target === this) {
                this.closest('.edit-modal').classList.remove('active');
            }
        });
    });

    // ===== SEARCH FILTERS =====
    function applyFilters() {
        const name    = document.getElementById('buscarNombre')?.value.trim() || '';
        const contact = document.getElementById('buscarContacto')?.value.trim() || '';

        const url = new URL(window.location.href);
        url.searchParams.set('page', '1'); // Reset to first page when filtering

        if (name) {
            url.searchParams.set('name', name);
        } else {
            url.searchParams.delete('name');
        }

        if (contact) {
            url.searchParams.set('contact', contact);
        } else {
            url.searchParams.delete('contact');
        }

        window.location.href = url.toString();
    }

    const btnBuscar = document.getElementById('btnBuscar');
    if (btnBuscar) {
        btnBuscar.addEventListener('click', applyFilters);
    }

    // Also trigger search on Enter key press in any filter input
    ['buscarNombre', 'buscarContacto'].forEach(function(id) {
        const input = document.getElementById(id);
        if (input) {
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') applyFilters();
            });
        }
    });

    // Clear filters
    const btnLimpiar = document.getElementById('limpiarFiltros');
    if (btnLimpiar) {
        btnLimpiar.addEventListener('click', function() {
            const url = new URL(window.location.href);
            url.searchParams.delete('name');
            url.searchParams.delete('contact');
            url.searchParams.set('page', '1');
            window.location.href = url.toString();
        });
    }
});