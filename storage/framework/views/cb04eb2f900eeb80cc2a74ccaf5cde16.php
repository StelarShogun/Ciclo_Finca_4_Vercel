<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title>Usuarios - Ciclo Pérez Admin</title>
    
    <!-- Favicons modernos -->
    <link rel="icon" type="image/svg+xml" href="<?php echo e(asset('favicon.svg')); ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo e(asset('favicon-32x32.png')); ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo e(asset('favicon-16x16.png')); ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo e(asset('apple-touch-icon.png')); ?>">
    <link rel="icon" type="image/x-icon" href="<?php echo e(asset('favicon.ico')); ?>">
    
    <link rel="stylesheet" href="<?php echo e(asset('estilos.php')); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="admin-layout">
    <?php echo $__env->make('partes.aside', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <main class="admin-main">
        <div class="usuarios-container">
            <header class="usuarios-header">
                <div>
                    <h1>Gestión de Usuarios</h1>
                    <p>Registra y administra los usuarios del sistema</p>
                </div>
                <div class="usuarios-actions">
                    <button class="btn btn-primary" id="open-new-usuario-modal">
                        <i class="fas fa-plus"></i>
                        Nuevo Usuario
                    </a>
                </div>
            </header>

            <!-- Mensajes Flash -->
            <?php if(session('success')): ?>
                <div class="alert alert-success" id="flashMessage">
                    <i class="fas fa-check-circle"></i>
                    <?php echo e(session('success')); ?>

                    <button type="button" class="alert-close" onclick="closeAlert()">&times;</button>
                </div>
            <?php endif; ?>

            <?php if(session('error')): ?>
                <div class="alert alert-error" id="flashMessage">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo e(session('error')); ?>

                    <button type="button" class="alert-close" onclick="closeAlert()">&times;</button>
                </div>
            <?php endif; ?>
        <!-- Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <p class="stat-title">Total Usuarios</p>
                        <p class="stat-value" id="totalUsuarios"><?php echo e($usuarios->total()); ?></p>
                    </div>
                    <div class="stat-icon blue">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <p class="stat-title">Clientes</p>
                        <p class="stat-value" id="totalClientes"><?php echo e($usuarios->where('rol', 'cliente')->count()); ?></p>
                    </div>
                    <div class="stat-icon green">
                        <i class="fas fa-user"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <p class="stat-title">Vendedores</p>
                        <p class="stat-value" id="totalVendedores"><?php echo e($usuarios->where('rol', 'vendedor')->count()); ?></p>
                    </div>
                    <div class="stat-icon yellow">
                        <i class="fas fa-user-tie"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <p class="stat-title">Administradores</p>
                        <p class="stat-value" id="totalAdmins"><?php echo e($usuarios->where('rol', 'admin')->count()); ?></p>
                    </div>
                    <div class="stat-icon purple">
                        <i class="fas fa-user-shield"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <p class="stat-title">Técnicos</p>
                        <p class="stat-value" id="totalTecnicos"><?php echo e($usuarios->where('rol', 'tecnico')->count()); ?></p>
                    </div>
                    <div class="stat-icon orange">
                        <i class="fas fa-tools"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filters-section">
            <h2 class="filters-title">Filtros de Búsqueda</h2>
            <div class="filters-container">
                <div class="search-input">
                    <input type="text" id="buscar" placeholder="Buscar por nombre, apellido o email..."
                        value="<?php echo e(request('search')); ?>">
                </div>
                <select id="filtroRol" class="filter-select">
                    <option value="">Todos los roles</option>
                    <option value="cliente" <?php echo e(request('rol') == 'cliente' ? 'selected' : ''); ?>>Cliente</option>
                    <option value="vendedor" <?php echo e(request('rol') == 'vendedor' ? 'selected' : ''); ?>>Vendedor</option>
                    <option value="admin" <?php echo e(request('rol') == 'admin' ? 'selected' : ''); ?>>Administrador</option>
                    <option value="tecnico" <?php echo e(request('rol') == 'tecnico' ? 'selected' : ''); ?>>Técnico</option>
                </select>
                <?php if(request('search') || request('rol')): ?>
                    <button type="button" class="button button-secondary" id="limpiarFiltros">
                        <i class="fas fa-times"></i>
                        Limpiar
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tabla de Usuarios -->
        <div class="usuarios-table-container">
            <div class="table-responsive">
                <table class="usuarios-table">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Fecha Registro</th>
                            <th>Último Acceso</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tablaUsuarios">
                        <?php $__empty_1 = true; $__currentLoopData = $usuarios; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $usuario): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr class="usuario-row" data-rol="<?php echo e($usuario->rol); ?>" data-usuario-id="<?php echo e($usuario->usuario_id); ?>">
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            <?php echo e(substr($usuario->nombre, 0, 1)); ?><?php echo e(substr($usuario->apellido, 0, 1)); ?>

                                        </div>
                                        <div class="user-details">
                                            <h4 class="usuario-nombre"><?php echo e($usuario->nombre); ?> <?php echo e($usuario->apellido); ?></h4>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="usuario-email"><?php echo e($usuario->email); ?></div>
                                </td>
                                <td>
                                    <?php if($usuario->rol === 'admin'): ?>
                                        <span class="role-badge admin">Administrador</span>
                                    <?php elseif($usuario->rol === 'vendedor'): ?>
                                        <span class="role-badge vendedor">Vendedor</span>
                                    <?php elseif($usuario->rol === 'tecnico'): ?>
                                        <span class="role-badge tecnico">Técnico</span>
                                    <?php else: ?>
                                        <span class="role-badge cliente">Cliente</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo e($usuario->fecha_creacion->format('d/m/Y')); ?>

                                </td>
                                <td>
                                    <?php echo e($usuario->ultimo_acceso ? $usuario->ultimo_acceso->format('d/m/Y H:i') : 'Nunca'); ?>

                                </td>
                                <td>
                                    <div class="actions-container">
                                        <button onclick="verDetalle('<?php echo e($usuario->usuario_id); ?>')" class="action-btn view" title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button onclick="editarUsuario('<?php echo e($usuario->usuario_id); ?>')" class="action-btn edit" title="Editar usuario">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" onclick="eliminarUsuario(event, this)" class="action-btn delete" title="Eliminar usuario" data-usuario-id="<?php echo e($usuario->usuario_id); ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <i class="fas fa-users"></i>
                                        <h3>No hay usuarios registrados</h3>
                                        <p>No se encontraron usuarios en el sistema.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Paginación -->
        <?php if (isset($component)) { $__componentOriginal41032d87daf360242eb88dbda6c75ed1 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal41032d87daf360242eb88dbda6c75ed1 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.pagination','data' => ['paginator' => $usuarios,'label' => 'de usuarios']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('pagination'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['paginator' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($usuarios),'label' => 'de usuarios']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal41032d87daf360242eb88dbda6c75ed1)): ?>
<?php $attributes = $__attributesOriginal41032d87daf360242eb88dbda6c75ed1; ?>
<?php unset($__attributesOriginal41032d87daf360242eb88dbda6c75ed1); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal41032d87daf360242eb88dbda6c75ed1)): ?>
<?php $component = $__componentOriginal41032d87daf360242eb88dbda6c75ed1; ?>
<?php unset($__componentOriginal41032d87daf360242eb88dbda6c75ed1); ?>
<?php endif; ?>

        <!-- Modal para ver detalles -->
        <div id="modalDetalle" class="edit-modal">
            <div class="modal-backdrop"></div>
            <div class="modal-content modal-auto-size">
                <div class="modal-header">
                    <h3><i class="fas fa-eye"></i> Detalles del Usuario</h3>
                    <button onclick="cerrarModal()" class="modal-close" id="close-view-usuario-modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div id="contenidoModal" class="modal-body">
                    <!-- Contenido dinámico -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModal()">
                        <i class="fas fa-times"></i> Cerrar
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal para editar usuario -->
        <div id="modalEditar" class="edit-modal">
            <div class="modal-backdrop"></div>
            <div class="modal-content modal-auto-size">
                <div class="modal-header">
                    <h3><i class="fas fa-edit"></i> Editar Usuario</h3>
                    <button onclick="cerrarModalEditar()" class="modal-close" id="close-edit-usuario-modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formEditarUsuario">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="_method" value="PUT">
                        <input type="hidden" id="usuario_id_edit" name="usuario_id">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nombre_edit">Nombre *</label>
                                <input type="text" id="nombre_edit" name="nombre" required>
                            </div>
                            <div class="form-group">
                                <label for="apellido_edit">Apellido *</label>
                                <input type="text" id="apellido_edit" name="apellido" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="email_edit">Email *</label>
                            <input type="email" id="email_edit" name="email" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="rol_edit">Rol *</label>
                            <select id="rol_edit" name="rol" required>
                                <option value="cliente">Cliente</option>
                                <option value="vendedor">Vendedor</option>
                                <option value="admin">Administrador</option>
                                <option value="tecnico">Técnico</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="telefono_edit">Teléfono</label>
                            <input type="tel" id="telefono_edit" name="telefono">
                        </div>
                        
                        <div class="form-group">
                            <label for="direccion_edit">Dirección</label>
                            <textarea id="direccion_edit" name="direccion" rows="3"></textarea>
                        </div>
                        
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="cerrarModalEditar()">
                                Cancelar
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal para crear nuevo usuario -->
        <div id="new-usuario-modal" class="edit-modal">
            <div class="modal-backdrop"></div>
            <div class="modal-content modal-auto-size">
                <div class="modal-header">
                    <h3><i class="fas fa-user-plus"></i> Nuevo Usuario</h3>
                    <button class="modal-close" id="close-new-usuario-modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="new-usuario-form">
                        <?php echo csrf_field(); ?>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="new-usuario-nombre">Nombre *</label>
                                <input type="text" id="new-usuario-nombre" name="nombre" required placeholder="Ej: Juan">
                            </div>
                            <div class="form-group">
                                <label for="new-usuario-apellido">Apellido *</label>
                                <input type="text" id="new-usuario-apellido" name="apellido" required placeholder="Ej: Pérez">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="new-usuario-email">Correo Electrónico *</label>
                            <input type="email" id="new-usuario-email" name="email" required placeholder="ejemplo@correo.com">
                        </div>
                        <div class="form-group">
                            <label for="new-usuario-rol">Rol *</label>
                            <select id="new-usuario-rol" name="rol" required>
                                <option value="">Selecciona un rol</option>
                                <option value="cliente">Cliente</option>
                                <option value="vendedor">Vendedor</option>
                                <option value="tecnico">Técnico</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="new-usuario-password">Contraseña *</label>
                                <input type="password" id="new-usuario-password" name="password" required minlength="8" placeholder="Mínimo 8 caracteres">
                            </div>
                            <div class="form-group">
                                <label for="new-usuario-password-confirmation">Confirmar Contraseña *</label>
                                <input type="password" id="new-usuario-password-confirmation" name="password_confirmation" required minlength="8" placeholder="Repite la contraseña">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cancel-new-usuario">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" id="save-new-usuario">
                        <i class="fas fa-user-plus"></i> Registrar Usuario
                    </button>
                </div>
            </div>
        </div>
    </main>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- <script src="<?php echo e(asset('js/admin.js')); ?>"></script> -->
    <!-- <script src="<?php echo e(asset('js/animations.js')); ?>"></script> -->
    <script src="<?php echo e(asset('js/usuarios.js')); ?>"></script>
    <script>
        // Verificar que las funciones estén disponibles
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM cargado');
            console.log('verDetalle disponible:', typeof verDetalle);
            console.log('cerrarModal disponible:', typeof cerrarModal);
            console.log('eliminarUsuario disponible:', typeof eliminarUsuario);
        });
        
        // Funciones de respaldo en caso de que no se cargue el archivo JS
        if (typeof verDetalle === 'undefined') {
            function verDetalle(id) {
                alert('Función verDetalle cargada como respaldo. ID: ' + id);
                console.log('verDetalle llamada con ID:', id);
            }
        }
        
        if (typeof cerrarModal === 'undefined') {
            function cerrarModal() {
                const modal = document.getElementById("modalDetalle");
                if (modal) {
                    modal.classList.remove('active');
                }
            }
        }
        
        if (typeof eliminarUsuario === 'undefined') {
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
                    alert('No se pudo identificar el usuario a eliminar');
                    return false;
                }
                
                if (confirm('¿Está seguro de que desea eliminar este usuario?')) {
                    // Obtener CSRF token
                    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                                   document.querySelector('input[name="_token"]')?.value;
                    
                    // Construir URL de eliminación
                    var url = '/usuarios/' + usuarioId;
                    
                    button.disabled = true;
                    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                    
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
                            window.location.reload();
                        } else {
                            alert('Error al eliminar el usuario');
                            button.disabled = false;
                            button.innerHTML = '<i class="fas fa-trash"></i>';
                        }
                    })
                    .catch(error => {
                        alert('Error de conexión');
                        button.disabled = false;
                        button.innerHTML = '<i class="fas fa-trash"></i>';
                    });
                }
                return false;
            }
        }
        
        // Función para editar usuario
        if (typeof editarUsuario === 'undefined') {
            function editarUsuario(id) {
                fetch(`/usuarios/${id}`, {
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const usuario = data.data;
                        document.getElementById('usuario_id_edit').value = usuario.usuario_id;
                        document.getElementById('nombre_edit').value = usuario.nombre || '';
                        document.getElementById('apellido_edit').value = usuario.apellido || '';
                        document.getElementById('email_edit').value = usuario.email || '';
                        document.getElementById('rol_edit').value = usuario.rol || '';
                        document.getElementById('telefono_edit').value = usuario.telefono || '';
                        document.getElementById('direccion_edit').value = usuario.direccion || '';
                        
                        document.getElementById('modalEditar').classList.add('active');
                    } else {
                        alert('Error al cargar los datos del usuario');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cargar los datos del usuario');
                });
            }
        }
        
        // Función para cerrar modal de edición
        if (typeof cerrarModalEditar === 'undefined') {
            function cerrarModalEditar() {
                document.getElementById('modalEditar').classList.remove('active');
            }
        }
        
        // Manejar envío del formulario de edición
        document.addEventListener('DOMContentLoaded', function() {
            const formEditar = document.getElementById('formEditarUsuario');
            if (formEditar) {
                formEditar.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    const usuarioId = document.getElementById('usuario_id_edit').value;
                    
                    fetch(`/usuarios/${usuarioId}`, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            cerrarModalEditar();
                            Swal.fire({
                                title: 'Éxito',
                                text: 'Usuario actualizado correctamente',
                                icon: 'success',
                                confirmButtonText: 'Entendido'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: data.message || 'Error al actualizar el usuario',
                                icon: 'error',
                                confirmButtonText: 'Entendido'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            title: 'Error',
                            text: 'Error de conexión',
                            icon: 'error',
                            confirmButtonText: 'Entendido'
                        });
                    });
                });
            }
        });
        
        // Función para cerrar alertas si no está definida
        if (typeof closeAlert === 'undefined') {
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
        }
        
        // Modal de nuevo usuario
        const openNewUsuarioBtn = document.getElementById('open-new-usuario-modal');
        const closeNewUsuarioBtn = document.getElementById('close-new-usuario-modal');
        const cancelNewUsuarioBtn = document.getElementById('cancel-new-usuario');
        const saveNewUsuarioBtn = document.getElementById('save-new-usuario');
        
        if (openNewUsuarioBtn) {
            openNewUsuarioBtn.addEventListener('click', function() {
                document.getElementById('new-usuario-modal').classList.add('active');
                document.getElementById('new-usuario-form').reset();
            });
        }
        
        if (closeNewUsuarioBtn) {
            closeNewUsuarioBtn.addEventListener('click', function() {
                document.getElementById('new-usuario-modal').classList.remove('active');
            });
        }
        
        if (cancelNewUsuarioBtn) {
            cancelNewUsuarioBtn.addEventListener('click', function() {
                document.getElementById('new-usuario-modal').classList.remove('active');
            });
        }
        
        if (saveNewUsuarioBtn) {
            saveNewUsuarioBtn.addEventListener('click', function() {
                const form = document.getElementById('new-usuario-form');
                const formData = new FormData(form);
                
                // Validar que las contraseñas coincidan
                const password = document.getElementById('new-usuario-password').value;
                const passwordConfirmation = document.getElementById('new-usuario-password-confirmation').value;
                
                if (password !== passwordConfirmation) {
                    Swal.fire({
                        title: 'Error',
                        text: 'Las contraseñas no coinciden',
                        icon: 'error',
                        confirmButtonText: 'Entendido'
                    });
                    return;
                }
                
                // Deshabilitar botón durante el envío
                saveNewUsuarioBtn.disabled = true;
                saveNewUsuarioBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registrando...';
                
                fetch('/usuarios', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Éxito',
                            text: data.message || 'Usuario registrado exitosamente',
                            icon: 'success',
                            confirmButtonText: 'Entendido'
                        }).then(() => {
                            document.getElementById('new-usuario-modal').classList.remove('active');
                            location.reload();
                        });
                    } else {
                        let errorMessage = data.message || 'Error al registrar el usuario';
                        if (data.errors) {
                            const errores = Object.values(data.errors).flat();
                            errorMessage = errores.join(', ');
                        }
                        Swal.fire({
                            title: 'Error',
                            text: errorMessage,
                            icon: 'error',
                            confirmButtonText: 'Entendido'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'Error de conexión',
                        icon: 'error',
                        confirmButtonText: 'Entendido'
                    });
                })
                .finally(() => {
                    saveNewUsuarioBtn.disabled = false;
                    saveNewUsuarioBtn.innerHTML = '<i class="fas fa-user-plus"></i> Registrar Usuario';
                });
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
        
        // Mejorar la experiencia de la tabla
        document.addEventListener('DOMContentLoaded', function() {
            // Agregar loading state a los botones de acción
            document.querySelectorAll('.action-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    if (this.classList.contains('delete')) {
                        return; // Dejar que eliminarUsuario maneje esto
                    }
                    this.style.opacity = '0.6';
                    this.style.pointerEvents = 'none';
                    
                    setTimeout(() => {
                        this.style.opacity = '1';
                        this.style.pointerEvents = 'auto';
                    }, 2000);
                });
            });
        });
    </script>
</body>
</html>
<?php /**PATH /var/www/html/resources/views/usuarios/index.blade.php ENDPATH**/ ?>