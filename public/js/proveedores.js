// ===== PROVEEDORES.JS - FUNCIONES PARA GESTIÓN DE PROVEEDORES =====

// Función para validar el formulario
function validarFormulario(form, tipo) {
    const errores = [];
    
    // Obtener los campos
    const nombre = form.querySelector('#nombre');
    const contactoPrincipal = form.querySelector('#contacto_principal');
    const telefono = form.querySelector('#telefono');
    const correoElectronico = form.querySelector('#correo_electronico');
    const direccion = form.querySelector('#direccion');
    const tiempoEntrega = form.querySelector('#tiempo_entrega');
    const evaluacion = form.querySelector('#evaluacion');
    
    // Validar nombre
    if (!nombre.value.trim()) {
        errores.push('El nombre del proveedor es obligatorio');
    }
    
    // Validar contacto principal
    if (!contactoPrincipal.value.trim()) {
        errores.push('El contacto principal es obligatorio');
    }
    
    // Validar teléfono
    if (!telefono.value.trim()) {
        errores.push('El teléfono es obligatorio');
    } else if (!/^[0-9+\s-]{8,}$/.test(telefono.value)) {
        errores.push('El teléfono debe tener al menos 8 dígitos');
    }
    
    // Validar correo electrónico
    if (!correoElectronico.value.trim()) {
        errores.push('El correo electrónico es obligatorio');
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correoElectronico.value)) {
        errores.push('El correo electrónico no es válido');
    }
    
    // Validar dirección
    if (!direccion.value.trim()) {
        errores.push('La dirección es obligatoria');
    }
    
    // Validar tiempo de entrega
    if (!tiempoEntrega.value) {
        errores.push('El tiempo de entrega es obligatorio');
    } else {
        const tiempo = parseInt(tiempoEntrega.value);
        if (isNaN(tiempo) || tiempo < 1 || tiempo > 365) {
            errores.push('El tiempo de entrega debe ser entre 1 y 365 días');
        }
    }
    
    // Validar evaluación si existe
    if (evaluacion && evaluacion.value) {
        const eval = parseFloat(evaluacion.value);
        if (isNaN(eval) || eval < 0 || eval > 5) {
            errores.push('La evaluación debe estar entre 0 y 5');
        }
    }
    
    return {
        valid: errores.length === 0,
        errores: errores
    };
}

// Función para mostrar mensajes
function mostrarMensaje(mensaje, tipo, elementoId) {
    const elemento = document.getElementById(elementoId);
    if (!elemento) {
        // Si no existe el elemento, usar SweetAlert como respaldo
        const iconType = tipo === 'exito' ? 'success' : 'error';
        Swal.fire({
            icon: iconType,
            title: tipo === 'exito' ? 'Éxito' : 'Error',
            text: mensaje.replace(/[✅❌]/g, '').trim(),
            confirmButtonText: 'Entendido'
        });
        return;
    }
    
    const mensajeTexto = elemento.querySelector('span');
    const icon = elemento.querySelector('i');
    
    // Actualizar clases CSS
    elemento.classList.remove('hidden', 'success', 'error', 'warning');
    elemento.classList.add(tipo);
    
    // Actualizar icono según el tipo
    if (icon) {
        if (tipo === 'exito') {
            icon.className = 'fas fa-check-circle';
        } else if (tipo === 'error') {
            icon.className = 'fas fa-exclamation-circle';
        } else {
            icon.className = 'fas fa-info-circle';
        }
    }
    
    // Actualizar texto del mensaje
    if (mensajeTexto) {
        mensajeTexto.textContent = mensaje;
    } else {
        elemento.textContent = mensaje;
    }
    
    // Mostrar el elemento
    elemento.classList.remove('hidden');
    
    // Auto-ocultar después de 5 segundos para mensajes de éxito
    if (tipo === 'exito') {
        setTimeout(() => {
            elemento.classList.add('hidden');
        }, 5000);
    }
}

async function registrarProveedor(e) {
    e.preventDefault();
    const form = document.getElementById("formRegistro");

    const validacion = validarFormulario(form, "registro");
    if (!validacion.valid) {
        const mensajeError = validacion.errores
            .map((error) => `• ${error}`)
            .join("\n");
        
        // Mostrar errores con SweetAlert
        Swal.fire({
            icon: 'error',
            title: 'Errores de validación',
            html: mensajeError.replace(/\n/g, '<br>'),
            confirmButtonText: 'Entendido'
        });
        return;
    }

    const btnSubmit = document.getElementById("btnRegistroSubmit");
    const btnTexto = document.getElementById("btnRegistroTexto");
    const btnCargando = document.getElementById("btnRegistroCargando");
    const mensaje = document.getElementById("mensajeFeedbackRegistro");

    btnSubmit.disabled = true;
    btnTexto.classList.add("hidden");
    btnCargando.classList.remove("hidden");
    if (mensaje) mensaje.classList.add("hidden");

    const formData = new FormData(form);

    try {
        const response = await fetch(form.action, {
            method: "POST",
            body: formData,
        });

        const data = await response.json();

        if (data.success) {
            // Mostrar mensaje de éxito con SweetAlert
            await Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: 'Proveedor registrado exitosamente.',
                confirmButtonText: 'Aceptar'
            });
            
            // Redireccionar
            window.location.href = data.redirect || "/proveedores";
        } else {
            let mensajeError = data.message || "Error al registrar proveedor";
            if (data.errors) {
                mensajeError = Object.values(data.errors).flat().join(", ");
            }
            mostrarMensaje(
                "❌ " + mensajeError,
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
        console.error("Error en registro:", error);
    } finally {
        btnSubmit.disabled = false;
        btnTexto.classList.remove("hidden");
        btnCargando.classList.add("hidden");
    }
}

//===============================FUNCION PARA EDITAR PROVEEDOR================================
async function editarProveedor(e) {
    e.preventDefault();

    const form = document.getElementById("formEdicion");

    const validacion = validarFormulario(form, "edicion");
    if (!validacion.valid) {
        const mensajeError = validacion.errores
            .map((error) => `• ${error}`)
            .join("\n");
        
        // Mostrar errores con SweetAlert
        Swal.fire({
            icon: 'error',
            title: 'Errores de validación',
            html: mensajeError.replace(/\n/g, '<br>'),
            confirmButtonText: 'Entendido'
        });
        return;
    }

    const btnSubmit = document.getElementById("btnSubmit");
    const btnTexto = document.getElementById("btnTexto");
    const btnCargando = document.getElementById("btnCargando");
    const mensaje = document.getElementById("mensajeFeedbackEditar");

    btnSubmit.disabled = true;
    btnTexto.classList.add("hidden");
    btnCargando.classList.remove("hidden");
    if (mensaje) mensaje.classList.add("hidden");

    const formData = new FormData(form);
    formData.append("_method", "PUT");

    try {
        const response = await fetch(form.action, {
            method: "POST",
            body: formData,
        });

        const data = await response.json();

        if (data.success) {
            // Mostrar mensaje de éxito con SweetAlert
            await Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: 'Proveedor actualizado exitosamente.',
                confirmButtonText: 'Aceptar'
            });
            
            // Redireccionar
            window.location.href = data.redirect || "/proveedores";
        } else {
            let mensajeError = data.message || "Error al actualizar proveedor";
            if (data.errors) {
                mensajeError = Object.values(data.errors).flat().join(", ");
            }
            mostrarMensaje(
                "❌ " + mensajeError,
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
        console.error("Error en edición:", error);
    } finally {
        btnSubmit.disabled = false;
        btnTexto.classList.remove("hidden");
        btnCargando.classList.add("hidden");
    }
}

// Función para ver detalles del proveedor
async function verDetalle(id) {
    try {
        const response = await fetch(`/proveedores/${id}`, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            }
        });

        if (!response.ok) {
            throw new Error('Error al obtener los datos del proveedor');
        }

        const data = await response.json();
        
        if (data.success) {
            const proveedor = data.data;
            
            // Llenar el modal con los datos
            document.getElementById('modalProveedorNombre').textContent = proveedor.nombre || 'N/A';
            document.getElementById('modalProveedorEmail').textContent = proveedor.email || 'N/A';
            document.getElementById('modalProveedorTelefono').textContent = proveedor.telefono || 'N/A';
            document.getElementById('modalProveedorDireccion').textContent = proveedor.direccion || 'N/A';
            document.getElementById('modalProveedorEvaluacion').textContent = proveedor.evaluacion || '0';
            document.getElementById('modalProveedorEstado').textContent = proveedor.estado || 'N/A';
            document.getElementById('modalProveedorFechaRegistro').textContent = proveedor.created_at ? new Date(proveedor.created_at).toLocaleDateString() : 'N/A';
            
            // Mostrar el modal
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

// Función para cerrar el modal
function cerrarModal() {
    document.getElementById('modalDetalleProveedor').classList.remove('active');
}

// Función para eliminar proveedor
async function eliminarProveedor(event) {
    event.preventDefault();
    
    const form = event.target;
    
    // Obtener el ID del proveedor de la URL del formulario
    const actionUrl = form.getAttribute('action');
    const proveedorId = actionUrl.split('/').pop();
    
    // Obtener el nombre del proveedor de la fila
    const row = form.closest('tr');
    const nombreCell = row.querySelector('.proveedor-nombre');
    const proveedorNombre = nombreCell ? nombreCell.textContent.trim() : 'este proveedor';

    const result = await Swal.fire({
        title: '¿Estás seguro?',
        text: `¿Deseas eliminar el proveedor "${proveedorNombre}"?`,
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

// Función para abrir modal de nuevo proveedor
function abrirModalNuevoProveedor() {
    const modal = document.getElementById('new-supplier-modal');
    if (modal) {
        modal.classList.add('active');
        // Limpiar formulario
        document.getElementById('new-supplier-form').reset();
        // Limpiar mensajes de error
        document.querySelectorAll('#new-supplier-form .error-message').forEach(el => {
            el.textContent = '';
            el.style.display = 'none';
        });
    }
}

// Función para cerrar modal de nuevo proveedor
function cerrarModalNuevoProveedor() {
    const modal = document.getElementById('new-supplier-modal');
    if (modal) {
        modal.classList.remove('active');
    }
}

// Función para editar proveedor
async function editarProveedor(id) {
    try {
        const response = await fetch(`/suppliers/${id}`, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            }
        });

        if (!response.ok) {
            throw new Error('Error al obtener los datos del proveedor');
        }

        const data = await response.json();
        
        if (data.success) {
            const proveedor = data.data;
            
            // Llenar el formulario con los datos
            document.getElementById('edit-supplier-id').value = proveedor.proveedor_id;
            document.getElementById('edit-supplier-nombre').value = proveedor.nombre || '';
            document.getElementById('edit-supplier-contacto').value = proveedor.contacto_principal || '';
            document.getElementById('edit-supplier-telefono').value = proveedor.telefono || '';
            document.getElementById('edit-supplier-email').value = proveedor.email || '';
            document.getElementById('edit-supplier-direccion').value = proveedor.direccion || '';
            document.getElementById('edit-supplier-tiempo').value = proveedor.tiempo_entrega || '';
            document.getElementById('edit-supplier-evaluacion').value = proveedor.evaluacion || '';
            
            // Limpiar mensajes de error
            document.querySelectorAll('#edit-supplier-form .error-message').forEach(el => {
                el.textContent = '';
                el.style.display = 'none';
            });
            
            // Mostrar el modal
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

// Función para cerrar modal de editar proveedor
function cerrarModalEditarProveedor() {
    const modal = document.getElementById('edit-supplier-modal');
    if (modal) {
        modal.classList.remove('active');
    }
}

// Event listeners cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    // Modal de nuevo proveedor
    const openNewSupplierBtn = document.getElementById('open-new-supplier-modal');
    const closeNewSupplierBtn = document.getElementById('close-new-supplier-modal');
    const cancelNewSupplierBtn = document.getElementById('cancel-new-supplier');
    const saveNewSupplierBtn = document.getElementById('save-new-supplier');
    
    if (openNewSupplierBtn) {
        openNewSupplierBtn.addEventListener('click', abrirModalNuevoProveedor);
    }
    
    if (closeNewSupplierBtn) {
        closeNewSupplierBtn.addEventListener('click', cerrarModalNuevoProveedor);
    }
    
    if (cancelNewSupplierBtn) {
        cancelNewSupplierBtn.addEventListener('click', cerrarModalNuevoProveedor);
    }
    
    // Guardar nuevo proveedor
    if (saveNewSupplierBtn) {
        saveNewSupplierBtn.addEventListener('click', async function() {
            const form = document.getElementById('new-supplier-form');
            const formData = new FormData(form);
            
            // Limpiar errores anteriores
            document.querySelectorAll('#new-supplier-form .error-message').forEach(el => {
                el.textContent = '';
                el.style.display = 'none';
            });
            
            try {
                // #region agent log
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const logData = {
                    location: 'proveedores.js:222',
                    message: 'Creating supplier - CSRF token check',
                    data: {
                        hasToken: !!csrfToken,
                        tokenLength: csrfToken?.length || 0,
                        tokenPreview: csrfToken ? csrfToken.substring(0, 10) + '...' : 'none',
                        formDataKeys: Array.from(formData.keys())
                    },
                    timestamp: Date.now(),
                    sessionId: 'debug-session',
                    runId: 'run1',
                    hypothesisId: 'A'
                };
                fetch('http://127.0.0.1:7242/ingest/06ff477a-d5ea-4b72-9c90-709614fdeca0',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(logData)}).catch(()=>{});
                // #endregion
                
                const response = await fetch('/suppliers', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': csrfToken || '',
                        'Accept': 'application/json'
                    }
                });
                
                // #region agent log
                const responseLog = {
                    location: 'proveedores.js:232',
                    message: 'Supplier creation response',
                    data: {
                        status: response.status,
                        statusText: response.statusText,
                        ok: response.ok,
                        headers: Object.fromEntries(response.headers.entries())
                    },
                    timestamp: Date.now(),
                    sessionId: 'debug-session',
                    runId: 'run1',
                    hypothesisId: 'A'
                };
                fetch('http://127.0.0.1:7242/ingest/06ff477a-d5ea-4b72-9c90-709614fdeca0',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(responseLog)}).catch(()=>{});
                // #endregion
                
                const data = await response.json();
                
                // #region agent log
                if (!response.ok) {
                    const errorLog = {
                        location: 'proveedores.js:240',
                        message: 'Supplier creation failed',
                        data: {
                            status: response.status,
                            errorData: data
                        },
                        timestamp: Date.now(),
                        sessionId: 'debug-session',
                        runId: 'run1',
                        hypothesisId: 'A'
                    };
                    fetch('http://127.0.0.1:7242/ingest/06ff477a-d5ea-4b72-9c90-709614fdeca0',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(errorLog)}).catch(()=>{});
                }
                // #endregion
                
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: data.message || 'Proveedor creado exitosamente'
                    }).then(() => {
                        cerrarModalNuevoProveedor();
                        location.reload();
                    });
                } else {
                    // Mostrar errores de validación
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
    
    // Modal de editar proveedor
    const closeEditSupplierBtn = document.getElementById('close-edit-supplier-modal');
    const cancelEditSupplierBtn = document.getElementById('cancel-edit-supplier');
    const saveEditSupplierBtn = document.getElementById('save-edit-supplier');
    
    if (closeEditSupplierBtn) {
        closeEditSupplierBtn.addEventListener('click', cerrarModalEditarProveedor);
    }
    
    if (cancelEditSupplierBtn) {
        cancelEditSupplierBtn.addEventListener('click', cerrarModalEditarProveedor);
    }
    
    // Guardar cambios de proveedor
    if (saveEditSupplierBtn) {
        saveEditSupplierBtn.addEventListener('click', async function() {
            const form = document.getElementById('edit-supplier-form');
            const formData = new FormData(form);
            const proveedorId = document.getElementById('edit-supplier-id').value;
            
            // Limpiar errores anteriores
            document.querySelectorAll('#edit-supplier-form .error-message').forEach(el => {
                el.textContent = '';
                el.style.display = 'none';
            });
            
            try {
                // #region agent log
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                fetch('http://127.0.0.1:7242/ingest/06ff477a-d5ea-4b72-9c90-709614fdeca0',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'proveedores.js:294',message:'Updating supplier - CSRF token check',data:{hasToken:!!csrfToken,tokenLength:csrfToken?.length||0,proveedorId},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'A'})}).catch(()=>{});
                // #endregion
                
                const response = await fetch(`/suppliers/${proveedorId}`, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': csrfToken || '',
                        'Accept': 'application/json'
                    }
                });
                
                // #region agent log
                fetch('http://127.0.0.1:7242/ingest/06ff477a-d5ea-4b72-9c90-709614fdeca0',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'proveedores.js:304',message:'Supplier update response',data:{status:response.status,statusText:response.statusText,ok:response.ok},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'A'})}).catch(()=>{});
                // #endregion
                
                const data = await response.json();
                
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: data.message || 'Proveedor actualizado exitosamente'
                    }).then(() => {
                        cerrarModalEditarProveedor();
                        location.reload();
                    });
                } else {
                    // Mostrar errores de validación
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
    
    // Cerrar modales al hacer clic en el backdrop
    document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
        backdrop.addEventListener('click', function(e) {
            if (e.target === this) {
                this.closest('.edit-modal').classList.remove('active');
            }
        });
    });
});