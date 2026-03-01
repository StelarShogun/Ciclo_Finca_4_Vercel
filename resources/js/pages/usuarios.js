// Funciones para manejar el login y registro
function mostrarLogin() {
    document.getElementById('loginForm').classList.add('active');
    document.getElementById('registroForm').classList.remove('active');
}

function mostrarRegistro() {
    document.getElementById('registroForm').classList.add('active');
    document.getElementById('loginForm').classList.remove('active');
}

// Función para login
function loginUsuario(event) {
    event.preventDefault();
    
    const form = document.getElementById('formLogin');
    const formData = new FormData(form);
    const btnSubmit = document.getElementById('btnLoginSubmit');
    const btnTexto = document.getElementById('btnLoginTexto');
    const btnCargando = document.getElementById('btnLoginCargando');
    const mensaje = document.getElementById('mensajeFeedbackLogin');
    const mensajeTexto = document.getElementById('mensajeLoginTexto');
    
    // Cambiar estado del botón
    btnSubmit.disabled = true;
    btnTexto.classList.add('hidden');
    btnCargando.classList.remove('hidden');
    
    // Ocultar mensajes anteriores
    mensaje.classList.add('hidden');
    
    fetch('/login', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                           document.querySelector('input[name="_token"]').value
        }
    })
    .then(response => {
        // Verificar si la respuesta es 403 (Forbidden) para acceso denegado
        if (response.status === 403) {
            throw new Error('access_denied');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Login exitoso
            mensaje.classList.remove('hidden');
            mensaje.className = 'feedback-message success';
            mensajeTexto.textContent = data.message;
            
            // NO restaurar el botón en caso de éxito - mantenerlo deshabilitado
            // Redireccionar inmediatamente
            window.location.href = data.redirect;
        } else {
            // Error de login - restaurar botón para permitir reintento
            mensaje.classList.remove('hidden');
            mensaje.className = 'feedback-message error';
            mensajeTexto.textContent = data.message || 'Error en el inicio de sesión';
            
            // Restaurar estado del botón solo en caso de error
            btnSubmit.disabled = false;
            btnTexto.classList.remove('hidden');
            btnCargando.classList.add('hidden');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mensaje.classList.remove('hidden');
        mensaje.className = 'feedback-message error';
        
        if (error.message === 'access_denied') {
            mensajeTexto.textContent = 'Acceso denegado. Solo los administradores pueden acceder al sistema.';
        } else {
            mensajeTexto.textContent = 'Error de conexión. Inténtalo de nuevo.';
        }
        
        // Restaurar estado del botón en caso de error
        btnSubmit.disabled = false;
        btnTexto.classList.remove('hidden');
        btnCargando.classList.add('hidden');
    });
}

// Función para registro
function registrarUsuario(event, tipo = 'admin') {
    event.preventDefault();
    
    const form = document.getElementById('formRegistro');
    const formData = new FormData(form);
    const btnSubmit = document.getElementById('btnRegistroSubmit');
    const btnTexto = document.getElementById('btnRegistroTexto');
    const btnCargando = document.getElementById('btnRegistroCargando');
    const mensaje = document.getElementById('mensajeFeedbackRegistro');
    const mensajeTexto = document.getElementById('mensajeRegistroTexto');
    
    // Cambiar estado del botón
    btnSubmit.disabled = true;
    btnTexto.classList.add('hidden');
    btnCargando.classList.remove('hidden');
    
    // Ocultar mensajes anteriores
    mensaje.classList.add('hidden');
    
    const url = tipo === 'login' ? '/usuarios/store-login' : '/usuarios';
    
    fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                           document.querySelector('input[name="_token"]').value
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Registro exitoso
            mensaje.classList.remove('hidden');
            mensaje.className = 'feedback-message success';
            mensajeTexto.textContent = data.message;
            
            // Limpiar formulario
            form.reset();
            
            // Cambiar a login después de 2 segundos
            setTimeout(() => {
                mostrarLogin();
            }, 2000);
        } else {
            // Error de registro
            mensaje.classList.remove('hidden');
            mensaje.className = 'feedback-message error';
            
            if (data.errors) {
                // Mostrar errores de validación
                const errores = Object.values(data.errors).flat();
                mensajeTexto.textContent = errores.join(', ');
            } else {
                mensajeTexto.textContent = data.message;
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mensaje.classList.remove('hidden');
        mensaje.className = 'feedback-message error';
        mensajeTexto.textContent = 'Error de conexión. Inténtalo de nuevo.';
    })
    .finally(() => {
        // Restaurar estado del botón
        btnSubmit.disabled = false;
        btnTexto.classList.remove('hidden');
        btnCargando.classList.add('hidden');
    });
}

// Inicializar cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    // Mostrar formulario de login por defecto
    mostrarLogin();
    
    // Verificar si hay mensajes de error en la URL
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('error')) {
        const mensaje = document.getElementById('mensajeFeedbackLogin');
        const mensajeTexto = document.getElementById('mensajeLoginTexto');
        mensaje.classList.remove('hidden');
        mensaje.className = 'feedback-message error';
        mensajeTexto.textContent = urlParams.get('error');
    }
});

// Funciones para gestión de usuarios (admin)
function cerrarModal() {
    const modal = document.getElementById("modalDetalle");
    if (modal) {
        modal.classList.remove('active');
    }
}

function verDetalle(id) {
    console.log('verDetalle llamada con ID:', id); // Debug
    
    const modal = document.getElementById("modalDetalle");
    const contenido = document.getElementById("contenidoModal");
    
    if (!modal || !contenido) {
        console.error('Modal o contenido no encontrados');
        return;
    }
    
    // Mostrar mensaje de carga
    contenido.innerHTML = '<div style="text-align: center; padding: 20px;"><p>Cargando información del usuario ' + id + '...</p></div>';
    
    // Mostrar modal
    modal.classList.add('active'); 

    // Realizar petición AJAX
    fetch('/usuarios/' + id, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                           document.querySelector('input[name="_token"]')?.value
        }
    })
    .then(function(response) {
        if (!response.ok) {
            throw new Error('No se pudo cargar la información del usuario');
        }
        return response.json();
    })
    .then(function(data) {
        console.log('Datos recibidos:', data); // Debug
        
        if (!data.success) {
            throw new Error(data.message || 'Error al obtener datos del usuario');
        }
        
        const usuario = data.data;
        if (!usuario) {
            throw new Error('No se recibieron datos del usuario');
        }
        
        // Crear contenido del modal
        var rolCapitalizado = usuario.rol ? usuario.rol.charAt(0).toUpperCase() + usuario.rol.slice(1) : 'Sin rol';
        var fechaCreacion = usuario.fecha_creacion ? new Date(usuario.fecha_creacion).toLocaleDateString('es-ES') : 'No disponible';
        var ultimoAcceso = usuario.ultimo_acceso ? new Date(usuario.ultimo_acceso).toLocaleString('es-ES') : 'Nunca';
        var iniciales = '';
        
        if (usuario.nombre && usuario.apellido) {
            iniciales = usuario.nombre.charAt(0) + usuario.apellido.charAt(0);
        }
        
        contenido.innerHTML = 
            '<div class="user-detail-card">' +
                '<div class="user-header">' +
                    '<div class="user-avatar-large">' + iniciales + '</div>' +
                    '<div class="user-info">' +
                        '<h3>' + (usuario.nombre || '') + ' ' + (usuario.apellido || '') + '</h3>' +
                        '<p class="user-role ' + (usuario.rol || '') + '">' + rolCapitalizado + '</p>' +
                    '</div>' +
                '</div>' +
                '<div class="user-details">' +
                    '<div class="detail-row"><strong>Email:</strong> ' + (usuario.email || 'N/A') + '</div>' +
                    '<div class="detail-row"><strong>Fecha de registro:</strong> ' + fechaCreacion + '</div>' +
                    '<div class="detail-row"><strong>Último acceso:</strong> ' + ultimoAcceso + '</div>' +
                '</div>' +
            '</div>';
    })
    .catch(function(error) {
        console.error('Error:', error);
        contenido.innerHTML = 
            '<div class="error-message" style="text-align: center; padding: 20px; color: red;">' +
                '<i class="fas fa-exclamation-triangle"></i>' +
                '<p>Error al cargar los datos del usuario</p>' +
                '<small>' + error.message + '</small>' +
            '</div>';
    });
}

function eliminarUsuario(event, button) {
    event.preventDefault();
    
    // Obtener el ID del usuario desde el botón
    var usuarioId = button.getAttribute('data-usuario-id');
    if (!usuarioId) {
        // Si no tiene data-usuario-id, intentar obtenerlo del contexto
        var row = button.closest('tr');
        var userIdElement = row.querySelector('[data-usuario-id]');
        if (userIdElement) {
            usuarioId = userIdElement.getAttribute('data-usuario-id');
        }
    }
    
    if (!usuarioId) {
        Swal.fire({
            title: 'Error',
            text: 'No se pudo identificar el usuario a eliminar',
            icon: 'error',
            confirmButtonText: 'Entendido'
        });
        return false;
    }
    
    Swal.fire({
        title: '¿Estás seguro?',
        text: '¿Está seguro de que desea eliminar este usuario? Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Agregar indicador visual de que se está procesando
            button.disabled = true;
            var originalHTML = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            // Obtener CSRF token
            var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                           document.querySelector('input[name="_token"]')?.value;
            
            // Construir URL de eliminación
            var url = '/usuarios/' + usuarioId;
            
            // Usar fetch para enviar la petición DELETE
            fetch(url, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (response.ok) {
                    return response.json();
                } else {
                    return response.json().then(data => {
                        throw new Error(data.message || 'Error al eliminar el usuario');
                    });
                }
            })
            .then(data => {
                Swal.fire({
                    title: 'Eliminado',
                    text: data.message || 'Usuario eliminado correctamente',
                    icon: 'success',
                    confirmButtonText: 'Entendido'
                }).then(() => {
                    window.location.reload();
                });
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'Error al eliminar el usuario: ' + error.message,
                    icon: 'error',
                    confirmButtonText: 'Entendido'
                });
                // Restaurar el botón
                button.disabled = false;
                button.innerHTML = originalHTML;
            });
        }
    });
    
    return false;
}

// Funciones de filtrado y búsqueda
document.addEventListener('DOMContentLoaded', function() {
    const buscarInput = document.getElementById('buscar');
    const filtroRol = document.getElementById('filtroRol');
    const limpiarBtn = document.getElementById('limpiarFiltros');
    
    if (buscarInput) {
        buscarInput.addEventListener('input', filtrarUsuarios);
    }
    
    if (filtroRol) {
        filtroRol.addEventListener('change', filtrarUsuarios);
    }
    
    if (limpiarBtn) {
        limpiarBtn.addEventListener('click', limpiarFiltros);
    }
});

function filtrarUsuarios() {
    const busqueda = document.getElementById('buscar')?.value;
    const rolFiltro = document.getElementById('filtroRol')?.value;
    
    // Construir URL con parámetros
    const params = new URLSearchParams();
    if (busqueda) params.append('search', busqueda);
    if (rolFiltro) params.append('rol', rolFiltro);
    
    window.location.href = `${window.location.pathname}?${params.toString()}`;
}

function limpiarFiltros() {
    window.location.href = window.location.pathname;
}

function exportUsers() {
    // Crear formulario temporal para exportar usuarios
    var form = document.createElement('form');
    form.method = 'GET';
    form.action = '/usuarios/export';
    
    // Agregar filtros actuales si existen
    var search = document.getElementById('buscar')?.value;
    var rol = document.getElementById('filtroRol')?.value;
    
    if (search) {
        var searchInput = document.createElement('input');
        searchInput.type = 'hidden';
        searchInput.name = 'search';
        searchInput.value = search;
        form.appendChild(searchInput);
    }
    
    if (rol) {
        var rolInput = document.createElement('input');
        rolInput.type = 'hidden';
        rolInput.name = 'rol';
        rolInput.value = rol;
        form.appendChild(rolInput);
    }
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

// Función para cerrar alertas
function closeAlert() {
    const alert = document.getElementById('flashMessage');
    if (alert) {
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-10px)';
        setTimeout(() => {
            alert.remove();
        }, 300);
    }
}

// Auto-cerrar alertas después de 5 segundos
document.addEventListener('DOMContentLoaded', function() {
    const alert = document.getElementById('flashMessage');
    if (alert) {
        setTimeout(() => {
            closeAlert();
        }, 5000);
    }
});

window.mostrarLogin = mostrarLogin;
window.mostrarRegistro = mostrarRegistro;
window.loginUsuario = loginUsuario;
window.registrarUsuario = registrarUsuario;
window.cerrarModal = cerrarModal;
window.verDetalle = verDetalle;
window.eliminarUsuario = eliminarUsuario;
window.filtrarUsuarios = filtrarUsuarios;
window.limpiarFiltros = limpiarFiltros;
window.exportUsers = exportUsers;
window.closeAlert = closeAlert;